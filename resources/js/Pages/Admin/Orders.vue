<script setup lang="ts">
import AdminLayout from "@/Layouts/AdminLayout.vue";
import Table from "@/Components/Table.vue";
import Modal from "@/Components/Modal.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import type { Order, OrderStatus, CourierReceipt } from "@/types/orders.ts";
import { activeCouriers, getCourierById } from "@/types/couriers";
import { formatCurrency } from "@/Composables/shipping";
import { useModal } from "@/Composables/useModal";
import { Head, Link, router, useForm, usePoll } from "@inertiajs/vue3";
import { ref, computed } from "vue";

const props = defineProps<{
    orders?: Order[];
}>();

usePoll(5000, {
    only: ["orders"],
});

const orders = computed<Order[]>(() => props.orders ?? []);

// ---------------------------------------------------------------------------
// Stats (top summary cards)
// ---------------------------------------------------------------------------
/** Returns null while the shipping fee is still unknown (pre-shipment). */
function orderTotal(order: Order): number | null {
    if (order.shipping_fee === null) return null;
    return order.quantity * order.unit_price + order.shipping_fee;
}

// ---------------------------------------------------------------------------
// Status filters + badges
// ---------------------------------------------------------------------------
const statusFilters: { label: string; value: OrderStatus | "All" }[] = [
    { label: "All", value: "All" },
    { label: "Processing", value: "processing" },
    { label: "In Production", value: "in_production" },
    { label: "Ready for Delivery", value: "ready_for_delivery" },
    { label: "Shipped", value: "shipped" },
    { label: "Delivered", value: "delivered" },
    { label: "Completed", value: "completed" },
];

const activeStatus = ref<OrderStatus | "All">("All");
const searchQuery = ref("");

const statusBadge: Record<OrderStatus, { label: string; class: string }> = {
    processing: { label: "Processing", class: "bg-yellow-100 text-yellow-700" },
    in_production: {
        label: "In Production",
        class: "bg-blue-100 text-blue-700",
    },
    ready_for_delivery: {
        label: "Ready for Delivery",
        class: "bg-purple-100 text-purple-700",
    },
    shipped: { label: "Shipped", class: "bg-indigo-100 text-indigo-700" },
    delivered: { label: "Delivered", class: "bg-teal-100 text-teal-700" },
    completed: { label: "Completed", class: "bg-green-100 text-green-700" },
};

// Statuses can only move forward, in this order.
const statusFlow: OrderStatus[] = [
    "processing",
    "in_production",
    "ready_for_delivery",
    "shipped",
    "delivered",
    "completed",
];

function nextAllowedStatuses(current: OrderStatus): OrderStatus[] {
    const idx = statusFlow.indexOf(current);
    if (idx === -1) return [];
    return statusFlow.slice(idx + 1);
}

/** Couriers available to pick from in the status-update form. */
const courierOptions = computed(() => activeCouriers());

/** Convenience accessor: the Courier record tied to an order's receipt. */
function courierFor(order: Order) {
    if (!order.courier_receipt) return undefined;
    return getCourierById(order.courier_receipt.courier_id);
}

const columns = [
    { key: "template_name", label: "Order", slot: "template" },
    { key: "customer", label: "Customer", slot: "customer" },
    { key: "quantity", label: "Qty" },
    { key: "destination", label: "Destination", slot: "destination" },
    { key: "total", label: "Total", slot: "total" },
    { key: "status", label: "Status", slot: "status" },
    { key: "tracking", label: "Tracking", slot: "tracking" },
    { key: "created_at", label: "Placed", slot: "date" },
    { key: "actions", label: "Action", slot: "actions" },
];

const filteredOrders = computed<Order[]>(() => {
    let list = orders.value;

    if (activeStatus.value !== "All") {
        list = list.filter((o) => o.status === activeStatus.value);
    }

    const q = searchQuery.value.trim().toLowerCase();
    if (q) {
        list = list.filter(
            (o) =>
                o.order_number.toLowerCase().includes(q) ||
                o.team_name.toLowerCase().includes(q) ||
                o.address.recipient_name.toLowerCase().includes(q) ||
                (o.courier_receipt?.transaction_number ?? "")
                    .toLowerCase()
                    .includes(q),
        );
    }

    return list;
});

