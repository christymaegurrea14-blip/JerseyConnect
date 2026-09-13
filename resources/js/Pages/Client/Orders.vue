<script setup lang="ts">
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import Modal from "@/Components/Modal.vue";
import ModalHeader from "@/Components/ModalHeader.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import InputLabel from "@/Components/InputLabel.vue";
import TextInput from "@/Components/TextInput.vue";
import InputError from "@/Components/InputError.vue";
import LocationPicker from "@/Components/LocationPicker.vue";
import type { Order, OrderStatus } from "@/types/orders";
import type { DesignRequest } from "@/types/jersey";
import { getCourierById } from "@/types/couriers";
import { formatCurrency } from "@/Composables/shipping";
import { useModal } from "@/Composables/useModal";
import { statusBadge as designStatusBadge, formatDate } from "@/utils/designRequestStatus";
import { Head, Link, useForm, usePoll, router } from "@inertiajs/vue3";
import { ref, computed, watch } from "vue";

const props = defineProps<{
    orders?: Order[];
    designs?: DesignRequest[];
}>();

usePoll(5000, { only: ["orders", "designs"] });

const orders = computed<Order[]>(() => props.orders ?? []);
const designs = computed<DesignRequest[]>(() => props.designs ?? []);

// ---------------------------------------------------------------------------
// Unified list — a client's kit request is one thing to them throughout its
// whole life, so "My Orders" merges the real DesignRequest rows (still in
// review/payment) and real Order rows (approved, in production/shipping)
// into a single sorted feed instead of splitting them by internal stage.
// ---------------------------------------------------------------------------
type UnifiedRow =
    | { kind: "order"; id: number; data: Order }
    | { kind: "design"; id: number; data: DesignRequest };

const unifiedRows = computed<UnifiedRow[]>(() => {
    const rows: UnifiedRow[] = [
        ...orders.value.map((o) => ({ kind: "order" as const, id: o.id, data: o })),
        ...designs.value.map((d) => ({ kind: "design" as const, id: d.id, data: d })),
    ];
    return rows.sort((a, b) => new Date(b.data.created_at).getTime() - new Date(a.data.created_at).getTime());
});

const orderStatusBadge: Record<OrderStatus, { label: string; class: string }> = {
    processing: { label: "Processing", class: "bg-yellow-100 text-yellow-700" },
    in_production: { label: "In Production", class: "bg-blue-100 text-blue-700" },
    ready_for_delivery: { label: "Ready for Delivery", class: "bg-purple-100 text-purple-700" },
    shipped: { label: "Shipped", class: "bg-indigo-100 text-indigo-700" },
    delivered: { label: "Delivered", class: "bg-teal-100 text-teal-700" },
    completed: { label: "Completed", class: "bg-green-100 text-green-700" },
};

// Real 6-stage production/delivery flow (same as Order::STATUS_FLOW).
const ORDER_PIPELINE_STAGES: { value: OrderStatus; label: string }[] = [
    { value: "processing", label: "Processing" },
    { value: "in_production", label: "In Production" },
    { value: "ready_for_delivery", label: "Ready" },
    { value: "shipped", label: "Shipped" },
    { value: "delivered", label: "Delivered" },
    { value: "completed", label: "Completed" },
];

function orderStageIndex(order: Order | null) {
    if (!order) return -1;
    return ORDER_PIPELINE_STAGES.findIndex((s) => s.value === order.status);
}

function rowImage(row: UnifiedRow) {
    return row.kind === "order" ? row.data.template_image : row.data.template_image_url;
}
function rowQuantity(row: UnifiedRow) {
    return row.kind === "order" ? row.data.quantity : row.data.estimated_quantity;
}
function rowUnitPrice(row: UnifiedRow) {
    return row.kind === "order" ? row.data.unit_price : row.data.template_price;
}
function rowStatusBadge(row: UnifiedRow) {
    return row.kind === "order" ? orderStatusBadge[row.data.status] : designStatusBadge[row.data.status];
}
function rowPlayersCount(row: UnifiedRow) {
    return row.data.players?.length ?? 0;
}
function rowLabel(row: UnifiedRow) {
    return row.kind === "order" ? row.data.order_number : "Design Request";
}

