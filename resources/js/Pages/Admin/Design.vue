<script setup lang="ts">
import AdminLayout from "@/Layouts/AdminLayout.vue";
import Table from "@/Components/Table.vue";
import Modal from "@/Components/Modal.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import DangerButton from "@/Components/DangerButton.vue";
import TextInput from "@/Components/TextInput.vue";
import SelectInput from "@/Components/SelectInput.vue";
import InputLabel from "@/Components/InputLabel.vue";
import InputError from "@/Components/InputError.vue";
import ImageUpload from "@/Components/ImageUpload.vue";
import type { DesignRequest, DesignRequestStatus } from "@/types/jersey";
import { useModal } from "@/Composables/useModal";
import { Head, Link, useForm, router, usePoll } from "@inertiajs/vue3";
import { ref, computed } from "vue";

const props = defineProps<{
    data?: DesignRequest[];
}>();

usePoll(5000, {
    only: ["data"],
});

const requests = computed(() => props.data ?? []);

const statusFilters: { label: string; value: DesignRequestStatus | "All" }[] = [
    { label: "All", value: "All" },
    { label: "Pending Review", value: "pending_review" },
    { label: "In Discussion", value: "in_discussion" },
    { label: "Revision Requested", value: "revision_requested" },
    { label: "Waiting for Down Payment", value: "waiting_for_down_payment" },
    {
        label: "Pending Down Payment Review",
        value: "pending_down_payment_review",
    },
    { label: "Approved", value: "approved" },
    { label: "Cancelled", value: "cancelled" },
];

const activeStatus = ref<DesignRequestStatus | "All">("All");

const statusBadge: Record<
    DesignRequestStatus,
    { label: string; class: string }
> = {
    pending_review: {
        label: "Pending Review",
        class: "bg-yellow-100 text-yellow-700",
    },
    in_discussion: {
        label: "In Discussion",
        class: "bg-blue-100 text-blue-700",
    },
    revision_requested: {
        label: "Revision Requested",
        class: "bg-orange-100 text-orange-700",
    },
    waiting_for_down_payment: {
        label: "Waiting for Down Payment",
        class: "bg-pink-100 text-pink-700",
    },
    pending_down_payment_review: {
        label: "Pending Down Payment Review",
        class: "bg-red-100 text-red-700",
    },
    approved: { label: "Approved", class: "bg-green-100 text-green-700" },
    cancelled: { label: "Cancelled", class: "bg-gray-200 text-gray-600" },
};

const columns = [
    { key: "template_name", label: "Design", slot: "template" },
    { key: "team_name", label: "Team" },
    { key: "colors", label: "Colors", slot: "colors" },
    { key: "estimated_quantity", label: "Qty" },
    { key: "template_price", label: "Price", slot: "price" },
    { key: "status", label: "Status", slot: "status" },
    { key: "created_at", label: "Submitted", slot: "date" },
    { key: "actions", label: "Action", slot: "actions" },
];

const filteredByStatus = computed<DesignRequest[]>(() => {
    if (activeStatus.value === "All") return requests.value;
    return requests.value.filter((r) => r.status === activeStatus.value);
});

const modal = useModal();
const selectedRequest = ref<DesignRequest | null>(null);

function viewRequest(request: DesignRequest) {
    selectedRequest.value = request;
    modal.title.value = "Design Request Details";
    modal.type.value = "View";
    modal.icon.value = "fa-solid fa-tshirt";
    modal.openModal();
}

function cancelRequest(request: DesignRequest) {
    selectedRequest.value = request;
    modal.title.value = "Cancel Request";
    modal.type.value = "Cancel";
    modal.icon.value = "fa-solid fa-xmark-circle";
    modal.openModal();
}

const cancelling = ref(false);

function confirmCancel() {
    if (!selectedRequest.value) return;

    cancelling.value = true;
    router.delete(route("admin.design.cancel", selectedRequest.value.id), {
        preserveScroll: true,
        onFinish: () => {
            cancelling.value = false;
            closeModal();
        },
    });
}

function closeModal() {
    selectedRequest.value = null;
    modal.closeModal();
}

function formatDate(value: string) {
    return new Date(value).toLocaleDateString("en-PH", {
        year: "numeric",
        month: "short",
        day: "numeric",
    });
}

// --- Payment review modal state ---
const paymentActionProcessing = ref(false);

