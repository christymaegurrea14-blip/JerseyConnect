<script setup lang="ts">
import AdminLayout from "@/Layouts/AdminLayout.vue";
import Modal from "@/Components/Modal.vue";
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
    user_info: userInfo;
    email: string;
    status: "active" | "inactive";
    created_at: string;
    updated_at: string;
}

const props = defineProps<{
    data?: User[];
}>();

type userStatus = "active" | "inactive";

const users = computed(() => props.data ?? []);

const activeCount = computed(
    () => users.value.filter((u) => u.status === "active").length,
);
const inactiveCount = computed(
    () => users.value.filter((u) => u.status === "inactive").length,
);

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

const searchQuery = ref("");
const dateFrom = ref("");
const dateTo = ref("");
const perPage = ref(10);
const perPageOptions = [5, 10, 25, 50];
const currentPage = ref(1);

const filteredUsers = computed(() => {
    let list = users.value;

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
                `${u.user_info.first_name} ${u.user_info.last_name}`.toLowerCase().includes(q),
        );
    }
    return list;
});

watch([dateFrom, dateTo, searchQuery, perPage], () => {
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
}

const hasActiveFilters = computed(
    () => !!dateFrom.value || !!dateTo.value || !!searchQuery.value.trim(),
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
            <div class="flex flex-col gap-1">
                <h1 class="text-2xl font-extrabold text-white tracking-tight">Users</h1>
                <p class="text-sm text-slate-400">
                    {{ users.length }} user{{ users.length === 1 ? "" : "s" }} total
                </p>
            </div>

            <!-- Stat cards -->
            <div class="grid grid-cols-3 gap-3.5">
                <div class="glass-panel rounded-xl p-4">
                    <span class="text-xs font-medium text-slate-400">Total users</span>
                    <div class="text-2xl font-bold text-white mt-1.5">{{ users.length }}</div>
                </div>
                <div class="glass-panel rounded-xl p-4">
                    <span class="text-xs font-medium text-slate-400">Active</span>
                    <div class="text-2xl font-bold text-emerald-300 mt-1.5">{{ activeCount }}</div>
                </div>
                <div class="glass-panel rounded-xl p-4">
                    <span class="text-xs font-medium text-slate-400">Inactive</span>
                    <div class="text-2xl font-bold text-rose-300 mt-1.5">{{ inactiveCount }}</div>
                </div>
            </div>

            <!-- Search -->
            <div class="relative w-full lg:w-72">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-500 pointer-events-none">
                    <font-awesome-icon icon="fa-solid fa-magnifying-glass" class="text-xs" />
                </span>
                <input
                    id="users-search"
                    v-model="searchQuery"
                    type="text"
                    name="search"
                    placeholder="Search name, email..."
                    aria-label="Search users"
                    class="w-full pl-8 pr-3 py-2 text-sm rounded-lg bg-slate-900/60 border border-white/10 text-slate-200 placeholder-slate-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500"
                />
            </div>

            <!-- Date range + per-page -->
            <div class="flex flex-wrap items-center gap-3">
                <div class="flex items-center gap-1.5">
                    <label for="users-from" class="text-xs text-slate-500">From</label>
                    <input
                        id="users-from"
                        v-model="dateFrom"
                        type="date"
                        name="dateFrom"
                        class="text-sm rounded-lg bg-slate-900/60 border border-white/10 text-slate-200 py-1.5 px-2.5 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500"
                    />
                </div>
                <div class="flex items-center gap-1.5">
                    <label for="users-to" class="text-xs text-slate-500">To</label>
                    <input
                        id="users-to"
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
                    <label for="users-per-page" class="text-xs text-slate-500">Show</label>
                    <select
                        id="users-per-page"
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
                                class="hover:bg-slate-800/30 transition-colors"
                            >
                                <td class="px-4 py-3 font-semibold text-white">
                                    {{ row.user_info.first_name }} {{ row.user_info.middle_name }} {{ row.user_info.last_name }}
                                </td>
                                <td class="px-4 py-3 text-slate-300">{{ formatDate(row.user_info.birth_date) }}</td>
                                <td class="px-4 py-3 text-slate-300">{{ row.user_info.phone }}</td>
                                <td class="px-4 py-3 text-slate-300">{{ row.user_info.address }}</td>
                                <td class="px-4 py-3 text-slate-300">{{ row.email }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-block rounded-full px-2.5 py-1 text-[11px] font-medium border" :class="userStatus[row.status].class">
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
        </div>

        <!-- Edit Modal -->
        <Modal :show="modal.type.value === 'Edit'" @close="closeModal()" :maxWidth="'md'">
            <form @submit.prevent="submitEdit" class="px-4 pt-5 pb-4 sm:p-6 bg-surface-card text-slate-200">
                <h2 class="text-lg font-semibold text-white">
                    <font-awesome-icon icon="fa-solid fa-edit" class="text-indigo-400" />
                    {{ modal.title.value }}
                </h2>
                <hr class="my-2 border-white/10" />

                <div class="flex flex-col gap-4">
                    <div>
                        <InputLabel for="status" value="Status" class="!text-slate-300" />
                        <SelectInput v-model="editForm.status" :options="statusOptions" class="mt-1 block w-full !bg-slate-900/60 !border-white/10 !text-slate-200" id="status" required />
                        <InputError :message="editForm.errors.status" class="mt-2" />
                    </div>
                </div>

                <hr class="mt-4 border-white/10" />
                <div class="mt-2 flex flex-col-reverse gap-2 sm:flex-row sm:justify-between">
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