// --- Search, date range, pagination ---
const search = ref("");
const dateFrom = ref("");
const dateTo = ref("");
const perPage = ref(10);
const currentPage = ref(1);

const filteredRows = computed<UnifiedRow[]>(() => {
    const query = search.value.trim().toLowerCase();
    return unifiedRows.value.filter((row) => {
        const matchesSearch =
            !query ||
            row.data.template_name.toLowerCase().includes(query) ||
            row.data.team_name.toLowerCase().includes(query);

        const submitted = new Date(row.data.created_at);
        const matchesFrom = !dateFrom.value || submitted >= new Date(dateFrom.value);
        const matchesTo = !dateTo.value || submitted <= new Date(dateTo.value);

        return matchesSearch && matchesFrom && matchesTo;
    });
});

const totalPages = computed(() => Math.ceil(filteredRows.value.length / perPage.value) || 1);
const paginatedRows = computed<UnifiedRow[]>(() => {
    const start = (currentPage.value - 1) * perPage.value;
    return filteredRows.value.slice(start, start + perPage.value);
});

watch([search, dateFrom, dateTo, perPage], () => {
    currentPage.value = 1;
});

const needsAttentionCount = computed(
    () => designs.value.filter((d) => d.status === "waiting_for_down_payment" || d.status === "revision_requested").length,
);

/** Convenience accessor: the Courier record tied to an order's receipt. */
function courierFor(order: Order) {
    if (!order.courier_receipt) return undefined;
    return getCourierById(order.courier_receipt.courier_id);
}

/** Returns null while the shipping fee is still unknown (pre-shipment). */
function orderTotal(order: Order): number | null {
    if (order.shipping_fee === null) return null;
    return order.quantity * order.unit_price + order.shipping_fee;
}

// ---------------------------------------------------------------------------
// Order-kind modals (View / Address) — design-kind rows link out to their
// own dedicated pages instead (Client/DesignDetail, DesignRoster, DesignPayment).
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

const addressForm = useForm({
    recipient_name: "",
    contact_number: "",
    line1: "",
    barangay: "",
    city: "",
    province: "",
    postal_code: "",
    latitude: null as number | null,
    longitude: null as number | null,
});

function handleLocationUpdate(lat: number, lng: number) {
    addressForm.latitude = lat;
    addressForm.longitude = lng;
}

function openAddressModal(order: Order) {
    selectedOrder.value = order;
    addressForm.clearErrors();
    addressForm.recipient_name = order.address.recipient_name ?? "";
    addressForm.contact_number = order.address.contact_number ?? "";
    addressForm.line1 = order.address.line1 ?? "";
    addressForm.barangay = order.address.barangay ?? "";
    addressForm.city = order.address.city ?? "";
    addressForm.province = order.address.province ?? "";
    addressForm.postal_code = order.address.postal_code ?? "";
    addressForm.latitude = order.address.latitude ?? null;
    addressForm.longitude = order.address.longitude ?? null;
    modal.title.value = "Delivery Address";
    modal.type.value = "Address";
    modal.icon.value = "fa-solid fa-location-dot";
    modal.openModal();
}

function submitAddressUpdate() {
    if (!selectedOrder.value) return;
    addressForm.patch(route("client.orders.update-address", selectedOrder.value.id), {
        onSuccess: () => closeModal(),
    });
}

function closeModal() {
    selectedOrder.value = null;
    addressForm.clearErrors();
    modal.closeModal();
}

// --- Cancel (design-kind rows only) ---
const cancelling = ref(false);
const cancelTarget = ref<DesignRequest | null>(null);

