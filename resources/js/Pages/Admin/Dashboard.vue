<script setup lang="ts">
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { Head, Link, usePage } from "@inertiajs/vue3";
import { computed } from "vue";

interface StatBlock {
    value: number;
    change: number;
    trend?: number[];
}

interface Stats {
    totalOrders: StatBlock;
    revenue: StatBlock;
    pendingDesignRequests: StatBlock;
    pendingGcash: StatBlock;
    unreadMessages: StatBlock;
    newUsers: StatBlock;
}

interface BestSellingTemplate {
    name: string;
    sold: number;
    orderCount: number;
    unitPrice: number;
    image: string | null;
    sport: string | null;
    badge: string | null;
}

interface RecentOrder {
    id: string;
    db_id: number;
    customer: string;
    template: string;
    amount: number;
    status: string;
    courier: string;
    date: string;
}

interface NeedsAttentionItem {
    label: string;
    count: number;
    icon: string;
    href: string;
}

interface DesignRequestItem {
    customer: string;
    status: string;
    date: string;
}

interface GcashTransaction {
    ref: string;
    customer: string;
    amount: number;
    status: string;
}

interface RecentMessage {
    customer: string;
    preview: string;
    time: string;
    design_request_id: number | null;
}

const props = defineProps<{
    stats: Stats;
    bestSellingTemplates: BestSellingTemplate[];
    recentOrders: RecentOrder[];
    needsAttention: NeedsAttentionItem[];
    designRequests: DesignRequestItem[];
    gcashTransactions: GcashTransaction[];
    recentMessages: RecentMessage[];
}>();

const maxSold = computed(() =>
    Math.max(...props.bestSellingTemplates.map((t) => t.sold), 1),
);

// Cycled per-card accent so the best-selling row doesn't read as one flat
// block of indigo, mirroring the varied palette in the reference.
const cardAccents = [
    { text: "text-cyan-400", bar: "bg-gradient-to-r from-cyan-500 to-blue-500" },
    { text: "text-emerald-400", bar: "bg-gradient-to-r from-emerald-500 to-teal-500" },
    { text: "text-amber-400", bar: "bg-gradient-to-r from-amber-500 to-orange-500" },
];

function badgeAccent(badge: string) {
    // Solid fills — this pill now sits directly over the white jersey
    // photo, so the old translucent-on-dark treatment lost all contrast.
    const map: Record<string, string> = {
        Hot: "bg-amber-500 text-white border-amber-600",
        New: "bg-indigo-600 text-white border-indigo-700",
        Bestseller: "bg-emerald-600 text-white border-emerald-700",
    };
    return { pill: map[badge] ?? "bg-slate-600 text-white border-slate-700" };
}

const user = computed(() => usePage().props.auth?.user as any);
const firstName = computed(
    () => user.value?.user_info?.first_name ?? user.value?.email ?? "there",
);

const greeting = computed(() => {
    const hour = new Date().getHours();
    if (hour < 12) return "Good morning";
    if (hour < 18) return "Good afternoon";
    return "Good evening";
});

const todayLabel = computed(() =>
    new Date().toLocaleDateString("en-US", {
        weekday: "long",
        month: "long",
        day: "numeric",
    }),
);

const statusSummary = computed(() => {
    const parts: string[] = [];
    if (props.stats.pendingDesignRequests.value > 0) {
        parts.push(
            `${props.stats.pendingDesignRequests.value} design request${props.stats.pendingDesignRequests.value === 1 ? "" : "s"} waiting on your review`,
        );
    }
    if (props.stats.pendingGcash.value > 0) {
        parts.push(
            `${props.stats.pendingGcash.value} GCash payment${props.stats.pendingGcash.value === 1 ? "" : "s"} to verify`,
        );
    }
    if (parts.length === 0) return "Everything is clear — no items waiting on you right now.";
    return parts.join(" and ") + ". Everything else is clear.";
});

function formatCurrency(value: number) {
    return new Intl.NumberFormat("en-PH", {
        style: "currency",
        currency: "PHP",
    }).format(value);
}

