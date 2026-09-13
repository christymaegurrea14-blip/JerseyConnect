<script setup lang="ts">
import AdminLayout from "@/Layouts/AdminLayout.vue";
import Modal from "@/Components/Modal.vue";
import ModalHeader from "@/Components/ModalHeader.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import SelectInput from "@/Components/SelectInput.vue";
import InputLabel from "@/Components/InputLabel.vue";
import InputError from "@/Components/InputError.vue";
import ImageUpload from "@/Components/ImageUpload.vue";
import { useModal } from "@/Composables/useModal";
import { Head, useForm, router } from "@inertiajs/vue3";
import { ref, computed, watch, onMounted, onUnmounted } from "vue";

interface JerseyTemplates {
    id: number;
    name: string;
    description: string | null;
    image: string;
    price: number;
    badge?: "New" | "Bestseller" | "Hot";
    primary_color: string;
    secondary_color: string;
    accent_color: string;
    sport: "Basketball" | "Soccer" | "Baseball" | "Volleyball" | "Esports";
    status: "active" | "inactive";
    created_at: string;
    updated_at: string;
}

const props = defineProps<{
    data?: JerseyTemplates[];
}>();

interface ImageStyle {
    backgroundImage: string;
}

type JerseyTemplatesStatus = "active" | "inactive";

const jerseyTemplates = computed(() => props.data ?? []);

const activeCount = computed(
    () => jerseyTemplates.value.filter((t) => t.status === "active").length,
);
const inactiveCount = computed(
    () => jerseyTemplates.value.filter((t) => t.status === "inactive").length,
);
const badgedCount = computed(
    () => jerseyTemplates.value.filter((t) => !!t.badge).length,
);

const statusBadge: Record<
    JerseyTemplatesStatus,
    { label: string; class: string }
> = {
    active: {
        label: "Active",
        class: "bg-emerald-500/15 text-emerald-300 border-emerald-500/25",
    },
    inactive: {
        label: "Inactive",
        class: "bg-rose-500/15 text-rose-300 border-rose-500/25",
    },
};

const jerseyBadgeClass: Record<string, string> = {
    Hot: "bg-amber-500/15 text-amber-400 border-amber-500/30",
    New: "bg-cyan-500/15 text-cyan-400 border-cyan-500/30",
    Bestseller: "bg-indigo-500/15 text-indigo-300 border-indigo-500/30",
};

const badgeOptions = ["New", "Bestseller", "Hot"];
const sportOptions = [
    "Basketball",
    "Soccer",
    "Baseball",
    "Volleyball",
    "Esports",
];
const jerseyStatus = [
    { value: "active", label: "Active" },
    { value: "inactive", label: "Inactive" },
];

const searchQuery = ref("");
const dateFrom = ref("");
const dateTo = ref("");
const perPage = ref(10);
const perPageOptions = [5, 10, 25, 50];
const currentPage = ref(1);

const filteredTemplates = computed(() => {
    let list = jerseyTemplates.value;

    if (dateFrom.value) {
        const from = new Date(dateFrom.value);
        list = list.filter((t) => new Date(t.created_at) >= from);
    }
    if (dateTo.value) {
        const to = new Date(dateTo.value);
        to.setHours(23, 59, 59, 999);
        list = list.filter((t) => new Date(t.created_at) <= to);
    }

    const q = searchQuery.value.trim().toLowerCase();
    if (q) {
        list = list.filter(
            (t) =>
                t.name.toLowerCase().includes(q) ||
                t.sport.toLowerCase().includes(q),
        );
    }
    return list;
});

watch([dateFrom, dateTo, searchQuery, perPage], () => {
    currentPage.value = 1;
});

const totalPages = computed(() =>
    Math.max(1, Math.ceil(filteredTemplates.value.length / perPage.value)),
);

const paginatedTemplates = computed(() => {
    const start = (currentPage.value - 1) * perPage.value;
    return filteredTemplates.value.slice(start, start + perPage.value);
});

const rangeStart = computed(() =>
    filteredTemplates.value.length === 0
        ? 0
        : (currentPage.value - 1) * perPage.value + 1,
);
const rangeEnd = computed(() =>
    Math.min(currentPage.value * perPage.value, filteredTemplates.value.length),
);

function clearFilters() {
    dateFrom.value = "";
    dateTo.value = "";
    searchQuery.value = "";
}

const hasActiveFilters = computed(
    () => !!dateFrom.value || !!dateTo.value || !!searchQuery.value.trim(),
);

const modal = useModal();

function closeModal() {
    modal.closeModal();
    addForm.reset();
    addForm.clearErrors();
    editForm.reset();
    editForm.clearErrors();
    addImageStyle.value = { backgroundImage: "" };
    editImageStyle.value = { backgroundImage: "" };
    selectedJersey.value = null;
    selectedJerseyMeta.value = null;
    deleteForm.clearErrors();
}

