<script setup lang="ts">
import AdminLayout from "@/Layouts/AdminLayout.vue";
import Modal from "@/Components/Modal.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import type { Order, OrderStatus, CourierReceipt } from "@/types/orders.ts";
import { activeCouriers, getCourierById } from "@/types/couriers";
import { formatCurrency } from "@/Composables/shipping";
import { useModal } from "@/Composables/useModal";
import { Head, Link, router, useForm, usePoll } from "@inertiajs/vue3";
import { ref, computed, watch } from "vue";

const props = defineProps<{
    orders?: Order[];
}>();

usePoll(5000, {
    only: ["orders"],
});

const orders = computed<Order[]>(() => props.orders ?? []);

// ---------------------------------------------------------------------------
// Stats (top summary cards) — derived entirely from real order data
// ---------------------------------------------------------------------------
/** Returns null while the shipping fee is still unknown (pre-shipment). */
function orderTotal(order: Order): number | null {
    if (order.shipping_fee === null) return null;
    return order.quantity * order.unit_price + order.shipping_fee;
}

const statusCounts = computed(() => {
    const counts: Record<OrderStatus, number> = {
        processing: 0,
        in_production: 0,
        ready_for_delivery: 0,
        shipped: 0,
        delivered: 0,
        completed: 0,
    };
    for (const o of orders.value) counts[o.status]++;
    return counts;
});

const activeOrdersCount = computed(
    () => orders.value.filter((o) => o.status !== "completed").length,
);

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
    processing: {
        label: "Processing",
        class: "bg-amber-500/15 text-amber-300 border-amber-500/25",
    },
    in_production: {
        label: "In Production",
        class: "bg-indigo-500/15 text-indigo-300 border-indigo-500/25",
    },
    ready_for_delivery: {
        label: "Ready for Delivery",
        class: "bg-cyan-500/15 text-cyan-300 border-cyan-500/25",
    },
    shipped: {
        label: "Shipped",
        class: "bg-purple-500/15 text-purple-300 border-purple-500/25",
    },
    delivered: {
        label: "Delivered",
        class: "bg-teal-500/15 text-teal-300 border-teal-500/25",
    },
    completed: {
        label: "Completed",
        class: "bg-emerald-500/15 text-emerald-300 border-emerald-500/25",
    },
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

// ---------------------------------------------------------------------------
// Date range + pagination (mirrors what the shared Table.vue used to give
// this page before it was hand-rolled for the dark redesign)
// ---------------------------------------------------------------------------
const dateFrom = ref("");
const dateTo = ref("");
const perPage = ref(10);
const perPageOptions = [5, 10, 25, 50];
const currentPage = ref(1);