// Real 7-day revenue trend, plotted as an SVG sparkline (viewBox 100x32).
const SPARK_W = 100;
const SPARK_H = 32;
const SPARK_PAD = 4;

const sparklinePoints = computed(() => {
    const trend = props.stats.revenue.trend ?? [];
    if (trend.length < 2) return [];

    const max = Math.max(...trend, 1);
    const min = Math.min(...trend, 0);
    const range = max - min || 1;
    const step = SPARK_W / (trend.length - 1);

    return trend.map((v, i) => {
        const x = i * step;
        const y = SPARK_H - SPARK_PAD - ((v - min) / range) * (SPARK_H - SPARK_PAD * 2);
        return { x, y };
    });
});

const sparklineLinePath = computed(() => {
    const pts = sparklinePoints.value;
    if (pts.length === 0) return "";
    return pts.map((p, i) => `${i === 0 ? "M" : "L"}${p.x.toFixed(1)},${p.y.toFixed(1)}`).join(" ");
});

const sparklineAreaPath = computed(() => {
    const pts = sparklinePoints.value;
    if (pts.length === 0) return "";
    const line = sparklineLinePath.value;
    const last = pts[pts.length - 1];
    const first = pts[0];
    return `${line} L${last.x.toFixed(1)},${SPARK_H} L${first.x.toFixed(1)},${SPARK_H} Z`;
});

function statusChip(status: string) {
    const map: Record<string, string> = {
        Processing: "bg-indigo-500/15 text-indigo-300 border-indigo-500/25",
        "In Production": "bg-violet-500/15 text-violet-300 border-violet-500/25",
        "Ready for Delivery": "bg-cyan-500/15 text-cyan-300 border-cyan-500/25",
        Shipped: "bg-purple-500/15 text-purple-300 border-purple-500/25",
        Delivered: "bg-emerald-500/15 text-emerald-300 border-emerald-500/25",
        Completed: "bg-emerald-500/15 text-emerald-300 border-emerald-500/25",
        New: "bg-indigo-500/15 text-indigo-300 border-indigo-500/25",
        "In Discussion": "bg-sky-500/15 text-sky-300 border-sky-500/25",
        "Revision Requested": "bg-orange-500/15 text-orange-300 border-orange-500/25",
        "Waiting for Payment": "bg-amber-500/15 text-amber-300 border-amber-500/25",
        "In Review": "bg-amber-500/15 text-amber-300 border-amber-500/25",
        Approved: "bg-emerald-500/15 text-emerald-300 border-emerald-500/25",
        Rejected: "bg-rose-500/15 text-rose-300 border-rose-500/25",
        Pending: "bg-amber-500/15 text-amber-300 border-amber-500/25",
        Verified: "bg-emerald-500/15 text-emerald-300 border-emerald-500/25",
    };
    return map[status] ?? "bg-slate-700/40 text-slate-300 border-white/5";
}
</script>

