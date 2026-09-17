<script setup lang="ts">
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { Head, router } from "@inertiajs/vue3";
import { computed } from "vue";

interface TrendPoint {
    date: string;
    label: string;
    revenue?: number;
    orders?: number;
    users?: number;
}

interface SalesData {
    totalRevenue: number;
    totalOrders: number;
    avgOrderValue: number;
    revenueTrend: TrendPoint[];
    ordersTrend: TrendPoint[];
    statusBreakdown: Record<string, number>;
    bestSelling: { name: string; sold: number; orderCount: number }[];
}

interface DesignPipelineData {
    total: number;
    approved: number;
    cancelled: number;
    inProgress: number;
    approvalRate: number;
    avgTurnaroundDays: number | null;
    statusBreakdown: Record<string, number>;
}

interface UsersData {
    totalClients: number;
    newClients: number;
    newUsersTrend: TrendPoint[];
}

interface ShippingData {
    perCourier: { name: string; shipments: number }[];
    shippedOrDelivered: number;
    awaitingShipment: number;
}

const props = defineProps<{
    range: string;
    sales: SalesData;
    designPipeline: DesignPipelineData;
    users: UsersData;
    shipping: ShippingData;
}>();

const ranges = [
    { value: "7", label: "7 days" },
    { value: "30", label: "30 days" },
    { value: "90", label: "90 days" },
];

function setRange(value: string) {
    router.get(route("admin.reports"), { range: value }, { preserveState: true, preserveScroll: true, replace: true });
}

function formatCurrency(value: number) {
    return new Intl.NumberFormat("en-PH", {
        style: "currency",
        currency: "PHP",
    }).format(value).replace(/\.\d{2}$/, "");
}

const orderStatusLabels: Record<string, string> = {
    processing: "Processing",
    in_production: "In Production",
    ready_for_delivery: "Ready for Delivery",
    shipped: "Shipped",
    delivered: "Delivered",
    completed: "Completed",
};

const designStatusLabels: Record<string, string> = {
    pending_review: "New",
    in_discussion: "In Discussion",
    revision_requested: "Revision Requested",
    waiting_for_down_payment: "Waiting for Payment",
    pending_down_payment_review: "In Review",
    approved: "Approved",
    cancelled: "Rejected",
};

function buildLinePath(points: TrendPoint[], key: "revenue" | "orders" | "users", w = 100, h = 36, pad = 4) {
    const values = points.map((p) => p[key] ?? 0);
    if (values.length < 2) return { line: "", area: "" };

    const max = Math.max(...values, 1);
    const min = Math.min(...values, 0);
    const range = max - min || 1;
    const step = w / (values.length - 1);

    const coords = values.map((v, i) => ({
        x: i * step,
        y: h - pad - ((v - min) / range) * (h - pad * 2),
    }));

    const line = coords.map((c, i) => `${i === 0 ? "M" : "L"}${c.x.toFixed(1)},${c.y.toFixed(1)}`).join(" ");
    const area = `${line} L${coords[coords.length - 1].x.toFixed(1)},${h} L${coords[0].x.toFixed(1)},${h} Z`;

    return { line, area };
}

const revenueChart = computed(() => buildLinePath(props.sales.revenueTrend, "revenue"));
const usersChart = computed(() => buildLinePath(props.users.newUsersTrend, "users"));

const totalOrderStatuses = computed(() =>
    Object.values(props.sales.statusBreakdown).reduce((a, b) => a + b, 0),
);
const maxOrderStatus = computed(() => Math.max(...Object.values(props.sales.statusBreakdown), 1));

const totalDesignStatuses = computed(() =>
    Object.values(props.designPipeline.statusBreakdown).reduce((a, b) => a + b, 0),
);
const maxDesignStatus = computed(() => Math.max(...Object.values(props.designPipeline.statusBreakdown), 1));

const maxCourierShipments = computed(() =>
    Math.max(...props.shipping.perCourier.map((c) => c.shipments), 1),
);

const statusColors: Record<string, string> = {
    processing: "bg-indigo-500",
    in_production: "bg-violet-500",
    ready_for_delivery: "bg-cyan-500",
    shipped: "bg-purple-500",
    delivered: "bg-emerald-500",
    completed: "bg-emerald-600",
    pending_review: "bg-indigo-500",
    in_discussion: "bg-sky-500",
    revision_requested: "bg-orange-500",
    waiting_for_down_payment: "bg-amber-500",
    pending_down_payment_review: "bg-amber-600",
    approved: "bg-emerald-500",
    cancelled: "bg-rose-500",
};
</script>

