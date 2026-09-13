<script setup lang="ts">
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { Head, Link, router, useForm, usePoll } from "@inertiajs/vue3";
import { ref, computed, onMounted } from "vue";
import { formatCurrency } from "@/Composables/shipping";
import type {
    MessageThread,
    ThreadMessage,
    ConversationStage,
} from "@/types/messages";

// ── Props ────────────────────────────────────────────────────────────────────
// Arriving from a "Message" button on the Design Request or Order tables:
//   route('admin.messages', { design_request_id: row.id })              // from Design Requests
//   route('admin.messages', { design_request_id: row.design_request_id }) // from Orders
// Both resolve to the SAME thread because a thread is keyed by design_request_id.
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
    { key: "order", label: "In Production / Delivery" },
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
                t.client_name.toLowerCase().includes(q) ||
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

// Read-receipt status for the admin's own latest message — the client side
// has no equivalent since it can't see the admin's read state.
const lastAdminMessageId = computed<string | null>(() => {
    if (!liveActiveThread.value) return null;
    const adminMessages = liveActiveThread.value.messages.filter((m) => m.from === "admin");
    return adminMessages.length ? adminMessages[adminMessages.length - 1].id : null;
});

function isSeenByClient(msg: ThreadMessage): boolean {
    const lastRead = liveActiveThread.value?.client_last_read_at;
    if (!lastRead) return false;
    return new Date(lastRead).getTime() >= new Date(msg.created_at).getTime();
}

function lastMessagePreview(thread: MessageThread): string {
    const last = thread.messages[thread.messages.length - 1];
    if (!last) return "No messages yet";
    if (last.body) return last.body;
    return last.attachment_url ? "Image" : "No messages yet";
}

