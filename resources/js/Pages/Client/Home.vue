<script setup lang="ts">
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import Card from "@/Components/Card.vue";
import CustomizeDesignModal from "@/Components/CustomizeDesignModal.vue";
import type { ActiveOrder, JerseyTemplate, OrderStatus } from "@/types/jersey";
import { Head, Link, usePage } from "@inertiajs/vue3";
import { ref, computed } from "vue";

// Templates come from the admin-managed catalog (only "active" ones are sent).
const props = defineProps<{
    data?: JerseyTemplate[];
    activeOrder?: ActiveOrder | null;
    featuredJerseyId?: number | null;
    pendingDesignRequestsCount?: number;
}>();

const page = usePage();
const firstName = computed(() => {
    const user = page.props.auth?.user as any;
    return user?.user_info?.first_name ?? "there";
});

const jerseyTemplates = computed<JerseyTemplate[]>(() => props.data ?? []);

// Home only teases the catalog — 6 templates max — with a link through to
// the full browsable Catalogue page for search/sport filtering.
const PREVIEW_COUNT = 8;
const previewTemplates = computed<JerseyTemplate[]>(() => jerseyTemplates.value.slice(0, PREVIEW_COUNT));

// Feature the real best-seller (most sets ordered across all customers) —
// updates automatically as orders come in, no manual "set as featured" field.
// Falls back to badge priority (Hot > Bestseller > New), then the most
// recently added template, for a brand-new shop with no order history yet.
const featuredTemplate = computed<JerseyTemplate | null>(() => {
    const bestSeller = props.featuredJerseyId
        ? jerseyTemplates.value.find((t) => t.id === props.featuredJerseyId)
        : null;
    if (bestSeller) return bestSeller;

    const priority = { Hot: 0, Bestseller: 1, New: 2 } as const;
    const badged = jerseyTemplates.value
        .filter((t) => t.badge)
        .sort((a, b) => (priority[a.badge!] ?? 9) - (priority[b.badge!] ?? 9));
    return badged[0] ?? jerseyTemplates.value[0] ?? null;
});

function formatPrice(value: number) {
    return `₱${value.toLocaleString("en-PH")}`;
}

// --- Active order pipeline (real data) ---
const ORDER_STAGES: { value: OrderStatus; label: string }[] = [
    { value: "processing", label: "Processing" },
    { value: "in_production", label: "In Production" },
    { value: "ready_for_delivery", label: "Ready for Delivery" },
    { value: "shipped", label: "Shipped" },
    { value: "delivered", label: "Delivered" },
];

const activeStageIndex = computed(() => {
    if (!props.activeOrder) return -1;
    return ORDER_STAGES.findIndex((s) => s.value === props.activeOrder!.status);
});

function formatDate(value: string) {
    return new Date(value).toLocaleDateString("en-PH", {
        year: "numeric",
        month: "short",
        day: "numeric",
    });
}

// Track which template the customer clicked so the modal can show its image
const selectedTemplate = ref<JerseyTemplate | null>(null);
const showCustomizeModal = ref(false);

function handleSelect(id: number) {
    selectedTemplate.value = jerseyTemplates.value.find((t) => t.id === id) ?? null;
    showCustomizeModal.value = true;
}

function closeCustomizeModal() {
    showCustomizeModal.value = false;
    selectedTemplate.value = null;
}
</script>