function viewPayment(request: DesignRequest) {
    selectedRequest.value = request;

    modal.title.value = "Review Payment";
    modal.type.value = "Payment";
    modal.icon.value = "fa-solid fa-money-bill-wave";
    modal.openModal();
}

function approvePayment() {
    if (!selectedRequest.value) return;

    paymentActionProcessing.value = true;
    router.post(
        route("admin.design.approve-payment", selectedRequest.value.id),
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                paymentActionProcessing.value = false;
                closeModal();
            },
        },
    );
}

function rejectPayment() {
    if (!selectedRequest.value) return;

    paymentActionProcessing.value = true;
    router.post(
        route("admin.design.reject-payment", selectedRequest.value.id),
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                paymentActionProcessing.value = false;
                closeModal();
            },
        },
    );
}

/* ---------------- EDIT ---------------- */

interface ImageStyle {
    backgroundImage: string;
}

const designRequestsStatus = [
    {
        value: "pending_review",
        label: "Pending Review",
    },
    {
        value: "in_discussion",
        label: "In Discussion",
    },
    {
        value: "revision_requested",
        label: "Revision Requested",
    },
    {
        value: "waiting_for_down_payment",
        label: "Waiting for Down Payment",
    },
];

const editImageStyle = ref<ImageStyle>({ backgroundImage: "" });

const editForm = useForm({
    id: null as number | null,
    name: "",
    quantity: "",
    image: null as File | null,
    primary_color: "#14202B",
    secondary_color: "#FFFFFF",
    accent_color: "#2E7D4F",
    status: "pending_review" as DesignRequestStatus,
});

function editRequest(row: DesignRequest) {
    editForm.reset();
    editForm.clearErrors();

    editForm.id = row.id;
    editForm.name = row.template_name;
    editForm.quantity =
        row.estimated_quantity != null
            ? row.estimated_quantity.toString()
            : "";
    editForm.primary_color = row.primary_color;
    editForm.secondary_color = row.secondary_color;
    editForm.accent_color = row.accent_color;
    editForm.status = row.status;

    editImageStyle.value = {
        backgroundImage: `url(${row.template_image_url})`,
    };

    modal.title.value = "Edit Design Request";
    modal.type.value = "Edit";
    modal.icon.value = "fa-solid fa-edit";
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
        .post(route("admin.design.update", editForm.id), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });
}
</script>