// ── Helpers ──────────────────────────────────────────────────────────────────
function stagePillClass(stage: ConversationStage): string {
    return stage === "design"
        ? "bg-fuchsia-500/15 text-fuchsia-300 border-fuchsia-500/25"
        : "bg-emerald-500/15 text-emerald-300 border-emerald-500/25";
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
            route("admin.messages.mark-read", thread.id),
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

/** Admin-only: flag a conversation as unread again for later follow-up. */
function markThreadUnread(thread: MessageThread): void {
    router.patch(
        route("admin.messages.mark-unread", thread.id),
        {},
        {
            preserveScroll: true,
            preserveState: true,
            only: ["threads"],
        },
    );
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

// Canned replies — just prefill the composer, nothing fake about the send itself.
const quickReplies = [
    { label: "GCash payment confirmed", text: "Your GCash payment has been verified. We're moving your order into production now." },
    { label: "Production started", text: "Good news — production has started on your jersey order." },
    { label: "Confirm proof/specs", text: "Could you confirm the design proof and specs (colors, sizes, names/numbers) before we proceed?" },
];

function useQuickReply(text: string): void {
    replyText.value = text;
}

/** True when the active thread's design request looks like it's waiting on a GCash down payment. */
const showVerifyGcash = computed<boolean>(() => {
    const t = liveActiveThread.value;
    if (!t || t.stage !== "design") return false;
    return /down payment|gcash/i.test(t.status_label ?? "");
});

function sendReply(thread: MessageThread): void {
    if ((!replyText.value.trim() && !replyImage.value) || thread.closed)
        return;

    const form = useForm({
        body: replyText.value.trim(),
        image: replyImage.value,
    });

    form.post(route("admin.messages.reply", thread.id), {
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
    <Head title="Messages" />

    <AdminLayout>
        <div class="space-y-1">
            <div v-reveal class="flex items-center gap-3 mb-5">
                <div class="w-8 h-8 rounded-lg bg-indigo-600/20 text-indigo-400 border border-indigo-500/30 flex items-center justify-center">
                    <font-awesome-icon icon="fa-solid fa-comments" class="text-sm" />
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h1 class="text-xl font-extrabold text-white tracking-tight">Messages</h1>
                        <span v-if="unreadCount" class="rounded-full bg-indigo-600 px-2 py-0.5 text-[10px] font-bold text-white">
                            {{ unreadCount }}
                        </span>
                    </div>
                    <p class="text-xs text-slate-400">Conversations are opened from a Design Request or Order.</p>
                </div>
            </div>

            <div v-reveal="80" class="grid grid-cols-1 lg:grid-cols-12 gap-5 h-[calc(100dvh-220px)] md:h-[75dvh]">
            <div class="lg:col-span-9 flex gap-5 min-h-0">
                <aside
                    class="w-full md:w-[390px] flex-shrink-0 glass-panel rounded-2xl overflow-hidden flex-col"
                    :class="activeThread ? 'hidden md:flex' : 'flex'"
                >
                    <!-- Search + filter tabs -->
                    <div class="p-3 border-b border-white/5 shrink-0 space-y-2">
                        <div class="relative">
                            <font-awesome-icon icon="fa-solid fa-magnifying-glass" class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-500 text-xs" />
                            <input
                                v-model="search"
                                type="text"
                                placeholder="Search client, team, or design..."
                                class="w-full pl-8 pr-3 py-2 text-xs rounded-lg bg-slate-950/60 border border-white/5 text-slate-200 placeholder-slate-500 outline-none focus:border-indigo-500/50 focus:ring-1 focus:ring-indigo-500/30"
                            />
                        </div>
                        <div class="flex items-center bg-slate-950/60 p-1 rounded-lg border border-white/5">
                            <button
                                v-for="tab in tabs"
                                :key="tab.key"
                                class="flex-1 py-1.5 px-2 text-xs font-semibold rounded-md transition-all truncate"
                                :class="
                                    activeTab === tab.key
                                        ? 'bg-indigo-600 text-white shadow-sm'
                                        : 'text-slate-400 hover:text-slate-200'
                                "
                                @click="activeTab = tab.key"
                            >
                                {{ tab.label }}
                            </button>
                        </div>
                    </div>

                    <!-- Thread list -->
                    <div class="flex-1 overflow-y-auto">
                        <div
                            v-for="thread in filtered"
                            :key="thread.id"
                            class="relative flex cursor-pointer gap-2.5 border-b border-white/5 px-4 py-3 transition-colors hover:bg-slate-800/30"
                            :class="{
                                'bg-indigo-500/10 hover:bg-indigo-500/10': activeThread?.id === thread.id,
                                'border-l-2 border-l-indigo-500': !thread.read,
                            }"
                            @click="openThread(thread)"
                        >
                            <img
                                :src="thread.template_image"
                                :alt="thread.template_name"
                                class="h-9 w-9 flex-shrink-0 rounded-lg object-contain bg-white border border-white/10 p-1"
                            />
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-1.5">
                                    <span
                                        class="truncate text-xs font-medium text-slate-200"
                                        :class="{ 'font-semibold text-white': !thread.read }"
                                    >
                                        {{ thread.team_name }}
                                    </span>
                                    <span v-if="!thread.read" class="h-1.5 w-1.5 flex-shrink-0 rounded-full bg-indigo-400" />
                                </div>
                                <div class="mt-1 flex flex-wrap items-center gap-1">
                                    <span class="rounded-full px-1.5 py-0.5 text-[9px] font-semibold border" :class="stagePillClass(thread.stage)">
                                        {{ stagePillLabel(thread.stage) }}
                                    </span>
                                    <span class="rounded-full px-1.5 py-0.5 text-[9px] font-semibold" :class="thread.status_class">
                                        {{ thread.status_label }}
                                    </span>
                                    <span class="font-mono text-[9px] text-slate-500 bg-slate-900/60 rounded px-1">
                                        {{ thread.order_ref ?? thread.design_request_ref }}
                                    </span>
                                </div>
                                <p class="mt-1 truncate text-xs text-slate-500">{{ lastMessagePreview(thread) }}</p>
                            </div>
                        </div>

                        <div v-if="filtered.length === 0" class="flex flex-col items-center gap-2 py-16 text-center text-slate-500">
                            <font-awesome-icon icon="fa-solid fa-inbox" class="text-3xl" />
                            <p class="text-sm">No conversations here</p>
                        </div>
                    </div>
                </aside>

                <main class="flex-1 flex-col overflow-hidden glass-panel rounded-2xl" :class="activeThread ? 'flex' : 'hidden md:flex'">
                    <!-- Empty state -->
                    <div v-if="!liveActiveThread" class="flex flex-1 flex-col items-center justify-center text-slate-500 gap-3">
                        <font-awesome-icon icon="fa-solid fa-inbox" class="text-5xl text-slate-700" />
                        <p class="font-medium text-slate-400">Select a conversation</p>
                        <p class="text-sm">Or open one from a Design Request or Order</p>
                    </div>

                    <!-- Thread view -->
                    <template v-else>
                        <!-- Thread header -->
                        <div class="flex flex-wrap items-start justify-between gap-2 border-b border-white/5 px-4 sm:px-6 py-4">
                            <div class="flex items-start gap-3 min-w-0">
                                <button
                                    class="md:hidden mt-0.5 -ml-1 flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-lg text-slate-400 hover:bg-slate-800/60 transition-colors"
                                    aria-label="Back to messages"
                                    @click="activeThread = null"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                                    </svg>
                                </button>
                                <img
                                    :src="liveActiveThread.template_image"
                                    :alt="liveActiveThread.template_name"
                                    class="h-10 w-10 flex-shrink-0 rounded-lg object-contain bg-white border border-white/10 p-1"
                                />
                                <div class="min-w-0">
                                    <h3 class="text-sm font-semibold text-white truncate">
                                        {{ liveActiveThread.team_name }} — {{ liveActiveThread.template_name }}
                                    </h3>
                                    <div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-slate-400">
                                        <span>{{ liveActiveThread.client_name }}</span>
                                        <span class="font-mono bg-slate-900/60 rounded px-1">
                                            {{ liveActiveThread.order_ref ?? liveActiveThread.design_request_ref }}
                                        </span>
                                        <span class="rounded-full px-2 py-0.5 text-[10px] font-semibold border" :class="stagePillClass(liveActiveThread.stage)">
                                            {{ stagePillLabel(liveActiveThread.stage) }}
                                        </span>
                                        <span class="rounded-full px-2 py-0.5 text-[10px] font-semibold" :class="liveActiveThread.status_class">
                                            {{ liveActiveThread.status_label }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="flex gap-2 flex-shrink-0">
                                <Link
                                    v-if="liveActiveThread.stage === 'order'"
                                    :href="route('admin.orders.index')"
                                    class="flex items-center gap-1.5 rounded-lg border border-white/10 px-3 py-1.5 text-sm text-slate-300 hover:bg-slate-800/60 transition-colors"
                                >
                                    <font-awesome-icon icon="fa-solid fa-box" />
                                    View order
                                </Link>
                                <Link
                                    v-else
                                    :href="route('admin.design.index')"
                                    class="flex items-center gap-1.5 rounded-lg border border-white/10 px-3 py-1.5 text-sm text-slate-300 hover:bg-slate-800/60 transition-colors"
                                >
                                    <font-awesome-icon icon="fa-solid fa-tshirt" />
                                    View design request
                                </Link>
                                <Link
                                    v-if="showVerifyGcash"
                                    :href="route('admin.design.index')"
                                    class="flex items-center gap-1.5 rounded-lg border border-emerald-500/30 bg-emerald-500/10 hover:bg-emerald-500/20 px-3 py-1.5 text-sm font-medium text-emerald-300 transition-colors"
                                >
                                    <font-awesome-icon icon="fa-solid fa-circle-check" />
                                    Verify GCash
                                </Link>
                                <button
                                    type="button"
                                    title="Flag this conversation as unread, for follow-up later"
                                    class="flex items-center gap-1.5 rounded-lg border border-white/10 px-3 py-1.5 text-sm text-slate-300 hover:bg-slate-800/60 transition-colors"
                                    @click="markThreadUnread(liveActiveThread)"
                                >
                                    <font-awesome-icon icon="fa-solid fa-envelope" />
                                    Mark unread
                                </button>
                            </div>
                        </div>

                        <!-- Messages -->
                        <div ref="threadBody" class="flex-1 overflow-y-auto px-4 sm:px-6 py-4 space-y-3">
                            <div v-if="liveActiveThread.messages.length === 0" class="flex h-full flex-col items-center justify-center gap-2 text-center text-slate-500">
                                <font-awesome-icon icon="fa-solid fa-comments" class="text-3xl text-slate-700" />
                                <p class="text-sm">
                                    No messages yet — say hello to {{ liveActiveThread.client_name }} to start the design discussion.
                                </p>
                            </div>
                            <div
                                v-for="msg in liveActiveThread.messages"
                                :key="msg.id"
                                class="flex"
                                :class="msg.from === 'admin' ? 'justify-end' : 'justify-start'"
                            >
                                <div
                                    class="max-w-[85%] sm:max-w-[72%] rounded-xl px-4 py-2.5 text-sm leading-relaxed"
                                    :class="
                                        msg.from === 'admin'
                                            ? 'rounded-br-sm bg-indigo-600 text-white'
                                            : 'rounded-bl-sm bg-slate-700/80 border border-white/10 text-slate-100'
                                    "
                                >
                                    <p class="mb-0.5 text-[10px] font-bold uppercase tracking-wide opacity-70">{{ msg.name }}</p>
                                    <img
                                        v-if="msg.attachment_url"
                                        :src="msg.attachment_url"
                                        :alt="msg.attachment_name ?? 'Attached image'"
                                        class="mb-1.5 max-h-56 w-full rounded-lg border border-white/10 object-cover cursor-pointer"
                                        @click="openAttachment(msg.attachment_url!)"
                                    />
                                    <p v-if="msg.body">{{ msg.body }}</p>
                                    <p class="mt-1 text-right text-[10px] opacity-70">{{ msg.time }}</p>
                                    <p
                                        v-if="msg.from === 'admin' && msg.id === lastAdminMessageId"
                                        class="mt-0.5 text-right text-[10px] font-semibold"
                                        :class="isSeenByClient(msg) ? 'text-sky-300' : 'text-white/50'"
                                    >
                                        {{ isSeenByClient(msg) ? "Seen" : "Sent" }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Quick replies -->
                        <div v-if="!liveActiveThread.closed" class="px-4 sm:px-6 py-2 border-t border-white/5 flex items-center gap-2 overflow-x-auto shrink-0">
                            <span class="text-[10px] font-semibold text-slate-500 uppercase tracking-wider shrink-0">Quick reply</span>
                            <button
                                v-for="qr in quickReplies"
                                :key="qr.label"
                                type="button"
                                class="px-2.5 py-1 text-xs bg-slate-800/70 hover:bg-slate-700 text-slate-300 rounded-full border border-white/10 transition-colors shrink-0"
                                @click="useQuickReply(qr.text)"
                            >
                                {{ qr.label }}
                            </button>
                        </div>

                        <!-- Reply box -->
                        <div class="border-t border-white/5 px-4 sm:px-6 py-4">
                            <div v-if="liveActiveThread.closed" class="rounded-lg bg-slate-900/60 border border-white/5 px-3 py-2 text-xs text-slate-500">
                                <font-awesome-icon icon="fa-solid fa-lock" />
                                This order is completed. The conversation is now read-only.
                            </div>
                            <template v-else>
                                <div v-if="replyImagePreview" class="relative mb-2 inline-block">
                                    <img :src="replyImagePreview" alt="Attachment preview" class="max-h-32 rounded-lg border border-white/10 object-cover" />
                                    <button
                                        type="button"
                                        class="absolute -right-2 -top-2 flex h-5 w-5 items-center justify-center rounded-full bg-rose-600 text-[10px] text-white hover:bg-rose-500"
                                        @click="removeReplyImage"
                                    >
                                        <font-awesome-icon icon="fa-solid fa-xmark" />
                                    </button>
                                </div>
                                <textarea
                                    v-model="replyText"
                                    rows="3"
                                    placeholder="Type your reply here…"
                                    class="w-full resize-none rounded-lg bg-slate-900/60 border border-white/10 px-3 py-2 text-sm text-slate-200 placeholder-slate-500 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
                                    @keydown.ctrl.enter="sendReply(liveActiveThread)"
                                />
                                <div class="mt-2 flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <label class="flex cursor-pointer items-center gap-1.5 rounded-lg border border-white/10 px-2.5 py-1.5 text-xs text-slate-300 hover:bg-slate-800/60 transition-colors">
                                            <font-awesome-icon icon="fa-solid fa-image" />
                                            Attach image
                                            <input type="file" accept="image/*" class="hidden" @change="handleImageSelect" />
                                        </label>
                                        <span class="hidden text-[11px] text-slate-500 sm:inline">Ctrl + Enter to send</span>
                                    </div>
                                    <button
                                        :disabled="!replyText.trim() && !replyImage"
                                        class="flex items-center gap-2 rounded-lg bg-gradient-to-r from-indigo-600 to-blue-600 px-4 py-1.5 text-sm font-semibold text-white hover:from-indigo-500 hover:to-blue-500 transition-all disabled:cursor-not-allowed disabled:opacity-40"
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

            <!-- Context panel (admin-only — the client chat has no equivalent) -->
            <aside v-if="liveActiveThread" class="hidden lg:flex lg:col-span-3 flex-col gap-4 overflow-y-auto">
                <div class="glass-panel rounded-2xl p-5">
                    <h2 class="text-xs font-black uppercase tracking-wide text-slate-500 mb-3">Order Specs</h2>
                    <div class="aspect-square rounded-xl bg-white border border-white/10 flex items-center justify-center p-3 mb-3">
                        <img :src="liveActiveThread.template_image" :alt="liveActiveThread.template_name" class="w-full h-full object-contain" />
                    </div>
                    <p class="text-sm font-bold text-white">{{ liveActiveThread.template_name }}</p>
                    <p class="text-xs text-slate-500">{{ liveActiveThread.team_name }}</p>

                    <div class="flex items-center gap-1.5 mt-3">
                        <span class="w-4 h-4 rounded-full ring-1 ring-white/10 cursor-help" :title="liveActiveThread.primary_color" :style="{ backgroundColor: liveActiveThread.primary_color }"></span>
                        <span class="w-4 h-4 rounded-full ring-1 ring-white/10 cursor-help" :title="liveActiveThread.secondary_color" :style="{ backgroundColor: liveActiveThread.secondary_color }"></span>
                        <span class="w-4 h-4 rounded-full ring-1 ring-white/10 cursor-help" :title="liveActiveThread.accent_color" :style="{ backgroundColor: liveActiveThread.accent_color }"></span>
                        <span v-if="liveActiveThread.font_style" class="ml-auto text-[11px] text-slate-500">{{ liveActiveThread.font_style }}</span>
                    </div>

                    <div class="mt-4 pt-3 border-t border-white/5 space-y-1.5 text-xs">
                        <div class="flex items-center justify-between">
                            <span class="text-slate-500">Price</span>
                            <span class="font-bold text-white">{{ formatCurrency(liveActiveThread.unit_price) }} /set</span>
                        </div>
                        <div v-if="liveActiveThread.quantity" class="flex items-center justify-between">
                            <span class="text-slate-500">Quantity</span>
                            <span class="font-bold text-white">{{ liveActiveThread.quantity }} sets</span>
                        </div>
                        <div v-if="subtotal !== null" class="flex items-center justify-between">
                            <span class="text-slate-500">Subtotal</span>
                            <span class="font-bold text-white">{{ formatCurrency(subtotal) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Client contact card — admin needs this to follow up outside chat; the client obviously doesn't need it about themselves. -->
                <div class="glass-panel rounded-2xl p-5">
                    <h2 class="text-xs font-black uppercase tracking-wide text-slate-500 mb-3">
                        <font-awesome-icon icon="fa-solid fa-address-card" class="text-indigo-400" />
                        Client Contact
                    </h2>
                    <p class="text-sm font-bold text-white truncate">{{ liveActiveThread.client_name }}</p>
                    <div class="mt-2 space-y-1.5 text-xs">
                        <a
                            :href="`mailto:${liveActiveThread.client_email}`"
                            class="flex items-center gap-2 text-slate-400 hover:text-indigo-300 transition-colors truncate"
                        >
                            <font-awesome-icon icon="fa-solid fa-envelope" class="text-slate-600 shrink-0" />
                            <span class="truncate">{{ liveActiveThread.client_email }}</span>
                        </a>
                        <a
                            v-if="liveActiveThread.client_phone"
                            :href="`tel:${liveActiveThread.client_phone}`"
                            class="flex items-center gap-2 text-slate-400 hover:text-indigo-300 transition-colors"
                        >
                            <font-awesome-icon icon="fa-solid fa-phone" class="text-slate-600 shrink-0" />
                            {{ liveActiveThread.client_phone }}
                        </a>
                    </div>
                    <Link
                        :href="route('admin.users.index')"
                        class="mt-3 inline-flex items-center gap-1 text-xs font-bold text-indigo-400 hover:text-indigo-300"
                    >
                        View in Users
                        <font-awesome-icon icon="fa-solid fa-arrow-right" class="text-[10px]" />
                    </Link>
                </div>

                <div class="glass-panel rounded-2xl p-5">
                    <div class="flex items-center justify-between mb-3">
                        <h2 class="text-xs font-black uppercase tracking-wide text-slate-500">
                            <font-awesome-icon icon="fa-solid fa-users" class="text-indigo-400" />
                            Team Roster
                        </h2>
                        <span class="text-xs text-slate-500">{{ liveActiveThread.players.length }}</span>
                    </div>
                    <div v-if="sizeDistribution.length" class="flex flex-wrap gap-1.5 mb-3">
                        <span
                            v-for="[size, count] in sizeDistribution"
                            :key="size"
                            class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-bold bg-indigo-500/15 text-indigo-300"
                        >
                            {{ size }}
                            <span class="px-1 rounded-full bg-indigo-600 text-white text-[9px]">{{ count }}</span>
                        </span>
                    </div>
                    <p v-else class="text-xs text-slate-500 mb-3">No roster submitted yet.</p>
                    <Link
                        :href="route('admin.design.index')"
                        class="inline-flex items-center gap-1 text-xs font-bold text-indigo-400 hover:text-indigo-300"
                    >
                        View in Design Requests
                        <font-awesome-icon icon="fa-solid fa-arrow-right" class="text-[10px]" />
                    </Link>
                </div>
            </aside>
        </div>
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
