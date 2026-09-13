<script setup lang="ts">
import AdminLayout from "@/Layouts/AdminLayout.vue";
import Modal from "@/Components/Modal.vue";
import ModalHeader from "@/Components/ModalHeader.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import SelectInput from "@/Components/SelectInput.vue";
import InputLabel from "@/Components/InputLabel.vue";
import InputError from "@/Components/InputError.vue";
import { useModal } from "@/Composables/useModal";
import { Head, useForm } from "@inertiajs/vue3";
import { ref, computed, watch } from "vue";

interface userInfo {
    id: number;
    first_name: string;
    middle_name: string;
    last_name: string;
    birth_date: string;
    phone: string;
    address: string;
    created_at: string;
    updated_at: string;
}

interface User {
    id: number;
    user_info: userInfo | null;
    email: string;
    status: "active" | "inactive";
    created_at: string;
    updated_at: string;
}

type OrderStatus = "processing" | "in_production" | "ready_for_delivery" | "shipped" | "delivered" | "completed";

interface RecentOrder {
    id: number;
    order_number: string;
    team_name: string;
    quantity: number;
    status: OrderStatus;
    amount: number;
    created_at: string;
    customer_name: string;
}

const props = defineProps<{
    data?: User[];
    recentOrders?: RecentOrder[];
}>();

type userStatus = "active" | "inactive";

const users = computed(() => props.data ?? []);
const recentOrders = computed(() => props.recentOrders ?? []);

const orderStatusBadge: Record<OrderStatus, { label: string; class: string }> = {
    processing: { label: "Processing", class: "bg-amber-500/15 text-amber-300 border-amber-500/25" },
    in_production: { label: "In Production", class: "bg-indigo-500/15 text-indigo-300 border-indigo-500/25" },
    ready_for_delivery: { label: "Ready for Delivery", class: "bg-cyan-500/15 text-cyan-300 border-cyan-500/25" },
    shipped: { label: "Shipped", class: "bg-purple-500/15 text-purple-300 border-purple-500/25" },
    delivered: { label: "Delivered", class: "bg-teal-500/15 text-teal-300 border-teal-500/25" },
    completed: { label: "Completed", class: "bg-emerald-500/15 text-emerald-300 border-emerald-500/25" },
};

function formatCurrency(value: number) {
    return `₱${value.toLocaleString("en-PH")}`;
}

function timeAgo(value: string) {
    const seconds = Math.floor((Date.now() - new Date(value).getTime()) / 1000);
    if (seconds < 60) return "just now";
    const minutes = Math.floor(seconds / 60);
    if (minutes < 60) return `${minutes}m ago`;
    const hours = Math.floor(minutes / 60);
    if (hours < 24) return `${hours}h ago`;
    const days = Math.floor(hours / 24);
    if (days < 30) return `${days}d ago`;
    return new Date(value).toLocaleDateString("en-PH", { year: "numeric", month: "short", day: "numeric" });
}

const activeCount = computed(
    () => users.value.filter((u) => u.status === "active").length,
);
const inactiveCount = computed(
    () => users.value.filter((u) => u.status === "inactive").length,
);
const newThisWeekCount = computed(() => {
    const weekAgo = new Date();
    weekAgo.setDate(weekAgo.getDate() - 7);
    return users.value.filter((u) => new Date(u.created_at) >= weekAgo).length;
});

