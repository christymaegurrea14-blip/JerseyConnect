<script setup lang="ts">
import AdminLayout from "@/Layouts/AdminLayout.vue";
import Modal from "@/Components/Modal.vue";
import ModalHeader from "@/Components/ModalHeader.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import TextAreaInput from "@/Components/TextAreaInput.vue";
import SelectInput from "@/Components/SelectInput.vue";
import InputLabel from "@/Components/InputLabel.vue";
import InputError from "@/Components/InputError.vue";
import { useModal } from "@/Composables/useModal";
import { Head, useForm } from "@inertiajs/vue3";
import { ref, computed, watch } from "vue";

type CourierStatus = "active" | "inactive";

interface Courier {
    id: number;
    name: string;
    site: string | null;
    status: CourierStatus;
    created_at: string;
    updated_at: string;
    courier_receipts_count: number;
}

interface CourierStats {
    total_waybills: number;
    total_shipping_fees: number;
}

const props = defineProps<{
    couriers: Courier[];
    stats: CourierStats;
}>();

function formatCurrency(value: number) {
    return `₱${value.toLocaleString("en-PH")}`;
}

const activeCount = computed(
    () => props.couriers.filter((c) => c.status === "active").length,
);
const inactiveCount = computed(
    () => props.couriers.filter((c) => c.status === "inactive").length,
);

const courierStatus: Record<CourierStatus, { label: string; class: string }> = {
    active: {
        label: "Active",
        class: "bg-emerald-500/15 text-emerald-300 border-emerald-500/25",
    },
    inactive: {
        label: "Inactive",
        class: "bg-rose-500/15 text-rose-300 border-rose-500/25",
    },
};

const statusOptions = [
    { value: "active", label: "Active" },
    { value: "inactive", label: "Inactive" },
];

const badgePalette = [
    "from-indigo-500/30 to-violet-500/20 border-indigo-500/30 text-indigo-300",
    "from-cyan-500/30 to-blue-500/20 border-cyan-500/30 text-cyan-300",
    "from-amber-500/30 to-orange-500/20 border-amber-500/30 text-amber-300",
    "from-emerald-500/30 to-teal-500/20 border-emerald-500/30 text-emerald-300",
    "from-rose-500/30 to-pink-500/20 border-rose-500/30 text-rose-300",
];

function courierBadgeClass(name: string) {
    let hash = 0;
    for (let i = 0; i < name.length; i++) hash = (hash * 31 + name.charCodeAt(i)) >>> 0;
    return badgePalette[hash % badgePalette.length];
}

function courierInitials(name: string) {
    const words = name.trim().split(/\s+/).filter(Boolean);
    if (words.length === 1) return words[0].slice(0, 3).toUpperCase();
    return words.slice(0, 2).map((w) => w[0]).join("").toUpperCase();
}

/* ---------------- FILTERING ---------------- */

const searchQuery = ref("");
const dateFrom = ref("");
const dateTo = ref("");
const statusFilter = ref<"all" | CourierStatus>("all");
const perPage = ref(10);
const perPageOptions = [5, 10, 25, 50];
const currentPage = ref(1);

const filteredCouriers = computed(() => {
    let list = props.couriers;

    if (statusFilter.value !== "all") {
        list = list.filter((c) => c.status === statusFilter.value);
    }
    if (dateFrom.value) {
        const from = new Date(dateFrom.value);
        list = list.filter((c) => new Date(c.created_at) >= from);
    }
    if (dateTo.value) {
        const to = new Date(dateTo.value);
        to.setHours(23, 59, 59, 999);
        list = list.filter((c) => new Date(c.created_at) <= to);
    }

    const q = searchQuery.value.trim().toLowerCase();
    if (q) {
        list = list.filter(
            (c) =>
                c.name.toLowerCase().includes(q) ||
                (c.site ?? "").toLowerCase().includes(q),
        );
    }
    return list;
});

watch([dateFrom, dateTo, searchQuery, statusFilter, perPage], () => {
    currentPage.value = 1;
});