<template>
    <Head title="Jersey Templates" />

    <AuthenticatedLayout>
        <!-- HERO -->
        <section v-reveal class="relative rounded-3xl bg-white border border-ink/10 shadow-xl shadow-cobalt/5 overflow-hidden mb-8">
            <div class="absolute -top-24 -left-24 w-96 h-96 rounded-full bg-cobalt/5 blur-3xl pointer-events-none"></div>
            <div class="absolute -bottom-20 -right-20 w-80 h-80 rounded-full bg-accent/5 blur-3xl pointer-events-none"></div>
            <div class="relative grid grid-cols-1 lg:grid-cols-12 gap-6 p-6 sm:p-10 items-center">
                <!-- Left -->
                <div class="lg:col-span-7 flex flex-col items-start gap-5">
                    <div class="flex items-center gap-2 text-cobalt font-black uppercase tracking-widest text-xs">
                        <font-awesome-icon icon="fa-solid fa-bolt" />
                        <span>Full-Dye Sublimation Studio</span>
                    </div>
                    <div class="space-y-2">
                        <h1 class="text-3xl sm:text-5xl font-black tracking-tight text-ink leading-[1.12]">
                            Welcome back, {{ firstName }}.<br />
                            <span class="bg-gradient-to-r from-cobalt via-cobalt to-accent bg-clip-text text-transparent">Let's build your kit.</span>
                        </h1>
                        <p class="text-sm sm:text-base text-ink/60 max-w-xl pt-1 leading-relaxed">
                            Pick a template, describe your team's colors and roster in one message, and we'll take it from design proof to doorstep.
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-3 pt-2 w-full">
                        <a
                            href="#catalog-grid"
                            class="flex items-center justify-center gap-2 bg-cobalt hover:bg-cobalt-dark text-white font-bold text-sm px-6 py-3.5 rounded-2xl shadow-lg shadow-cobalt/25 transition-all hover:scale-[1.02] active:scale-[0.98]"
                        >
                            <font-awesome-icon icon="fa-solid fa-shirt" />
                            <span>Explore the Catalog</span>
                        </a>
                        <Link
                            :href="route('client.chat.index')"
                            class="flex items-center justify-center gap-2 bg-ink/5 hover:bg-ink/10 text-ink font-bold text-sm px-5 py-3.5 rounded-2xl border border-ink/10 transition-all"
                        >
                            <font-awesome-icon icon="fa-solid fa-message" class="text-cobalt" />
                            <span>Message Us</span>
                        </Link>
                    </div>

                    <div class="grid grid-cols-3 gap-3 pt-2 w-full max-w-lg border-t border-ink/10">
                        <div class="flex items-center gap-2 pt-1">
                            <font-awesome-icon icon="fa-solid fa-circle-check" class="text-good text-[15px]" />
                            <div class="flex flex-col">
                                <span class="text-xs font-bold text-ink leading-none">GCash Verified</span>
                                <span class="text-[10px] text-ink/50">50% down payment</span>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 pt-1">
                            <font-awesome-icon icon="fa-solid fa-comments" class="text-cobalt text-[15px]" />
                            <div class="flex flex-col">
                                <span class="text-xs font-bold text-ink leading-none">Direct Chat</span>
                                <span class="text-[10px] text-ink/50">Real-time with our team</span>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 pt-1">
                            <font-awesome-icon icon="fa-solid fa-truck-fast" class="text-warn text-[15px]" />
                            <div class="flex flex-col">
                                <span class="text-xs font-bold text-ink leading-none">Tracked Delivery</span>
                                <span class="text-[10px] text-ink/50">Via your courier's page</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right: featured real template -->
                <div v-if="featuredTemplate" class="lg:col-span-5 relative">
                    <div class="relative bg-gradient-to-b from-ink/[0.02] to-ink/[0.05] rounded-2xl border border-ink/10 p-4 sm:p-5 shadow-inner flex flex-col items-center">
                        <div class="w-full flex items-center justify-between pb-3 border-b border-ink/10">
                            <div class="flex items-center gap-2">
                                <span class="px-2 py-0.5 rounded text-[10px] font-extrabold uppercase bg-cobalt text-white">Featured</span>
                                <span class="text-xs font-bold text-ink truncate max-w-[140px]">{{ featuredTemplate.name }}</span>
                            </div>
                            <span class="text-[11px] font-semibold text-ink/50">{{ featuredTemplate.sport }}</span>
                        </div>
                        <div class="relative w-full h-72 sm:h-80 flex items-center justify-center my-3 overflow-hidden rounded-xl bg-white group">
                            <img
                                :src="featuredTemplate.imagePath"
                                :alt="featuredTemplate.name"
                                class="w-full h-full object-contain p-2 transition-transform duration-500 group-hover:scale-105"
                            />
                            <div class="absolute top-3 right-3 bg-white/90 backdrop-blur-md text-ink px-2.5 py-1 rounded-lg text-xs font-black shadow-md border border-ink/10">
                                {{ formatPrice(featuredTemplate.price) }} <span class="text-[10px] font-medium text-ink/50">/set</span>
                            </div>
                        </div>
                        <div class="w-full flex items-center justify-between pt-1">
                            <div class="flex items-center gap-2">
                                <span class="text-[11px] font-semibold text-ink/50">Colorways:</span>
                                <div class="flex items-center gap-1.5">
                                    <span class="w-5 h-5 rounded-full ring-2 ring-cobalt ring-offset-1 shadow-sm" :style="{ backgroundColor: featuredTemplate.primaryColor }"></span>
                                    <span class="w-5 h-5 rounded-full ring-1 ring-ink/10 shadow-sm" :style="{ backgroundColor: featuredTemplate.secondaryColor }"></span>
                                    <span class="w-5 h-5 rounded-full ring-1 ring-ink/10 shadow-sm" :style="{ backgroundColor: featuredTemplate.accentColor }"></span>
                                </div>
                            </div>
                            <button
                                type="button"
                                class="flex items-center gap-1 text-xs font-extrabold text-cobalt hover:text-cobalt-dark group"
                                @click="handleSelect(featuredTemplate.id)"
                            >
                                <span>Customize This Kit</span>
                                <font-awesome-icon icon="fa-solid fa-arrow-right" class="text-[11px] transition-transform group-hover:translate-x-1" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ACTIVE ORDER PIPELINE (real) -->
        <section v-reveal="80" class="mb-8">
            <div v-if="activeOrder" class="rounded-2xl bg-white border border-ink/10 p-5 shadow-sm">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 mb-4 pb-3 border-b border-ink/10">
                    <div class="flex items-center gap-3">
                        <div class="flex items-center justify-center w-8 h-8 rounded-xl bg-cobalt/10 text-cobalt">
                            <font-awesome-icon icon="fa-solid fa-truck" />
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-black uppercase text-ink">{{ activeOrder.order_number }}</span>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-cobalt/10 text-cobalt uppercase tracking-wider flex items-center gap-1">
                                    <span class="w-1.5 h-1.5 rounded-full bg-cobalt animate-pulse"></span>
                                    {{ ORDER_STAGES.find(s => s.value === activeOrder!.status)?.label ?? activeOrder.status }}
                                </span>
                            </div>
                            <span class="text-xs text-ink/50 font-medium">{{ activeOrder.team_name }} • {{ activeOrder.quantity }} sets • {{ activeOrder.template_name }}</span>
                        </div>
                    </div>
                    <Link
                        :href="route('client.orders.index')"
                        class="px-3 py-1.5 rounded-xl bg-ink/5 hover:bg-ink/10 text-ink text-xs font-bold flex items-center gap-1.5 transition-all shrink-0"
                    >
                        <font-awesome-icon icon="fa-solid fa-eye" class="text-cobalt" />
                        <span>View Order</span>
                    </Link>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                    <div
                        v-for="(stage, i) in ORDER_STAGES"
                        :key="stage.value"
                        class="flex items-start gap-3 p-3 rounded-xl border"
                        :class="i < activeStageIndex
                            ? 'bg-good/5 border-good/20'
                            : i === activeStageIndex
                                ? 'bg-cobalt/5 border-cobalt/20 shadow-xs'
                                : 'bg-ink/[0.02] border-dashed border-ink/10 opacity-70'"
                    >
                        <div
                            class="w-7 h-7 rounded-full flex items-center justify-center shrink-0 mt-0.5 font-bold text-xs"
                            :class="i < activeStageIndex
                                ? 'bg-good text-white'
                                : i === activeStageIndex
                                    ? 'bg-cobalt text-white'
                                    : 'bg-ink/10 text-ink/40'"
                        >
                            <font-awesome-icon v-if="i < activeStageIndex" icon="fa-solid fa-check" class="text-[11px]" />
                            <span v-else>{{ i + 1 }}</span>
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs font-bold text-ink">{{ stage.label }}</p>
                            <p v-if="stage.value === 'shipped' && activeOrder.courier_receipt" class="text-[11px] text-ink/50 truncate">
                                {{ activeOrder.courier_receipt.courier?.name }} • {{ activeOrder.courier_receipt.transaction_number }}
                            </p>
                            <p v-else-if="i === activeStageIndex" class="text-[11px] text-cobalt font-semibold">In progress</p>
                            <p v-else-if="i < activeStageIndex" class="text-[11px] text-ink/50">Done</p>
                            <p v-else class="text-[11px] text-ink/40">Upcoming</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty state -->
            <div v-else class="rounded-2xl bg-white border border-dashed border-ink/15 p-6 flex flex-col sm:flex-row items-center justify-between gap-4 text-center sm:text-left">
                <div class="flex items-center gap-3">
                    <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-ink/5 text-ink/40">
                        <font-awesome-icon icon="fa-solid fa-box" />
                    </div>
                    <div>
                        <p class="text-sm font-bold text-ink">No active order yet</p>
                        <p class="text-xs text-ink/50">
                            {{ pendingDesignRequestsCount ? `You have ${pendingDesignRequestsCount} design request${pendingDesignRequestsCount === 1 ? '' : 's'} in review.` : "Pick a template below to start your first design request." }}
                        </p>
                    </div>
                </div>
                <a href="#catalog-grid" class="px-4 py-2 rounded-xl bg-cobalt/10 text-cobalt text-xs font-bold hover:bg-cobalt/20 transition-all shrink-0">
                    Browse Catalog
                </a>
            </div>
        </section>

        <!-- CATALOG PREVIEW -->
        <section id="catalog-grid" v-reveal="140">
            <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 mb-5">
                <div>
                    <div class="flex items-center gap-1.5 text-accent font-black uppercase text-xs tracking-wider">
                        <font-awesome-icon icon="fa-solid fa-shirt" class="text-[13px]" />
                        <span>Live Catalog</span>
                    </div>
                    <h2 class="text-2xl sm:text-3xl font-black text-ink tracking-tight">Full-Dye Sublimation Catalog</h2>
                    <p class="text-sm text-ink/50 mt-1">A quick look at {{ previewTemplates.length }} of our {{ jerseyTemplates.length }} templates.</p>
                </div>
                <Link
                    :href="route('client.catalogue.index')"
                    class="flex items-center justify-center gap-2 bg-ink/5 hover:bg-ink/10 text-ink font-bold text-sm px-5 py-3 rounded-2xl border border-ink/10 transition-all shrink-0"
                >
                    <span>Browse Full Catalogue</span>
                    <font-awesome-icon icon="fa-solid fa-arrow-right" class="text-xs" />
                </Link>
            </div>

            <div
                v-if="previewTemplates.length"
                class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
            >
                <Card
                    v-for="(template, i) in previewTemplates"
                    :key="template.id"
                    v-reveal="(i % 4) * 70"
                    :template="template"
                    @select="handleSelect"
                />
            </div>

            <div v-else class="py-16 text-center text-sm text-ink/50">
                No templates available yet. Check back soon.
            </div>

            <div v-if="jerseyTemplates.length > PREVIEW_COUNT" class="flex justify-center mt-6">
                <Link
                    :href="route('client.catalogue.index')"
                    class="flex items-center justify-center gap-2 bg-cobalt hover:bg-cobalt-dark text-white font-bold text-sm px-6 py-3 rounded-2xl shadow-lg shadow-cobalt/25 transition-all hover:scale-[1.02] active:scale-[0.98]"
                >
                    <font-awesome-icon icon="fa-solid fa-layer-group" />
                    <span>See All {{ jerseyTemplates.length }} Templates</span>
                </Link>
            </div>
        </section>

        <CustomizeDesignModal :show="showCustomizeModal" :template="selectedTemplate" @close="closeCustomizeModal" />
    </AuthenticatedLayout>
</template>