<template>
    <Head title="Reports" />

    <AdminLayout>
        <div class="space-y-8">
            <!-- Header -->
            <section class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="space-y-1">
                    <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight">Reports</h1>
                    <p class="text-sm text-slate-400">Sales, design pipeline, users, and shipping — in one place.</p>
                </div>
                <div class="inline-flex items-center gap-1 p-1 rounded-xl bg-surface-card border border-surface-border">
                    <button
                        v-for="r in ranges"
                        :key="r.value"
                        @click="setRange(r.value)"
                        class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition-colors"
                        :class="range === r.value ? 'bg-indigo-600 text-white' : 'text-slate-400 hover:text-slate-200'"
                    >
                        {{ r.label }}
                    </button>
                </div>
            </section>

            <!-- Sales -->
            <section class="space-y-4">
                <h2 class="text-sm font-bold uppercase tracking-wider text-slate-200 px-1">Sales &amp; Revenue</h2>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="glass-panel rounded-xl p-4">
                        <span class="text-xs font-medium text-slate-400">Total revenue</span>
                        <div class="text-2xl font-bold text-white mt-1.5">{{ formatCurrency(sales.totalRevenue) }}</div>
                    </div>
                    <div class="glass-panel rounded-xl p-4">
                        <span class="text-xs font-medium text-slate-400">Total orders</span>
                        <div class="text-2xl font-bold text-white mt-1.5">{{ sales.totalOrders }}</div>
                    </div>
                    <div class="glass-panel rounded-xl p-4">
                        <span class="text-xs font-medium text-slate-400">Avg. order value</span>
                        <div class="text-2xl font-bold text-white mt-1.5">{{ formatCurrency(sales.avgOrderValue) }}</div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                    <div class="lg:col-span-2 glass-panel rounded-2xl p-5">
                        <h3 class="text-sm font-bold text-slate-200 mb-3">Revenue trend</h3>
                        <svg v-if="revenueChart.line" viewBox="0 0 100 36" preserveAspectRatio="none" class="w-full h-32">
                            <defs>
                                <linearGradient id="reportsRevenueFill" x1="0" y1="0" x2="0" y2="1">
                                    <stop offset="0%" stop-color="#06b6d4" stop-opacity="0.35" />
                                    <stop offset="100%" stop-color="#2563eb" stop-opacity="0" />
                                </linearGradient>
                                <linearGradient id="reportsRevenueLine" x1="0" y1="0" x2="1" y2="0">
                                    <stop offset="0%" stop-color="#06b6d4" />
                                    <stop offset="100%" stop-color="#6366f1" />
                                </linearGradient>
                            </defs>
                            <path :d="revenueChart.area" fill="url(#reportsRevenueFill)" stroke="none" />
                            <path :d="revenueChart.line" fill="none" stroke="url(#reportsRevenueLine)" stroke-width="1.5" vector-effect="non-scaling-stroke" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        <div class="flex justify-between text-[10px] text-slate-500 font-medium mt-2">
                            <span>{{ sales.revenueTrend[0]?.label }}</span>
                            <span>{{ sales.revenueTrend[sales.revenueTrend.length - 1]?.label }}</span>
                        </div>
                    </div>

                    <div class="glass-panel rounded-2xl p-5">
                        <h3 class="text-sm font-bold text-slate-200 mb-3">Orders by status</h3>
                        <div v-if="totalOrderStatuses > 0" class="space-y-2.5">
                            <div v-for="(count, status) in sales.statusBreakdown" :key="status" v-show="count > 0">
                                <div class="flex justify-between text-[11px] text-slate-400 mb-1">
                                    <span>{{ orderStatusLabels[status] ?? status }}</span>
                                    <span class="font-mono text-slate-300">{{ count }}</span>
                                </div>
                                <div class="w-full h-1.5 rounded-full bg-slate-800/90 overflow-hidden">
                                    <div class="h-full rounded-full" :class="statusColors[status] ?? 'bg-slate-500'" :style="{ width: (count / maxOrderStatus) * 100 + '%' }"></div>
                                </div>
                            </div>
                        </div>
                        <p v-else class="text-sm text-slate-500">No orders in this range.</p>
                    </div>
                </div>

                <div class="glass-panel rounded-2xl overflow-hidden">
                    <div class="p-5 pb-0">
                        <h3 class="text-sm font-bold text-slate-200">Best-selling templates</h3>
                    </div>
                    <div v-if="sales.bestSelling.length" class="divide-y divide-white/5 mt-3">
                        <div v-for="t in sales.bestSelling" :key="t.name" class="px-5 py-3 flex items-center justify-between">
                            <span class="text-sm text-slate-200">{{ t.name }}</span>
                            <span class="text-xs text-slate-400">{{ t.sold }} sold · {{ t.orderCount }} order{{ t.orderCount === 1 ? "" : "s" }}</span>
                        </div>
                    </div>
                    <p v-else class="px-5 py-4 text-sm text-slate-500">No orders in this range.</p>
                </div>
            </section>

            <!-- Design pipeline -->
            <section class="space-y-4">
                <h2 class="text-sm font-bold uppercase tracking-wider text-slate-200 px-1">Design Requests Pipeline</h2>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    <div class="glass-panel rounded-xl p-4">
                        <span class="text-xs font-medium text-slate-400">Total requests</span>
                        <div class="text-2xl font-bold text-white mt-1.5">{{ designPipeline.total }}</div>
                    </div>
                    <div class="glass-panel rounded-xl p-4">
                        <span class="text-xs font-medium text-slate-400">Approval rate</span>
                        <div class="text-2xl font-bold text-emerald-400 mt-1.5">{{ designPipeline.approvalRate }}%</div>
                    </div>
                    <div class="glass-panel rounded-xl p-4">
                        <span class="text-xs font-medium text-slate-400">Avg. turnaround</span>
                        <div class="text-2xl font-bold text-white mt-1.5">
                            {{ designPipeline.avgTurnaroundDays !== null ? designPipeline.avgTurnaroundDays + 'd' : '—' }}
                        </div>
                    </div>
                    <div class="glass-panel rounded-xl p-4">
                        <span class="text-xs font-medium text-slate-400">In progress</span>
                        <div class="text-2xl font-bold text-white mt-1.5">{{ designPipeline.inProgress }}</div>
                    </div>
                </div>

                <div class="glass-panel rounded-2xl p-5">
                    <h3 class="text-sm font-bold text-slate-200 mb-3">Requests by status</h3>
                    <div v-if="totalDesignStatuses > 0" class="space-y-2.5">
                        <div v-for="(count, status) in designPipeline.statusBreakdown" :key="status" v-show="count > 0">
                            <div class="flex justify-between text-[11px] text-slate-400 mb-1">
                                <span>{{ designStatusLabels[status] ?? status }}</span>
                                <span class="font-mono text-slate-300">{{ count }}</span>
                            </div>
                            <div class="w-full h-1.5 rounded-full bg-slate-800/90 overflow-hidden">
                                <div class="h-full rounded-full" :class="statusColors[status] ?? 'bg-slate-500'" :style="{ width: (count / maxDesignStatus) * 100 + '%' }"></div>
                            </div>
                        </div>
                    </div>
                    <p v-else class="text-sm text-slate-500">No design requests in this range.</p>
                </div>
            </section>

            <!-- Users -->
            <section class="space-y-4">
                <h2 class="text-sm font-bold uppercase tracking-wider text-slate-200 px-1">Users &amp; Growth</h2>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                    <div class="glass-panel rounded-xl p-4">
                        <span class="text-xs font-medium text-slate-400">Total clients</span>
                        <div class="text-2xl font-bold text-white mt-1.5">{{ users.totalClients }}</div>
                    </div>
                    <div class="glass-panel rounded-xl p-4">
                        <span class="text-xs font-medium text-slate-400">New clients (this range)</span>
                        <div class="text-2xl font-bold text-white mt-1.5">{{ users.newClients }}</div>
                    </div>
                    <div class="lg:col-span-1 glass-panel rounded-2xl p-5 col-span-2">
                        <h3 class="text-xs font-bold text-slate-200 mb-2">Signups trend</h3>
                        <svg v-if="usersChart.line" viewBox="0 0 100 36" preserveAspectRatio="none" class="w-full h-16">
                            <path :d="usersChart.area" fill="#6366f1" fill-opacity="0.15" stroke="none" />
                            <path :d="usersChart.line" fill="none" stroke="#818cf8" stroke-width="1.5" vector-effect="non-scaling-stroke" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </div>
                </div>
            </section>

            <!-- Shipping -->
            <section class="space-y-4 pb-4">
                <h2 class="text-sm font-bold uppercase tracking-wider text-slate-200 px-1">Shipping &amp; Couriers</h2>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                    <div class="glass-panel rounded-xl p-4">
                        <span class="text-xs font-medium text-slate-400">Shipped or delivered</span>
                        <div class="text-2xl font-bold text-emerald-400 mt-1.5">{{ shipping.shippedOrDelivered }}</div>
                    </div>
                    <div class="glass-panel rounded-xl p-4">
                        <span class="text-xs font-medium text-slate-400">Awaiting shipment</span>
                        <div class="text-2xl font-bold text-amber-400 mt-1.5">{{ shipping.awaitingShipment }}</div>
                    </div>
                    <div class="lg:col-span-1 glass-panel rounded-2xl p-5 col-span-2">
                        <h3 class="text-xs font-bold text-slate-200 mb-3">Shipments per courier</h3>
                        <div v-if="shipping.perCourier.length" class="space-y-2.5">
                            <div v-for="c in shipping.perCourier" :key="c.name">
                                <div class="flex justify-between text-[11px] text-slate-400 mb-1">
                                    <span>{{ c.name }}</span>
                                    <span class="font-mono text-slate-300">{{ c.shipments }}</span>
                                </div>
                                <div class="w-full h-1.5 rounded-full bg-slate-800/90 overflow-hidden">
                                    <div class="h-full rounded-full bg-gradient-to-r from-cyan-500 to-blue-500" :style="{ width: (c.shipments / maxCourierShipments) * 100 + '%' }"></div>
                                </div>
                            </div>
                        </div>
                        <p v-else class="text-sm text-slate-500">No couriers set up yet.</p>
                    </div>
                </div>
            </section>
        </div>
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
