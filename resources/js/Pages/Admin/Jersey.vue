<script setup lang="ts">
import AdminLayout from "@/Layouts/AdminLayout.vue";
import Modal from "@/Components/Modal.vue";
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

const editForm = useForm({
    id: null as number | null,
    name: "",
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

    editForm.id = row.id;
    editForm.name = row.name;
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
</script>

<template>
    <Head title="Jersey Templates" />

    <AdminLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
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
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3.5">
                <div class="glass-panel rounded-xl p-4">
                    <span class="text-xs font-medium text-slate-400">Total templates</span>
                    <div class="text-2xl font-bold text-white mt-1.5">{{ jerseyTemplates.length }}</div>
                </div>
                <div class="glass-panel rounded-xl p-4">
                    <span class="text-xs font-medium text-slate-400">Active</span>
                    <div class="text-2xl font-bold text-emerald-300 mt-1.5">{{ activeCount }}</div>
                </div>
                <div class="glass-panel rounded-xl p-4">
                    <span class="text-xs font-medium text-slate-400">Inactive</span>
                    <div class="text-2xl font-bold text-rose-300 mt-1.5">{{ inactiveCount }}</div>
                </div>
                <div class="glass-panel rounded-xl p-4">
                    <span class="text-xs font-medium text-slate-400">Badged</span>
                    <div class="text-2xl font-bold text-indigo-300 mt-1.5">{{ badgedCount }}</div>
                </div>
            </div>

            <!-- Search -->
            <div class="relative w-full lg:w-72">
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
            <div class="glass-panel rounded-2xl overflow-hidden">
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
                                        <img
                                            :src="row.image"
                                            :alt="row.name"
                                            class="h-9 w-9 flex-shrink-0 rounded-lg object-contain bg-slate-900 border border-white/10 p-1"
                                        />
                                        <span class="font-semibold text-white">{{ row.name }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-right text-slate-200 font-medium">₱{{ row.price }}</td>
                                <td class="px-4 py-3 text-slate-300">{{ row.badge ?? "—" }}</td>
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
            <form @submit.prevent="submitAdd" class="px-4 pt-5 pb-4 sm:p-6 bg-surface-card text-slate-200">
                <h2 class="text-lg font-semibold text-white">
                    <font-awesome-icon icon="fa-solid fa-plus-circle" class="text-indigo-400" />
                    {{ modal.title.value }}
                </h2>
                <hr class="my-2 border-white/10" />
                <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block font-medium text-slate-300">Jersey Image</label>
                        <ImageUpload class="mt-1" :image="addImageStyle" @imageFile="onAddImageChange" />
                        <InputError :message="addForm.errors.image" class="mt-2" />
                    </div>

                    <div class="flex flex-col gap-4">
                        <div>
                            <InputLabel for="name" value="Name" class="!text-slate-300" />
                            <TextInput v-model="addForm.name" class="mt-1 block w-full !bg-slate-900/60 !border-white/10 !text-slate-200" id="name" required />
                            <InputError :message="addForm.errors.name" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel for="price" value="Price" class="!text-slate-300" />
                            <TextInput v-model="addForm.price" class="mt-1 block w-full !bg-slate-900/60 !border-white/10 !text-slate-200" id="price" required />
                            <InputError :message="addForm.errors.price" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel for="badge" value="Badge" class="!text-slate-300" />
                            <SelectInput v-model="addForm.badge" :options="badgeOptions" class="mt-1 block w-full !bg-slate-900/60 !border-white/10 !text-slate-200" id="badge" required />
                            <InputError :message="addForm.errors.badge" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel for="sport" value="Sport" class="!text-slate-300" />
                            <SelectInput v-model="addForm.sport" :options="sportOptions" class="mt-1 block w-full !bg-slate-900/60 !border-white/10 !text-slate-200" id="sport" required />
                            <InputError :message="addForm.errors.sport" class="mt-2" />
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div>
                                <InputLabel for="primary" value="Primary Color" class="!text-slate-300" />
                                <div class="mt-1 flex items-center gap-1">
                                    <input type="color" v-model="addForm.primary_color" class="h-10 w-12 rounded border border-white/10 bg-slate-900" id="primary" />
                                    <TextInput v-model="addForm.primary_color" class="block w-full !bg-slate-900/60 !border-white/10 !text-slate-200" />
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-300">Secondary Color</label>
                                <div class="mt-1 flex items-center gap-1">
                                    <input type="color" v-model="addForm.secondary_color" class="h-10 w-12 rounded border border-white/10 bg-slate-900" />
                                    <TextInput v-model="addForm.secondary_color" class="block w-full !bg-slate-900/60 !border-white/10 !text-slate-200" />
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-300">Accent Color</label>
                                <div class="mt-1 flex items-center gap-1">
                                    <input type="color" v-model="addForm.accent_color" class="h-10 w-12 rounded border border-white/10 bg-slate-900" />
                                    <TextInput v-model="addForm.accent_color" class="block w-full !bg-slate-900/60 !border-white/10 !text-slate-200" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <hr class="mt-4 border-white/10" />
                <div class="mt-2 flex flex-col-reverse gap-2 sm:flex-row sm:justify-between">
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
            <div v-if="selectedJersey" class="px-4 pt-5 pb-4 sm:p-6 bg-surface-card text-slate-200">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-white">
                        <font-awesome-icon icon="fa-solid fa-eye" class="text-indigo-400" />
                        {{ modal.title.value }}
                    </h2>
                    <SecondaryButton @click="closeModal()">
                        <font-awesome-icon icon="fa-solid fa-xmark" />
                    </SecondaryButton>
                </div>
                <hr class="my-2 border-white/10" />
                <div class="mt-2 flex items-center gap-3">
                    <img
                        :src="selectedJersey.image"
                        :alt="selectedJersey.name"
                        class="mx-auto max-h-80 w-full rounded object-contain bg-slate-950/50 border border-white/10 p-4"
                    />
                </div>
            </div>
        </Modal>

        <!-- Edit Modal -->
        <Modal :show="modal.type.value === 'Edit'" @close="closeModal()" :maxWidth="'5xl'">
            <form @submit.prevent="submitEdit" class="px-4 pt-5 pb-4 sm:p-6 bg-surface-card text-slate-200">
                <h2 class="text-lg font-semibold text-white">
                    <font-awesome-icon icon="fa-solid fa-edit" class="text-indigo-400" />
                    {{ modal.title.value }}
                </h2>
                <hr class="my-2 border-white/10" />
                <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-slate-300">Jersey Image</label>
                        <ImageUpload class="mt-1" :image="editImageStyle" @imageFile="onEditImageChange" />
                        <InputError :message="editForm.errors.image" class="mt-2" />
                    </div>

                    <div class="flex flex-col gap-4">
                        <div>
                            <InputLabel for="name" value="Name" class="!text-slate-300" />
                            <TextInput v-model="editForm.name" class="mt-1 block w-full !bg-slate-900/60 !border-white/10 !text-slate-200" id="name" required />
                            <InputError :message="editForm.errors.name" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel for="price" value="Price" class="!text-slate-300" />
                            <TextInput v-model="editForm.price" class="mt-1 block w-full !bg-slate-900/60 !border-white/10 !text-slate-200" id="price" required />
                            <InputError :message="editForm.errors.price" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel for="badge" value="Badge" class="!text-slate-300" />
                            <SelectInput v-model="editForm.badge" :options="badgeOptions" class="mt-1 block w-full !bg-slate-900/60 !border-white/10 !text-slate-200" id="badge" required />
                            <InputError :message="editForm.errors.badge" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel for="sport" value="Sport" class="!text-slate-300" />
                            <SelectInput v-model="editForm.sport" :options="sportOptions" class="mt-1 block w-full !bg-slate-900/60 !border-white/10 !text-slate-200" id="sport" required />
                            <InputError :message="editForm.errors.sport" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel for="status" value="Status" class="!text-slate-300" />
                            <SelectInput v-model="editForm.status" :options="jerseyStatus" class="mt-1 block w-full !bg-slate-900/60 !border-white/10 !text-slate-200" id="status" required />
                            <InputError :message="editForm.errors.status" class="mt-2" />
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div>
                                <InputLabel for="primary" value="Primary Color" class="!text-slate-300" />
                                <div class="mt-1 flex items-center gap-1">
                                    <input type="color" v-model="editForm.primary_color" class="h-10 w-12 rounded border border-white/10 bg-slate-900" id="primary" />
                                    <TextInput v-model="editForm.primary_color" class="block w-full !bg-slate-900/60 !border-white/10 !text-slate-200" />
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-300">Secondary Color</label>
                                <div class="mt-1 flex items-center gap-1">
                                    <input type="color" v-model="editForm.secondary_color" class="h-10 w-12 rounded border border-white/10 bg-slate-900" />
                                    <TextInput v-model="editForm.secondary_color" class="block w-full !bg-slate-900/60 !border-white/10 !text-slate-200" />
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-300">Accent Color</label>
                                <div class="mt-1 flex items-center gap-1">
                                    <input type="color" v-model="editForm.accent_color" class="h-10 w-12 rounded border border-white/10 bg-slate-900" />
                                    <TextInput v-model="editForm.accent_color" class="block w-full !bg-slate-900/60 !border-white/10 !text-slate-200" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <hr class="mt-4 border-white/10" />
                <div class="mt-2 flex flex-col-reverse gap-2 sm:flex-row sm:justify-between">
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
            </form>
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
