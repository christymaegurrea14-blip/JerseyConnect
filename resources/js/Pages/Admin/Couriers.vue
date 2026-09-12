<script setup lang="ts">
import AdminLayout from "@/Layouts/AdminLayout.vue";
import Modal from "@/Components/Modal.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import TextAreaInput from "@/Components/TextAreaInput.vue";
import SelectInput from "@/Components/SelectInput.vue";
import InputLabel from "@/Components/InputLabel.vue";
import InputError from "@/Components/InputError.vue";
import { useModal } from "@/Composables/useModal";
import { Head, useForm } from "@inertiajs/vue3";
import { ref, computed } from "vue";

type CourierStatus = "active" | "inactive";

interface Courier {
    id: number;
    name: string;
    site: string | null;
    status: CourierStatus;
    created_at: string;
    updated_at: string;
}

const props = defineProps<{
    couriers: Courier[];
}>();

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
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex flex-col gap-1">
                    <h1 class="text-2xl font-extrabold text-white tracking-tight">Couriers</h1>
                    <p class="text-sm text-slate-400">
                        {{ props.couriers.length }} courier{{ props.couriers.length === 1 ? "" : "s" }} total
                    </p>
                </div>
                <PrimaryButton class="flex items-center justify-center gap-1.5 w-full sm:w-auto" @click="openAddModal">
                    <font-awesome-icon icon="fa-solid fa-plus-circle" />
                    Add Courier
                </PrimaryButton>
            </div>

            <!-- Stat cards -->
            <div class="grid grid-cols-3 gap-3.5">
                <div class="glass-panel rounded-xl p-4">
                    <span class="text-xs font-medium text-slate-400">Total couriers</span>
                    <div class="text-2xl font-bold text-white mt-1.5">{{ props.couriers.length }}</div>
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

            <!-- Table -->
            <div class="glass-panel rounded-2xl overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-white/5">
                                <th class="px-4 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-slate-400">Courier</th>
                                <th class="px-4 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-slate-400">Site</th>
                                <th class="px-4 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-slate-400">Status</th>
                                <th class="px-4 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-slate-400">Created</th>
                                <th class="px-4 py-3 text-center text-[11px] font-bold uppercase tracking-wider text-slate-400">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            <tr v-if="props.couriers.length === 0">
                                <td colspan="5" class="px-4 py-10 text-center text-sm text-slate-500">No couriers yet.</td>
                            </tr>
                            <tr
                                v-for="row in props.couriers"
                                :key="row.id"
                                class="hover:bg-slate-800/30 transition-colors"
                            >
                                <td class="px-4 py-3 font-semibold text-white">{{ row.name }}</td>
                                <td class="px-4 py-3">
                                    <a v-if="row.site" :href="row.site" target="_blank" class="text-indigo-400 hover:text-indigo-300">
                                        <font-awesome-icon icon="fa-solid fa-link" />
                                        Visit site
                                    </a>
                                    <span v-else class="text-slate-600">—</span>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-block rounded-full px-2.5 py-1 text-[11px] font-medium border" :class="courierStatus[row.status].class">
                                        {{ courierStatus[row.status].label }}
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
        </div>

        <!-- Add Modal -->
        <Modal :show="modal.type.value === 'Add'" @close="closeModal()" :maxWidth="'md'">
            <form @submit.prevent="submitAdd" class="px-4 pt-5 pb-4 sm:p-6 bg-surface-card text-slate-200">
                <h2 class="text-lg font-semibold text-white">
                    <font-awesome-icon icon="fa-solid fa-plus-circle" class="text-indigo-400" />
                    {{ modal.title.value }}
                </h2>
                <hr class="my-2 border-white/10" />
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

                <hr class="mt-4 border-white/10" />
                <div class="mt-2 flex flex-col-reverse gap-2 sm:flex-row sm:justify-between">
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
            <div class="px-4 pt-5 pb-4 sm:p-6 bg-surface-card text-slate-200">
                <h2 class="text-lg font-semibold text-white">
                    <font-awesome-icon icon="fa-solid fa-trash" class="text-rose-400" />
                    {{ modal.title.value }}
                </h2>
                <hr class="my-2 border-white/10" />

                <p class="text-sm text-slate-400">
                    Are you sure you want to delete
                    <span class="font-semibold text-white">{{ courierToDelete?.name }}</span>? This action cannot be undone.
                </p>

                <hr class="mt-4 border-white/10" />
                <InputError :message="deleteForm.errors.courier" class="mt-2" />
                <div class="mt-4 flex flex-col-reverse gap-2 sm:flex-row sm:justify-between">
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
