<script setup lang="ts">
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import Table from "@/Components/Table.vue";
import Modal from "@/Components/Modal.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import DangerButton from "@/Components/DangerButton.vue";
import type { DesignRequest, DesignRequestStatus } from "@/types/jersey";
import { useModal } from "@/Composables/useModal";
import { Head, Link, useForm, router, usePoll } from "@inertiajs/vue3";
import { ref, computed } from "vue";

interface GcashSetting {
    account_name: string;
    account_number: string;
    instructions: string | null;
    qr_image_url: string | null;
}

const props = defineProps<{
    data?: DesignRequest[];
    gcash: GcashSetting;
}>();

usePoll(5000, {
    only: ["data", "gcash"],
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

const gcashQrImage = "https://placehold.co/300x300?text=GCash+QR";

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
    router.delete(route("client.design.cancel", selectedRequest.value.id), {
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

// --- Payment modal state ---
const paymentPreview = ref<string | null>(null);

const paymentForm = useForm({
    request_id: null as number | null,
    gcash_number: "",
    reference_number: "",
    proof_image: null as File | null,
});

function payRequest(request: DesignRequest) {
    selectedRequest.value = request;
    paymentForm.reset();
    paymentForm.request_id = request.id;
    paymentPreview.value = null;

    modal.title.value = "GCash Payment";
    modal.type.value = "Payment";
    modal.icon.value = "fa-solid fa-credit-card";
    modal.openModal();
}

function handleProofUpload(e: Event) {
    const target = e.target as HTMLInputElement;
    const file = target.files?.[0];
    if (!file) return;

    paymentForm.proof_image = file;
    paymentPreview.value = URL.createObjectURL(file);
}

function removeProofImage() {
    paymentForm.proof_image = null;
    paymentPreview.value = null;
}

function submitPayment() {
    if (!selectedRequest.value) return;

    paymentForm.post(route("client.design.pay", selectedRequest.value.id), {
        forceFormData: true,
        onSuccess: () => closeModal(),
    });
}
</script>

<template>
    <Head title="Design Requests" />

    <AuthenticatedLayout>
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
                        <Link
                            v-if="
                                row.status !== 'approved' &&
                                row.status !== 'cancelled'
                            "
                            class="text-xs font-medium bg-orange-600 text-white rounded-md px-2 py-2 transition-colors hover:bg-orange-500"
                            :href="route('client.chat.index')"
                        >
                            <font-awesome-icon icon="fa-solid fa-message" />
                            Message
                        </Link>
                        <button
                            v-if="row.status === 'waiting_for_down_payment'"
                            type="button"
                            class="text-xs font-medium bg-green-600 text-white rounded-md px-2 py-2 transition-colors hover:bg-green-500"
                            @click="payRequest(row)"
                        >
                            <font-awesome-icon icon="fa-solid fa-credit-card" />
                            Pay
                        </button>
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
                    >
                        Confirm
                        <font-awesome-icon icon="fa-solid fa-thumbs-up" />
                    </DangerButton>
                </div>
            </div>
        </Modal>
        <!-- Payment Modal -->
        <Modal
            :show="modal.type.value === 'Payment'"
            @close="closeModal"
            :maxWidth="'md'"
        >
            <div class="px-4 pt-5 pb-4 sm:p-6" v-if="selectedRequest">
                <div class="flex items-center justify-between gap-2">
                    <h2 class="text-base sm:text-lg font-medium text-gray-900">
                        <font-awesome-icon :icon="modal.icon.value" />
                        {{ modal.title.value }}
                    </h2>
                </div>
                <hr class="my-3" />

                <!-- Order summary -->
                <div class="rounded-lg bg-[#14202B]/5 p-3 text-sm mb-4">
                    <div class="flex justify-between">
                        <span class="text-[#14202B]/70">Design</span>
                        <span class="font-medium text-[#14202B]">{{
                            selectedRequest.template_name
                        }}</span>
                    </div>
                    <div class="flex justify-between mt-1">
                        <span class="text-[#14202B]/70">Amount Due</span>
                        <span class="font-bold text-[#2E7D4F]">
                            ₱
                            {{
                                (
                                    selectedRequest.estimated_quantity *
                                    selectedRequest.template_price
                                ).toFixed(2)
                            }}
                        </span>
                    </div>
                </div>

                <!-- GCash instructions -->
                <div
                    class="rounded-lg border border-[#2E7D4F]/20 bg-[#2E7D4F]/5 p-3 mb-4"
                >
                    <p class="text-sm font-semibold text-[#14202B] mb-3">
                        <font-awesome-icon icon="fa-solid fa-circle-info" />
                        Send payment via GCash
                    </p>

                    <div
                        class="flex flex-col items-center gap-4 sm:flex-row sm:items-start"
                    >
                        <!-- QR code -->
                        <div
                            class="flex flex-shrink-0 flex-col items-center gap-1.5"
                        >
                            <div
                                class="flex h-32 w-32 items-center justify-center overflow-hidden rounded-lg border border-[#14202B]/10 bg-white p-1.5"
                            >
                                <img
                                    :src="gcash.qr_image_url ?? gcashQrImage"
                                    alt="GCash QR code"
                                    class="h-full w-full object-contain"
                                />
                            </div>
                            <span
                                class="text-[10px] font-medium uppercase tracking-wide text-[#14202B]/40"
                            >
                                Scan to pay
                            </span>
                        </div>

                        <!-- Account details -->
                        <div class="flex-1 text-sm">
                            <p
                                class="text-[10px] font-medium uppercase tracking-wide text-[#14202B]/40"
                            >
                                Account Name
                            </p>
                            <p class="text-[#14202B] font-medium">{{ gcash.account_name }}</p>

                            <p
                                class="mt-2 text-[10px] font-medium uppercase tracking-wide text-[#14202B]/40"
                            >
                                Account Number
                            </p>
                            <p class="text-[#14202B] font-medium">
                                {{ gcash.account_number }}
                            </p>

                            <p class="mt-2 text-xs text-[#14202B]/60">
                                After sending, fill out the details below and
                                upload your receipt screenshot.
                            </p>
                        </div>
                    </div>
                </div>

                <form
                    @submit.prevent="submitPayment"
                    class="flex flex-col gap-4"
                >
                    <!-- GCash number -->
                    <div>
                        <label
                            for="gcash_number"
                            class="block text-sm font-medium text-[#14202B] mb-1"
                        >
                            Your GCash Number
                        </label>
                        <input
                            id="gcash_number"
                            v-model="paymentForm.gcash_number"
                            type="text"
                            name="gcash_number"
                            inputmode="numeric"
                            placeholder="09XX XXX XXXX"
                            class="w-full rounded-md border border-[#14202B]/15 px-3 py-2 text-sm focus:border-[#2E7D4F] focus:ring-[#2E7D4F]"
                            required
                        />
                        <p
                            v-if="paymentForm.errors.gcash_number"
                            class="text-xs text-red-600 mt-1"
                        >
                            {{ paymentForm.errors.gcash_number }}
                        </p>
                    </div>

                    <!-- Reference number -->
                    <div>
                        <label
                            for="reference_number"
                            class="block text-sm font-medium text-[#14202B] mb-1"
                        >
                            Transaction Reference Number
                        </label>
                        <input
                            id="reference_number"
                            v-model="paymentForm.reference_number"
                            type="text"
                            name="reference_number"
                            placeholder="e.g. 1234567890123"
                            class="w-full rounded-md border border-[#14202B]/15 px-3 py-2 text-sm focus:border-[#2E7D4F] focus:ring-[#2E7D4F]"
                            required
                        />
                        <p
                            v-if="paymentForm.errors.reference_number"
                            class="text-xs text-red-600 mt-1"
                        >
                            {{ paymentForm.errors.reference_number }}
                        </p>
                    </div>

                    <!-- Proof upload -->
                    <div>
                        <label
                            class="block text-sm font-medium text-[#14202B] mb-1"
                        >
                            Upload Transaction Screenshot
                        </label>

                        <div v-if="!paymentPreview">
                            <label
                                class="flex flex-col items-center justify-center gap-2 rounded-lg border-2 border-dashed border-[#14202B]/20 py-6 cursor-pointer hover:border-[#2E7D4F] transition-colors"
                            >
                                <font-awesome-icon
                                    icon="fa-solid fa-cloud-arrow-up"
                                    class="text-2xl text-[#14202B]/40"
                                />
                                <span class="text-xs text-[#14202B]/60"
                                    >Click to upload (JPG, PNG)</span
                                >
                                <input
                                    type="file"
                                    accept="image/*"
                                    class="hidden"
                                    @change="handleProofUpload"
                                />
                            </label>
                        </div>

                        <div v-else class="relative w-full">
                            <img
                                :src="paymentPreview"
                                alt="Payment proof preview"
                                class="w-full max-h-64 object-contain rounded-lg border border-[#14202B]/10 bg-[#14202B]/5"
                            />
                            <button
                                type="button"
                                @click="removeProofImage"
                                class="absolute top-2 right-2 bg-red-600 text-white rounded-full h-7 w-7 flex items-center justify-center hover:bg-red-500"
                            >
                                <font-awesome-icon icon="fa-solid fa-xmark" />
                            </button>
                        </div>
                        <p
                            v-if="paymentForm.errors.proof_image"
                            class="text-xs text-red-600 mt-1"
                        >
                            {{ paymentForm.errors.proof_image }}
                        </p>
                    </div>

                    <div class="mt-2 flex justify-between">
                        <SecondaryButton type="button" @click="closeModal">
                            Cancel
                        </SecondaryButton>

                        <button
                            type="submit"
                            :disabled="
                                paymentForm.processing ||
                                !paymentForm.proof_image
                            "
                            class="rounded-md bg-[#2E7D4F] px-4 py-2 text-sm font-medium text-white hover:bg-[#2E7D4F]/90 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
                        >
                            <font-awesome-icon icon="fa-solid fa-paper-plane" />
                            {{
                                paymentForm.processing
                                    ? "Submitting..."
                                    : "Submit Payment"
                            }}
                        </button>
                    </div>
                </form>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
