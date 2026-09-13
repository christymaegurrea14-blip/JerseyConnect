<script setup lang="ts">
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, Link, router, useForm, usePoll } from "@inertiajs/vue3";
import { ref, computed, onMounted } from "vue";
import type {
    MessageThread,
    ConversationStage,
} from "@/types/messages";

// ── Props ────────────────────────────────────────────────────────────────────
// Arriving from a "Message" button on the client's Design Request or Order tables:
//   route('client.chat.index', { design_request_id: row.id })              // from Design Requests
//   route('client.chat.index', { design_request_id: row.design_request_id }) // from Orders
// Both resolve to the SAME thread, since a thread is keyed by design_request_id.
const props = withDefaults(
    defineProps<{
        threads?: MessageThread[];
        design_request_id?: number | string | null;
    }>(),
    {
        threads: () => [],
        design_request_id: null,
    },
);

// Threads/messages are fully server-driven now — no local mock data and no
// client-side stub creation. A thread always exists once a design request
// has been submitted (created server-side in DesignRequest::booted()).
usePoll(1500, { only: ["threads"] });

// ── State ────────────────────────────────────────────────────────────────────
type TabKey = "all" | "design" | "order";
const activeTab = ref<TabKey>("all");
const search = ref("");
const activeThread = ref<MessageThread | null>(null);
const replyText = ref<string>("");
const replyImage = ref<File | null>(null);
const replyImagePreview = ref<string | null>(null);
const threadBody = ref<HTMLElement | null>(null);

const tabs: { key: TabKey; label: string }[] = [
    { key: "all", label: "All" },
    { key: "design", label: "In Design" },
    { key: "order", label: "In Production" },
];

// Quick-fill shortcuts — these just pre-fill the same real reply textarea,
// nothing more. Not tied to any canned-response backend feature.
const QUICK_REPLIES = [
    "Looks perfect, ready to print!",
    "Can we adjust the colors a bit?",
    "What's the status on this?",
    "Please confirm once payment is received.",
];

// ── Computed ─────────────────────────────────────────────────────────────────
const threads = computed<MessageThread[]>(() => props.threads ?? []);

const unreadCount = computed<number>(
    () => threads.value.filter((t) => !t.read).length,
);

const sorted = computed<MessageThread[]>(() =>
    [...threads.value].sort(
        (a, b) =>
            new Date(b.updated_at).getTime() - new Date(a.updated_at).getTime(),
    ),
);

const filtered = computed<MessageThread[]>(() => {
    let list = sorted.value;
    if (activeTab.value === "design")
        list = list.filter((t) => t.stage === "design");
    else if (activeTab.value === "order")
        list = list.filter((t) => t.stage === "order");

    const q = search.value.trim().toLowerCase();
    if (q) {
        list = list.filter(
            (t) =>
                t.team_name.toLowerCase().includes(q) ||
                t.template_name.toLowerCase().includes(q) ||
                (t.order_ref ?? t.design_request_ref).toLowerCase().includes(q),
        );
    }
    return list;
});

// Keep activeThread pointing at the live object from `threads` after every
// poll/refresh, instead of a stale snapshot.
const liveActiveThread = computed<MessageThread | null>(() => {
    if (!activeThread.value) return null;
    return (
        threads.value.find((t) => t.id === activeThread.value!.id) ??
        activeThread.value
    );
});

const subtotal = computed(() => {
    if (!liveActiveThread.value || !liveActiveThread.value.quantity) return null;
    return liveActiveThread.value.quantity * liveActiveThread.value.unit_price;
});

const sizeDistribution = computed(() => {
    if (!liveActiveThread.value) return [];
    const counts: Record<string, number> = {};
    for (const p of liveActiveThread.value.players) {
        if (!p.size) continue;
        counts[p.size] = (counts[p.size] ?? 0) + 1;
    }
    return Object.entries(counts);
});