function formatDate(value: string) {
    return new Date(value).toLocaleDateString("en-PH", {
        year: "numeric",
        month: "short",
        day: "numeric",
    });
}

/* ---------------- RATE LIMIT ---------------- */
const showLimitModal = ref(false);
const retryAfterSeconds = ref<number | null>(null);
let countdownTimer: ReturnType<typeof setInterval> | null = null;
let removeInvalidListener: (() => void) | undefined;

function startCountdown(seconds: number | null) {
    stopCountdown();
    if (!seconds || seconds <= 0) return;

    retryAfterSeconds.value = seconds;
    countdownTimer = setInterval(() => {
        if (retryAfterSeconds.value === null) return;
        if (retryAfterSeconds.value <= 1) {
            retryAfterSeconds.value = 0;
            stopCountdown();
            return;
        }
        retryAfterSeconds.value -= 1;
    }, 1000);
}

function stopCountdown() {
    if (countdownTimer) {
        clearInterval(countdownTimer);
        countdownTimer = null;
    }
}

function closeLimitModal() {
    showLimitModal.value = false;
    stopCountdown();
    retryAfterSeconds.value = null;
}

onMounted(() => {
    removeInvalidListener = router.on("invalid", (event) => {
        const status = event.detail.response?.status;

        if (status === 429) {
            event.preventDefault();

            const retryAfterHeader =
                event.detail.response?.headers?.["retry-after"];
            const seconds = retryAfterHeader
                ? Number(retryAfterHeader)
                : null;

            startCountdown(seconds);
            showLimitModal.value = true;
        }
    });
});

onUnmounted(() => {
    removeInvalidListener?.();
    stopCountdown();
});

/* ---------------- ADD ---------------- */

const addImageStyle = ref<ImageStyle>({ backgroundImage: "" });

const addForm = useForm({
    name: "",
    description: "",
    price: "",
    badge: "" as "" | (typeof badgeOptions)[number],
    primary_color: "#14202B",
    secondary_color: "#FFFFFF",
    accent_color: "#2E7D4F",
    sport: "Basketball" as (typeof sportOptions)[number],
    image: null as File | null,
});

function openAddModal() {
    addForm.reset();
    addForm.clearErrors();
    addImageStyle.value = { backgroundImage: "" };
    modal.title.value = "Add Jersey Template";
    modal.type.value = "Add";
    modal.openModal();
}

function onAddImageChange(file: File) {
    addForm.image = file;
}

function submitAdd() {
    addForm.post(route("admin.jersey.store"), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => closeModal(),
    });
}

/* ---------------- VIEW ---------------- */

const selectedJersey = ref<JerseyTemplates | null>(null);

function openViewModal(row: JerseyTemplates) {
    selectedJersey.value = row;
    modal.title.value = "View Jersey Template";
    modal.type.value = "View";
    modal.openModal();
}

/* ---------------- EDIT ---------------- */

const editImageStyle = ref<ImageStyle>({ backgroundImage: "" });
const selectedJerseyMeta = ref<JerseyTemplates | null>(null);

const editForm = useForm({
    id: null as number | null,
    name: "",
    description: "",
    price: "",
    badge: "" as "" | (typeof badgeOptions)[number],
    primary_color: "#14202B",
    secondary_color: "#FFFFFF",
    accent_color: "#2E7D4F",
    sport: "Basketball" as (typeof sportOptions)[number],
    status: "active" as JerseyTemplatesStatus,
    image: null as File | null,
});

function openEditModal(row: JerseyTemplates) {
    editForm.reset();
    editForm.clearErrors();
    selectedJerseyMeta.value = row;

    editForm.id = row.id;
    editForm.name = row.name;
    editForm.description = row.description ?? "";
    editForm.price = String(row.price);
    editForm.badge = row.badge ?? "";
    editForm.primary_color = row.primary_color;
    editForm.secondary_color = row.secondary_color;
    editForm.accent_color = row.accent_color;
    editForm.sport = row.sport;
    editForm.status = row.status;

    editImageStyle.value = { backgroundImage: `url(${row.image})` };

    modal.title.value = "Edit Jersey Template";
    modal.type.value = "Edit";
    modal.openModal();
}

function onEditImageChange(file: File) {
    editForm.image = file;
}

function submitEdit() {
    if (!editForm.id) return;

    editForm
        .transform((data) => ({
            ...data,
            _method: "put",
        }))
        .post(route("admin.jersey.update", editForm.id), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });
}

/* ---------------- DELETE ---------------- */

const deleteForm = useForm({});

function openDeleteModal() {
    modal.title.value = "Delete Jersey Template";
    modal.type.value = "Delete";
    modal.openModal();
}

function submitDelete() {
    if (!selectedJerseyMeta.value) return;

    deleteForm.delete(route("admin.jersey.destroy", selectedJerseyMeta.value.id), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
    });
}
</script>

