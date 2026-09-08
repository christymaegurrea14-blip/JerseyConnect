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
    if (activeTab.value === "design")
        return sorted.value.filter((t) => t.stage === "design");
    if (activeTab.value === "order")
        return sorted.value.filter((t) => t.stage === "order");
    return sorted.value;
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

function lastMessagePreview(thread: MessageThread): string {
    const last = thread.messages[thread.messages.length - 1];
    if (!last) return "No messages yet";
    if (last.body) return last.body;
    return last.attachment_url ? "📷 Image" : "No messages yet";
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
    <Head title="Messages" />

    <AuthenticatedLayout>
        <div>
            <div
                class="flex h-[calc(100dvh-140px)] md:h-[80dvh-40px] rounded-xl border border-gray-200 bg-white shadow-sm overflow-hidden"
            >
                <aside
                    class="w-full md:w-80 flex-shrink-0 border-r border-gray-200 flex-col"
                    :class="activeThread ? 'hidden md:flex' : 'flex'"
                >
                    <!-- Header -->
                    <div class="p-4 border-b border-gray-100">
                        <div class="flex items-center gap-2">
                            <font-awesome-icon
                                icon="fa-solid fa-comments"
                                class="text-ink"
                            />
                            <span class="font-semibold text-slate-900 text-sm"
                                >Messages</span
                            >
                            <span
                                v-if="unreadCount"
                                class="ml-auto rounded-full bg-warn px-2 py-0.5 text-[10px] font-bold text-ink"
                            >
                                {{ unreadCount }}
                            </span>
                        </div>
                        <p class="mt-1 text-[11px] text-gray-400">
                            These are your design and order conversations
                            with the shop.
                        </p>
                    </div>

                    <!-- Tabs -->
                    <div class="flex border-b border-gray-100">
                        <button
                            v-for="tab in tabs"
                            :key="tab.key"
                            class="flex-1 py-2.5 text-xs font-medium border-b-2 transition"
                            :class="
                                activeTab === tab.key
                                    ? 'border-warn text-slate-900'
                                    : 'border-transparent text-gray-500 hover:text-slate-900'
                            "
                            @click="activeTab = tab.key"
                        >
                            {{ tab.label }}
                        </button>
                    </div>

                    <!-- Thread list -->
                    <div class="flex-1 overflow-y-auto">
                        <div
                            v-for="thread in filtered"
                            :key="thread.id"
                            class="relative flex cursor-pointer gap-2.5 border-b border-gray-100 px-4 py-3 transition hover:bg-gray-50"
                            :class="{
                                'bg-blue-50 hover:bg-blue-50':
                                    activeThread?.id === thread.id,
                                'border-l-2 border-l-warn': !thread.read,
                            }"
                            @click="openThread(thread)"
                        >
                            <img
                                :src="thread.template_image"
                                :alt="thread.template_name"
                                class="h-9 w-9 flex-shrink-0 rounded object-contain bg-gray-100 p-1"
                            />
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-1.5">
                                    <span
                                        class="truncate text-xs font-medium text-slate-800"
                                        :class="{
                                            'font-semibold': !thread.read,
                                        }"
                                    >
                                        {{ thread.template_name }}
                                    </span>
                                    <span
                                        v-if="!thread.read"
                                        class="h-1.5 w-1.5 flex-shrink-0 rounded-full bg-warn"
                                    />
                                </div>
                                <div
                                    class="mt-1 flex flex-wrap items-center gap-1"
                                >
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
                                    <span
                                        class="font-mono text-[9px] text-gray-400 bg-gray-100 rounded px-1"
                                        >{{
                                            thread.order_ref ??
                                            thread.design_request_ref
                                        }}</span
                                    >
                                </div>
                                <p
                                    class="mt-1 truncate text-xs text-gray-500"
                                >
                                    {{ lastMessagePreview(thread) }}
                                </p>
                            </div>
                        </div>

                        <div
                            v-if="filtered.length === 0"
                            class="flex flex-col items-center gap-2 py-16 text-center text-gray-400"
                        >
                            <span class="text-3xl">📭</span>
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
                        class="flex flex-1 flex-col items-center justify-center text-gray-400 gap-3"
                    >
                        <span class="text-5xl">
                            <font-awesome-icon icon="fa-solid fa-inbox" />
                        </span>
                        <p class="font-medium text-gray-500">
                            Select a conversation
                        </p>
                        <p class="text-sm">
                            Or tap "Message" on a design request or order
                        </p>
                    </div>

                    <!-- Thread view -->
                    <template v-else>
                        <!-- Thread header -->
                        <div
                            class="flex flex-wrap items-start justify-between gap-2 border-b border-gray-100 px-4 sm:px-6 py-4"
                        >
                            <div class="flex items-start gap-3 min-w-0">
                                <button
                                    class="md:hidden mt-0.5 -ml-1 flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-lg text-gray-500 hover:bg-gray-50 transition"
                                    aria-label="Back to messages"
                                    @click="activeThread = null"
                                >
                                    <svg
                                        class="w-5 h-5"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M15.75 19.5 8.25 12l7.5-7.5"
                                        />
                                    </svg>
                                </button>
                                <img
                                    :src="liveActiveThread.template_image"
                                    :alt="liveActiveThread.template_name"
                                    class="h-10 w-10 flex-shrink-0 rounded object-contain bg-gray-100 p-1"
                                />
                                <div class="min-w-0">
                                    <h3
                                        class="text-sm font-semibold text-slate-900 truncate"
                                    >
                                        {{ liveActiveThread.template_name }} —
                                        {{ liveActiveThread.team_name }}
                                    </h3>
                                    <div
                                        class="mt-1 flex flex-wrap items-center gap-2 text-xs text-gray-500"
                                    >
                                        <span
                                            class="font-mono bg-gray-100 rounded px-1"
                                            >{{
                                                liveActiveThread.order_ref ??
                                                liveActiveThread.design_request_ref
                                            }}</span
                                        >
                                        <span
                                            class="rounded-full px-2 py-0.5 text-[10px] font-semibold"
                                            :class="
                                                stagePillClass(
                                                    liveActiveThread.stage,
                                                )
                                            "
                                        >
                                            {{
                                                stagePillLabel(
                                                    liveActiveThread.stage,
                                                )
                                            }}
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
                                    class="flex items-center gap-1.5 rounded-lg border border-gray-200 px-3 py-1.5 text-sm text-gray-600 hover:bg-gray-50 transition"
                                >
                                    <font-awesome-icon
                                        icon="fa-solid fa-box"
                                    />
                                    View order
                                </Link>
                                <Link
                                    v-else
                                    :href="route('client.design.index')"
                                    class="flex items-center gap-1.5 rounded-lg border border-gray-200 px-3 py-1.5 text-sm text-gray-600 hover:bg-gray-50 transition"
                                >
                                    <font-awesome-icon
                                        icon="fa-solid fa-tshirt"
                                    />
                                    View design request
                                </Link>
                            </div>
                        </div>

                        <!-- Messages -->
                        <div
                            ref="threadBody"
                            class="flex-1 overflow-y-auto px-4 sm:px-6 py-4 space-y-3"
                        >
                            <div
                                v-if="liveActiveThread.messages.length === 0"
                                class="flex h-full flex-col items-center justify-center gap-2 text-center text-gray-400"
                            >
                                <span class="text-3xl">💬</span>
                                <p class="text-sm">
                                    No messages yet — ask a question about
                                    your design to get started.
                                </p>
                            </div>
                            <div
                                v-for="msg in liveActiveThread.messages"
                                :key="msg.id"
                                class="flex"
                                :class="
                                    msg.from === 'client'
                                        ? 'justify-end'
                                        : 'justify-start'
                                "
                            >
                                <div
                                    class="max-w-[85%] sm:max-w-[72%] rounded-xl px-4 py-2.5 text-sm leading-relaxed"
                                    :class="
                                        msg.from === 'client'
                                            ? 'rounded-br-sm bg-ink text-white'
                                            : 'rounded-bl-sm border border-gray-200 bg-gray-50 text-gray-800'
                                    "
                                >
                                    <p
                                        class="mb-0.5 text-[10px] font-bold uppercase tracking-wide opacity-60"
                                    >
                                        {{ msg.name }}
                                    </p>
                                    <img
                                        v-if="msg.attachment_url"
                                        :src="msg.attachment_url"
                                        :alt="msg.attachment_name ?? 'Attached image'"
                                        class="mb-1.5 max-h-56 w-full rounded-lg border border-black/10 object-cover cursor-pointer"
                                        @click="
                                            openAttachment(msg.attachment_url!)
                                        "
                                    />
                                    <p v-if="msg.body">{{ msg.body }}</p>
                                    <p
                                        class="mt-1 text-right text-[10px] opacity-80"
                                    >
                                        {{ msg.time }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Reply box -->
                        <div
                            class="border-t border-gray-100 px-4 sm:px-6 py-4"
                        >
                            <div
                                v-if="liveActiveThread.closed"
                                class="rounded-lg bg-gray-50 px-3 py-2 text-xs text-gray-500"
                            >
                                <font-awesome-icon
                                    icon="fa-solid fa-lock"
                                />
                                This order is completed. The conversation is
                                now read-only.
                            </div>
                            <template v-else>
                                <div
                                    v-if="replyImagePreview"
                                    class="relative mb-2 inline-block"
                                >
                                    <img
                                        :src="replyImagePreview"
                                        alt="Attachment preview"
                                        class="max-h-32 rounded-lg border border-gray-200 object-cover"
                                    />
                                    <button
                                        type="button"
                                        class="absolute -right-2 -top-2 flex h-5 w-5 items-center justify-center rounded-full bg-red-600 text-[10px] text-white hover:bg-red-500"
                                        @click="removeReplyImage"
                                    >
                                        <font-awesome-icon
                                            icon="fa-solid fa-xmark"
                                        />
                                    </button>
                                </div>
                                <textarea
                                    v-model="replyText"
                                    rows="3"
                                    placeholder="Type your message here…"
                                    class="w-full resize-none rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-700 focus:border-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-900/10"
                                    @keydown.ctrl.enter="
                                        sendReply(liveActiveThread)
                                    "
                                />
                                <div
                                    class="mt-2 flex items-center justify-between"
                                >
                                    <div class="flex items-center gap-2">
                                        <label
                                            class="flex cursor-pointer items-center gap-1.5 rounded-lg border border-gray-200 px-2.5 py-1.5 text-xs text-gray-600 hover:bg-gray-50 transition"
                                        >
                                            <font-awesome-icon
                                                icon="fa-solid fa-image"
                                            />
                                            Attach image
                                            <input
                                                type="file"
                                                accept="image/*"
                                                class="hidden"
                                                @change="handleImageSelect"
                                            />
                                        </label>
                                        <span
                                            class="hidden text-[11px] text-gray-400 sm:inline"
                                            >Ctrl + Enter to send</span
                                        >
                                    </div>
                                    <button
                                        :disabled="
                                            !replyText.trim() && !replyImage
                                        "
                                        class="flex items-center gap-2 rounded-lg bg-ink px-4 py-1.5 text-sm font-semibold text-white hover:bg-ink/90 transition disabled:cursor-not-allowed disabled:opacity-40"
                                        @click="sendReply(liveActiveThread)"
                                    >
                                        Send
                                        <font-awesome-icon
                                            icon="fa-solid fa-paper-plane"
                                        />
                                    </button>
                                </div>
                            </template>
                        </div>
                    </template>
                </main>
            </div>
        </div>
    </AuthenticatedLayout>
</template>