const filteredOrders = computed<Order[]>(() => {
    let list = orders.value;

    if (activeStatus.value !== "All") {
        list = list.filter((o) => o.status === activeStatus.value);
    }

    if (dateFrom.value) {
        const from = new Date(dateFrom.value);
        list = list.filter((o) => new Date(o.created_at) >= from);
    }
    if (dateTo.value) {
        const to = new Date(dateTo.value);
        to.setHours(23, 59, 59, 999);
        list = list.filter((o) => new Date(o.created_at) <= to);
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

watch([activeStatus, dateFrom, dateTo, searchQuery, perPage], () => {
    currentPage.value = 1;
});

const totalPages = computed(() =>
    Math.max(1, Math.ceil(filteredOrders.value.length / perPage.value)),
);

const paginatedOrders = computed<Order[]>(() => {
    const start = (currentPage.value - 1) * perPage.value;
    return filteredOrders.value.slice(start, start + perPage.value);
});

const rangeStart = computed(() =>
    filteredOrders.value.length === 0
        ? 0
        : (currentPage.value - 1) * perPage.value + 1,
);
const rangeEnd = computed(() =>
    Math.min(currentPage.value * perPage.value, filteredOrders.value.length),
);

function clearFilters() {
    dateFrom.value = "";
    dateTo.value = "";
    searchQuery.value = "";
    activeStatus.value = "All";
}

const hasActiveFilters = computed(
    () => !!dateFrom.value || !!dateTo.value || !!searchQuery.value.trim() || activeStatus.value !== "All",
);

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
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col gap-1">
                <h1 class="text-2xl font-extrabold text-white tracking-tight">Orders</h1>
                <p class="text-sm text-slate-400">
                    {{ orders.length }} order{{ orders.length === 1 ? "" : "s" }} total ·
                    {{ activeOrdersCount }} active
                </p>
            </div>

            <!-- Stat cards (derived from real order data) -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3.5">
                <div class="glass-panel rounded-xl p-4">
                    <span class="text-xs font-medium text-slate-400">Active orders</span>
                    <div class="text-2xl font-bold text-white mt-1.5">{{ activeOrdersCount }}</div>
                </div>
                <div class="glass-panel rounded-xl p-4">
                    <span class="text-xs font-medium text-slate-400">In production</span>
                    <div class="text-2xl font-bold text-indigo-300 mt-1.5">{{ statusCounts.in_production }}</div>
                </div>
                <div class="glass-panel rounded-xl p-4">
                    <span class="text-xs font-medium text-slate-400">Awaiting shipment</span>
                    <div class="text-2xl font-bold text-cyan-300 mt-1.5">{{ statusCounts.ready_for_delivery }}</div>
                </div>
                <div class="glass-panel rounded-xl p-4">
                    <span class="text-xs font-medium text-slate-400">Completed</span>
                    <div class="text-2xl font-bold text-emerald-300 mt-1.5">{{ statusCounts.completed }}</div>
                </div>
            </div>

            <!-- Filters -->
            <div class="flex flex-col lg:flex-row gap-3 lg:items-center lg:justify-between">
                <div class="flex flex-wrap gap-1.5 p-1.5 rounded-xl bg-slate-900/60 border border-white/5">
                    <button
                        v-for="filter in statusFilters"
                        :key="filter.value"
                        type="button"
                        class="rounded-lg px-3 py-1.5 text-xs font-semibold transition-colors"
                        :class="
                            activeStatus === filter.value
                                ? 'bg-indigo-600 text-white shadow-sm'
                                : 'text-slate-400 hover:text-slate-200'
                        "
                        @click="activeStatus = filter.value"
                    >
                        {{ filter.label }}
                    </button>
                </div>

                <div class="relative w-full lg:w-72">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-500 pointer-events-none">
                        <font-awesome-icon icon="fa-solid fa-magnifying-glass" class="text-xs" />
                    </span>
                    <input
                        id="orders-search"
                        v-model="searchQuery"
                        type="text"
                        name="search"
                        placeholder="Search order #, team, customer..."
                        aria-label="Search orders"
                        class="w-full pl-8 pr-3 py-2 text-sm rounded-lg bg-slate-900/60 border border-white/10 text-slate-200 placeholder-slate-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500"
                    />
                </div>
            </div>

            <!-- Date range + per-page -->
            <div class="flex flex-wrap items-center gap-3">
                <div class="flex items-center gap-1.5">
                    <label for="orders-from" class="text-xs text-slate-500">From</label>
                    <input
                        id="orders-from"
                        v-model="dateFrom"
                        type="date"
                        name="dateFrom"
                        class="text-sm rounded-lg bg-slate-900/60 border border-white/10 text-slate-200 py-1.5 px-2.5 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500"
                    />
                </div>
                <div class="flex items-center gap-1.5">
                    <label for="orders-to" class="text-xs text-slate-500">To</label>
                    <input
                        id="orders-to"
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
                    <label for="orders-per-page" class="text-xs text-slate-500">Show</label>
                    <select
                        id="orders-per-page"
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
                                <th class="px-4 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-slate-400">Order</th>
                                <th class="px-4 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-slate-400">Customer</th>
                                <th class="px-4 py-3 text-center text-[11px] font-bold uppercase tracking-wider text-slate-400">Qty</th>
                                <th class="px-4 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-slate-400">Destination</th>
                                <th class="px-4 py-3 text-right text-[11px] font-bold uppercase tracking-wider text-slate-400">Total</th>
                                <th class="px-4 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-slate-400">Status</th>
                                <th class="px-4 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-slate-400">Tracking</th>
                                <th class="px-4 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-slate-400">Placed</th>
                                <th class="px-4 py-3 text-center text-[11px] font-bold uppercase tracking-wider text-slate-400">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            <tr v-if="filteredOrders.length === 0">
                                <td colspan="9" class="px-4 py-10 text-center text-sm text-slate-500">
                                    No orders match your filters.
                                </td>
                            </tr>
                            <tr
                                v-for="row in paginatedOrders"
                                :key="row.id"
                                class="hover:bg-slate-800/30 transition-colors"
                            >
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2.5">
                                        <img
                                            :src="row.template_image"
                                            :alt="row.template_name"
                                            class="h-9 w-9 flex-shrink-0 rounded-lg object-contain bg-slate-900 border border-white/10 p-1"
                                        />
                                        <div class="flex flex-col">
                                            <span class="font-semibold text-white">{{ row.team_name }}</span>
                                            <span class="text-[11px] text-slate-500 font-mono">{{ row.order_number }}</span>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-4 py-3">
                                    <div class="flex flex-col text-xs">
                                        <span class="font-medium text-slate-200">{{ row.address.recipient_name }}</span>
                                        <span class="text-slate-500">{{ row.address.contact_number }}</span>
                                    </div>
                                </td>

                                <td class="px-4 py-3 text-center text-slate-300">{{ row.quantity }}</td>

                                <td class="px-4 py-3">
                                    <div class="flex flex-col text-xs">
                                        <span class="font-medium text-slate-200">{{ row.address.province }}</span>
                                        <span class="text-slate-500">{{ row.address.city }}</span>
                                    </div>
                                </td>

                                <td class="px-4 py-3 text-right">
                                    <div v-if="orderTotal(row) !== null" class="flex flex-col">
                                        <span class="font-semibold text-white">{{ formatCurrency(orderTotal(row)!) }}</span>
                                        <span class="text-[11px] text-slate-500">incl. {{ formatCurrency(row.shipping_fee!) }} shipping</span>
                                    </div>
                                    <span v-else class="text-xs text-slate-500">Pending shipping fee</span>
                                </td>

                                <td class="px-4 py-3">
                                    <span
                                        class="inline-block rounded-full px-2.5 py-1 text-[11px] font-medium border"
                                        :class="statusBadge[row.status].class"
                                    >
                                        {{ statusBadge[row.status].label }}
                                    </span>
                                </td>

                                <td class="px-4 py-3">
                                    <div v-if="row.courier_receipt" class="flex flex-col text-xs">
                                        <span class="font-medium text-indigo-300 font-mono">{{ row.courier_receipt.transaction_number }}</span>
                                        <span class="text-slate-500">{{ courierFor(row)?.name ?? "Unknown courier" }}</span>
                                    </div>
                                    <span v-else class="text-xs text-slate-600">—</span>
                                </td>

                                <td class="px-4 py-3 text-xs text-slate-400">{{ formatDate(row.created_at) }}</td>

                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-center gap-1">
                                        <button
                                            type="button"
                                            class="text-xs font-medium bg-indigo-600/20 text-indigo-300 border border-indigo-500/30 rounded-md px-2 py-2 transition-colors hover:bg-indigo-600/30"
                                            @click="viewOrder(row)"
                                        >
                                            <font-awesome-icon icon="fa-solid fa-eye" />
                                        </button>

                                        <button
                                            v-if="row.status !== 'completed'"
                                            type="button"
                                            class="text-xs font-medium bg-slate-800 text-slate-300 border border-white/10 rounded-md px-2 py-2 transition-colors hover:bg-slate-700"
                                            @click="openStatusModal(row)"
                                        >
                                            <font-awesome-icon icon="fa-solid fa-edit" />
                                        </button>

                                        <Link
                                            v-if="row.status !== 'completed'"
                                            class="text-xs font-medium bg-amber-500/20 text-amber-300 border border-amber-500/30 rounded-md px-2 py-2 transition-colors hover:bg-amber-500/30"
                                            :href="route('admin.messages.index')"
                                        >
                                            <font-awesome-icon icon="fa-solid fa-message" />
                                        </Link>

                                        <a
                                            class="text-xs font-medium bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 rounded-md px-2 py-2 transition-colors hover:bg-emerald-500/30"
                                            v-if="row.courier_receipt && row.status === 'shipped' && courierFor(row)"
                                            :href="courierFor(row)!.site"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                        >
                                            <font-awesome-icon icon="fa-solid fa-truck" />
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="filteredOrders.length > 0" class="flex flex-col sm:flex-row items-center justify-between gap-3">
                <p class="text-xs text-slate-500">
                    Showing <span class="text-slate-300 font-medium">{{ rangeStart }}–{{ rangeEnd }}</span>
                    of <span class="text-slate-300 font-medium">{{ filteredOrders.length }}</span> orders
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

        <!-- View Order Modal -->
        <Modal :show="modal.type.value === 'View'" @close="closeModal" :maxWidth="'5xl'">
            <div
                v-if="selectedOrder"
                class="overflow-y-auto max-h-[90vh] px-4 pt-5 pb-4 sm:p-6 bg-surface-card text-slate-200"
            >
                <div class="flex items-center justify-between gap-2">
                    <h2 class="text-base sm:text-lg font-semibold text-white truncate">
                        <font-awesome-icon :icon="modal.icon.value" class="text-indigo-400" />
                        {{ modal.title.value }} — {{ selectedOrder.order_number }}
                    </h2>
                    <SecondaryButton @click="closeModal" class="flex-shrink-0">
                        <font-awesome-icon icon="fa-solid fa-xmark" />
                    </SecondaryButton>
                </div>
                <hr class="my-3 border-white/10" />

                <div class="flex flex-col gap-4 sm:flex-row">
                    <!-- Jersey preview -->
                    <div class="flex flex-col gap-3 border border-white/10 px-3 py-3 rounded-xl w-full sm:w-1/3 bg-slate-900/40">
                        <p class="text-sm font-bold text-white text-center">{{ selectedOrder.template_name }}</p>
                        <div class="flex aspect-square w-full items-center justify-center overflow-hidden rounded-lg border border-white/10 bg-slate-950/50">
                            <img
                                :src="selectedOrder.template_image"
                                :alt="selectedOrder.template_name"
                                class="h-full w-full object-contain p-4"
                            />
                        </div>
                        <div class="flex items-center justify-center gap-1">
                            <span class="h-4 w-4 rounded-full border border-white/20" :style="{ backgroundColor: selectedOrder.primary_color }" />
                            <span class="h-4 w-4 rounded-full border border-white/20" :style="{ backgroundColor: selectedOrder.secondary_color }" />
                            <span class="h-4 w-4 rounded-full border border-white/20" :style="{ backgroundColor: selectedOrder.accent_color }" />
                        </div>
                        <p class="text-xs text-center text-slate-400">{{ selectedOrder.font_style }} • Qty {{ selectedOrder.quantity }}</p>
                        <Link :href="route('admin.design.index')" class="text-center text-xs text-indigo-400 hover:text-indigo-300">
                            View original design request
                        </Link>
                    </div>

                    <!-- Delivery + cost -->
                    <div class="flex flex-col gap-4 w-full sm:w-2/3">
                        <div class="border border-white/10 rounded-xl p-3 bg-slate-900/40">
                            <p class="text-sm font-bold text-white mb-2">
                                <font-awesome-icon icon="fa-solid fa-location-dot" class="text-indigo-400" />
                                Delivery Address
                            </p>
                            <p class="text-sm text-slate-300" v-if="selectedOrder.address.recipient_name && selectedOrder.address.contact_number">
                                {{ selectedOrder.address.recipient_name }} • {{ selectedOrder.address.contact_number }}
                            </p>
                            <p class="text-sm text-slate-300" v-if="selectedOrder.address.line1 && selectedOrder.address.province">
                                {{ selectedOrder.address.line1 }}<span v-if="selectedOrder.address.barangay">, {{ selectedOrder.address.barangay }}</span>,
                                {{ selectedOrder.address.city }}, {{ selectedOrder.address.province }} {{ selectedOrder.address.postal_code }}
                            </p>
                            <p class="text-xs text-slate-500 mt-1">
                                Address is managed by the customer. Ask them to update it from their account.
                            </p>
                        </div>

                        <div class="border border-white/10 rounded-xl p-3 bg-slate-900/40">
                            <p class="text-sm font-bold text-white mb-2">
                                <font-awesome-icon icon="fa-solid fa-receipt" class="text-indigo-400" />
                                Cost Breakdown
                            </p>
                            <div class="flex justify-between text-sm text-slate-300">
                                <span>{{ selectedOrder.quantity }} × {{ formatCurrency(selectedOrder.unit_price) }}</span>
                                <span>{{ formatCurrency(selectedOrder.quantity * selectedOrder.unit_price) }}</span>
                            </div>
                            <div class="flex justify-between text-sm text-slate-300">
                                <span>Shipping fee</span>
                                <span>{{ selectedOrder.shipping_fee !== null ? formatCurrency(selectedOrder.shipping_fee) : "To be determined" }}</span>
                            </div>
                            <hr class="my-2 border-white/10" />
                            <div class="flex justify-between text-sm font-semibold text-white">
                                <span>Total</span>
                                <span>{{ orderTotal(selectedOrder) !== null ? formatCurrency(orderTotal(selectedOrder)!) : "Pending shipping fee" }}</span>
                            </div>
                        </div>

                        <div class="border border-white/10 rounded-xl p-3 bg-slate-900/40">
                            <p class="text-sm font-bold text-white mb-2">
                                <font-awesome-icon icon="fa-solid fa-truck" class="text-indigo-400" />
                                Shipping Status
                            </p>
                            <span class="inline-block rounded-full px-2.5 py-1 text-xs font-medium border" :class="statusBadge[selectedOrder.status].class">
                                {{ statusBadge[selectedOrder.status].label }}
                            </span>

                            <div v-if="selectedOrder.courier_receipt" class="mt-2 text-sm text-slate-300 space-y-0.5">
                                <p>Courier: {{ courierFor(selectedOrder)?.name ?? "Unknown courier" }}</p>
                                <p>Transaction #: {{ selectedOrder.courier_receipt.transaction_number }}</p>
                                <p>Shipping fee (from receipt): {{ formatCurrency(selectedOrder.courier_receipt.shipping_fee) }}</p>
                                <p>Shipped: {{ formatDate(selectedOrder.courier_receipt.date_shipped) }}</p>
                                <p v-if="selectedOrder.courier_receipt.remarks">Remarks: {{ selectedOrder.courier_receipt.remarks }}</p>
                                <a
                                    v-if="courierFor(selectedOrder)"
                                    :href="courierFor(selectedOrder)!.site"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="inline-flex items-center gap-1 mt-1 text-indigo-400 hover:text-indigo-300"
                                >
                                    <font-awesome-icon icon="fa-solid fa-arrow-up-right-from-square" />
                                    Track Package
                                </a>
                            </div>
                            <p v-else class="mt-2 text-xs text-slate-500">No courier receipt attached yet.</p>
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
        <Modal :show="modal.type.value === 'UpdateStatus'" @close="closeModal" :maxWidth="'md'">
            <div v-if="selectedOrder" class="px-4 pt-5 pb-4 sm:p-6 bg-surface-card text-slate-200">
                <h2 class="text-lg font-semibold text-white">
                    <font-awesome-icon :icon="modal.icon.value" class="text-indigo-400" />
                    {{ modal.title.value }}
                </h2>
                <p class="mt-1 text-xs text-slate-500">{{ selectedOrder.order_number }} — {{ selectedOrder.team_name }}</p>
                <hr class="my-3 border-white/10" />

                <label class="block text-sm font-medium text-slate-300 mb-1">Move to</label>
                <select
                    v-model="statusForm.status"
                    class="w-full rounded-md bg-slate-900/60 border border-white/10 text-slate-200 text-sm py-2 px-3 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500"
                >
                    <option v-for="s in nextAllowedStatuses(selectedOrder.status)" :key="s" :value="s">
                        {{ statusBadge[s].label }}
                    </option>
                </select>
                <p v-if="statusForm.errors.status" class="mt-1 text-xs text-rose-400">{{ statusForm.errors.status }}</p>

                <div v-if="needsCourierReceipt" class="mt-4 border-t border-white/10 pt-4 space-y-3">
                    <p class="text-xs text-slate-500">
                        No courier API is connected — enter the transaction number and shipping fee from the courier's receipt. The
                        tracking site is pulled from the Courier module, so there's nothing to paste in for that.
                    </p>

                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-1">Courier</label>
                        <select
                            v-model.number="statusForm.courier_id"
                            class="w-full rounded-md bg-slate-900/60 border border-white/10 text-slate-200 text-sm py-2 px-3 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500"
                        >
                            <option :value="null" disabled>Select courier</option>
                            <option v-for="c in courierOptions" :key="c.id" :value="c.id">{{ c.name }}</option>
                        </select>
                        <p v-if="courierOptions.length === 0" class="mt-1 text-xs text-rose-400">
                            No active couriers found. Add or activate one in the Couriers module first.
                        </p>
                        <p v-if="statusForm.errors.courier_id" class="mt-1 text-xs text-rose-400">{{ statusForm.errors.courier_id }}</p>
                    </div>

                    <div>
                        <label for="transaction_number" class="block text-sm font-medium text-slate-300 mb-1">Transaction / Waybill No.</label>
                        <input
                            id="transaction_number"
                            v-model="statusForm.transaction_number"
                            type="text"
                            name="transaction_number"
                            placeholder="e.g. JT-88213764521"
                            class="w-full rounded-md bg-slate-900/60 border border-white/10 text-slate-200 placeholder-slate-500 text-sm py-2 px-3 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500"
                        />
                        <p v-if="statusForm.errors.transaction_number" class="mt-1 text-xs text-rose-400">{{ statusForm.errors.transaction_number }}</p>
                    </div>

                    <div>
                        <label for="shipping_fee" class="block text-sm font-medium text-slate-300 mb-1">Shipping Fee (from receipt)</label>
                        <input
                            id="shipping_fee"
                            v-model.number="statusForm.shipping_fee"
                            type="number"
                            name="shipping_fee"
                            min="0"
                            step="0.01"
                            placeholder="e.g. 380"
                            class="w-full rounded-md bg-slate-900/60 border border-white/10 text-slate-200 placeholder-slate-500 text-sm py-2 px-3 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500"
                        />
                        <p v-if="statusForm.errors.shipping_fee" class="mt-1 text-xs text-rose-400">{{ statusForm.errors.shipping_fee }}</p>
                    </div>

                    <div>
                        <label for="remarks" class="block text-sm font-medium text-slate-300 mb-1">Remarks (optional)</label>
                        <input
                            id="remarks"
                            v-model="statusForm.remarks"
                            type="text"
                            name="remarks"
                            placeholder="e.g. 3 boxes"
                            class="w-full rounded-md bg-slate-900/60 border border-white/10 text-slate-200 placeholder-slate-500 text-sm py-2 px-3 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500"
                        />
                        <p v-if="statusForm.errors.remarks" class="mt-1 text-xs text-rose-400">{{ statusForm.errors.remarks }}</p>
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
                            <font-awesome-icon icon="fa-solid fa-spinner" spin />
                        </div>
                        Save
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