const userStatus: Record<userStatus, { label: string; class: string }> = {
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

const avatarPalette = [
    "from-amber-500/30 to-orange-500/20 border-amber-500/30 text-amber-300",
    "from-cyan-600 to-indigo-600 border-transparent text-white",
    "from-purple-500/30 to-indigo-500/20 border-purple-500/30 text-purple-300",
    "from-emerald-500/30 to-teal-500/20 border-emerald-500/30 text-emerald-300",
    "from-cyan-500/30 to-blue-500/20 border-cyan-500/30 text-cyan-300",
];

function avatarClass(email: string) {
    let hash = 0;
    for (let i = 0; i < email.length; i++) hash = (hash * 31 + email.charCodeAt(i)) >>> 0;
    return avatarPalette[hash % avatarPalette.length];
}

function userInitials(info: userInfo | null) {
    if (!info) return "?";
    return `${info.first_name?.[0] ?? ""}${info.last_name?.[0] ?? ""}`.toUpperCase() || "?";
}

function userFullName(info: userInfo | null) {
    if (!info) return "No info provided";
    return [info.first_name, info.middle_name, info.last_name].filter(Boolean).join(" ");
}

const searchQuery = ref("");
const dateFrom = ref("");
const dateTo = ref("");
const statusFilter = ref<"all" | userStatus>("all");
const perPage = ref(10);
const perPageOptions = [5, 10, 25, 50];
const currentPage = ref(1);

const filteredUsers = computed(() => {
    let list = users.value;

    if (statusFilter.value !== "all") {
        list = list.filter((u) => u.status === statusFilter.value);
    }
    if (dateFrom.value) {
        const from = new Date(dateFrom.value);
        list = list.filter((u) => new Date(u.created_at) >= from);
    }
    if (dateTo.value) {
        const to = new Date(dateTo.value);
        to.setHours(23, 59, 59, 999);
        list = list.filter((u) => new Date(u.created_at) <= to);
    }

    const q = searchQuery.value.trim().toLowerCase();
    if (q) {
        list = list.filter(
            (u) =>
                u.email.toLowerCase().includes(q) ||
                `${u.user_info?.first_name ?? ""} ${u.user_info?.last_name ?? ""}`.toLowerCase().includes(q),
        );
    }
    return list;
});

watch([dateFrom, dateTo, searchQuery, statusFilter, perPage], () => {
    currentPage.value = 1;
});

const totalPages = computed(() =>
    Math.max(1, Math.ceil(filteredUsers.value.length / perPage.value)),
);

const paginatedUsers = computed(() => {
    const start = (currentPage.value - 1) * perPage.value;
    return filteredUsers.value.slice(start, start + perPage.value);
});

const rangeStart = computed(() =>
    filteredUsers.value.length === 0
        ? 0
        : (currentPage.value - 1) * perPage.value + 1,
);
const rangeEnd = computed(() =>
    Math.min(currentPage.value * perPage.value, filteredUsers.value.length),
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
    editForm.reset();
    editForm.clearErrors();
}

function formatDate(value: string) {
    return new Date(value).toLocaleDateString("en-PH", {
        year: "numeric",
        month: "short",
        day: "numeric",
    });
}

/* ---------------- EDIT ---------------- */

const editForm = useForm({
    id: null as number | null,
    status: "active" as userStatus,
});

function openEditModal(row: User) {
    editForm.reset();
    editForm.clearErrors();

    editForm.id = row.id;
    editForm.status = row.status;

    modal.title.value = "Edit User Status";
    modal.type.value = "Edit";
    modal.openModal();
}

function submitEdit() {
    if (!editForm.id) return;
    editForm.put(route("admin.user.update", editForm.id), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
    });
}
</script>