<template>
    <Head title="Dashboard" />

    <AdminLayout>
        <div class="space-y-8">
            <!-- Hero -->
            <section v-reveal class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-stretch">
                <div class="lg:col-span-8 flex flex-col justify-between py-2">
                    <div class="space-y-2.5">
                        <div class="text-[11px] font-bold uppercase tracking-[0.18em] text-indigo-400/90 flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
                            {{ todayLabel }}
                        </div>
                        <h1 class="text-3xl sm:text-4xl font-extrabold text-white tracking-tight">
                            {{ greeting }}, {{ firstName }}.
                        </h1>
                        <p class="text-sm sm:text-base text-slate-400 max-w-xl leading-relaxed">
                            {{ statusSummary }}
                        </p>
                    </div>
                    <div class="flex flex-wrap items-center gap-3 pt-5 sm:pt-4">
                        <Link
                            :href="route('admin.design.index')"
                            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-semibold text-white shadow-lg shadow-indigo-600/20 bg-gradient-to-r from-indigo-600 via-indigo-500 to-blue-600 hover:from-indigo-500 hover:to-blue-500 transition-all active:scale-[0.98]"
                        >
                            <font-awesome-icon icon="fa-solid fa-circle-check" class="text-[13px]" />
                            Review design requests
                        </Link>
                        <Link
                            :href="route('admin.orders.index')"
                            class="inline-flex items-center justify-center px-4 py-2.5 rounded-xl text-xs font-medium text-slate-300 bg-surface-card hover:bg-slate-800/80 border border-surface-border hover:border-slate-600/50 transition-all active:scale-[0.98]"
                        >
                            Open orders
                        </Link>
                    </div>
                </div>

                <!-- Revenue card -->
                <Link
                    :href="route('admin.orders.index')"
                    class="lg:col-span-4 glass-panel rounded-2xl p-5 relative overflow-hidden flex flex-col justify-between shadow-xl transition-all hover:-translate-y-0.5 hover:border-indigo-500/30 cursor-pointer"
                >
                    <div class="relative z-10">
                        <div class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Revenue this week</div>
                        <div class="flex items-baseline gap-1 mt-1.5">
                            <span class="text-3xl font-extrabold text-white tracking-tight">{{ formatCurrency(stats.revenue.value).replace(/\.\d{2}$/, "") }}</span>
                        </div>
                        <div
                            class="flex items-center gap-1.5 mt-2 text-xs font-semibold"
                            :class="stats.revenue.change >= 0 ? 'text-emerald-400' : 'text-rose-400'"
                        >
                            <font-awesome-icon :icon="stats.revenue.change >= 0 ? 'fa-solid fa-arrow-trend-up' : 'fa-solid fa-arrow-trend-down'" />
                            <span>{{ stats.revenue.change >= 0 ? "+" : "" }}{{ stats.revenue.change }}% <span class="text-slate-400 font-normal">vs last week</span></span>
                        </div>
                    </div>

                    <!-- Real 7-day revenue sparkline -->
                    <div v-if="sparklinePoints.length > 1" class="relative z-10 mt-4 -mx-1">
                        <svg viewBox="0 0 100 32" preserveAspectRatio="none" class="w-full h-12">
                            <defs>
                                <linearGradient id="revenueSparkFill" x1="0" y1="0" x2="0" y2="1">
                                    <stop offset="0%" stop-color="#06b6d4" stop-opacity="0.35" />
                                    <stop offset="100%" stop-color="#2563eb" stop-opacity="0" />
                                </linearGradient>
                                <linearGradient id="revenueSparkLine" x1="0" y1="0" x2="1" y2="0">
                                    <stop offset="0%" stop-color="#06b6d4" />
                                    <stop offset="100%" stop-color="#6366f1" />
                                </linearGradient>
                            </defs>
                            <path :d="sparklineAreaPath" fill="url(#revenueSparkFill)" stroke="none" />
                            <path :d="sparklineLinePath" fill="none" stroke="url(#revenueSparkLine)" stroke-width="2" vector-effect="non-scaling-stroke" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        <div class="flex justify-between text-[9px] text-slate-500 font-medium mt-1 px-0.5">
                            <span>6 days ago</span>
                            <span>Today</span>
                        </div>
                    </div>
                </Link>
            </section>

            <!-- Quick stats -->
            <section v-reveal="80" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3.5 sm:gap-4">
                <Link :href="route('admin.orders.index')" class="glass-panel p-4 rounded-xl flex flex-col justify-between transition-all hover:-translate-y-0.5 hover:border-indigo-500/30 cursor-pointer">
                    <span class="text-xs font-medium text-slate-400 truncate">Total orders</span>
                    <div class="text-2xl font-bold text-white mt-1.5">{{ stats.totalOrders.value }}</div>
                </Link>
                <Link :href="route('admin.design.index')" class="glass-panel p-4 rounded-xl flex flex-col justify-between border-indigo-500/20 transition-all hover:-translate-y-0.5 hover:border-indigo-500/30 cursor-pointer">
                    <span class="text-xs font-medium text-slate-400 truncate">Design requests</span>
                    <div class="text-2xl font-bold text-white mt-1.5 flex items-center justify-between">
                        <span>{{ stats.pendingDesignRequests.value }}</span>
                        <span v-if="stats.pendingDesignRequests.value > 0" class="w-1.5 h-1.5 rounded-full bg-indigo-400"></span>
                    </div>
                </Link>
                <Link :href="route('admin.design.index')" class="glass-panel p-4 rounded-xl flex flex-col justify-between transition-all hover:-translate-y-0.5 hover:border-indigo-500/30 cursor-pointer">
                    <span class="text-xs font-medium text-slate-400 truncate">Pending GCash</span>
                    <div class="text-2xl font-bold text-slate-300 mt-1.5">{{ stats.pendingGcash.value }}</div>
                </Link>
                <Link :href="route('admin.messages.index')" class="glass-panel p-4 rounded-xl flex flex-col justify-between transition-all hover:-translate-y-0.5 hover:border-indigo-500/30 cursor-pointer">
                    <span class="text-xs font-medium text-slate-400 truncate">Unread messages</span>
                    <div class="text-2xl font-bold text-slate-300 mt-1.5">{{ stats.unreadMessages.value }}</div>
                </Link>
                <Link :href="route('admin.users.index')" class="glass-panel p-4 rounded-xl flex flex-col justify-between col-span-2 sm:col-span-1 transition-all hover:-translate-y-0.5 hover:border-indigo-500/30 cursor-pointer">
                    <span class="text-xs font-medium text-slate-400 truncate">New users</span>
                    <div class="text-2xl font-bold text-white mt-1.5">{{ stats.newUsers.value }}</div>
                </Link>
            </section>

            <!-- Needs attention -->
            <section class="space-y-4" v-if="needsAttention.length" v-reveal="140">
                <div class="flex items-center gap-2 px-1">
                    <h2 class="text-sm font-bold uppercase tracking-wider text-slate-200">Needs attention</h2>
                    <span class="text-xs px-2 py-0.5 rounded-md font-semibold bg-slate-800 text-slate-400 border border-white/5">
                        {{ needsAttention.length }} item{{ needsAttention.length === 1 ? "" : "s" }}
                    </span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                    <Link
                        v-for="(n, i) in needsAttention"
                        :key="n.label"
                        :href="n.href"
                        v-reveal="(i % 3) * 70"
                        class="glass-panel rounded-xl p-4 flex items-center gap-3.5 transition-all hover:-translate-y-0.5 hover:border-amber-500/30 cursor-pointer"
                    >
                        <div class="w-10 h-10 rounded-xl bg-amber-500/10 border border-amber-500/20 text-amber-400 flex items-center justify-center shrink-0">
                            <font-awesome-icon :icon="n.icon" />
                        </div>
                        <div class="min-w-0">
                            <h3 class="text-xs font-semibold text-slate-100 truncate">{{ n.label }}</h3>
                            <p class="text-[11px] text-slate-400 mt-0.5">{{ n.count }} item{{ n.count === 1 ? "" : "s" }}</p>
                        </div>
                    </Link>
                </div>
            </section>

            <!-- Best-selling templates -->
            <section class="space-y-5 pt-2" v-reveal="200">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div class="space-y-1">
                        <h2 class="text-lg sm:text-xl font-bold text-white tracking-tight">Best-Selling Templates</h2>
                        <p class="text-xs sm:text-sm text-slate-400">Top jersey templates by units sold.</p>
                    </div>
                </div>

                <div v-if="bestSellingTemplates.length" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                    <Link
                        v-for="(t, i) in bestSellingTemplates"
                        :key="t.name"
                        :href="route('admin.jersey.index')"
                        v-reveal="(i % 3) * 70"
                        class="group glass-panel rounded-2xl p-4 flex flex-col gap-3.5 transition-all duration-300 hover:-translate-y-1 hover:border-indigo-500/30 hover:shadow-xl hover:shadow-indigo-950/40 cursor-pointer"
                    >
                        <div class="relative w-full h-40 rounded-xl bg-white border border-white/5 flex items-center justify-center overflow-hidden">
                            <span
                                v-if="t.badge"
                                class="absolute top-2.5 left-2.5 z-10 text-[10px] font-bold px-2.5 py-1 rounded-full border backdrop-blur-md"
                                :class="badgeAccent(t.badge).pill"
                            >
                                {{ t.badge }}
                            </span>
                            <span class="absolute bottom-2.5 right-2.5 z-10 text-xs font-extrabold px-2.5 py-1 rounded-lg bg-slate-950/90 text-white border border-white/10 shadow-lg">
                                {{ formatCurrency(t.unitPrice) }}
                            </span>
                            <img
                                v-if="t.image"
                                :src="t.image"
                                :alt="t.name"
                                class="h-full w-full object-contain p-3 transition-transform duration-300 group-hover:scale-105"
                            />
                            <font-awesome-icon v-else icon="fa-solid fa-tshirt" class="text-3xl text-slate-600" />
                        </div>
                        <div class="space-y-2">
                            <div class="flex items-center justify-between text-[10px] font-bold uppercase tracking-wide">
                                <span :class="cardAccents[i % cardAccents.length].text">{{ t.sport ?? "Jersey" }}</span>
                                <span class="text-slate-500 font-medium normal-case">{{ t.orderCount }} order{{ t.orderCount === 1 ? "" : "s" }} placed</span>
                            </div>
                            <h3 class="text-sm font-bold text-white tracking-tight truncate group-hover:text-indigo-200 transition-colors">{{ t.name }}</h3>
                            <div class="space-y-1.5 pt-1">
                                <div class="flex justify-between items-center text-[11px]">
                                    <span class="text-slate-400">Units sold</span>
                                    <span class="text-indigo-300 font-semibold font-mono">{{ t.sold }}</span>
                                </div>
                                <div class="w-full h-1.5 rounded-full bg-slate-800/90 overflow-hidden">
                                    <div
                                        class="h-full rounded-full transition-all duration-500"
                                        :class="cardAccents[i % cardAccents.length].bar"
                                        :style="{ width: (t.sold / maxSold) * 100 + '%' }"
                                    ></div>
                                </div>
                            </div>
                        </div>
                        <div class="pt-3 mt-1 border-t border-white/5 flex items-center justify-between">
                            <span
                                class="text-xs font-semibold transition-colors"
                                :class="cardAccents[i % cardAccents.length].text"
                            >
                                View in catalog →
                            </span>
                        </div>
                    </Link>
                </div>
                <p v-else class="text-sm text-slate-500">No orders yet.</p>
            </section>

            <!-- Recent orders -->
            <section class="space-y-4 pt-2" v-reveal="260">
                <div class="flex items-center justify-between px-1">
                    <h2 class="text-sm font-bold uppercase tracking-wider text-slate-200">Recent orders</h2>
                    <Link :href="route('admin.orders.index')" class="text-xs font-semibold text-indigo-400 hover:text-indigo-300 flex items-center gap-1 transition-colors">
                        View all <span>→</span>
                    </Link>
                </div>

                <div v-if="recentOrders.length" class="glass-panel rounded-2xl overflow-hidden divide-y divide-white/5 shadow-md">
                    <Link
                        v-for="o in recentOrders"
                        :key="o.id"
                        :href="route('admin.orders.index', { order: o.db_id })"
                        class="p-4 sm:p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4 hover:bg-slate-800/30 transition-colors cursor-pointer"
                    >
                        <div class="flex items-center gap-3.5">
                            <div class="w-11 h-11 rounded-xl bg-indigo-500/10 border border-indigo-500/20 flex items-center justify-center shrink-0">
                                <font-awesome-icon icon="fa-solid fa-receipt" class="text-indigo-400" />
                            </div>
                            <div>
                                <div class="text-xs font-bold text-white tracking-wide">{{ o.customer }}</div>
                                <div class="text-[11px] text-slate-400 mt-0.5 font-mono">{{ o.id }} · {{ o.template }} · {{ o.date }}</div>
                            </div>
                        </div>
                        <div class="flex items-center justify-between sm:justify-end gap-6">
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold border" :class="statusChip(o.status)">
                                {{ o.status }}
                            </span>
                            <span class="text-sm font-bold text-white tracking-tight">{{ formatCurrency(o.amount) }}</span>
                        </div>
                    </Link>
                </div>
                <p v-else class="text-sm text-slate-500">No orders yet.</p>
            </section>

            <!-- Design Requests / GCash / Messages -->
            <section class="grid grid-cols-1 lg:grid-cols-3 gap-5 pb-2" v-reveal="320">
                <div class="glass-panel rounded-2xl p-5">
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-bold text-slate-200">Design Requests</h3>
                        <Link :href="route('admin.design.index')" class="text-xs font-semibold text-indigo-400 hover:text-indigo-300">View all →</Link>
                    </div>
                    <ul v-if="designRequests.length" class="mt-4 divide-y divide-white/5">
                        <li v-for="d in designRequests" :key="d.customer + d.date">
                            <Link :href="route('admin.design.index')" class="flex items-center justify-between py-2.5 text-sm hover:opacity-80 transition-opacity">
                                <div>
                                    <p class="text-slate-200">{{ d.customer }}</p>
                                    <p class="text-[11px] text-slate-500">{{ d.date }}</p>
                                </div>
                                <span class="rounded-full px-2.5 py-1 text-[11px] font-medium border" :class="statusChip(d.status)">{{ d.status }}</span>
                            </Link>
                        </li>
                    </ul>
                    <p v-else class="mt-4 text-sm text-slate-500">No design requests yet.</p>
                </div>

                <div class="glass-panel rounded-2xl p-5">
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-bold text-slate-200">GCash Transactions</h3>
                        <Link :href="route('admin.gcash.index')" class="text-xs font-semibold text-indigo-400 hover:text-indigo-300">View all →</Link>
                    </div>
                    <ul v-if="gcashTransactions.length" class="mt-4 divide-y divide-white/5">
                        <li v-for="g in gcashTransactions" :key="g.ref">
                            <Link :href="route('admin.gcash.index')" class="flex items-center justify-between py-2.5 text-sm hover:opacity-80 transition-opacity">
                                <div>
                                    <p class="text-slate-200">{{ g.customer }}</p>
                                    <p class="text-[11px] text-slate-500 font-mono">{{ g.ref }} · {{ formatCurrency(g.amount) }}</p>
                                </div>
                                <span class="rounded-full px-2.5 py-1 text-[11px] font-medium border" :class="statusChip(g.status)">{{ g.status }}</span>
                            </Link>
                        </li>
                    </ul>
                    <p v-else class="mt-4 text-sm text-slate-500">No GCash transactions yet.</p>
                </div>

                <div class="glass-panel rounded-2xl p-5">
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-bold text-slate-200">Recent Messages</h3>
                        <Link :href="route('admin.messages.index')" class="text-xs font-semibold text-indigo-400 hover:text-indigo-300">View all →</Link>
                    </div>
                    <ul v-if="recentMessages.length" class="mt-4 divide-y divide-white/5">
                        <li v-for="m in recentMessages" :key="m.customer + m.time">
                            <Link
                                :href="m.design_request_id ? route('admin.messages.index', { design_request_id: m.design_request_id }) : route('admin.messages.index')"
                                class="block py-2.5 text-sm hover:opacity-80 transition-opacity"
                            >
                                <div class="flex items-center justify-between">
                                    <p class="font-medium text-slate-200">{{ m.customer }}</p>
                                    <p class="text-[11px] text-slate-500">{{ m.time }}</p>
                                </div>
                                <p class="mt-0.5 truncate text-xs text-slate-500">{{ m.preview }}</p>
                            </Link>
                        </li>
                    </ul>
                    <p v-else class="mt-4 text-sm text-slate-500">No messages yet.</p>
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
.glass-panel:hover {
    border-color: rgba(99, 102, 241, 0.22);
}
</style>