<template>
    <Head title="Jersey Templates" />

    <AdminLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div v-reveal class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex flex-col gap-1">
                    <h1 class="text-2xl font-extrabold text-white tracking-tight">Jersey Templates</h1>
                    <p class="text-sm text-slate-400">
                        {{ jerseyTemplates.length }} template{{ jerseyTemplates.length === 1 ? "" : "s" }} total
                    </p>
                </div>
                <PrimaryButton class="flex items-center justify-center gap-1.5 w-full sm:w-auto" @click="openAddModal">
                    <font-awesome-icon icon="fa-solid fa-plus-circle" />
                    Add Jersey
                </PrimaryButton>
            </div>

            <!-- Stat cards -->
            <div v-reveal="80" class="grid grid-cols-2 lg:grid-cols-4 gap-3.5">
                <div v-reveal="0" class="glass-panel rounded-2xl p-5 relative overflow-hidden group hover:border-slate-500/40 transition-all">
                    <div class="absolute -right-4 -bottom-4 w-20 h-20 bg-slate-500/5 rounded-full blur-xl group-hover:bg-slate-400/10 transition-all"></div>
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium text-slate-400">Total templates</span>
                        <span class="p-1.5 rounded-lg bg-slate-800/80 text-slate-400">
                            <font-awesome-icon icon="fa-solid fa-shirt" class="text-xs" />
                        </span>
                    </div>
                    <div class="text-2xl font-bold text-white mt-3">{{ jerseyTemplates.length }}</div>
                </div>
                <div v-reveal="70" class="glass-panel rounded-2xl p-5 relative overflow-hidden group hover:border-emerald-500/40 transition-all">
                    <div class="absolute -right-4 -bottom-4 w-20 h-20 bg-emerald-500/10 rounded-full blur-xl group-hover:bg-emerald-400/20 transition-all"></div>
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium text-slate-400">Active</span>
                        <span class="p-1.5 rounded-lg bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                            <font-awesome-icon icon="fa-solid fa-circle-check" class="text-xs" />
                        </span>
                    </div>
                    <div class="text-2xl font-bold text-emerald-300 mt-3">{{ activeCount }}</div>
                </div>
                <div v-reveal="140" class="glass-panel rounded-2xl p-5 relative overflow-hidden group hover:border-rose-500/40 transition-all">
                    <div class="absolute -right-4 -bottom-4 w-20 h-20 bg-rose-500/5 rounded-full blur-xl group-hover:bg-rose-400/10 transition-all"></div>
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium text-slate-400">Inactive</span>
                        <span class="p-1.5 rounded-lg bg-slate-800/80 text-rose-400/70 border border-white/5">
                            <font-awesome-icon icon="fa-solid fa-xmark-circle" class="text-xs" />
                        </span>
                    </div>
                    <div class="text-2xl font-bold text-rose-400 mt-3">{{ inactiveCount }}</div>
                </div>
                <div v-reveal="210" class="glass-panel rounded-2xl p-5 relative overflow-hidden group hover:border-indigo-500/40 transition-all">
                    <div class="absolute -right-4 -bottom-4 w-20 h-20 bg-indigo-500/10 rounded-full blur-xl group-hover:bg-indigo-400/20 transition-all"></div>
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium text-slate-400">Badged</span>
                        <span class="p-1.5 rounded-lg bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">
                            <font-awesome-icon icon="fa-solid fa-star" class="text-xs" />
                        </span>
                    </div>
                    <div class="text-2xl font-bold text-indigo-300 mt-3">{{ badgedCount }}</div>
                </div>
            </div>

            <!-- Search -->
            <div v-reveal="140" class="relative w-full lg:w-72">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-500 pointer-events-none">
                    <font-awesome-icon icon="fa-solid fa-magnifying-glass" class="text-xs" />
                </span>
                <input
                    id="jersey-templates-search"
                    v-model="searchQuery"
                    type="text"
                    name="search"
                    placeholder="Search name, sport..."
                    aria-label="Search jersey templates"
                    class="w-full pl-8 pr-3 py-2 text-sm rounded-lg bg-slate-900/60 border border-white/10 text-slate-200 placeholder-slate-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500"
                />
            </div>

            <!-- Date range + per-page -->
            <div class="flex flex-wrap items-center gap-3">
                <div class="flex items-center gap-1.5">
                    <label for="jersey-from" class="text-xs text-slate-500">From</label>
                    <input
                        id="jersey-from"
                        v-model="dateFrom"
                        type="date"
                        name="dateFrom"
                        class="text-sm rounded-lg bg-slate-900/60 border border-white/10 text-slate-200 py-1.5 px-2.5 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500"
                    />
                </div>
                <div class="flex items-center gap-1.5">
                    <label for="jersey-to" class="text-xs text-slate-500">To</label>
                    <input
                        id="jersey-to"
                        v-model="dateTo"
                        type="date"
                        name="dateTo"
                        class="text-sm rounded-lg bg-slate-900/60 border border-white/10 text-slate-200 py-1.5 px-2.5 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500"
                    />
                </div>

                <button
                    v-if="hasActiveFilters"
                    type="button"
                    @click="clearFilters"
                    class="flex items-center gap-1.5 text-xs font-medium text-rose-300 border border-rose-500/25 bg-rose-500/10 hover:bg-rose-500/20 rounded-lg px-2.5 py-1.5 transition-colors"
                >
                    <font-awesome-icon icon="fa-solid fa-xmark" />
                    Clear filters
                </button>

                <div class="flex items-center gap-1.5 ml-auto">
                    <label for="jersey-per-page" class="text-xs text-slate-500">Show</label>
                    <select
                        id="jersey-per-page"
                        v-model.number="perPage"
                        name="perPage"
                        class="text-sm rounded-lg bg-slate-900/60 border border-white/10 text-slate-200 py-1.5 px-2.5 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500"
                    >
                        <option v-for="n in perPageOptions" :key="n" :value="n">{{ n }}</option>
                    </select>
                    <span class="text-xs text-slate-500">per page</span>
                </div>
            </div>

            <!-- Table -->
            <div v-reveal="200" class="glass-panel rounded-2xl overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-white/5">
                                <th class="px-4 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-slate-400">Design</th>
                                <th class="px-4 py-3 text-right text-[11px] font-bold uppercase tracking-wider text-slate-400">Price</th>
                                <th class="px-4 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-slate-400">Badge</th>
                                <th class="px-4 py-3 text-center text-[11px] font-bold uppercase tracking-wider text-slate-400">Colors</th>
                                <th class="px-4 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-slate-400">Sport</th>
                                <th class="px-4 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-slate-400">Status</th>
                                <th class="px-4 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-slate-400">Submitted</th>
                                <th class="px-4 py-3 text-center text-[11px] font-bold uppercase tracking-wider text-slate-400">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            <tr v-if="filteredTemplates.length === 0">
                                <td colspan="8" class="px-4 py-10 text-center text-sm text-slate-500">No jersey templates yet.</td>
                            </tr>
                            <tr
                                v-for="row in paginatedTemplates"
                                :key="row.id"
                                class="hover:bg-slate-800/30 transition-colors"
                            >
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2.5">
                                        <div
                                            class="h-10 w-10 flex-shrink-0 rounded-lg p-[1.5px]"
                                            :style="{ background: `linear-gradient(135deg, ${row.primary_color}, ${row.secondary_color})` }"
                                        >
                                            <div class="h-full w-full rounded-[6px] bg-white flex items-center justify-center overflow-hidden p-0.5">
                                                <img :src="row.image" :alt="row.name" class="h-full w-full object-contain" />
                                            </div>
                                        </div>
                                        <span class="font-semibold text-white">{{ row.name }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-right text-slate-200 font-medium">₱{{ row.price }}</td>
                                <td class="px-4 py-3">
                                    <span
                                        v-if="row.badge"
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-semibold border"
                                        :class="jerseyBadgeClass[row.badge]"
                                    >
                                        {{ row.badge }}
                                    </span>
                                    <span v-else class="text-xs text-slate-600 font-mono">—</span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-center gap-1">
                                        <span class="h-4 w-4 rounded-full border border-white/20" :style="{ backgroundColor: row.primary_color }" />
                                        <span class="h-4 w-4 rounded-full border border-white/20" :style="{ backgroundColor: row.secondary_color }" />
                                        <span class="h-4 w-4 rounded-full border border-white/20" :style="{ backgroundColor: row.accent_color }" />
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-slate-300">{{ row.sport }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-block rounded-full px-2.5 py-1 text-[11px] font-medium border" :class="statusBadge[row.status].class">
                                        {{ statusBadge[row.status].label }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-xs text-slate-400">{{ formatDate(row.created_at) }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-center gap-1">
                                        <button
                                            type="button"
                                            class="text-xs font-medium bg-indigo-600/20 text-indigo-300 border border-indigo-500/30 rounded-md px-2 py-2 transition-colors hover:bg-indigo-600/30"
                                            @click="openViewModal(row)"
                                        >
                                            <font-awesome-icon icon="fa-solid fa-eye" />
                                        </button>
                                        <button
                                            type="button"
                                            class="text-xs font-medium bg-slate-800 text-slate-300 border border-white/10 rounded-md px-2 py-2 transition-colors hover:bg-slate-700"
                                            @click="openEditModal(row)"
                                        >
                                            <font-awesome-icon icon="fa-solid fa-edit" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="filteredTemplates.length > 0" class="flex flex-col sm:flex-row items-center justify-between gap-3">
                <p class="text-xs text-slate-500">
                    Showing <span class="text-slate-300 font-medium">{{ rangeStart }}–{{ rangeEnd }}</span>
                    of <span class="text-slate-300 font-medium">{{ filteredTemplates.length }}</span> templates
                </p>
                <div class="flex items-center gap-1">
                    <button
                        type="button"
                        @click="currentPage--"
                        :disabled="currentPage === 1"
                        class="px-2.5 py-1.5 rounded-lg border text-xs transition-colors"
                        :class="currentPage === 1 ? 'border-white/5 text-slate-700 cursor-not-allowed' : 'border-white/10 text-slate-300 hover:bg-slate-800/60'"
                    >
                        <font-awesome-icon icon="fa-solid fa-chevron-left" />
                    </button>
                    <span class="px-3 py-1.5 text-xs text-slate-300">Page {{ currentPage }} of {{ totalPages }}</span>
                    <button
                        type="button"
                        @click="currentPage++"
                        :disabled="currentPage === totalPages"
                        class="px-2.5 py-1.5 rounded-lg border text-xs transition-colors"
                        :class="currentPage === totalPages ? 'border-white/5 text-slate-700 cursor-not-allowed' : 'border-white/10 text-slate-300 hover:bg-slate-800/60'"
                    >
                        <font-awesome-icon icon="fa-solid fa-chevron-right" />
                    </button>
                </div>
            </div>
        </div>

        <!-- Add Modal -->
        <Modal :show="modal.type.value === 'Add'" @close="closeModal()" :maxWidth="'5xl'">
            <ModalHeader
                icon="fa-solid fa-plus-circle"
                :title="modal.title.value"
                subtitle="Add a new template to the storefront catalog"
                @close="closeModal()"
            />
            <form @submit.prevent="submitAdd" class="text-slate-200">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 px-5 py-5 max-h-[calc(88vh-140px)] overflow-y-auto">
                    <!-- Visual -->
                    <div class="lg:col-span-5 flex flex-col gap-3">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Jersey Image</span>
                        <ImageUpload :image="addImageStyle" @imageFile="onAddImageChange" />
                        <InputError :message="addForm.errors.image" />
                    </div>

                    <!-- Specifications -->
                    <div class="lg:col-span-7 flex flex-col gap-4">
                        <div class="text-xs font-bold uppercase tracking-wider text-slate-400 border-b border-white/10 pb-2">
                            Template Specifications
                        </div>

                        <div>
                            <InputLabel for="name" value="Template Name" class="!text-slate-300 !text-xs !font-semibold" />
                            <TextInput v-model="addForm.name" class="mt-1.5 block w-full !rounded-xl !bg-slate-950/70 !border-white/10 !text-slate-100" id="name" required placeholder="e.g. Arctic Frost" />
                            <InputError :message="addForm.errors.name" class="mt-1.5" />
                        </div>

                        <div>
                            <InputLabel for="description" value="Description" class="!text-slate-300 !text-xs !font-semibold" />
                            <textarea
                                id="description"
                                v-model="addForm.description"
                                rows="3"
                                placeholder="A short description shown on the customer-facing catalog card"
                                class="mt-1.5 block w-full rounded-xl bg-slate-950/70 border border-white/10 text-sm text-slate-100 px-3 py-2.5 focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 outline-none resize-none"
                            ></textarea>
                            <InputError :message="addForm.errors.description" class="mt-1.5" />
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                            <div>
                                <InputLabel for="price" value="Base Price (PHP)" class="!text-slate-300 !text-xs !font-semibold" />
                                <div class="relative mt-1.5">
                                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400 font-semibold text-sm pointer-events-none">₱</span>
                                    <input
                                        id="price"
                                        v-model="addForm.price"
                                        type="number"
                                        min="0"
                                        required
                                        class="w-full pl-8 pr-3 py-2.5 rounded-xl bg-slate-950/70 border border-white/10 text-sm text-white font-semibold focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 outline-none"
                                    />
                                </div>
                                <InputError :message="addForm.errors.price" class="mt-1.5" />
                            </div>

                            <div>
                                <InputLabel for="badge" value="Commercial Badge" class="!text-slate-300 !text-xs !font-semibold" />
                                <SelectInput v-model="addForm.badge" :options="badgeOptions" class="mt-1.5 block w-full !rounded-xl !bg-slate-950/70 !border-white/10 !text-slate-100" id="badge" required />
                                <InputError :message="addForm.errors.badge" class="mt-1.5" />
                            </div>
                        </div>

                        <div>
                            <InputLabel for="sport" value="Sport / Apparel Type" class="!text-slate-300 !text-xs !font-semibold" />
                            <SelectInput v-model="addForm.sport" :options="sportOptions" class="mt-1.5 block w-full !rounded-xl !bg-slate-950/70 !border-white/10 !text-slate-100" id="sport" required />
                            <InputError :message="addForm.errors.sport" class="mt-1.5" />
                        </div>

                        <!-- Colorway swatches -->
                        <div class="p-3.5 rounded-xl bg-slate-950/60 border border-white/10 space-y-2.5">
                            <span class="text-xs font-bold text-slate-200">Colorway Swatches</span>
                            <div class="grid grid-cols-3 gap-2.5">
                                <div class="space-y-1">
                                    <span class="text-[10px] text-slate-400 block font-medium">Primary</span>
                                    <label class="flex items-center gap-2 p-1.5 rounded-lg bg-slate-900 border border-white/10 cursor-pointer">
                                        <input type="color" v-model="addForm.primary_color" class="h-5 w-5 rounded border-0 p-0 bg-transparent shrink-0 cursor-pointer" />
                                        <input type="text" v-model="addForm.primary_color" class="w-full bg-transparent border-0 p-0 text-[11px] font-mono text-slate-200 focus:ring-0 uppercase font-semibold" />
                                    </label>
                                </div>
                                <div class="space-y-1">
                                    <span class="text-[10px] text-slate-400 block font-medium">Secondary</span>
                                    <label class="flex items-center gap-2 p-1.5 rounded-lg bg-slate-900 border border-white/10 cursor-pointer">
                                        <input type="color" v-model="addForm.secondary_color" class="h-5 w-5 rounded border-0 p-0 bg-transparent shrink-0 cursor-pointer" />
                                        <input type="text" v-model="addForm.secondary_color" class="w-full bg-transparent border-0 p-0 text-[11px] font-mono text-slate-200 focus:ring-0 uppercase font-semibold" />
                                    </label>
                                </div>
                                <div class="space-y-1">
                                    <span class="text-[10px] text-slate-400 block font-medium">Accent</span>
                                    <label class="flex items-center gap-2 p-1.5 rounded-lg bg-slate-900 border border-white/10 cursor-pointer">
                                        <input type="color" v-model="addForm.accent_color" class="h-5 w-5 rounded border-0 p-0 bg-transparent shrink-0 cursor-pointer" />
                                        <input type="text" v-model="addForm.accent_color" class="w-full bg-transparent border-0 p-0 text-[11px] font-mono text-slate-200 focus:ring-0 uppercase font-semibold" />
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="px-5 py-4 border-t border-white/10 bg-black/20 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <SecondaryButton type="button" class="flex items-center justify-center" @click="closeModal()">Cancel</SecondaryButton>

                    <PrimaryButton
                        type="submit"
                        class="flex items-center justify-center gap-1"
                        :disabled="addForm.processing"
                        :class="{ 'opacity-25': addForm.processing }"
                    >
                        <div class="text-sm" v-if="addForm.processing">
                            <font-awesome-icon icon="fa-solid fa-spinner" spin />
                        </div>
                        Save
                        <font-awesome-icon icon="fa-solid fa-circle-down" />
                    </PrimaryButton>
                </div>
            </form>
        </Modal>

        <!-- View Modal -->
        <Modal :show="modal.type.value === 'View'" @close="closeModal()" :maxWidth="'lg'">
            <div v-if="selectedJersey">
                <ModalHeader
                    icon="fa-solid fa-eye"
                    :title="modal.title.value"
                    @close="closeModal()"
                />
                <div class="px-5 py-5 text-slate-200 space-y-3">
                    <img
                        :src="selectedJersey.image"
                        :alt="selectedJersey.name"
                        class="mx-auto max-h-80 w-full rounded object-contain bg-white border border-white/10 p-4"
                    />
                    <p v-if="selectedJersey.description" class="text-sm text-slate-300 leading-relaxed">
                        {{ selectedJersey.description }}
                    </p>
                    <p v-else class="text-xs text-slate-500 italic">No description yet.</p>
                </div>
            </div>
        </Modal>

        <!-- Edit Modal -->
        <Modal :show="modal.type.value === 'Edit'" @close="closeModal()" :maxWidth="'5xl'">
            <ModalHeader
                icon="fa-solid fa-edit"
                :title="modal.title.value"
                subtitle="Update catalog listing and sublimation assets"
                :badge="editForm.status === 'active' ? 'Live in Storefront' : 'Inactive'"
                :badge-class="editForm.status === 'active'
                    ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20'
                    : 'bg-slate-700/40 text-slate-300 border-slate-600/40'"
                @close="closeModal()"
            />
            <form @submit.prevent="submitEdit" class="text-slate-200">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 px-5 py-5 max-h-[calc(88vh-140px)] overflow-y-auto">
                    <!-- Visual -->
                    <div class="lg:col-span-5 flex flex-col gap-3">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Jersey Image</span>
                        <ImageUpload :image="editImageStyle" @imageFile="onEditImageChange" />
                        <InputError :message="editForm.errors.image" />
                    </div>

                    <!-- Specifications -->
                    <div class="lg:col-span-7 flex flex-col gap-4">
                        <div class="flex items-center justify-between border-b border-white/10 pb-2">
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Template Specifications</span>
                            <span class="text-[11px] text-slate-400">ID: <strong class="text-slate-300 font-mono">JRS-{{ String(editForm.id).padStart(4, "0") }}</strong></span>
                        </div>

                        <div>
                            <InputLabel for="edit-name" value="Template Name" class="!text-slate-300 !text-xs !font-semibold" />
                            <TextInput v-model="editForm.name" class="mt-1.5 block w-full !rounded-xl !bg-slate-950/70 !border-white/10 !text-slate-100" id="edit-name" required />
                            <InputError :message="editForm.errors.name" class="mt-1.5" />
                        </div>

                        <div>
                            <InputLabel for="edit-description" value="Description" class="!text-slate-300 !text-xs !font-semibold" />
                            <textarea
                                id="edit-description"
                                v-model="editForm.description"
                                rows="3"
                                placeholder="A short description shown on the customer-facing catalog card"
                                class="mt-1.5 block w-full rounded-xl bg-slate-950/70 border border-white/10 text-sm text-slate-100 px-3 py-2.5 focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 outline-none resize-none"
                            ></textarea>
                            <InputError :message="editForm.errors.description" class="mt-1.5" />
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                            <div>
                                <InputLabel for="edit-price" value="Base Price (PHP)" class="!text-slate-300 !text-xs !font-semibold" />
                                <div class="relative mt-1.5">
                                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400 font-semibold text-sm pointer-events-none">₱</span>
                                    <input
                                        id="edit-price"
                                        v-model="editForm.price"
                                        type="number"
                                        min="0"
                                        required
                                        class="w-full pl-8 pr-3 py-2.5 rounded-xl bg-slate-950/70 border border-white/10 text-sm text-white font-semibold focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 outline-none"
                                    />
                                </div>
                                <InputError :message="editForm.errors.price" class="mt-1.5" />
                            </div>

                            <div>
                                <InputLabel for="edit-status" value="Catalog Status" class="!text-slate-300 !text-xs !font-semibold" />
                                <SelectInput v-model="editForm.status" :options="jerseyStatus" class="mt-1.5 block w-full !rounded-xl !bg-slate-950/70 !border-white/10 !text-slate-100" id="edit-status" required />
                                <InputError :message="editForm.errors.status" class="mt-1.5" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                            <div>
                                <InputLabel for="edit-badge" value="Commercial Badge" class="!text-slate-300 !text-xs !font-semibold" />
                                <SelectInput v-model="editForm.badge" :options="badgeOptions" class="mt-1.5 block w-full !rounded-xl !bg-slate-950/70 !border-white/10 !text-slate-100" id="edit-badge" required />
                                <InputError :message="editForm.errors.badge" class="mt-1.5" />
                            </div>

                            <div>
                                <InputLabel for="edit-sport" value="Sport / Apparel Type" class="!text-slate-300 !text-xs !font-semibold" />
                                <SelectInput v-model="editForm.sport" :options="sportOptions" class="mt-1.5 block w-full !rounded-xl !bg-slate-950/70 !border-white/10 !text-slate-100" id="edit-sport" required />
                                <InputError :message="editForm.errors.sport" class="mt-1.5" />
                            </div>
                        </div>

                        <!-- Colorway swatches -->
                        <div class="p-3.5 rounded-xl bg-slate-950/60 border border-white/10 space-y-2.5">
                            <span class="text-xs font-bold text-slate-200">Colorway Swatches</span>
                            <div class="grid grid-cols-3 gap-2.5">
                                <div class="space-y-1">
                                    <span class="text-[10px] text-slate-400 block font-medium">Primary</span>
                                    <label class="flex items-center gap-2 p-1.5 rounded-lg bg-slate-900 border border-white/10 cursor-pointer">
                                        <input type="color" v-model="editForm.primary_color" class="h-5 w-5 rounded border-0 p-0 bg-transparent shrink-0 cursor-pointer" />
                                        <input type="text" v-model="editForm.primary_color" class="w-full bg-transparent border-0 p-0 text-[11px] font-mono text-slate-200 focus:ring-0 uppercase font-semibold" />
                                    </label>
                                </div>
                                <div class="space-y-1">
                                    <span class="text-[10px] text-slate-400 block font-medium">Secondary</span>
                                    <label class="flex items-center gap-2 p-1.5 rounded-lg bg-slate-900 border border-white/10 cursor-pointer">
                                        <input type="color" v-model="editForm.secondary_color" class="h-5 w-5 rounded border-0 p-0 bg-transparent shrink-0 cursor-pointer" />
                                        <input type="text" v-model="editForm.secondary_color" class="w-full bg-transparent border-0 p-0 text-[11px] font-mono text-slate-200 focus:ring-0 uppercase font-semibold" />
                                    </label>
                                </div>
                                <div class="space-y-1">
                                    <span class="text-[10px] text-slate-400 block font-medium">Accent</span>
                                    <label class="flex items-center gap-2 p-1.5 rounded-lg bg-slate-900 border border-white/10 cursor-pointer">
                                        <input type="color" v-model="editForm.accent_color" class="h-5 w-5 rounded border-0 p-0 bg-transparent shrink-0 cursor-pointer" />
                                        <input type="text" v-model="editForm.accent_color" class="w-full bg-transparent border-0 p-0 text-[11px] font-mono text-slate-200 focus:ring-0 uppercase font-semibold" />
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="px-5 py-4 border-t border-white/10 bg-black/20 flex flex-col sm:flex-row items-center justify-between gap-3">
                    <div v-if="selectedJerseyMeta" class="flex items-center gap-2 text-xs text-slate-400">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        <span>Last updated <strong class="text-slate-300 font-medium">{{ formatDate(selectedJerseyMeta.updated_at) }}</strong></span>
                    </div>
                    <div class="flex items-center gap-2.5 w-full sm:w-auto justify-end">
                        <button
                            type="button"
                            class="px-3.5 py-2 text-xs font-semibold text-rose-400 hover:text-rose-300 hover:bg-rose-500/10 rounded-xl transition-colors border border-transparent hover:border-rose-500/20"
                            @click="openDeleteModal()"
                        >
                            Delete
                        </button>
                        <SecondaryButton type="button" class="flex items-center justify-center" @click="closeModal()">Cancel</SecondaryButton>

                        <PrimaryButton
                            type="submit"
                            class="flex items-center justify-center gap-1"
                            :disabled="editForm.processing"
                            :class="{ 'opacity-25': editForm.processing }"
                        >
                            <div class="text-sm" v-if="editForm.processing">
                                <font-awesome-icon icon="fa-solid fa-spinner" spin />
                            </div>
                            Save
                            <font-awesome-icon icon="fa-solid fa-circle-down" />
                        </PrimaryButton>
                    </div>
                </div>
            </form>
        </Modal>

        <!-- Delete Modal -->
        <Modal :show="modal.type.value === 'Delete'" @close="closeModal()" :maxWidth="'sm'">
            <ModalHeader
                icon="fa-solid fa-trash"
                icon-class="text-rose-400 bg-rose-500/15 border-rose-500/25"
                title="Delete Jersey Template"
                @close="closeModal()"
            />
            <div class="px-5 py-5 text-slate-200">
                <p class="text-sm text-slate-400">
                    Are you sure you want to delete
                    <span class="font-semibold text-white">{{ selectedJerseyMeta?.name }}</span>? This removes it from the storefront catalog and cannot be undone.
                </p>
                <div class="mt-5 pt-4 border-t border-white/10 flex flex-col-reverse gap-2 sm:flex-row sm:justify-between">
                    <SecondaryButton type="button" class="flex items-center justify-center" @click="closeModal()">Cancel</SecondaryButton>

                    <PrimaryButton
                        type="button"
                        class="flex items-center justify-center gap-1 !bg-rose-600 hover:!bg-rose-500"
                        :disabled="deleteForm.processing"
                        :class="{ 'opacity-25': deleteForm.processing }"
                        @click="submitDelete"
                    >
                        <div class="text-sm" v-if="deleteForm.processing">
                            <font-awesome-icon icon="fa-solid fa-spinner" spin />
                        </div>
                        Delete
                        <font-awesome-icon icon="fa-solid fa-trash" />
                    </PrimaryButton>
                </div>
            </div>
        </Modal>

        <!-- Rate Limit Modal -->
        <Modal :show="showLimitModal" @close="closeLimitModal()" :maxWidth="'md'">
            <div class="px-4 pt-6 pb-5 sm:p-6 text-center bg-surface-card text-slate-200">
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-amber-500/15 border border-amber-500/25">
                    <font-awesome-icon icon="fa-solid fa-hourglass-half" class="text-amber-400 text-lg" />
                </div>

                <h2 class="mt-4 text-lg font-semibold text-white">Whoa, slow down a bit!</h2>

                <p class="mt-2 text-sm text-slate-400">
                    You've made too many requests in a short time. Please wait a moment before trying again.
                </p>

                <p v-if="retryAfterSeconds !== null && retryAfterSeconds > 0" class="mt-3 text-sm font-medium text-slate-300">
                    You can try again in <span class="text-amber-400">{{ retryAfterSeconds }}s</span>
                </p>

                <div class="mt-6 flex justify-center">
                    <PrimaryButton
                        type="button"
                        :disabled="retryAfterSeconds !== null && retryAfterSeconds > 0"
                        :class="{
                            'opacity-40 cursor-not-allowed': retryAfterSeconds !== null && retryAfterSeconds > 0,
                        }"
                        @click="closeLimitModal()"
                    >
                        Got it
                    </PrimaryButton>
                </div>
            </div>
        </Modal>
    </AdminLayout>
</template>

<style scoped>
.glass-panel {
    background: linear-gradient(145deg, rgba(18, 24, 39, 0.85) 0%, rgba(13, 17, 28, 0.8) 100%);
    border: 1px solid rgba(255, 255, 255, 0.06);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
}
</style>