<template>
    <Head title="Users" />

    <AdminLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div v-reveal class="flex items-center gap-3">
                <div class="w-11 h-11 rounded-xl bg-indigo-500/10 border border-indigo-500/20 flex items-center justify-center text-indigo-400 shrink-0">
                    <font-awesome-icon icon="fa-solid fa-users" />
                </div>
                <div class="flex flex-col gap-1">
                    <h1 class="text-2xl font-extrabold text-white tracking-tight">Users &amp; Customers</h1>
                    <p class="text-sm text-slate-400">
                        Manage customer profiles, delivery addresses, and account access.
                    </p>
                </div>
            </div>

            <!-- Stat cards -->
            <div v-reveal="80" class="grid grid-cols-2 lg:grid-cols-4 gap-3.5">
                <div v-reveal="0" class="glass-panel rounded-2xl p-5 relative overflow-hidden group hover:border-indigo-500/40 transition-all">
                    <div class="absolute -right-4 -bottom-4 w-20 h-20 bg-indigo-500/10 rounded-full blur-xl group-hover:bg-indigo-400/20 transition-all"></div>
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium text-slate-400">Total users</span>
                        <span class="p-1.5 rounded-lg bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">
                            <font-awesome-icon icon="fa-solid fa-users" class="text-xs" />
                        </span>
                    </div>
                    <div class="text-2xl font-bold text-white mt-3">{{ users.length }}</div>
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
                        <span class="text-xs font-medium text-slate-400">New this week</span>
                        <span class="p-1.5 rounded-lg bg-cyan-500/10 text-cyan-400 border border-cyan-500/20">
                            <font-awesome-icon icon="fa-solid fa-user-plus" class="text-xs" />
                        </span>
                    </div>
                    <div class="text-2xl font-bold text-cyan-300 mt-3">{{ newThisWeekCount }}</div>
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
                            id="users-search"
                            v-model="searchQuery"
                            type="text"
                            name="search"
                            placeholder="Search by name, email, address..."
                            aria-label="Search users"
                            class="w-full pl-10 pr-4 py-2.5 text-xs rounded-xl bg-slate-950/60 border border-white/10 text-slate-200 placeholder-slate-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500"
                        />
                    </div>

                    <div class="flex flex-wrap items-center gap-2.5">
                        <div class="flex items-center gap-1.5 bg-slate-950/60 border border-white/10 rounded-xl px-3 py-2 text-xs text-slate-300">
                            <span class="text-slate-500 font-medium">From</span>
                            <input id="users-from" v-model="dateFrom" type="date" name="dateFrom" class="bg-transparent border-none p-0 text-xs text-slate-200 focus:ring-0" />
                        </div>
                        <div class="flex items-center gap-1.5 bg-slate-950/60 border border-white/10 rounded-xl px-3 py-2 text-xs text-slate-300">
                            <span class="text-slate-500 font-medium">To</span>
                            <input id="users-to" v-model="dateTo" type="date" name="dateTo" class="bg-transparent border-none p-0 text-xs text-slate-200 focus:ring-0" />
                        </div>
                        <div class="flex items-center gap-1.5 bg-slate-950/60 border border-white/10 rounded-xl px-3 py-2 text-xs text-slate-300">
                            <span class="text-slate-500">Show</span>
                            <select id="users-per-page" v-model.number="perPage" name="perPage" class="bg-transparent border-none text-xs text-indigo-400 font-bold focus:ring-0 p-0 pr-1 cursor-pointer">
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
                        All ({{ users.length }})
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
                                <th class="px-4 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-slate-400">Name</th>
                                <th class="px-4 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-slate-400">Birth Date</th>
                                <th class="px-4 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-slate-400">Phone</th>
                                <th class="px-4 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-slate-400">Address</th>
                                <th class="px-4 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-slate-400">Email</th>
                                <th class="px-4 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-slate-400">Status</th>
                                <th class="px-4 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-slate-400">Created</th>
                                <th class="px-4 py-3 text-center text-[11px] font-bold uppercase tracking-wider text-slate-400">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            <tr v-if="filteredUsers.length === 0">
                                <td colspan="8" class="px-4 py-10 text-center text-sm text-slate-500">No users found.</td>
                            </tr>
                            <tr
                                v-for="row in paginatedUsers"
                                :key="row.id"
                                class="hover:bg-slate-800/30 transition-colors group"
                            >
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="w-9 h-9 rounded-xl bg-gradient-to-tr border flex items-center justify-center font-bold text-[11px] shrink-0"
                                            :class="avatarClass(row.email)"
                                        >
                                            {{ userInitials(row.user_info) }}
                                        </div>
                                        <div>
                                            <div
                                                class="font-semibold group-hover:text-indigo-300 transition-colors"
                                                :class="row.user_info ? 'text-white' : 'text-slate-500 italic'"
                                            >
                                                {{ userFullName(row.user_info) }}
                                            </div>
                                            <span class="text-[10px] text-slate-500 font-mono">UID-{{ String(row.id).padStart(4, "0") }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-slate-300">{{ row.user_info?.birth_date ? formatDate(row.user_info.birth_date) : "—" }}</td>
                                <td class="px-4 py-3 text-slate-300 font-mono">{{ row.user_info?.phone ?? "—" }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-1.5 text-slate-300">
                                        <font-awesome-icon icon="fa-solid fa-location-dot" class="text-indigo-400 text-xs shrink-0" />
                                        <span>{{ row.user_info?.address ?? "—" }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-slate-300">{{ row.email }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-medium border" :class="userStatus[row.status].class">
                                        <span
                                            class="w-1.5 h-1.5 rounded-full"
                                            :class="row.status === 'active' ? 'bg-emerald-400' : 'bg-rose-400'"
                                        ></span>
                                        {{ userStatus[row.status].label }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-xs text-slate-400">{{ formatDate(row.created_at) }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-center">
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
            <div v-if="filteredUsers.length > 0" class="flex flex-col sm:flex-row items-center justify-between gap-3">
                <p class="text-xs text-slate-500">
                    Showing <span class="text-slate-300 font-medium">{{ rangeStart }}–{{ rangeEnd }}</span>
                    of <span class="text-slate-300 font-medium">{{ filteredUsers.length }}</span> users
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

            <!-- Recent customer activity -->
            <div v-reveal="260" class="glass-panel rounded-2xl overflow-hidden">
                <div class="flex items-center justify-between border-b border-white/5 px-5 py-4 bg-black/10">
                    <h3 class="text-sm font-bold text-white">Recent Customer Activity</h3>
                    <span class="text-[11px] text-slate-500">Latest orders placed</span>
                </div>
                <div class="p-3 space-y-2">
                    <p v-if="recentOrders.length === 0" class="text-sm text-slate-500 text-center py-6">No orders placed yet.</p>
                    <div
                        v-for="order in recentOrders"
                        :key="order.id"
                        class="p-3 rounded-xl bg-slate-950/50 border border-white/5 flex items-center justify-between gap-3 hover:border-indigo-500/20 transition-colors"
                    >
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="w-8 h-8 rounded-lg bg-indigo-500/10 border border-indigo-500/20 flex items-center justify-center text-indigo-400 shrink-0">
                                <font-awesome-icon icon="fa-solid fa-box" class="text-xs" />
                            </div>
                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <span class="font-mono text-xs font-bold text-indigo-300">{{ order.order_number }}</span>
                                    <span class="inline-flex px-1.5 py-0.5 rounded text-[10px] font-medium border" :class="orderStatusBadge[order.status].class">
                                        {{ orderStatusBadge[order.status].label }}
                                    </span>
                                </div>
                                <p class="text-xs text-slate-400 mt-0.5 truncate">{{ order.customer_name }} • {{ order.quantity }}x {{ order.team_name }}</p>
                            </div>
                        </div>
                        <div class="text-right shrink-0">
                            <span class="text-xs font-bold text-white font-mono">{{ formatCurrency(order.amount) }}</span>
                            <p class="text-[10px] text-slate-500">{{ timeAgo(order.created_at) }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Modal -->
        <Modal :show="modal.type.value === 'Edit'" @close="closeModal()" :maxWidth="'md'">
            <ModalHeader
                icon="fa-solid fa-edit"
                :title="modal.title.value"
                subtitle="Enable or disable this customer's account access"
                @close="closeModal()"
            />
            <form @submit.prevent="submitEdit" class="px-5 py-5 text-slate-200">
                <div class="flex flex-col gap-4">
                    <div>
                        <InputLabel for="status" value="Status" class="!text-slate-300" />
                        <SelectInput v-model="editForm.status" :options="statusOptions" class="mt-1 block w-full !bg-slate-900/60 !border-white/10 !text-slate-200" id="status" required />
                        <InputError :message="editForm.errors.status" class="mt-2" />
                    </div>
                </div>

                <div class="mt-5 pt-4 border-t border-white/10 flex flex-col-reverse gap-2 sm:flex-row sm:justify-between">
                    <SecondaryButton type="button" class="flex items-center justify-center" @click="closeModal()">Cancel</SecondaryButton>

                    <PrimaryButton type="submit" class="flex items-center justify-center gap-1" :disabled="editForm.processing">
                        Save
                        <font-awesome-icon icon="fa-solid fa-circle-down" />
                    </PrimaryButton>
                </div>
            </form>
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