function lastMessagePreview(thread: MessageThread): string {
    const last = thread.messages[thread.messages.length - 1];
    if (!last) return "No messages yet";
    if (last.body) return last.body;
    return last.attachment_url ? "📷 Image" : "No messages yet";
}

function formatPrice(value: number) {
    return `₱${value.toLocaleString("en-PH")}`;
}

// ── Helpers ──────────────────────────────────────────────────────────────────
function stagePillClass(stage: ConversationStage): string {
    return stage === "design"
        ? "bg-purple-100 text-purple-700"
        : "bg-emerald-100 text-emerald-700";
}

function stagePillLabel(stage: ConversationStage): string {
    return stage === "design" ? "Design Request" : "Order";
}

// ── Actions ──────────────────────────────────────────────────────────────────
function openThread(thread: MessageThread): void {
    activeThread.value = thread;
    replyText.value = "";

    if (!thread.read) {
        router.patch(
            route("client.chat.mark-read", thread.id),
            {},
            {
                preserveScroll: true,
                preserveState: true,
                only: ["threads"],
            },
        );
    }

    scrollThread();
}

function scrollThread(): void {
    setTimeout(() => {
        if (threadBody.value)
            threadBody.value.scrollTop = threadBody.value.scrollHeight;
    }, 50);
}

function handleImageSelect(e: Event): void {
    const target = e.target as HTMLInputElement;
    const file = target.files?.[0];
    if (!file) return;
    replyImage.value = file;
    replyImagePreview.value = URL.createObjectURL(file);
    target.value = ""; // allow re-selecting the same file later
}

function removeReplyImage(): void {
    replyImage.value = null;
    replyImagePreview.value = null;
}

function openAttachment(url: string): void {
    window.open(url, "_blank");
}

function useQuickReply(text: string): void {
    replyText.value = text;
}

function sendReply(thread: MessageThread): void {
    if ((!replyText.value.trim() && !replyImage.value) || thread.closed)
        return;

    const form = useForm({
        body: replyText.value.trim(),
        image: replyImage.value,
    });

    form.post(route("client.chat.reply", thread.id), {
        forceFormData: true,
        preserveScroll: true,
        preserveState: true,
        only: ["threads"],
        onSuccess: () => {
            replyText.value = "";
            removeReplyImage();
            scrollThread();
        },
    });
}

/** Opens the thread tied to a given design_request_id, if it exists. */
function resolveAndOpenByDesignRequestId(designRequestId: number): void {
    const thread = threads.value.find(
        (t) => t.design_request_id === designRequestId,
    );
    if (thread) openThread(thread);
}

onMounted(() => {
    if (props.design_request_id) {
        resolveAndOpenByDesignRequestId(Number(props.design_request_id));
    }
});
</script>