// ---------------------------------------------------------------------------
// Modal state
// ---------------------------------------------------------------------------
const modal = useModal();
const selectedOrder = ref<Order | null>(null);

function viewOrder(order: Order) {
    selectedOrder.value = order;
    modal.title.value = "Order Details";
    modal.type.value = "View";
    modal.icon.value = "fa-solid fa-shirt";
    modal.openModal();
}

// --- Advance status / attach courier receipt ---
const statusForm = useForm({
    status: null as OrderStatus | null,
    courier_id: null as number | null,
    transaction_number: "",
    shipping_fee: null as number | null,
    remarks: "",
});

const needsCourierReceipt = computed(() => statusForm.status === "shipped");

function openStatusModal(order: Order) {
    selectedOrder.value = order;
    statusForm.clearErrors();
    statusForm.status = nextAllowedStatuses(order.status)[0] ?? null;
    statusForm.courier_id = order.courier_receipt?.courier_id ?? null;
    statusForm.transaction_number =
        order.courier_receipt?.transaction_number ?? "";
    statusForm.shipping_fee = order.courier_receipt?.shipping_fee ?? null;
    statusForm.remarks = "";
    modal.title.value = "Update Order Status";
    modal.type.value = "UpdateStatus";
    modal.icon.value = "fa-solid fa-truck";
    modal.openModal();
}

function submitStatusUpdate() {
    if (!selectedOrder.value || !statusForm.status) return;
    statusForm.patch(
        route("admin.orders.update-status", selectedOrder.value.id),
        { onSuccess: () => closeModal() },
    );
}

const isFormValid = computed(() => {
    if (!statusForm.status) return false;
    if (needsCourierReceipt.value) {
        return (
            statusForm.courier_id !== null &&
            statusForm.transaction_number.trim() !== "" &&
            statusForm.shipping_fee !== null &&
            statusForm.shipping_fee >= 0
        );
    }
    return true;
});

function closeModal() {
    selectedOrder.value = null;
    statusForm.clearErrors();
    modal.closeModal();
}

function formatDate(value: string) {
    return new Date(value).toLocaleDateString("en-PH", {
        year: "numeric",
        month: "short",
        day: "numeric",
    });
}
</script>