const totalPages = computed(() =>
    Math.max(1, Math.ceil(filteredCouriers.value.length / perPage.value)),
);

const paginatedCouriers = computed(() => {
    const start = (currentPage.value - 1) * perPage.value;
    return filteredCouriers.value.slice(start, start + perPage.value);
});

const rangeStart = computed(() =>
    filteredCouriers.value.length === 0
        ? 0
        : (currentPage.value - 1) * perPage.value + 1,
);
const rangeEnd = computed(() =>
    Math.min(currentPage.value * perPage.value, filteredCouriers.value.length),
);

function clearFilters() {
    dateFrom.value = "";
    dateTo.value = "";
    searchQuery.value = "";
    statusFilter.value = "all";
}

const hasActiveFilters = computed(
    () => !!dateFrom.value || !!dateTo.value || !!searchQuery.value.trim() || statusFilter.value !== "all",
);

const modal = useModal();

function closeModal() {
    modal.closeModal();
    addForm.reset();
    addForm.clearErrors();
    editForm.reset();
    editForm.clearErrors();
    deleteForm.clearErrors();
    courierToDelete.value = null;
}

function formatDate(value: string) {
    return new Date(value).toLocaleDateString("en-PH", {
        year: "numeric",
        month: "short",
        day: "numeric",
    });
}

/* ---------------- ADD ---------------- */

const addForm = useForm({
    name: "",
    site: "",
    status: "active" as CourierStatus,
});

function openAddModal() {
    addForm.reset();
    addForm.clearErrors();
    modal.title.value = "Add Courier";
    modal.type.value = "Add";
    modal.openModal();
}

function submitAdd() {
    addForm.post(route("admin.couriers.store"), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
    });
}

/* ---------------- EDIT ---------------- */

const editForm = useForm({
    id: null as number | null,
    name: "",
    site: "",
    status: "active" as CourierStatus,
});

function openEditModal(row: Courier) {
    editForm.reset();
    editForm.clearErrors();

    editForm.id = row.id;
    editForm.name = row.name;
    editForm.site = row.site ?? "";
    editForm.status = row.status;

    modal.title.value = "Edit Courier";
    modal.type.value = "Edit";
    modal.openModal();
}

function submitEdit() {
    if (!editForm.id) return;

    editForm
        .transform((data) => ({
            ...data,
            _method: "put",
        }))
        .post(route("admin.couriers.update", editForm.id), {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });
}

/* ---------------- DELETE ---------------- */

const courierToDelete = ref<Courier | null>(null);

const deleteForm = useForm<{ courier?: string }>({});

function openDeleteModal(row: Courier) {
    courierToDelete.value = row;
    modal.title.value = "Delete Courier";
    modal.type.value = "Delete";
    modal.openModal();
}

function submitDelete() {
    if (!courierToDelete.value) return;

    deleteForm.delete(
        route("admin.couriers.destroy", courierToDelete.value.id),
        {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        },
    );
}
</script>