function askCancel(request: DesignRequest) {
    cancelTarget.value = request;
}

function confirmCancel() {
    if (!cancelTarget.value) return;
    cancelling.value = true;
    router.delete(route("client.design.cancel", cancelTarget.value.id), {
        preserveScroll: true,
        onFinish: () => {
            cancelling.value = false;
            cancelTarget.value = null;
        },
    });
}
</script>

<template>
    <Head title="My Orders" />

    <AuthenticatedLayout>
        <!-- Header -->
        <section v-reveal class="rounded-2xl bg-white border border-ink/10 shadow-sm p-5 sm:p-6 mb-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-3.5">
                    <div class="w-11 h-11 rounded-xl bg-cobalt/10 text-cobalt flex items-center justify-center border border-cobalt/20 shrink-0">
                        <font-awesome-icon icon="fa-solid fa-shopping-basket" class="text-lg" />
                    </div>
                    <div>
                        <h1 class="text-xl font-black tracking-tight text-ink">My Orders</h1>
                        <p class="text-sm text-ink/50 mt-0.5">
                            {{ unifiedRows.length }} order{{ unifiedRows.length === 1 ? "" : "s" }} total — from design request to delivery
                        </p>
                    </div>
                </div>
                <span
                    v-if="needsAttentionCount > 0"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-warn/10 text-warn text-xs font-bold self-start sm:self-auto"
                >
                    <span class="w-1.5 h-1.5 rounded-full bg-warn animate-pulse"></span>
                    {{ needsAttentionCount }} need{{ needsAttentionCount === 1 ? "s" : "" }} your attention
                </span>
            </div>
        </section>

        <section v-reveal="80" class="rounded-2xl bg-white border border-ink/10 shadow-sm p-5 sm:p-6">
            <!-- Search + filters -->
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between mb-5">
                <div class="relative w-full sm:max-w-xs">
                    <font-awesome-icon icon="fa-solid fa-magnifying-glass" class="absolute left-3 top-1/2 -translate-y-1/2 text-ink/30 text-xs" />
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Search design or team..."
                        class="w-full pl-8 pr-3 py-2 text-sm rounded-xl border border-ink/15 outline-none focus:border-cobalt focus:ring-1 focus:ring-cobalt"
                    />
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <div class="flex items-center gap-1.5 text-xs text-ink/50">
                        <span>From</span>
                        <input v-model="dateFrom" type="date" class="text-xs rounded-lg border border-ink/15 px-2 py-1.5 outline-none focus:border-cobalt" />
                    </div>
                    <div class="flex items-center gap-1.5 text-xs text-ink/50">
                        <span>To</span>
                        <input v-model="dateTo" type="date" class="text-xs rounded-lg border border-ink/15 px-2 py-1.5 outline-none focus:border-cobalt" />
                    </div>
                    <select v-model="perPage" class="text-xs rounded-lg border border-ink/15 px-2 py-1.5 outline-none focus:border-cobalt">
                        <option :value="5">5 / page</option>
                        <option :value="10">10 / page</option>
                        <option :value="25">25 / page</option>
                    </select>
                </div>
            </div>

            <!-- Card list -->
            <div v-if="paginatedRows.length" class="space-y-3">
                <div
                    v-for="(row, i) in paginatedRows"
                    :key="`${row.kind}-${row.id}`"
                    v-reveal="Math.min(i, 6) * 50"
                    class="flex flex-col sm:flex-row sm:items-center gap-4 p-4 rounded-xl border border-ink/10 hover:border-cobalt/30 hover:shadow-sm transition-all"
                >
                    <img
                        :src="rowImage(row)"
                        :alt="row.data.template_name"
                        class="w-28 h-28 sm:w-24 sm:h-24 flex-shrink-0 rounded-xl object-contain bg-ink/5 p-2 border border-ink/10"
                    />

                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="font-bold text-ink">{{ row.data.template_name }}</span>
                            <span class="text-ink/30">•</span>
                            <span class="text-ink/60 text-sm">{{ row.data.team_name }}</span>
                            <span
                                class="inline-block rounded-full px-2 py-0.5 text-[11px] font-medium"
                                :class="rowStatusBadge(row).class"
                            >
                                {{ rowStatusBadge(row).label }}
                            </span>
                        </div>
                        <div class="flex items-center gap-3 mt-1.5 text-xs text-ink/50 flex-wrap">
                            <span class="font-mono">{{ rowLabel(row) }}</span>
                            <span>{{ rowQuantity(row) }} sets</span>
                            <span>₱{{ (rowQuantity(row) * rowUnitPrice(row)).toFixed(2) }}</span>
                            <span v-if="rowPlayersCount(row) > 0" class="flex items-center gap-1">
                                <font-awesome-icon icon="fa-solid fa-users" class="text-cobalt/50" />
                                {{ rowPlayersCount(row) }} on roster
                            </span>
                            <span>Submitted {{ formatDate(row.data.created_at) }}</span>
                        </div>
                    </div>

                    <div class="flex items-center flex-wrap gap-1.5 shrink-0">
                        <!-- Order-kind actions -->
                        <template v-if="row.kind === 'order'">
                            <button
                                type="button"
                                class="text-xs font-bold bg-cobalt text-white rounded-lg px-2.5 py-2 transition-colors hover:bg-cobalt-dark"
                                @click="viewOrder(row.data)"
                            >
                                <font-awesome-icon icon="fa-solid fa-eye" />
                                View
                            </button>
                            <Link
                                v-if="row.data.design_request_id"
                                :href="route('client.design.roster', row.data.design_request_id)"
                                class="text-xs font-bold bg-ink text-white rounded-lg px-2.5 py-2 transition-colors hover:bg-ink/80"
                            >
                                <font-awesome-icon icon="fa-solid fa-users" />
                                Roster
                            </Link>
                            <button
                                v-if="['processing', 'in_production', 'ready_for_delivery'].includes(row.data.status)"
                                type="button"
                                class="text-xs font-bold bg-good text-white rounded-lg px-2.5 py-2 transition-colors hover:bg-good/90"
                                @click="openAddressModal(row.data)"
                            >
                                <font-awesome-icon icon="fa-solid fa-location-dot" />
                                Address
                            </button>
                            <Link
                                v-if="row.data.status !== 'completed'"
                                class="text-xs font-bold bg-warn text-white rounded-lg px-2.5 py-2 transition-colors hover:bg-warn/90"
                                :href="route('client.chat.index')"
                            >
                                <font-awesome-icon icon="fa-solid fa-message" />
                                Message
                            </Link>
                            <a
                                v-if="row.data.courier_receipt && row.data.status === 'shipped' && courierFor(row.data)"
                                class="text-xs font-bold bg-accent text-white rounded-lg px-2.5 py-2 transition-colors hover:bg-accent-dark"
                                :href="courierFor(row.data)!.site"
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                <font-awesome-icon icon="fa-solid fa-truck" />
                                Track
                            </a>
                        </template>

                        <!-- Design-kind actions -->
                        <template v-else>
                            <Link
                                :href="route('client.design.show', row.data.id)"
                                class="text-xs font-bold bg-cobalt text-white rounded-lg px-2.5 py-2 transition-colors hover:bg-cobalt-dark"
                            >
                                <font-awesome-icon icon="fa-solid fa-eye" />
                                View
                            </Link>
                            <Link
                                :href="route('client.design.roster', row.data.id)"
                                class="text-xs font-bold bg-ink text-white rounded-lg px-2.5 py-2 transition-colors hover:bg-ink/80"
                            >
                                <font-awesome-icon icon="fa-solid fa-users" />
                                Roster
                                <span
                                    v-if="rowPlayersCount(row) > 0"
                                    class="ml-0.5 inline-flex items-center justify-center rounded-full bg-white/20 px-1.5 text-[10px] font-bold"
                                >
                                    {{ rowPlayersCount(row) }}
                                </span>
                            </Link>
                            <Link
                                v-if="row.data.status !== 'approved' && row.data.status !== 'cancelled'"
                                class="text-xs font-bold bg-warn text-white rounded-lg px-2.5 py-2 transition-colors hover:bg-warn/90"
                                :href="route('client.chat.index')"
                            >
                                <font-awesome-icon icon="fa-solid fa-message" />
                                Message
                            </Link>
                            <Link
                                v-if="row.data.status === 'waiting_for_down_payment'"
                                :href="route('client.design.pay.show', row.data.id)"
                                class="text-xs font-bold bg-good text-white rounded-lg px-2.5 py-2 transition-colors hover:bg-good/90"
                            >
                                <font-awesome-icon icon="fa-solid fa-credit-card" />
                                Pay
                            </Link>
                            <button
                                v-if="
                                    row.data.status !== 'approved' &&
                                    row.data.status !== 'cancelled' &&
                                    row.data.status !== 'revision_requested' &&
                                    row.data.status !== 'waiting_for_down_payment' &&
                                    row.data.status !== 'pending_down_payment_review'
                                "
                                type="button"
                                class="text-xs font-bold bg-accent text-white rounded-lg px-2.5 py-2 transition-colors hover:bg-accent-dark"
                                @click="askCancel(row.data)"
                            >
                                <font-awesome-icon icon="fa-solid fa-xmark-circle" />
                                Cancel
                            </button>
                        </template>
                    </div>
                </div>
            </div>
            <div v-else class="py-16 text-center text-sm text-ink/40">
                <font-awesome-icon icon="fa-solid fa-calendar-xmark" class="text-2xl mb-2 block mx-auto" />
                No orders yet. Pick a template from the Catalogue to start one.
            </div>

            <!-- Pagination -->
            <div v-if="filteredRows.length" class="flex items-center justify-between gap-2 mt-5 pt-4 border-t border-ink/10">
                <p class="text-xs text-ink/50">
                    Showing {{ (currentPage - 1) * perPage + 1 }}–{{ Math.min(currentPage * perPage, filteredRows.length) }}
                    of {{ filteredRows.length }}
                </p>
                <div class="flex items-center gap-1">
                    <button
                        type="button"
                        :disabled="currentPage === 1"
                        class="px-2.5 py-1.5 rounded-lg border text-xs transition-colors"
                        :class="currentPage === 1 ? 'border-ink/10 text-ink/25 cursor-not-allowed' : 'border-ink/15 text-ink/60 hover:bg-ink/5'"
                        @click="currentPage--"
                    >
                        <font-awesome-icon icon="fa-solid fa-chevron-left" />
                    </button>
                    <span class="text-xs text-ink/60 px-2">Page {{ currentPage }} of {{ totalPages }}</span>
                    <button
                        type="button"
                        :disabled="currentPage === totalPages"
                        class="px-2.5 py-1.5 rounded-lg border text-xs transition-colors"
                        :class="currentPage === totalPages ? 'border-ink/10 text-ink/25 cursor-not-allowed' : 'border-ink/15 text-ink/60 hover:bg-ink/5'"
                        @click="currentPage++"
                    >
                        <font-awesome-icon icon="fa-solid fa-chevron-right" />
                    </button>
                </div>
            </div>
        </section>

        <!-- View Order Modal -->
        <Modal :show="modal.type.value === 'View'" @close="closeModal" :maxWidth="'5xl'" :dark="false">
            <div v-if="selectedOrder">
                <ModalHeader
                    :dark="false"
                    :icon="modal.icon.value"
                    icon-class="text-cobalt bg-cobalt/10 border-cobalt/20"
                    :title="`${modal.title.value} — ${selectedOrder.order_number}`"
                    :subtitle="selectedOrder.team_name"
                    @close="closeModal"
                />
                <div class="px-4 pt-5 pb-4 sm:p-6">
                    <!-- Production & delivery pipeline (real order status flow) -->
                    <div class="rounded-xl border border-ink/10 p-4 mb-4">
                        <p class="text-sm font-bold text-ink mb-3">
                            <font-awesome-icon icon="fa-solid fa-truck-fast" class="text-cobalt" />
                            Production &amp; Delivery Pipeline
                        </p>
                        <div class="grid grid-cols-2 sm:grid-cols-6 gap-2">
                            <div
                                v-for="(stage, i) in ORDER_PIPELINE_STAGES"
                                :key="stage.value"
                                class="flex flex-col items-center gap-1.5 p-2.5 rounded-xl border text-center"
                                :class="
                                    i < orderStageIndex(selectedOrder)
                                        ? 'bg-good/5 border-good/20'
                                        : i === orderStageIndex(selectedOrder)
                                            ? 'bg-cobalt/5 border-cobalt/20 shadow-xs'
                                            : 'bg-ink/[0.02] border-dashed border-ink/10 opacity-70'
                                "
                            >
                                <div
                                    class="w-7 h-7 rounded-full flex items-center justify-center font-bold text-[11px]"
                                    :class="
                                        i < orderStageIndex(selectedOrder)
                                            ? 'bg-good text-white'
                                            : i === orderStageIndex(selectedOrder)
                                                ? 'bg-cobalt text-white'
                                                : 'bg-ink/10 text-ink/40'
                                    "
                                >
                                    <font-awesome-icon v-if="i < orderStageIndex(selectedOrder)" icon="fa-solid fa-check" class="text-[10px]" />
                                    <span v-else>{{ i + 1 }}</span>
                                </div>
                                <p class="text-[11px] font-bold text-ink leading-tight">{{ stage.label }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col gap-4 sm:flex-row">
                        <!-- Jersey preview -->
                        <div class="flex flex-col gap-3 border border-ink/10 px-3 py-3 rounded-xl w-full sm:w-1/3">
                            <p class="text-sm font-bold text-ink text-center">{{ selectedOrder.template_name }}</p>
                            <div class="flex aspect-square w-full items-center justify-center overflow-hidden rounded-lg border border-ink/10 bg-ink/[0.02]">
                                <img :src="selectedOrder.template_image" :alt="selectedOrder.template_name" class="h-full w-full object-contain p-4" />
                            </div>
                            <div class="flex items-center justify-center gap-1">
                                <span class="h-4 w-4 rounded-full border border-ink/15" :style="{ backgroundColor: selectedOrder.primary_color }" />
                                <span class="h-4 w-4 rounded-full border border-ink/15" :style="{ backgroundColor: selectedOrder.secondary_color }" />
                                <span class="h-4 w-4 rounded-full border border-ink/15" :style="{ backgroundColor: selectedOrder.accent_color }" />
                            </div>
                            <p class="text-xs text-center text-ink/50">{{ selectedOrder.font_style }} • Qty {{ selectedOrder.quantity }}</p>
                            <Link
                                v-if="selectedOrder.design_request_id"
                                :href="route('client.design.roster', selectedOrder.design_request_id)"
                                class="text-center text-xs text-cobalt hover:text-cobalt-dark font-bold"
                            >
                                Manage Team Roster →
                            </Link>
                        </div>

                        <!-- Delivery + cost -->
                        <div class="flex flex-col gap-4 w-full sm:w-2/3">
                            <div class="border border-ink/10 rounded-xl p-3">
                                <p class="text-sm font-bold text-ink mb-2">
                                    <font-awesome-icon icon="fa-solid fa-location-dot" class="text-cobalt" />
                                    Delivery Address
                                </p>
                                <p class="text-sm text-ink/70">
                                    {{ selectedOrder.address.recipient_name }} • {{ selectedOrder.address.contact_number }}
                                </p>
                                <p class="text-sm text-ink/70">
                                    {{ selectedOrder.address.line1
                                    }}<span v-if="selectedOrder.address.barangay">, {{ selectedOrder.address.barangay }}</span>,
                                    {{ selectedOrder.address.city }}, {{ selectedOrder.address.province }} {{ selectedOrder.address.postal_code }}
                                </p>
                            </div>

                            <div class="border border-ink/10 rounded-xl p-3">
                                <p class="text-sm font-bold text-ink mb-2">
                                    <font-awesome-icon icon="fa-solid fa-receipt" class="text-cobalt" />
                                    Cost Breakdown
                                </p>
                                <div class="flex justify-between text-sm text-ink/70">
                                    <span>{{ selectedOrder.quantity }} × {{ formatCurrency(selectedOrder.unit_price) }}</span>
                                    <span>{{ formatCurrency(selectedOrder.quantity * selectedOrder.unit_price) }}</span>
                                </div>
                                <div class="flex justify-between text-sm text-ink/70">
                                    <span>Shipping fee</span>
                                    <span>{{ selectedOrder.shipping_fee !== null ? formatCurrency(selectedOrder.shipping_fee) : "To be determined" }}</span>
                                </div>
                                <hr class="my-2 border-ink/10" />
                                <div class="flex justify-between text-sm font-semibold text-ink">
                                    <span>Total</span>
                                    <span>{{ orderTotal(selectedOrder) !== null ? formatCurrency(orderTotal(selectedOrder)!) : "Pending shipping fee" }}</span>
                                </div>
                                <p v-if="selectedOrder.shipping_fee === null" class="mt-1 text-xs text-ink/40">
                                    The shipping fee is confirmed once your order ships and we get the courier's receipt.
                                </p>
                            </div>

                            <div class="border border-ink/10 rounded-xl p-3">
                                <p class="text-sm font-bold text-ink mb-2">
                                    <font-awesome-icon icon="fa-solid fa-truck" class="text-cobalt" />
                                    Shipping Status
                                </p>
                                <span class="inline-block rounded-full px-2.5 py-1 text-xs font-medium" :class="orderStatusBadge[selectedOrder.status].class">
                                    {{ orderStatusBadge[selectedOrder.status].label }}
                                </span>

                                <div v-if="selectedOrder.courier_receipt" class="mt-2 text-sm text-ink/70">
                                    <p>Courier: {{ courierFor(selectedOrder)?.name ?? "Unknown courier" }}</p>
                                    <p>Transaction #: {{ selectedOrder.courier_receipt.transaction_number }}</p>
                                    <p>Shipped: {{ formatDate(selectedOrder.courier_receipt.date_shipped) }}</p>
                                    <a
                                        v-if="courierFor(selectedOrder)"
                                        :href="courierFor(selectedOrder)!.site"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="inline-flex items-center gap-1 mt-1 text-cobalt hover:underline"
                                    >
                                        <font-awesome-icon icon="fa-solid fa-arrow-up-right-from-square" />
                                        Track Package
                                    </a>
                                </div>
                                <p v-else class="mt-2 text-xs text-ink/40">Tracking details will appear here once the order ships.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Modal>

        <!-- Address Management Modal -->
        <Modal :show="modal.type.value === 'Address'" @close="closeModal" :maxWidth="'lg'" :dark="false">
            <div v-if="selectedOrder">
                <ModalHeader
                    :dark="false"
                    :icon="modal.icon.value"
                    icon-class="text-cobalt bg-cobalt/10 border-cobalt/20"
                    :title="modal.title.value"
                    @close="closeModal"
                />
                <div class="px-4 pt-5 pb-4 sm:p-6">
                    <div class="flex flex-col gap-3">
                        <div>
                            <InputLabel for="recipient_name" value="Recipient Name" />
                            <TextInput v-model="addressForm.recipient_name" class="mt-1 block w-full" id="recipient_name" required />
                            <InputError :message="addressForm.errors.recipient_name" class="mt-2" />
                        </div>
                        <div>
                            <InputLabel for="contact_number" value="Contact Number" />
                            <TextInput v-model="addressForm.contact_number" class="mt-1 block w-full" id="contact_number" required />
                            <InputError :message="addressForm.errors.contact_number" class="mt-2" />
                        </div>
                        <div>
                            <InputLabel for="postal_code" value="Postal Code" />
                            <TextInput v-model="addressForm.postal_code" class="mt-1 block w-full" id="postal_code" required />
                            <InputError :message="addressForm.errors.postal_code" class="mt-2" />
                        </div>
                        <div>
                            <InputLabel for="line1" value="Address Line 1" />
                            <TextInput v-model="addressForm.line1" class="mt-1 block w-full" id="line1" required />
                            <InputError :message="addressForm.errors.line1" class="mt-2" />
                        </div>
                        <div>
                            <InputLabel for="barangay" value="Barangay (optional)" />
                            <TextInput v-model="addressForm.barangay" class="mt-1 block w-full" id="barangay" />
                            <InputError :message="addressForm.errors.barangay" class="mt-2" />
                        </div>
                        <div>
                            <InputLabel for="city" value="City / Municipality" />
                            <TextInput v-model="addressForm.city" class="mt-1 block w-full" id="city" required />
                            <InputError :message="addressForm.errors.city" class="mt-2" />
                        </div>
                        <div>
                            <InputLabel for="province" value="Province" />
                            <TextInput v-model="addressForm.province" class="mt-1 block w-full" id="province" required />
                            <InputError :message="addressForm.errors.province" class="mt-2" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-ink mb-1">Pin Location</label>
                            <LocationPicker :model-lat="addressForm.latitude" :model-lng="addressForm.longitude" @update:location="handleLocationUpdate" />
                            <p v-if="addressForm.latitude && addressForm.longitude" class="mt-1 text-xs text-ink/50">
                                {{ addressForm.latitude.toFixed(6) }}, {{ addressForm.longitude.toFixed(6) }}
                            </p>
                        </div>
                    </div>

                    <p class="mt-4 text-xs text-ink/50">
                        The shipping fee for this order will be confirmed once it ships and isn't affected by this address form.
                    </p>

                    <div class="mt-6 flex justify-between">
                        <SecondaryButton @click="closeModal">Close</SecondaryButton>
                        <PrimaryButton
                            class="flex items-center justify-center gap-1"
                            :disabled="addressForm.processing"
                            @click="submitAddressUpdate"
                            :class="{ 'opacity-25': addressForm.processing }"
                        >
                            <div class="text-sm" v-if="addressForm.processing">
                                <font-awesome-icon icon="fa-solid fa-spinner" spin />
                            </div>
                            Save Address
                        </PrimaryButton>
                    </div>
                </div>
            </div>
        </Modal>

        <!-- Cancel confirm (design-kind rows) -->
        <div
            v-if="cancelTarget"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-ink/40 backdrop-blur-sm"
            @click.self="cancelTarget = null"
        >
            <div class="bg-white rounded-2xl shadow-2xl max-w-sm w-full p-5">
                <h3 class="text-base font-bold text-ink mb-2">Cancel Request</h3>
                <p class="text-sm text-ink/60 mb-5">Are you sure you want to cancel this request?</p>
                <div class="flex justify-between gap-2">
                    <SecondaryButton :disabled="cancelling" @click="cancelTarget = null">Close</SecondaryButton>
                    <PrimaryButton class="!bg-accent hover:!bg-accent-dark" :disabled="cancelling" @click="confirmCancel">
                        Confirm Cancel
                    </PrimaryButton>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>