<template>
    <Head title="Orders" />

    <AdminLayout>
        <div class="mx-auto">
            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div class="p-4">
                    <div
                        class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between"
                    >
                        <h1 class="text-lg font-semibold text-[#14202B]">
                            <font-awesome-icon
                                icon="fa-solid fa-cart-shopping"
                            />
                            Orders
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
                    <hr class="mb-4 mt-3" />

                    <Table
                        :data="filteredOrders"
                        :columns="columns"
                        date-key="created_at"
                        :empty-state-message="'No orders match your filters.'"
                    >
                        <template #template="{ row }">
                            <div class="flex items-center gap-2 text-left">
                                <img
                                    :src="row.template_image"
                                    :alt="row.template_name"
                                    class="h-8 w-8 flex-shrink-0 rounded object-contain bg-[#14202B]/5 p-1"
                                />
                                <div class="flex flex-col">
                                    <span class="font-medium text-[#14202B]">{{
                                        row.team_name
                                    }}</span>
                                    <span class="text-xs text-[#14202B]/50">{{
                                        row.order_number
                                    }}</span>
                                </div>
                            </div>
                        </template>

                        <template #customer="{ row }">
                            <div class="flex flex-col text-xs">
                                <span class="font-medium text-[#14202B]">{{
                                    row.address.recipient_name
                                }}</span>
                                <span class="text-[#14202B]/50">{{
                                    row.address.contact_number
                                }}</span>
                            </div>
                        </template>

                        <template #destination="{ row }">
                            <div class="flex flex-col items-center text-xs">
                                <span class="font-medium text-[#14202B]">{{
                                    row.address.province
                                }}</span>
                                <span class="text-[#14202B]/50">{{
                                    row.address.city
                                }}</span>
                            </div>
                        </template>

                        <template #total="{ row }">
                            <div
                                v-if="orderTotal(row) !== null"
                                class="flex flex-col items-center text-xs"
                            >
                                <span class="font-semibold text-[#14202B]">{{
                                    formatCurrency(orderTotal(row)!)
                                }}</span>
                                <span class="text-[#14202B]/40">
                                    incl.
                                    {{ formatCurrency(row.shipping_fee!) }}
                                    shipping
                                </span>
                            </div>
                            <span v-else class="text-xs text-[#14202B]/40">
                                Pending shipping fee
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

                        <template #tracking="{ row }">
                            <div
                                v-if="row.courier_receipt"
                                class="flex flex-col text-xs"
                            >
                                <span class="font-medium text-blue-600">{{
                                    row.courier_receipt.transaction_number
                                }}</span>
                                <span class="text-[#14202B]/40">{{
                                    courierFor(row)?.name ?? "Unknown courier"
                                }}</span>
                            </div>
                            <span v-else class="text-xs text-[#14202B]/40"
                                >—</span
                            >
                        </template>

                        <template #date="{ row }">
                            {{ formatDate(row.created_at) }}
                        </template>

                        <template #actions="{ row }">
                            <div class="flex items-center justify-center gap-1">
                                <button
                                    type="button"
                                    class="text-xs font-medium bg-blue-600 text-white rounded-md px-2 py-2 transition-colors hover:bg-blue-500"
                                    @click="viewOrder(row)"
                                >
                                    <font-awesome-icon icon="fa-solid fa-eye" />
                                    View
                                </button>

                                <button
                                    v-if="row.status !== 'completed'"
                                    type="button"
                                    class="text-xs font-medium bg-gray-600 text-white rounded-md px-2 py-2 transition-colors hover:bg-gray-500"
                                    @click="openStatusModal(row)"
                                >
                                    <font-awesome-icon
                                        icon="fa-solid fa-edit"
                                    />
                                    Update
                                </button>

                                <Link
                                    v-if="row.status !== 'completed'"
                                    class="text-xs font-medium bg-orange-600 text-white rounded-md px-2 py-2 transition-colors hover:bg-orange-500"
                                    :href="route('admin.messages.index')"
                                >
                                    <font-awesome-icon
                                        icon="fa-solid fa-message"
                                    />
                                    Message
                                </Link>

                                <a
                                    class="text-xs font-medium bg-ink text-white rounded-md px-2 py-2 transition-colors hover:bg-ink/90"
                                    v-if="
                                        row.courier_receipt &&
                                        row.status === 'shipped' &&
                                        courierFor(row)
                                    "
                                    :href="courierFor(row)!.site"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                >
                                    <font-awesome-icon
                                        icon="fa-solid fa-truck"
                                    />
                                    Track
                                </a>
                            </div>
                        </template>
                    </Table>
                </div>
            </div>
        </div>

        <!-- View Order Modal -->
        <Modal
            :show="modal.type.value === 'View'"
            @close="closeModal"
            :maxWidth="'5xl'"
        >
            <div
                class="overflow-y-auto max-h-[90vh] px-4 pt-5 pb-4 sm:p-6"
                v-if="selectedOrder"
            >
                <div class="flex items-center justify-between gap-2">
                    <h2
                        class="text-base sm:text-lg font-medium text-gray-900 truncate"
                    >
                        <font-awesome-icon :icon="modal.icon.value" />
                        {{ modal.title.value }} —
                        {{ selectedOrder.order_number }}
                    </h2>
                    <SecondaryButton @click="closeModal" class="flex-shrink-0">
                        <font-awesome-icon icon="fa-solid fa-xmark" />
                    </SecondaryButton>
                </div>
                <hr class="my-3" />

                <div class="flex flex-col gap-4 sm:flex-row">
                    <!-- Jersey preview -->
                    <div
                        class="flex flex-col gap-3 border border-[#14202B]/10 px-3 py-3 rounded w-full sm:w-1/3"
                    >
                        <p class="text-sm font-bold text-[#14202B] text-center">
                            {{ selectedOrder.template_name }}
                        </p>
                        <div
                            class="flex aspect-square w-full items-center justify-center overflow-hidden rounded-lg border border-[#14202B]/10 bg-[#14202B]/5"
                        >
                            <img
                                :src="selectedOrder.template_image"
                                :alt="selectedOrder.template_name"
                                class="h-full w-full object-contain p-4"
                            />
                        </div>
                        <div class="flex items-center justify-center gap-1">
                            <span
                                class="h-4 w-4 rounded-full border border-[#14202B]/15"
                                :style="{
                                    backgroundColor:
                                        selectedOrder.primary_color,
                                }"
                            />
                            <span
                                class="h-4 w-4 rounded-full border border-[#14202B]/15"
                                :style="{
                                    backgroundColor:
                                        selectedOrder.secondary_color,
                                }"
                            />
                            <span
                                class="h-4 w-4 rounded-full border border-[#14202B]/15"
                                :style="{
                                    backgroundColor: selectedOrder.accent_color,
                                }"
                            />
                        </div>
                        <p class="text-xs text-center text-[#14202B]/50">
                            {{ selectedOrder.font_style }} • Qty
                            {{ selectedOrder.quantity }}
                        </p>
                        <Link
                            :href="route('admin.design.index')"
                            class="text-center text-xs text-blue-600 hover:underline"
                        >
                            View original design request
                        </Link>
                    </div>

                    <!-- Delivery + cost -->
                    <div class="flex flex-col gap-4 w-full sm:w-2/3">
                        <div class="border border-[#14202B]/10 rounded p-3">
                            <p class="text-sm font-bold text-[#14202B] mb-2">
                                <font-awesome-icon
                                    icon="fa-solid fa-location-dot"
                                />
                                Delivery Address
                            </p>
                            <p
                                class="text-sm text-[#14202B]/80"
                                v-if="
                                    selectedOrder.address.recipient_name &&
                                    selectedOrder.address.contact_number
                                "
                            >
                                {{ selectedOrder.address.recipient_name }} •
                                {{ selectedOrder.address.contact_number }}
                            </p>
                            <p
                                class="text-sm text-[#14202B]/80"
                                v-if="
                                    selectedOrder.address.line1 &&
                                    selectedOrder.address.province
                                "
                            >
                                {{ selectedOrder.address.line1
                                }}<span v-if="selectedOrder.address.barangay"
                                    >,
                                    {{ selectedOrder.address.barangay }}</span
                                >, {{ selectedOrder.address.city }},
                                {{ selectedOrder.address.province }}
                                {{ selectedOrder.address.postal_code }}
                            </p>
                            <p class="text-xs text-[#14202B]/40 mt-1">
                                Address is managed by the customer. Ask them to
                                update it from their account.
                            </p>
                        </div>

                        <div class="border border-[#14202B]/10 rounded p-3">
                            <p class="text-sm font-bold text-[#14202B] mb-2">
                                <font-awesome-icon icon="fa-solid fa-receipt" />
                                Cost Breakdown
                            </p>
                            <div
                                class="flex justify-between text-sm text-[#14202B]/80"
                            >
                                <span
                                    >{{ selectedOrder.quantity }} ×
                                    {{
                                        formatCurrency(selectedOrder.unit_price)
                                    }}</span
                                >
                                <span>{{
                                    formatCurrency(
                                        selectedOrder.quantity *
                                            selectedOrder.unit_price,
                                    )
                                }}</span>
                            </div>
                            <div
                                class="flex justify-between text-sm text-[#14202B]/80"
                            >
                                <span>Shipping fee</span>
                                <span>{{
                                    selectedOrder.shipping_fee !== null
                                        ? formatCurrency(
                                              selectedOrder.shipping_fee,
                                          )
                                        : "To be determined"
                                }}</span>
                            </div>
                            <hr class="my-2" />
                            <div
                                class="flex justify-between text-sm font-semibold text-[#14202B]"
                            >
                                <span>Total</span>
                                <span>{{
                                    orderTotal(selectedOrder) !== null
                                        ? formatCurrency(
                                              orderTotal(selectedOrder)!,
                                          )
                                        : "Pending shipping fee"
                                }}</span>
                            </div>
                        </div>

                        <div class="border border-[#14202B]/10 rounded p-3">
                            <p class="text-sm font-bold text-[#14202B] mb-2">
                                <font-awesome-icon icon="fa-solid fa-truck" />
                                Shipping Status
                            </p>
                            <span
                                class="inline-block rounded-full px-2.5 py-1 text-xs font-medium"
                                :class="statusBadge[selectedOrder.status].class"
                            >
                                {{ statusBadge[selectedOrder.status].label }}
                            </span>

                            <div
                                v-if="selectedOrder.courier_receipt"
                                class="mt-2 text-sm text-[#14202B]/80"
                            >
                                <p>
                                    Courier:
                                    {{
                                        courierFor(selectedOrder)?.name ??
                                        "Unknown courier"
                                    }}
                                </p>
                                <p>
                                    Transaction #:
                                    {{
                                        selectedOrder.courier_receipt
                                            .transaction_number
                                    }}
                                </p>
                                <p>
                                    Shipping fee (from receipt):
                                    {{
                                        formatCurrency(
                                            selectedOrder.courier_receipt
                                                .shipping_fee,
                                        )
                                    }}
                                </p>
                                <p>
                                    Shipped:
                                    {{
                                        formatDate(
                                            selectedOrder.courier_receipt
                                                .date_shipped,
                                        )
                                    }}
                                </p>
                                <p v-if="selectedOrder.courier_receipt.remarks">
                                    Remarks:
                                    {{ selectedOrder.courier_receipt.remarks }}
                                </p>
                                <a
                                    v-if="courierFor(selectedOrder)"
                                    :href="courierFor(selectedOrder)!.site"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="inline-flex items-center gap-1 mt-1 text-blue-600 hover:underline"
                                >
                                    <font-awesome-icon
                                        icon="fa-solid fa-arrow-up-right-from-square"
                                    />
                                    Track Package
                                </a>
                            </div>
                            <p v-else class="mt-2 text-xs text-[#14202B]/40">
                                No courier receipt attached yet.
                            </p>
                        </div>

                        <div>
                            <PrimaryButton
                                v-if="selectedOrder.status !== 'completed'"
                                class="w-full flex items-center justify-center gap-1"
                                @click="
                                    () => {
                                        const order = selectedOrder!;
                                        closeModal();
                                        openStatusModal(order);
                                    }
                                "
                            >
                                <font-awesome-icon icon="fa-solid fa-edit" />
                                Update Status
                            </PrimaryButton>
                        </div>
                    </div>
                </div>
            </div>
        </Modal>

        <!-- Update Status / Courier Receipt Modal -->
        <Modal
            :show="modal.type.value === 'UpdateStatus'"
            @close="closeModal"
            :maxWidth="'md'"
        >
            <div class="px-4 pt-5 pb-4 sm:p-6" v-if="selectedOrder">
                <h2 class="text-lg font-medium text-gray-900">
                    <font-awesome-icon :icon="modal.icon.value" />
                    {{ modal.title.value }}
                </h2>
                <p class="mt-1 text-xs text-[#14202B]/50">
                    {{ selectedOrder.order_number }} —
                    {{ selectedOrder.team_name }}
                </p>
                <hr class="my-3" />

                <label class="block text-sm font-medium text-[#14202B] mb-1"
                    >Move to</label
                >
                <select
                    v-model="statusForm.status"
                    class="w-full rounded-md border border-gray-300 text-sm py-2 px-3 focus:outline-none focus:ring-1 focus:ring-blue-500"
                >
                    <option
                        v-for="s in nextAllowedStatuses(selectedOrder.status)"
                        :key="s"
                        :value="s"
                    >
                        {{ statusBadge[s].label }}
                    </option>
                </select>
                <p
                    v-if="statusForm.errors.status"
                    class="mt-1 text-xs text-red-600"
                >
                    {{ statusForm.errors.status }}
                </p>

                <div
                    v-if="needsCourierReceipt"
                    class="mt-4 border-t border-[#14202B]/10 pt-4 space-y-3"
                >
                    <p class="text-xs text-[#14202B]/60">
                        No courier API is connected enter the transaction
                        number and shipping fee from the courier's receipt. The
                        tracking site is pulled from the Courier module, so
                        there's nothing to paste in for that.
                    </p>

                    <div>
                        <label
                            class="block text-sm font-medium text-[#14202B] mb-1"
                            >Courier</label
                        >
                        <select
                            v-model.number="statusForm.courier_id"
                            class="w-full rounded-md border border-gray-300 text-sm py-2 px-3 focus:outline-none focus:ring-1 focus:ring-blue-500"
                        >
                            <option :value="null" disabled>
                                Select courier
                            </option>
                            <option
                                v-for="c in courierOptions"
                                :key="c.id"
                                :value="c.id"
                            >
                                {{ c.name }}
                            </option>
                        </select>
                        <p
                            v-if="courierOptions.length === 0"
                            class="mt-1 text-xs text-red-600"
                        >
                            No active couriers found. Add or activate one in the
                            Couriers module first.
                        </p>
                        <p
                            v-if="statusForm.errors.courier_id"
                            class="mt-1 text-xs text-red-600"
                        >
                            {{ statusForm.errors.courier_id }}
                        </p>
                    </div>

                    <div>
                        <label
                            for="transaction_number"
                            class="block text-sm font-medium text-[#14202B] mb-1"
                            >Transaction / Waybill No.</label
                        >
                        <input
                            id="transaction_number"
                            v-model="statusForm.transaction_number"
                            type="text"
                            name="transaction_number"
                            placeholder="e.g. JT-88213764521"
                            class="w-full rounded-md border border-gray-300 text-sm py-2 px-3 focus:outline-none focus:ring-1 focus:ring-blue-500"
                        />
                        <p
                            v-if="statusForm.errors.transaction_number"
                            class="mt-1 text-xs text-red-600"
                        >
                            {{ statusForm.errors.transaction_number }}
                        </p>
                    </div>

                    <div>
                        <label
                            for="shipping_fee"
                            class="block text-sm font-medium text-[#14202B] mb-1"
                            >Shipping Fee (from receipt)</label
                        >
                        <input
                            id="shipping_fee"
                            v-model.number="statusForm.shipping_fee"
                            type="number"
                            name="shipping_fee"
                            min="0"
                            step="0.01"
                            placeholder="e.g. 380"
                            class="w-full rounded-md border border-gray-300 text-sm py-2 px-3 focus:outline-none focus:ring-1 focus:ring-blue-500"
                        />
                        <p
                            v-if="statusForm.errors.shipping_fee"
                            class="mt-1 text-xs text-red-600"
                        >
                            {{ statusForm.errors.shipping_fee }}
                        </p>
                    </div>

                    <div>
                        <label
                            for="remarks"
                            class="block text-sm font-medium text-[#14202B] mb-1"
                            >Remarks (optional)</label
                        >
                        <input
                            id="remarks"
                            v-model="statusForm.remarks"
                            type="text"
                            name="remarks"
                            placeholder="e.g. 3 boxes"
                            class="w-full rounded-md border border-gray-300 text-sm py-2 px-3 focus:outline-none focus:ring-1 focus:ring-blue-500"
                        />
                        <p
                            v-if="statusForm.errors.remarks"
                            class="mt-1 text-xs text-red-600"
                        >
                            {{ statusForm.errors.remarks }}
                        </p>
                    </div>
                </div>

                <div class="mt-6 flex justify-between">
                    <SecondaryButton @click="closeModal">Close</SecondaryButton>
                    <PrimaryButton
                        class="flex items-center justify-center gap-1"
                        @click="submitStatusUpdate"
                        :disabled="!isFormValid || statusForm.processing"
                        :class="{ 'opacity-25': !isFormValid || statusForm.processing }"
                    >
                        <div class="text-sm" v-if="!isFormValid || statusForm.processing">
                            <font-awesome-icon
                                icon="fa-solid fa-spinner"
                                spin
                            />
                        </div>
                        Save
                    </PrimaryButton>
                </div>
            </div>
        </Modal>
    </AdminLayout>
</template>