<template>
    <Head title="Artist Chat" />

    <AuthenticatedLayout>
        <!-- Header -->
        <section v-reveal class="rounded-2xl bg-white border border-ink/10 shadow-sm p-5 sm:p-6 mb-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-3.5">
                    <div class="w-11 h-11 rounded-xl bg-cobalt/10 text-cobalt flex items-center justify-center border border-cobalt/20 shrink-0">
                        <font-awesome-icon icon="fa-solid fa-comments" class="text-lg" />
                    </div>
                    <div>
                        <h1 class="text-xl font-black tracking-tight text-ink">Artist Chat</h1>
                        <p class="text-sm text-ink/50 mt-0.5">
                            Your design and order conversations with our team.
                        </p>
                    </div>
                </div>
                <span
                    v-if="unreadCount"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-warn/10 text-warn text-xs font-bold self-start sm:self-auto"
                >
                    <span class="w-1.5 h-1.5 rounded-full bg-warn animate-pulse"></span>
                    {{ unreadCount }} unread
                </span>
            </div>
        </section>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <div
                v-reveal="80"
                class="lg:col-span-9 flex h-[calc(100dvh-260px)] min-h-[520px] rounded-2xl border border-ink/10 bg-white shadow-sm overflow-hidden"
            >
                <!-- Thread list -->
                <aside
                    class="w-full md:w-80 flex-shrink-0 border-r border-ink/10 flex-col"
                    :class="activeThread ? 'hidden md:flex' : 'flex'"
                >
                    <div class="p-4 border-b border-ink/10 space-y-3">
                        <div class="relative">
                            <font-awesome-icon icon="fa-solid fa-magnifying-glass" class="absolute left-3 top-1/2 -translate-y-1/2 text-ink/30 text-xs" />
                            <input
                                v-model="search"
                                type="text"
                                placeholder="Search team or design..."
                                class="w-full pl-8 pr-3 py-2 text-sm rounded-xl border border-ink/15 outline-none focus:border-cobalt focus:ring-1 focus:ring-cobalt"
                            />
                        </div>
                        <div class="flex items-center gap-1 bg-ink/5 rounded-xl p-1">
                            <button
                                v-for="tab in tabs"
                                :key="tab.key"
                                type="button"
                                class="flex-1 py-1.5 rounded-lg text-[11px] font-bold transition-all"
                                :class="
                                    activeTab === tab.key
                                        ? 'bg-white text-cobalt shadow-sm'
                                        : 'text-ink/50 hover:text-ink'
                                "
                                @click="activeTab = tab.key"
                            >
                                {{ tab.label }}
                            </button>
                        </div>
                    </div>

                    <div class="flex-1 overflow-y-auto client-scrollbar">
                        <div
                            v-for="thread in filtered"
                            :key="thread.id"
                            class="relative flex cursor-pointer gap-2.5 border-b border-ink/[0.06] px-4 py-3 transition hover:bg-ink/[0.02]"
                            :class="{
                                'bg-cobalt/5 hover:bg-cobalt/5':
                                    activeThread?.id === thread.id,
                            }"
                            @click="openThread(thread)"
                        >
                            <img
                                :src="thread.template_image"
                                :alt="thread.template_name"
                                class="h-10 w-10 flex-shrink-0 rounded-lg object-contain bg-ink/[0.03] p-1 border border-ink/10"
                            />
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-1.5">
                                    <span
                                        class="truncate text-xs text-ink"
                                        :class="thread.read ? 'font-medium' : 'font-bold'"
                                    >
                                        {{ thread.team_name }}
                                    </span>
                                    <span
                                        v-if="!thread.read"
                                        class="h-1.5 w-1.5 flex-shrink-0 rounded-full bg-warn"
                                    />
                                </div>
                                <div class="mt-1 flex flex-wrap items-center gap-1">
                                    <span
                                        class="rounded-full px-1.5 py-0.5 text-[9px] font-semibold"
                                        :class="stagePillClass(thread.stage)"
                                    >
                                        {{ stagePillLabel(thread.stage) }}
                                    </span>
                                    <span
                                        class="rounded-full px-1.5 py-0.5 text-[9px] font-semibold"
                                        :class="thread.status_class"
                                    >
                                        {{ thread.status_label }}
                                    </span>
                                </div>
                                <p class="mt-1 truncate text-xs text-ink/50">
                                    {{ lastMessagePreview(thread) }}
                                </p>
                            </div>
                        </div>

                        <div
                            v-if="filtered.length === 0"
                            class="flex flex-col items-center gap-2 py-16 text-center text-ink/30"
                        >
                            <font-awesome-icon icon="fa-solid fa-inbox" class="text-2xl" />
                            <p class="text-sm">No conversations here</p>
                        </div>
                    </div>
                </aside>

                <main
                    class="flex-1 flex-col overflow-hidden"
                    :class="activeThread ? 'flex' : 'hidden md:flex'"
                >
                    <!-- Empty state -->
                    <div
                        v-if="!liveActiveThread"
                        class="flex flex-1 flex-col items-center justify-center text-ink/30 gap-3"
                    >
                        <font-awesome-icon icon="fa-solid fa-inbox" class="text-4xl" />
                        <p class="font-medium text-ink/50">Select a conversation</p>
                        <p class="text-sm">Or tap "Message" on a design request or order</p>
                    </div>

                    <!-- Thread view -->
                    <template v-else>
                        <!-- Thread header -->
                        <div class="flex flex-wrap items-start justify-between gap-2 border-b border-ink/10 px-4 sm:px-6 py-4">
                            <div class="flex items-start gap-3 min-w-0">
                                <button
                                    class="md:hidden mt-0.5 -ml-1 flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-lg text-ink/50 hover:bg-ink/5 transition"
                                    aria-label="Back to messages"
                                    @click="activeThread = null"
                                >
                                    <font-awesome-icon icon="fa-solid fa-arrow-left" class="text-xs" />
                                </button>
                                <img
                                    :src="liveActiveThread.template_image"
                                    :alt="liveActiveThread.template_name"
                                    class="h-10 w-10 flex-shrink-0 rounded-lg object-contain bg-ink/[0.03] p-1 border border-ink/10"
                                />
                                <div class="min-w-0">
                                    <h3 class="text-sm font-bold text-ink truncate">
                                        {{ liveActiveThread.template_name }} — {{ liveActiveThread.team_name }}
                                    </h3>
                                    <div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-ink/50">
                                        <span class="font-mono bg-ink/5 rounded px-1">
                                            {{ liveActiveThread.order_ref ?? liveActiveThread.design_request_ref }}
                                        </span>
                                        <span
                                            class="rounded-full px-2 py-0.5 text-[10px] font-semibold"
                                            :class="stagePillClass(liveActiveThread.stage)"
                                        >
                                            {{ stagePillLabel(liveActiveThread.stage) }}
                                        </span>
                                        <span
                                            class="rounded-full px-2 py-0.5 text-[10px] font-semibold"
                                            :class="liveActiveThread.status_class"
                                        >
                                            {{ liveActiveThread.status_label }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="flex gap-2 flex-shrink-0">
                                <Link
                                    v-if="liveActiveThread.stage === 'order'"
                                    :href="route('client.orders.index')"
                                    class="flex items-center gap-1.5 rounded-lg border border-ink/10 px-3 py-1.5 text-sm text-ink/70 hover:bg-ink/5 transition"
                                >
                                    <font-awesome-icon icon="fa-solid fa-box" />
                                    View order
                                </Link>
                                <Link
                                    v-else
                                    :href="route('client.design.show', liveActiveThread.design_request_id)"
                                    class="flex items-center gap-1.5 rounded-lg border border-ink/10 px-3 py-1.5 text-sm text-ink/70 hover:bg-ink/5 transition"
                                >
                                    <font-awesome-icon icon="fa-solid fa-tshirt" />
                                    View design request
                                </Link>
                            </div>
                        </div>

                        <!-- Messages -->
                        <div
                            ref="threadBody"
                            class="flex-1 overflow-y-auto client-scrollbar px-4 sm:px-6 py-4 space-y-3"
                        >
                            <div
                                v-if="liveActiveThread.messages.length === 0"
                                class="flex h-full flex-col items-center justify-center gap-2 text-center text-ink/30"
                            >
                                <font-awesome-icon icon="fa-solid fa-comment-dots" class="text-3xl" />
                                <p class="text-sm">No messages yet — ask a question about your design to get started.</p>
                            </div>
                            <div
                                v-for="msg in liveActiveThread.messages"
                                :key="msg.id"
                                class="flex"
                                :class="msg.from === 'client' ? 'justify-end' : 'justify-start'"
                            >
                                <div
                                    class="max-w-[85%] sm:max-w-[72%] rounded-2xl px-4 py-2.5 text-sm leading-relaxed"
                                    :class="
                                        msg.from === 'client'
                                            ? 'rounded-br-sm bg-cobalt text-white'
                                            : 'rounded-bl-sm border border-ink/10 bg-ink/[0.02] text-ink'
                                    "
                                >
                                    <p class="mb-0.5 text-[10px] font-bold uppercase tracking-wide opacity-60">
                                        {{ msg.name }}
                                    </p>
                                    <img
                                        v-if="msg.attachment_url"
                                        :src="msg.attachment_url"
                                        :alt="msg.attachment_name ?? 'Attached image'"
                                        class="mb-1.5 max-h-56 w-full rounded-lg border border-black/10 object-cover cursor-pointer"
                                        @click="openAttachment(msg.attachment_url!)"
                                    />
                                    <p v-if="msg.body">{{ msg.body }}</p>
                                    <p class="mt-1 text-right text-[10px] opacity-70">{{ msg.time }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Reply box -->
                        <div class="border-t border-ink/10 px-4 sm:px-6 py-4">
                            <div
                                v-if="liveActiveThread.closed"
                                class="rounded-xl bg-ink/[0.03] px-3 py-2 text-xs text-ink/50"
                            >
                                <font-awesome-icon icon="fa-solid fa-lock" />
                                This order is completed. The conversation is now read-only.
                            </div>
                            <template v-else>
                                <div class="flex items-center gap-1.5 overflow-x-auto pb-2 mb-1">
                                    <button
                                        v-for="prompt in QUICK_REPLIES"
                                        :key="prompt"
                                        type="button"
                                        class="whitespace-nowrap px-2.5 py-1 rounded-full border border-ink/10 bg-ink/[0.02] text-[11px] font-semibold text-ink/60 hover:border-cobalt/30 hover:text-cobalt transition-colors shrink-0"
                                        @click="useQuickReply(prompt)"
                                    >
                                        {{ prompt }}
                                    </button>
                                </div>
                                <div v-if="replyImagePreview" class="relative mb-2 inline-block">
                                    <img
                                        :src="replyImagePreview"
                                        alt="Attachment preview"
                                        class="max-h-32 rounded-lg border border-ink/10 object-cover"
                                    />
                                    <button
                                        type="button"
                                        class="absolute -right-2 -top-2 flex h-5 w-5 items-center justify-center rounded-full bg-accent text-[10px] text-white hover:bg-accent-dark"
                                        @click="removeReplyImage"
                                    >
                                        <font-awesome-icon icon="fa-solid fa-xmark" />
                                    </button>
                                </div>
                                <textarea
                                    v-model="replyText"
                                    rows="3"
                                    placeholder="Type your message here…"
                                    class="w-full resize-none rounded-xl border border-ink/15 px-3 py-2 text-sm text-ink focus:border-cobalt focus:outline-none focus:ring-2 focus:ring-cobalt/10"
                                    @keydown.ctrl.enter="sendReply(liveActiveThread)"
                                />
                                <div class="mt-2 flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <label
                                            class="flex cursor-pointer items-center gap-1.5 rounded-lg border border-ink/10 px-2.5 py-1.5 text-xs text-ink/60 hover:bg-ink/5 transition"
                                        >
                                            <font-awesome-icon icon="fa-solid fa-image" />
                                            Attach image
                                            <input type="file" accept="image/*" class="hidden" @change="handleImageSelect" />
                                        </label>
                                        <span class="hidden text-[11px] text-ink/40 sm:inline">Ctrl + Enter to send</span>
                                    </div>
                                    <button
                                        :disabled="!replyText.trim() && !replyImage"
                                        class="flex items-center gap-2 rounded-xl bg-cobalt px-4 py-1.5 text-sm font-bold text-white hover:bg-cobalt-dark transition disabled:cursor-not-allowed disabled:opacity-40"
                                        @click="sendReply(liveActiveThread)"
                                    >
                                        Send
                                        <font-awesome-icon icon="fa-solid fa-paper-plane" />
                                    </button>
                                </div>
                            </template>
                        </div>
                    </template>
                </main>
            </div>

            <!-- Order Specs panel (real data only) -->
            <aside v-if="liveActiveThread" v-reveal class="hidden lg:flex lg:col-span-3 flex-col gap-4">
                <div class="rounded-2xl bg-white border border-ink/10 shadow-sm p-5">
                    <h2 class="text-xs font-black uppercase tracking-wide text-ink/50 mb-3">Order Specs</h2>
                    <div class="aspect-square rounded-xl bg-ink/[0.02] border border-ink/10 flex items-center justify-center p-3 mb-3">
                        <img :src="liveActiveThread.template_image" :alt="liveActiveThread.template_name" class="w-full h-full object-contain" />
                    </div>
                    <p class="text-sm font-bold text-ink">{{ liveActiveThread.template_name }}</p>
                    <p class="text-xs text-ink/50">{{ liveActiveThread.team_name }}</p>

                    <div class="flex items-center gap-1.5 mt-3">
                        <span class="w-4 h-4 rounded-full ring-1 ring-ink/10 cursor-help" :title="liveActiveThread.primary_color" :style="{ backgroundColor: liveActiveThread.primary_color }"></span>
                        <span class="w-4 h-4 rounded-full ring-1 ring-ink/10 cursor-help" :title="liveActiveThread.secondary_color" :style="{ backgroundColor: liveActiveThread.secondary_color }"></span>
                        <span class="w-4 h-4 rounded-full ring-1 ring-ink/10 cursor-help" :title="liveActiveThread.accent_color" :style="{ backgroundColor: liveActiveThread.accent_color }"></span>
                        <span v-if="liveActiveThread.font_style" class="ml-auto text-[11px] text-ink/40">{{ liveActiveThread.font_style }}</span>
                    </div>

                    <div class="mt-4 pt-3 border-t border-ink/10 space-y-1.5 text-xs">
                        <div class="flex items-center justify-between">
                            <span class="text-ink/50">Price</span>
                            <span class="font-bold text-ink">{{ formatPrice(liveActiveThread.unit_price) }} /set</span>
                        </div>
                        <div v-if="liveActiveThread.quantity" class="flex items-center justify-between">
                            <span class="text-ink/50">Quantity</span>
                            <span class="font-bold text-ink">{{ liveActiveThread.quantity }} sets</span>
                        </div>
                        <div v-if="subtotal !== null" class="flex items-center justify-between">
                            <span class="text-ink/50">Subtotal</span>
                            <span class="font-bold text-ink">{{ formatPrice(subtotal) }}</span>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl bg-white border border-ink/10 shadow-sm p-5">
                    <div class="flex items-center justify-between mb-3">
                        <h2 class="text-xs font-black uppercase tracking-wide text-ink/50">
                            <font-awesome-icon icon="fa-solid fa-users" class="text-cobalt" />
                            Team Roster
                        </h2>
                        <span class="text-xs text-ink/40">{{ liveActiveThread.players.length }}</span>
                    </div>
                    <div v-if="sizeDistribution.length" class="flex flex-wrap gap-1.5 mb-3">
                        <span
                            v-for="[size, count] in sizeDistribution"
                            :key="size"
                            class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-bold bg-cobalt/10 text-cobalt"
                        >
                            {{ size }}
                            <span class="px-1 rounded-full bg-cobalt text-white text-[9px]">{{ count }}</span>
                        </span>
                    </div>
                    <p v-else class="text-xs text-ink/40 mb-3">No roster submitted yet.</p>
                    <Link
                        :href="route('client.design.roster', liveActiveThread.design_request_id)"
                        class="inline-flex items-center gap-1 text-xs font-bold text-cobalt hover:text-cobalt-dark"
                    >
                        Manage Roster
                        <font-awesome-icon icon="fa-solid fa-arrow-right" class="text-[10px]" />
                    </Link>
                </div>

                <Link
                    v-if="liveActiveThread.status_key === 'waiting_for_down_payment'"
                    :href="route('client.design.pay.show', liveActiveThread.design_request_id)"
                    class="flex items-center justify-center gap-1.5 rounded-xl bg-good text-white text-sm font-bold py-2.5 hover:bg-good/90 transition-colors"
                >
                    <font-awesome-icon icon="fa-solid fa-credit-card" />
                    Pay Now
                </Link>
            </aside>
        </div>
    </AuthenticatedLayout>
</template>