<template>
    <Head title="Couriers" />

    <AdminLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div v-reveal class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 rounded-xl bg-indigo-500/10 border border-indigo-500/20 flex items-center justify-center text-indigo-400 shrink-0">
                        <font-awesome-icon icon="fa-solid fa-truck-fast" />
                    </div>
                    <div class="flex flex-col gap-1">
                        <h1 class="text-2xl font-extrabold text-white tracking-tight">Couriers</h1>
                        <p class="text-sm text-slate-400">
                            {{ props.couriers.length }} courier{{ props.couriers.length === 1 ? "" : "s" }} registered for delivery
                        </p>
                    </div>
                </div>
                <PrimaryButton class="flex items-center justify-center gap-1.5 w-full sm:w-auto" @click="openAddModal">
                    <font-awesome-icon icon="fa-solid fa-plus-circle" />
                    Add Courier
                </PrimaryButton>
            </div>

            <!-- Stat cards -->
            <div v-reveal="80" class="grid grid-cols-2 lg:grid-cols-4 gap-3.5">
                <div v-reveal="0" class="glass-panel rounded-2xl p-5 relative overflow-hidden group hover:border-indigo-500/40 transition-all">
                    <div class="absolute -right-4 -bottom-4 w-20 h-20 bg-indigo-500/10 rounded-full blur-xl group-hover:bg-indigo-400/20 transition-all"></div>
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium text-slate-400">Total couriers</span>
                        <span class="p-1.5 rounded-lg bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">
                            <font-awesome-icon icon="fa-solid fa-truck" class="text-xs" />
                        </span>
                    </div>
                    <div class="text-2xl font-bold text-white mt-3">{{ props.couriers.length }}</div>
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
                <div v-reveal="210" class="glass-panel rounded-2xl p-5 relative overflow-hidden group hover:border-cyan-500/40 transition-all">
                    <div class="absolute -right-4 -bottom-4 w-20 h-20 bg-cyan-500/10 rounded-full blur-xl group-hover:bg-cyan-400/20 transition-all"></div>
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium text-slate-400">Waybills dispatched</span>
                        <span class="p-1.5 rounded-lg bg-cyan-500/10 text-cyan-400 border border-cyan-500/20">
                            <font-awesome-icon icon="fa-solid fa-box" class="text-xs" />
                        </span>
                    </div>
                    <div class="text-2xl font-bold text-cyan-300 mt-3">{{ stats.total_waybills }}</div>
                    <p class="text-[11px] text-slate-500 mt-1">{{ formatCurrency(stats.total_shipping_fees) }} in shipping fees</p>
                </div>
            </div>

            <!-- Filter toolbar -->
            <div v-reveal="140" class="glass-panel rounded-2xl p-4 space-y-3">
                <div class="flex flex-col lg:flex-row items-stretch lg:items-center justify-between gap-3">
                    <div class="relative w-full lg:w-96">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-500 pointer-events-none">
                            <font-awesome-icon icon="fa-solid fa-magnifying-glass" class="text-xs" />
                        </span>
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Search couriers by name or site..."
                            class="w-full pl-10 pr-4 py-2.5 text-xs rounded-xl bg-slate-950/60 border border-white/10 text-slate-200 placeholder-slate-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500"
                        />
                    </div>

                    <div class="flex flex-wrap items-center gap-2.5">
                        <div class="flex items-center gap-1.5 bg-slate-950/60 border border-white/10 rounded-xl px-3 py-2 text-xs text-slate-300">
                            <span class="text-slate-500 font-medium">From</span>
                            <input v-model="dateFrom" type="date" class="bg-transparent border-none p-0 text-xs text-slate-200 focus:ring-0" />
                        </div>
                        <div class="flex items-center gap-1.5 bg-slate-950/60 border border-white/10 rounded-xl px-3 py-2 text-xs text-slate-300">
                            <span class="text-slate-500 font-medium">To</span>
                            <input v-model="dateTo" type="date" class="bg-transparent border-none p-0 text-xs text-slate-200 focus:ring-0" />
                        </div>
                        <div class="flex items-center gap-1.5 bg-slate-950/60 border border-white/10 rounded-xl px-3 py-2 text-xs text-slate-300">
                            <span class="text-slate-500">Show</span>
                            <select v-model.number="perPage" class="bg-transparent border-none text-xs text-indigo-400 font-bold focus:ring-0 p-0 pr-1 cursor-pointer">
                                <option v-for="n in perPageOptions" :key="n" :value="n">{{ n }}</option>
                            </select>
                        </div>
                        <button
                            v-if="hasActiveFilters"
                            type="button"
                            @click="clearFilters"
                            class="flex items-center gap-1.5 text-xs font-medium text-rose-300 border border-rose-500/25 bg-rose-500/10 hover:bg-rose-500/20 rounded-xl px-2.5 py-2 transition-colors"
                        >
                            <font-awesome-icon icon="fa-solid fa-xmark" />
                            Clear
                        </button>
                    </div>
                </div>

                <div class="flex items-center gap-1 bg-slate-950/60 border border-white/5 p-1 rounded-xl w-fit">
                    <button
                        type="button"
                        @click="statusFilter = 'all'"
                        class="px-3 py-1 rounded-lg text-xs font-semibold transition-colors"
                        :class="statusFilter === 'all' ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-400 hover:text-slate-200'"
                    >
                        All ({{ props.couriers.length }})
                    </button>
                    <button
                        type="button"
                        @click="statusFilter = 'active'"
                        class="px-3 py-1 rounded-lg text-xs font-medium transition-colors flex items-center gap-1.5"
                        :class="statusFilter === 'active' ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-400 hover:text-slate-200'"
                    >
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                        Active ({{ activeCount }})
                    </button>
                    <button
                        type="button"
                        @click="statusFilter = 'inactive'"
                        class="px-3 py-1 rounded-lg text-xs font-medium transition-colors flex items-center gap-1.5"
                        :class="statusFilter === 'inactive' ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-400 hover:text-slate-200'"
                    >
                        <span class="w-1.5 h-1.5 rounded-full bg-rose-400"></span>
                        Inactive ({{ inactiveCount }})
                    </button>
                </div>
            </div>

            <!-- Table -->
            <div v-reveal="200" class="glass-panel rounded-2xl overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-white/5">
                                <th class="px-4 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-slate-400">Courier</th>
                                <th class="px-4 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-slate-400">Site</th>
                                <th class="px-4 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-slate-400">Status</th>
                                <th class="px-4 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-slate-400">Waybills</th>
                                <th class="px-4 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-slate-400">Created</th>
                                <th class="px-4 py-3 text-center text-[11px] font-bold uppercase tracking-wider text-slate-400">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            <tr v-if="filteredCouriers.length === 0">
                                <td colspan="6" class="px-4 py-10 text-center text-sm text-slate-500">No couriers found.</td>
                            </tr>
                            <tr
                                v-for="row in paginatedCouriers"
                                :key="row.id"
                                class="hover:bg-slate-800/30 transition-colors group"
                            >
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="w-9 h-9 rounded-xl bg-gradient-to-tr border flex items-center justify-center font-bold text-[11px] shrink-0"
                                            :class="courierBadgeClass(row.name)"
                                        >
                                            {{ courierInitials(row.name) }}
                                        </div>
                                        <span class="font-semibold text-white group-hover:text-indigo-300 transition-colors">{{ row.name }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <a v-if="row.site" :href="row.site" target="_blank" class="inline-flex items-center gap-1.5 text-indigo-400 hover:text-indigo-300">
                                        <font-awesome-icon icon="fa-solid fa-link" class="text-xs" />
                                        Visit site
                                    </a>
                                    <span v-else class="text-slate-600">—</span>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-medium border" :class="courierStatus[row.status].class">
                                        <span
                                            class="w-1.5 h-1.5 rounded-full"
                                            :class="row.status === 'active' ? 'bg-emerald-400' : 'bg-rose-400'"
                                        ></span>
                                        {{ courierStatus[row.status].label }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-300">
                                        <font-awesome-icon icon="fa-solid fa-box" class="text-cyan-400 text-[10px]" />
                                        {{ row.courier_receipts_count }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-xs text-slate-400">{{ formatDate(row.created_at) }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-center gap-1">
                                        <button
                                            type="button"
                                            class="text-xs font-medium bg-slate-800 text-slate-300 border border-white/10 rounded-md px-2 py-2 transition-colors hover:bg-slate-700"
                                            @click="openEditModal(row)"
                                        >
                                            <font-awesome-icon icon="fa-solid fa-edit" />
                                        </button>
                                        <button
                                            type="button"
                                            class="text-xs font-medium bg-rose-500/20 text-rose-300 border border-rose-500/30 rounded-md px-2 py-2 transition-colors hover:bg-rose-500/30"
                                            @click="openDeleteModal(row)"
                                        >
                                            <font-awesome-icon icon="fa-solid fa-trash" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="filteredCouriers.length > 0" class="flex flex-col sm:flex-row items-center justify-between gap-3">
                <p class="text-xs text-slate-500">
                    Showing <span class="text-slate-300 font-medium">{{ rangeStart }}–{{ rangeEnd }}</span>
                    of <span class="text-slate-300 font-medium">{{ filteredCouriers.length }}</span> couriers
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
        <Modal :show="modal.type.value === 'Add'" @close="closeModal()" :maxWidth="'md'">
            <ModalHeader
                icon="fa-solid fa-plus-circle"
                :title="modal.title.value"
                subtitle="Register a new delivery partner"
                @close="closeModal()"
            />
            <form @submit.prevent="submitAdd" class="px-5 py-5 text-slate-200">
                <div class="flex flex-col gap-4">
                    <div>
                        <InputLabel for="name" value="Name" class="!text-slate-300" />
                        <TextInput v-model="addForm.name" class="mt-1 block w-full !bg-slate-900/60 !border-white/10 !text-slate-200" id="name" required placeholder="e.g. DHL" />
                        <InputError :message="addForm.errors.name" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="site" value="Site" class="!text-slate-300" />
                        <TextAreaInput v-model="addForm.site" class="mt-1 block w-full !bg-slate-900/60 !border-white/10 !text-slate-200" id="site" rows="3" placeholder="e.g. https://example.com/track?..." />
                        <InputError :message="addForm.errors.site" class="mt-2" />
                    </div>
                </div>
                <div class="mt-5 pt-4 border-t border-white/10 flex flex-col-reverse gap-2 sm:flex-row sm:justify-between">
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

        <!-- Edit Modal -->
        <Modal :show="modal.type.value === 'Edit'" @close="closeModal()" :maxWidth="'md'">
            <ModalHeader
                icon="fa-solid fa-edit"
                :title="modal.title.value"
                subtitle="Update delivery partner details"
                @close="closeModal()"
            />
            <form @submit.prevent="submitEdit" class="px-5 py-5 text-slate-200">
                <div class="flex flex-col gap-4">
                    <div>
                        <InputLabel for="edit-name" value="Name" class="!text-slate-300" />
                        <TextInput v-model="editForm.name" class="mt-1 block w-full !bg-slate-900/60 !border-white/10 !text-slate-200" id="edit-name" required placeholder="e.g. DHL" />
                        <InputError :message="editForm.errors.name" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="edit-site" value="Site" class="!text-slate-300" />
                        <TextAreaInput v-model="editForm.site" class="mt-1 block w-full !bg-slate-900/60 !border-white/10 !text-slate-200" id="edit-site" rows="3" placeholder="e.g. https://example.com/track?..." />
                        <InputError :message="editForm.errors.site" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="edit-status" value="Status" class="!text-slate-300" />
                        <SelectInput v-model="editForm.status" :options="statusOptions" class="mt-1 block w-full !bg-slate-900/60 !border-white/10 !text-slate-200" id="edit-status" required />
                        <InputError :message="editForm.errors.status" class="mt-2" />
                    </div>
                </div>

                <div class="mt-5 pt-4 border-t border-white/10 flex flex-col-reverse gap-2 sm:flex-row sm:justify-between">
                    <SecondaryButton type="button" class="flex items-center justify-center" @click="closeModal()">Cancel</SecondaryButton>

                    <PrimaryButton
                        type="submit"
                        class="flex items-center justify-center gap-1"
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

        <!-- Delete Modal -->
        <Modal :show="modal.type.value === 'Delete'" @close="closeModal()" :maxWidth="'md'">
            <ModalHeader
                icon="fa-solid fa-trash"
                icon-class="text-rose-400 bg-rose-500/15 border-rose-500/25"
                :title="modal.title.value"
                @close="closeModal()"
            />
            <div class="px-5 py-5 text-slate-200">
                <p class="text-sm text-slate-400">
                    Are you sure you want to delete
                    <span class="font-semibold text-white">{{ courierToDelete?.name }}</span>? This action cannot be undone.
                </p>

                <InputError :message="deleteForm.errors.courier" class="mt-2" />
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