<template>
    <Head title="Design Requests" />

    <AdminLayout>
        <div class="bg-white p-6">
            <div
                class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
            >
                <h1 class="text-lg font-semibold text-[#14202B]">
                    Design Requests
                </h1>

                <div class="flex flex-wrap gap-2">
                    <button
                        v-for="filter in statusFilters"
                        :key="filter.value"
                        type="button"
                        class="rounded-full px-3 py-1.5 text-sm font-medium transition-colors"
                        :class="
                            activeStatus === filter.value
                                ? 'bg-[#2E7D4F] text-white'
                                : 'bg-[#14202B]/5 text-[#14202B]/70 hover:bg-[#14202B]/10'
                        "
                        @click="activeStatus = filter.value"
                    >
                        {{ filter.label }}
                    </button>
                </div>
            </div>
            <hr class="mb-4 mt-2" />
            <Table
                :data="filteredByStatus"
                :columns="columns"
                date-key="created_at"
                :empty-state-message="'No design requests yet. Select a template to get started.'"
            >
                <template #template="{ row }">
                    <div class="flex items-center gap-2 text-left">
                        <img
                            :src="row.template_image_url"
                            :alt="row.template_name"
                            class="h-8 w-8 flex-shrink-0 rounded object-contain bg-[#14202B]/5 p-1"
                        />
                        <span class="font-medium text-[#14202B]">{{
                            row.template_name
                        }}</span>
                    </div>
                </template>

                <template #colors="{ row }">
                    <div class="flex items-center justify-center gap-1">
                        <span
                            class="h-4 w-4 rounded-full border border-[#14202B]/15"
                            :style="{ backgroundColor: row.primary_color }"
                        />
                        <span
                            class="h-4 w-4 rounded-full border border-[#14202B]/15"
                            :style="{ backgroundColor: row.secondary_color }"
                        />
                        <span
                            class="h-4 w-4 rounded-full border border-[#14202B]/15"
                            :style="{ backgroundColor: row.accent_color }"
                        />
                    </div>
                </template>

                <template #price="{ row }">
                    <span>
                        ₱
                        {{
                            (
                                row.estimated_quantity * row.template_price
                            ).toFixed(2)
                        }}
                    </span>
                </template>

                <template #status="{ row }">
                    <span
                        class="inline-block rounded-full px-2.5 py-1 text-xs font-medium"
                        :class="statusBadge[row.status].class"
                    >
                        {{ statusBadge[row.status].label }}
                    </span>
                </template>

                <template #date="{ row }">
                    {{ formatDate(row.created_at) }}
                </template>

                <template #actions="{ row }">
                    <div class="flex items-center justify-center gap-1">
                        <button
                            type="button"
                            class="text-xs font-medium bg-blue-600 text-white rounded-md px-2 py-2 transition-colors hover:bg-blue-500"
                            @click="viewRequest(row)"
                        >
                            <font-awesome-icon icon="fa-solid fa-eye" />
                            View
                        </button>
                        <button
                            v-if="
                                row.status !== 'approved' &&
                                row.status !== 'cancelled' &&
                                row.status !== 'waiting_for_down_payment' &&
                                row.status !== 'pending_down_payment_review'
                            "
                            type="button"
                            class="text-xs font-medium bg-gray-600 text-white rounded-md px-2 py-2 transition-colors hover:bg-gray-500"
                            @click="editRequest(row)"
                        >
                            <font-awesome-icon icon="fa-solid fa-edit" />
                            Edit
                        </button>
                        <button
                            v-if="row.status === 'pending_down_payment_review'"
                            type="button"
                            class="text-xs font-medium bg-green-600 text-white rounded-md px-2 py-2 transition-colors hover:bg-green-500"
                            @click="viewPayment(row)"
                        >
                            <font-awesome-icon
                                icon="fa-solid fa-money-bill-wave"
                            />
                            Check Payment
                        </button>
                        <Link
                            v-if="
                                row.status !== 'approved' &&
                                row.status !== 'cancelled'
                            "
                            class="text-xs font-medium bg-orange-600 text-white rounded-md px-2 py-2 transition-colors hover:bg-orange-500"
                            :href="route('admin.messages.index')"
                        >
                            <font-awesome-icon icon="fa-solid fa-message" />
                            Message
                        </Link>
                        <button
                            v-if="
                                row.status !== 'approved' &&
                                row.status !== 'cancelled' &&
                                row.status !== 'revision_requested' &&
                                row.status !== 'waiting_for_down_payment' &&
                                row.status !== 'pending_down_payment_review'
                            "
                            type="button"
                            class="text-xs font-medium bg-red-600 text-white rounded-md px-2 py-2 transition-colors hover:bg-red-500"
                            @click="cancelRequest(row)"
                        >
                            <font-awesome-icon
                                icon="fa-solid fa-xmark-circle"
                            />
                            Cancel
                        </button>
                    </div>
                </template>
            </Table>
        </div>

        <!-- Details Modal -->
        <Modal
            :show="modal.type.value === 'View'"
            @close="closeModal"
            :maxWidth="'5xl'"
        >
            <div
                class="overflow-y-auto max-h-[90vh] px-4 pt-5 pb-4 sm:p-6"
                v-if="selectedRequest"
            >
                <div class="flex items-center justify-between gap-2">
                    <h2
                        class="text-base sm:text-lg font-medium text-gray-900 truncate"
                    >
                        <font-awesome-icon :icon="modal.icon.value" />
                        {{ modal.title.value }}
                    </h2>
                    <SecondaryButton @click="closeModal" class="flex-shrink-0">
                        <font-awesome-icon icon="fa-solid fa-xmark" />
                    </SecondaryButton>
                </div>
                <hr class="my-3" />
                <div
                    class="mt-4 flex flex-col gap-4 sm:flex-row sm:gap-3 justify-between"
                >
                    <!-- Previous Design -->
                    <div
                        class="flex flex-col gap-3 border border-[#14202B]/10 px-3 py-3 rounded w-full"
                    >
                        <p class="text-sm font-bold text-[#14202B] text-center">
                            Previous Design
                        </p>
                        <div class="flex w-full flex-col items-center gap-2">
                            <div
                                class="flex aspect-square w-full items-center justify-center overflow-hidden rounded-lg border border-[#14202B]/10 bg-[#14202B]/5"
                            >
                                <img
                                    v-if="
                                        selectedRequest.original_template_image
                                    "
                                    :src="
                                        selectedRequest.original_template_image
                                    "
                                    :alt="selectedRequest.template_name"
                                    class="h-full w-full object-contain p-4"
                                />
                                <span v-else class="text-sm text-[#14202B]/40">
                                    Original template no longer available.
                                </span>
                            </div>
                        </div>
                    </div>
                    <!-- Current Design -->
                    <div
                        class="flex flex-col gap-3 border border-[#14202B]/10 px-3 py-3 rounded w-full"
                    >
                        <p class="text-sm font-bold text-[#14202B] text-center">
                            Current Design
                        </p>
                        <div class="flex w-full flex-col items-center gap-2">
                            <div
                                class="flex aspect-square w-full items-center justify-center overflow-hidden rounded-lg border border-[#14202B]/10 bg-[#14202B]/5"
                            >
                                <img
                                    :src="selectedRequest.template_image_url"
                                    :alt="selectedRequest.template_name"
                                    class="h-full w-full object-contain p-4"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Modal>

        <!-- Edit Modal -->
        <Modal
            :show="modal.type.value === 'Edit'"
            @close="closeModal"
            :maxWidth="'5xl'"
        >
            <form @submit.prevent="submitEdit" class="px-4 pt-5 pb-4 sm:p-6">
                <h2 class="text-lg font-medium text-gray-900">
                    <font-awesome-icon icon="fa-solid fa-edit" />
                    {{ modal.title.value }}
                </h2>
                <hr class="my-2" />
                <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-gray-700"
                            >Jersey Image</label
                        >
                        <ImageUpload
                            class="mt-1"
                            :image="editImageStyle"
                            @imageFile="onEditImageChange"
                        />
                        <InputError
                            :message="editForm.errors.image"
                            class="mt-2"
                        />
                    </div>

                    <div class="flex flex-col gap-4">
                        <div>
                            <InputLabel for="name" value="Name" />
                            <TextInput
                                v-model="editForm.name"
                                class="mt-1 block w-full"
                                id="name"
                                required
                            />
                            <InputError
                                :message="editForm.errors.name"
                                class="mt-2"
                            />
                        </div>

                        <div>
                            <InputLabel for="quantity" value="Quantity" />
                            <TextInput
                                v-model="editForm.quantity"
                                class="mt-1 block w-full"
                                id="quantity"
                                required
                            />
                            <InputError
                                :message="editForm.errors.quantity"
                                class="mt-2"
                            />
                        </div>
                        <div>
                            <InputLabel for="status" value="Status" />
                            <SelectInput
                                v-model="editForm.status"
                                :options="designRequestsStatus"
                                class="mt-1 block w-full"
                                id="status"
                                required
                            />
                            <InputError
                                :message="editForm.errors.status"
                                class="mt-2"
                            />
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                            <div>
                                <InputLabel
                                    for="primary"
                                    value="Primary Color"
                                />
                                <div class="mt-1 flex items-center gap-1">
                                    <input
                                        type="color"
                                        v-model="editForm.primary_color"
                                        class="h-10 w-12 rounded border border-gray-300"
                                        id="primary"
                                    />
                                    <TextInput
                                        v-model="editForm.primary_color"
                                        class="block w-full"
                                    />
                                </div>
                            </div>

                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700"
                                    >Secondary Color</label
                                >
                                <div class="mt-1 flex items-center gap-1">
                                    <input
                                        type="color"
                                        v-model="editForm.secondary_color"
                                        class="h-10 w-12 rounded border border-gray-300"
                                    />
                                    <TextInput
                                        v-model="editForm.secondary_color"
                                        class="block w-full"
                                    />
                                </div>
                            </div>

                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700"
                                    >Accent Color</label
                                >
                                <div class="mt-1 flex items-center gap-1">
                                    <input
                                        type="color"
                                        v-model="editForm.accent_color"
                                        class="h-10 w-12 rounded border border-gray-300"
                                    />
                                    <TextInput
                                        v-model="editForm.accent_color"
                                        class="block w-full"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <hr class="mt-4" />
                <div
                    class="mt-2 flex flex-col-reverse gap-2 sm:flex-row sm:justify-between"
                >
                    <SecondaryButton
                        type="button"
                        class="flex items-center justify-center"
                        @click="closeModal()"
                    >
                        Cancel
                    </SecondaryButton>

                    <PrimaryButton
                        type="submit"
                        class="flex items-center justify-center gap-1"
                        :disabled="cancelling"
                        :class="{ 'opacity-25': editForm.processing }"
                    >
                        <div class="text-sm" v-if="editForm.processing">
                            <font-awesome-icon
                                icon="fa-solid fa-spinner"
                                spin
                            />
                        </div>
                        Save
                        <font-awesome-icon icon="fa-solid fa-circle-down" />
                    </PrimaryButton>
                </div>
            </form>
        </Modal>
        <!-- Cancel Modal -->
        <Modal
            :show="modal.type.value === 'Cancel'"
            @close="closeModal()"
            :maxWidth="'sm'"
        >
            <div class="px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <h2 class="text-lg font-medium text-gray-900">
                    <font-awesome-icon :icon="modal.icon.value" />
                    {{ modal.title.value }}
                </h2>

                <div class="mt-6">
                    <p class="text-gray-600">
                        Are you sure you want to cancel this request?
                    </p>
                </div>

                <div class="mt-6 flex justify-between">
                    <SecondaryButton
                        class="flex items-center"
                        :disabled="cancelling"
                        @click="closeModal()"
                    >
                        Close
                    </SecondaryButton>

                    <DangerButton
                        class="flex items-center gap-1"
                        :disabled="cancelling"
                        @click="confirmCancel()"
                        :class="{ 'opacity-25': cancelling }"
                    >
                        <div class="text-sm" v-if="cancelling">
                            <font-awesome-icon
                                icon="fa-solid fa-spinner"
                                spin
                            />
                        </div>
                        Confirm
                        <font-awesome-icon icon="fa-solid fa-thumbs-up" />
                    </DangerButton>
                </div>
            </div>
        </Modal>
        <!-- View Payment Modal -->
        <Modal
            :show="modal.type.value === 'Payment'"
            @close="closeModal"
            :maxWidth="'md'"
        >
            <div
                class="overflow-y-auto px-4 pt-5 pb-4 sm:p-6"
                v-if="selectedRequest"
            >
                <div class="flex items-center justify-between gap-2">
                    <h2 class="text-base sm:text-lg font-medium text-gray-900">
                        <font-awesome-icon :icon="modal.icon.value" />
                        {{ modal.title.value }}
                    </h2>
                    <SecondaryButton type="button" @click="closeModal">
                        <font-awesome-icon icon="fa-solid fa-xmark" />
                    </SecondaryButton>
                </div>
                <hr class="my-3" />

                <div class="flex flex-col gap-4">
                    <!-- GCash number -->
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-ink">
                            Customer's GCash Number:
                        </span>
                        <p class="text-md font-medium text-ink italic">
                            {{ selectedRequest.gcash_number ?? "—" }}
                        </p>
                    </div>

                    <!-- Reference number -->
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-ink">
                            Transaction Reference Number:
                        </span>
                        <p class="text-md font-medium text-ink italic">
                            {{ selectedRequest.reference_number ?? "—" }}
                        </p>
                    </div>

                    <!-- Proof upload -->
                    <div>
                        <label
                            class="block text-sm font-medium text-[#14202B] mb-1"
                        >
                            Uploaded Transaction Screenshot:
                        </label>

                        <div
                            class="flex aspect-square w-full items-center justify-center overflow-hidden rounded-lg border border-[#14202B]/10 bg-ink/5"
                        >
                            <img
                                v-if="selectedRequest.proof_image_url"
                                :src="selectedRequest.proof_image_url"
                                alt="Payment proof"
                                class="h-full w-full object-contain p-4"
                            />
                            <span v-else class="text-sm text-[#14202B]/40">
                                No screenshot uploaded.
                            </span>
                        </div>
                    </div>

                    <div class="mt-2 flex justify-between gap-2">
                        <DangerButton
                            class="flex flex-1 items-center justify-center gap-1"
                            :disabled="paymentActionProcessing"
                            @click="rejectPayment()"
                            :class="{ 'opacity-25': paymentActionProcessing }"
                        >
                            Reject
                            <font-awesome-icon icon="fa-solid fa-xmark" />
                        </DangerButton>
                        <PrimaryButton
                            class="flex flex-1 items-center justify-center gap-1"
                            :disabled="paymentActionProcessing"
                            @click="approvePayment()"
                            :class="{ 'opacity-25': paymentActionProcessing }"
                        >
                            Approve
                            <font-awesome-icon icon="fa-solid fa-check" />
                        </PrimaryButton>
                    </div>
                </div>
            </div>
        </Modal>
    </AdminLayout>
</template>
