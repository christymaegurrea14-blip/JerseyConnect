<script setup lang="ts">
import AdminLayout from "@/Layouts/AdminLayout.vue";
import Modal from "@/Components/Modal.vue";
import ModalHeader from "@/Components/ModalHeader.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import DangerButton from "@/Components/DangerButton.vue";
import TextInput from "@/Components/TextInput.vue";
import SelectInput from "@/Components/SelectInput.vue";
import InputLabel from "@/Components/InputLabel.vue";
import InputError from "@/Components/InputError.vue";
import ImageUpload from "@/Components/ImageUpload.vue";
import type { DesignRequest, DesignRequestStatus } from "@/types/jersey";
import { useModal } from "@/Composables/useModal";
import { Head, Link, useForm, router, usePoll } from "@inertiajs/vue3";
import { ref, computed, watch } from "vue";

const props = defineProps<{
    data?: DesignRequest[];
}>();

usePoll(5000, {
    only: ["data"],
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
const searchQuery = ref("");

// Statuses that mean "an admin needs to act on this" — surfaced as a dot on
// the matching filter pill so pending work is easy to spot at a glance.
const needsAttentionStatuses = new Set<DesignRequestStatus>([
    "pending_review",
    "pending_down_payment_review",
]);

function needsAttentionDot(value: DesignRequestStatus | "All") {
    if (value === "All" || !needsAttentionStatuses.has(value)) return false;
    return (statusCounts.value[value] ?? 0) > 0;
}

const statusBadge: Record<
    DesignRequestStatus,
    { label: string; class: string }
> = {
    pending_review: {
        label: "Pending Review",
        class: "bg-amber-500/15 text-amber-300 border-amber-500/25",
    },
    in_discussion: {
        label: "In Discussion",
        class: "bg-sky-500/15 text-sky-300 border-sky-500/25",
    },
    revision_requested: {
        label: "Revision Requested",
        class: "bg-orange-500/15 text-orange-300 border-orange-500/25",
    },
    waiting_for_down_payment: {
        label: "Waiting for Down Payment",
        class: "bg-pink-500/15 text-pink-300 border-pink-500/25",
    },
    pending_down_payment_review: {
        label: "Pending Down Payment Review",
        class: "bg-rose-500/15 text-rose-300 border-rose-500/25",
    },
    approved: {
        label: "Approved",
        class: "bg-emerald-500/15 text-emerald-300 border-emerald-500/25",
    },
    cancelled: {
        label: "Cancelled",
        class: "bg-slate-700/40 text-slate-400 border-white/5",
    },
};

// Real, derived-only summary counts
const statusCounts = computed(() => {
    const counts: Record<DesignRequestStatus, number> = {
        pending_review: 0,
        in_discussion: 0,
        revision_requested: 0,
        waiting_for_down_payment: 0,
        pending_down_payment_review: 0,
        approved: 0,
        cancelled: 0,
    };
    for (const r of requests.value) counts[r.status]++;
    return counts;
});

const awaitingPaymentCount = computed(
    () =>
        statusCounts.value.waiting_for_down_payment +
        statusCounts.value.pending_down_payment_review,
);

const dateFrom = ref("");
const dateTo = ref("");
const perPage = ref(10);
const perPageOptions = [5, 10, 25, 50];
const currentPage = ref(1);

const filteredByStatus = computed<DesignRequest[]>(() => {
    let list = requests.value;
    if (activeStatus.value !== "All") {
        list = list.filter((r) => r.status === activeStatus.value);
    }
    if (dateFrom.value) {
        const from = new Date(dateFrom.value);
        list = list.filter((r) => new Date(r.created_at) >= from);
    }
    if (dateTo.value) {
        const to = new Date(dateTo.value);
        to.setHours(23, 59, 59, 999);
        list = list.filter((r) => new Date(r.created_at) <= to);
    }
    const q = searchQuery.value.trim().toLowerCase();
    if (q) {
        list = list.filter(
            (r) =>
                r.template_name.toLowerCase().includes(q) ||
                (r.team_name ?? "").toLowerCase().includes(q),
        );
    }
    return list;
});

watch([activeStatus, dateFrom, dateTo, searchQuery, perPage], () => {
    currentPage.value = 1;
});

const totalPages = computed(() =>
    Math.max(1, Math.ceil(filteredByStatus.value.length / perPage.value)),
);

const paginatedRequests = computed<DesignRequest[]>(() => {
    const start = (currentPage.value - 1) * perPage.value;
    return filteredByStatus.value.slice(start, start + perPage.value);
});

const rangeStart = computed(() =>
    filteredByStatus.value.length === 0
        ? 0
        : (currentPage.value - 1) * perPage.value + 1,
);
const rangeEnd = computed(() =>
    Math.min(currentPage.value * perPage.value, filteredByStatus.value.length),
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
    router.delete(route("admin.design.cancel", selectedRequest.value.id), {
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

// --- Payment review modal state ---
const paymentActionProcessing = ref(false);

function viewPayment(request: DesignRequest) {
    selectedRequest.value = request;

    modal.title.value = "Review Payment";
    modal.type.value = "Payment";
    modal.icon.value = "fa-solid fa-money-bill-wave";
    modal.openModal();
}

function approvePayment() {
    if (!selectedRequest.value) return;

    paymentActionProcessing.value = true;
    router.post(
        route("admin.design.approve-payment", selectedRequest.value.id),
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                paymentActionProcessing.value = false;
                closeModal();
            },
        },
    );
}

function rejectPayment() {
    if (!selectedRequest.value) return;

    paymentActionProcessing.value = true;
    router.post(
        route("admin.design.reject-payment", selectedRequest.value.id),
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                paymentActionProcessing.value = false;
                closeModal();
            },
        },
    );
}

/* ---------------- EDIT ---------------- */

interface ImageStyle {
    backgroundImage: string;
}

const designRequestsStatus = [
    { value: "pending_review", label: "Pending Review" },
    { value: "in_discussion", label: "In Discussion" },
    { value: "revision_requested", label: "Revision Requested" },
    { value: "waiting_for_down_payment", label: "Waiting for Down Payment" },
];

const editImageStyle = ref<ImageStyle>({ backgroundImage: "" });

const editForm = useForm({
    id: null as number | null,
    name: "",
    quantity: "",
    image: null as File | null,
    primary_color: "#14202B",
    secondary_color: "#FFFFFF",
    accent_color: "#2E7D4F",
    status: "pending_review" as DesignRequestStatus,
});

function editRequest(row: DesignRequest) {
    editForm.reset();
    editForm.clearErrors();

    editForm.id = row.id;
    editForm.name = row.template_name;
    editForm.quantity =
        row.estimated_quantity != null
            ? row.estimated_quantity.toString()
            : "";
    editForm.primary_color = row.primary_color;
    editForm.secondary_color = row.secondary_color;
    editForm.accent_color = row.accent_color;
    editForm.status = row.status;

    editImageStyle.value = {
        backgroundImage: `url(${row.template_image_url})`,
    };

    modal.title.value = "Edit Design Request";
    modal.type.value = "Edit";
    modal.icon.value = "fa-solid fa-edit";
    modal.openModal();
}

function onEditImageChange(file: File) {
    editForm.image = file;
}

function submitEdit() {
    if (!editForm.id) return;

    editForm
        .transform((data) => ({
            ...data,
            _method: "put",
        }))
        .post(route("admin.design.update", editForm.id), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });
}
</script>

<template>
    <Head title="Design Requests" />

    <AdminLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div v-reveal class="flex flex-col gap-1">
                <h1 class="text-2xl font-extrabold text-white tracking-tight">Design Requests</h1>
                <p class="text-sm text-slate-400">
                    {{ requests.length }} request{{ requests.length === 1 ? "" : "s" }} total
                </p>
            </div>

            <!-- Stat cards (derived from real data) -->
            <div v-reveal="80" class="grid grid-cols-2 lg:grid-cols-4 gap-3.5">
                <div v-reveal="0" class="glass-panel rounded-2xl p-5 relative overflow-hidden group hover:border-amber-500/30 transition-all">
                    <div class="absolute -right-4 -bottom-4 w-20 h-20 bg-amber-500/5 rounded-full blur-xl group-hover:bg-amber-500/10 transition-all"></div>
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium text-slate-400">Pending review</span>
                        <span class="w-2 h-2 rounded-full bg-amber-400 ring-4 ring-amber-400/10"></span>
                    </div>
                    <div class="mt-3 flex items-baseline gap-2">
                        <span class="text-2xl font-bold text-amber-300">{{ statusCounts.pending_review }}</span>
                        <span class="text-[11px] text-slate-500">awaiting admin review</span>
                    </div>
                </div>
                <div v-reveal="70" class="glass-panel rounded-2xl p-5 relative overflow-hidden group hover:border-sky-500/30 transition-all">
                    <div class="absolute -right-4 -bottom-4 w-20 h-20 bg-sky-500/5 rounded-full blur-xl group-hover:bg-sky-500/10 transition-all"></div>
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium text-slate-400">In discussion</span>
                        <span class="w-2 h-2 rounded-full bg-sky-400 ring-4 ring-sky-400/10"></span>
                    </div>
                    <div class="mt-3 flex items-baseline gap-2">
                        <span class="text-2xl font-bold text-sky-300">{{ statusCounts.in_discussion }}</span>
                        <span class="text-[11px] text-slate-500">back-and-forth with client</span>
                    </div>
                </div>
                <div v-reveal="140" class="glass-panel rounded-2xl p-5 relative overflow-hidden group hover:border-rose-500/30 transition-all">
                    <div class="absolute -right-4 -bottom-4 w-20 h-20 bg-rose-500/5 rounded-full blur-xl group-hover:bg-rose-500/10 transition-all"></div>
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium text-slate-400">Awaiting payment</span>
                        <span class="w-2 h-2 rounded-full bg-rose-400 ring-4 ring-rose-400/10"></span>
                    </div>
                    <div class="mt-3 flex items-baseline gap-2">
                        <span class="text-2xl font-bold text-rose-300">{{ awaitingPaymentCount }}</span>
                        <span class="text-[11px] text-slate-500">50% down payment required</span>
                    </div>
                </div>
                <div v-reveal="210" class="glass-panel rounded-2xl p-5 relative overflow-hidden group hover:border-emerald-500/30 transition-all">
                    <div class="absolute -right-4 -bottom-4 w-20 h-20 bg-emerald-500/5 rounded-full blur-xl group-hover:bg-emerald-500/10 transition-all"></div>
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium text-slate-400">Approved</span>
                        <span class="w-2 h-2 rounded-full bg-emerald-400 ring-4 ring-emerald-400/10"></span>
                    </div>
                    <div class="mt-3 flex items-baseline gap-2">
                        <span class="text-2xl font-bold text-emerald-300">{{ statusCounts.approved }}</span>
                        <span class="text-[11px] text-slate-500">ready for print</span>
                    </div>
                </div>
            </div>

            <!-- Status filter pills -->
            <div v-reveal="140" class="flex flex-wrap gap-1.5 p-1.5 rounded-xl bg-slate-900/60 border border-white/5 w-fit">
                <button
                    v-for="filter in statusFilters"
                    :key="filter.value"
                    type="button"
                    class="relative rounded-lg px-3 py-1.5 text-xs font-semibold transition-colors flex items-center gap-1.5"
                    :class="
                        activeStatus === filter.value
                            ? 'bg-indigo-600 text-white shadow-sm'
                            : 'text-slate-400 hover:text-slate-200'
                    "
                    @click="activeStatus = filter.value"
                >
                    <span
                        v-if="needsAttentionDot(filter.value)"
                        class="w-1.5 h-1.5 rounded-full bg-amber-400 animate-pulse"
                        title="Needs your attention"
                    ></span>
                    {{ filter.label }}
                </button>
            </div>

            <!-- Search -->
            <div class="relative w-full">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-500 pointer-events-none">
                    <font-awesome-icon icon="fa-solid fa-magnifying-glass" class="text-xs" />
                </span>
                <input
                    id="design-requests-search"
                    v-model="searchQuery"
                    type="text"
                    name="search"
                    placeholder="Search design, team..."
                    aria-label="Search design requests"
                    class="w-full pl-8 pr-3 py-2.5 text-sm rounded-xl bg-slate-900/60 border border-white/10 text-slate-200 placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition-all"
                />
            </div>

            <!-- Date range + per-page -->
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex flex-wrap items-center gap-3">
                    <div class="flex items-center gap-1.5">
                        <label for="design-from" class="text-xs text-slate-500">From</label>
                        <input
                            id="design-from"
                            v-model="dateFrom"
                            type="date"
                            name="dateFrom"
                            class="text-sm rounded-lg bg-slate-900/60 border border-white/10 text-slate-200 py-1.5 px-2.5 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500"
                        />
                    </div>
                    <div class="flex items-center gap-1.5">
                        <label for="design-to" class="text-xs text-slate-500">To</label>
                        <input
                            id="design-to"
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
                </div>

                <div class="flex items-center gap-1.5 ml-auto">
                    <label for="design-per-page" class="text-xs text-slate-500">Show</label>
                    <select
                        id="design-per-page"
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
            <div v-reveal="200" class="glass-panel rounded-2xl overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-white/5">
                                <th class="px-4 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-slate-400">Design</th>
                                <th class="px-4 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-slate-400">Team</th>
                                <th class="px-4 py-3 text-center text-[11px] font-bold uppercase tracking-wider text-slate-400">Colors</th>
                                <th class="px-4 py-3 text-center text-[11px] font-bold uppercase tracking-wider text-slate-400">Qty</th>
                                <th class="px-4 py-3 text-right text-[11px] font-bold uppercase tracking-wider text-slate-400">Price</th>
                                <th class="px-4 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-slate-400">Status</th>
                                <th class="px-4 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-slate-400">Submitted</th>
                                <th class="px-4 py-3 text-center text-[11px] font-bold uppercase tracking-wider text-slate-400">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            <tr v-if="filteredByStatus.length === 0">
                                <td colspan="8" class="px-4 py-10 text-center text-sm text-slate-500">
                                    No design requests yet. Select a template to get started.
                                </td>
                            </tr>
                            <tr
                                v-for="row in paginatedRequests"
                                :key="row.id"
                                class="hover:bg-slate-800/30 transition-colors"
                            >
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2.5">
                                        <img
                                            :src="row.template_image_url"
                                            :alt="row.template_name"
                                            class="h-9 w-9 flex-shrink-0 rounded-lg object-contain bg-white border border-white/10 p-1"
                                        />
                                        <span class="font-semibold text-white">{{ row.template_name }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-slate-300">{{ row.team_name }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-center gap-1">
                                        <span class="h-4 w-4 rounded-full border border-white/20" :style="{ backgroundColor: row.primary_color }" />
                                        <span class="h-4 w-4 rounded-full border border-white/20" :style="{ backgroundColor: row.secondary_color }" />
                                        <span class="h-4 w-4 rounded-full border border-white/20" :style="{ backgroundColor: row.accent_color }" />
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-center text-slate-300">{{ row.estimated_quantity }}</td>
                                <td class="px-4 py-3 text-right text-slate-200 font-medium">
                                    ₱{{ (row.estimated_quantity * row.template_price).toFixed(2) }}
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-block rounded-full px-2.5 py-1 text-[11px] font-medium border" :class="statusBadge[row.status].class">
                                        {{ statusBadge[row.status].label }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-xs text-slate-400">{{ formatDate(row.created_at) }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-center gap-1">
                                        <button
                                            type="button"
                                            class="text-xs font-medium bg-indigo-600/20 text-indigo-300 border border-indigo-500/30 rounded-md px-2 py-2 transition-colors hover:bg-indigo-600/30"
                                            @click="viewRequest(row)"
                                        >
                                            <font-awesome-icon icon="fa-solid fa-eye" />
                                        </button>
                                        <button
                                            v-if="
                                                row.status !== 'approved' &&
                                                row.status !== 'cancelled' &&
                                                row.status !== 'waiting_for_down_payment' &&
                                                row.status !== 'pending_down_payment_review'
                                            "
                                            type="button"
                                            class="text-xs font-medium bg-slate-800 text-slate-300 border border-white/10 rounded-md px-2 py-2 transition-colors hover:bg-slate-700"
                                            @click="editRequest(row)"
                                        >
                                            <font-awesome-icon icon="fa-solid fa-edit" />
                                        </button>
                                        <button
                                            v-if="row.status === 'pending_down_payment_review'"
                                            type="button"
                                            class="text-xs font-medium bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 rounded-md px-2 py-2 transition-colors hover:bg-emerald-500/30"
                                            @click="viewPayment(row)"
                                        >
                                            <font-awesome-icon icon="fa-solid fa-money-bill-wave" />
                                        </button>
                                        <Link
                                            v-if="row.status !== 'approved' && row.status !== 'cancelled'"
                                            class="text-xs font-medium bg-amber-500/20 text-amber-300 border border-amber-500/30 rounded-md px-2 py-2 transition-colors hover:bg-amber-500/30"
                                            :href="route('admin.messages.index')"
                                        >
                                            <font-awesome-icon icon="fa-solid fa-message" />
                                        </Link>
                                        <button
                                            v-if="
                                                row.status !== 'approved' &&
                                                row.status !== 'cancelled' &&
                                                row.status !== 'revision_requested' &&
                                                row.status !== 'waiting_for_down_payment' &&
                                                row.status !== 'pending_down_payment_review'
                                            "
                                            type="button"
                                            class="text-xs font-medium bg-rose-500/20 text-rose-300 border border-rose-500/30 rounded-md px-2 py-2 transition-colors hover:bg-rose-500/30"
                                            @click="cancelRequest(row)"
                                        >
                                            <font-awesome-icon icon="fa-solid fa-xmark-circle" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="filteredByStatus.length > 0" class="flex flex-col sm:flex-row items-center justify-between gap-3">
                <p class="text-xs text-slate-500">
                    Showing <span class="text-slate-300 font-medium">{{ rangeStart }}–{{ rangeEnd }}</span>
                    of <span class="text-slate-300 font-medium">{{ filteredByStatus.length }}</span> requests
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

        <!-- Details Modal -->
        <Modal :show="modal.type.value === 'View'" @close="closeModal" :maxWidth="'5xl'">
            <div v-if="selectedRequest" class="overflow-y-auto max-h-[90vh]">
                <ModalHeader
                    :icon="modal.icon.value"
                    :title="modal.title.value"
                    :subtitle="selectedRequest.team_name"
                    @close="closeModal"
                />
                <div class="px-5 py-5 text-slate-200">
                <div class="flex flex-col gap-4 sm:flex-row sm:gap-3 justify-between">
                    <div class="flex flex-col gap-3 border border-white/10 px-3 py-3 rounded-xl w-full bg-slate-900/40">
                        <p class="text-sm font-bold text-white text-center">Previous Design</p>
                        <div class="flex w-full flex-col items-center gap-2">
                            <div class="flex aspect-square w-full items-center justify-center overflow-hidden rounded-lg border border-white/10 bg-white">
                                <img
                                    v-if="selectedRequest.original_template_image"
                                    :src="selectedRequest.original_template_image"
                                    :alt="selectedRequest.template_name"
                                    class="h-full w-full object-contain p-4"
                                />
                                <span v-else class="text-sm text-slate-500">Original template no longer available.</span>
                            </div>
                        </div>
                    </div>
                    <div class="flex flex-col gap-3 border border-white/10 px-3 py-3 rounded-xl w-full bg-slate-900/40">
                        <p class="text-sm font-bold text-white text-center">Current Design</p>
                        <div class="flex w-full flex-col items-center gap-2">
                            <div class="flex aspect-square w-full items-center justify-center overflow-hidden rounded-lg border border-white/10 bg-white">
                                <img
                                    :src="selectedRequest.template_image_url"
                                    :alt="selectedRequest.template_name"
                                    class="h-full w-full object-contain p-4"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-4 border border-white/10 rounded-xl p-3 bg-slate-900/40">
                    <p class="text-sm font-bold text-white mb-2 flex items-center justify-between">
                        <span>
                            <font-awesome-icon icon="fa-solid fa-users" class="text-indigo-400" />
                            Team Roster
                        </span>
                        <span class="text-xs font-normal text-slate-400">
                            {{ selectedRequest.players?.length ?? 0 }} player{{ (selectedRequest.players?.length ?? 0) === 1 ? "" : "s" }}
                        </span>
                    </p>
                    <div v-if="selectedRequest.players?.length" class="grid grid-cols-1 sm:grid-cols-2 gap-2 max-h-48 overflow-y-auto pr-1">
                        <div
                            v-for="p in selectedRequest.players"
                            :key="p.id"
                            class="flex items-center gap-2.5 p-2 rounded-lg bg-slate-950/40 border border-white/5"
                        >
                            <div class="w-7 h-7 rounded-full bg-indigo-500/15 text-indigo-300 flex items-center justify-center text-[11px] font-bold shrink-0">
                                {{ p.number ?? "—" }}
                            </div>
                            <div class="min-w-0">
                                <p class="text-xs font-semibold text-white truncate">{{ p.name }}</p>
                                <p class="text-[11px] text-slate-400">{{ [p.position, p.size].filter(Boolean).join(" • ") || "—" }}</p>
                            </div>
                        </div>
                    </div>
                    <p v-else class="text-xs text-slate-500">No roster submitted yet.</p>
                </div>
                </div>
            </div>
        </Modal>

        <!-- Edit Modal -->
        <Modal :show="modal.type.value === 'Edit'" @close="closeModal" :maxWidth="'5xl'">
            <ModalHeader
                icon="fa-solid fa-edit"
                :title="modal.title.value"
                @close="closeModal"
            />
            <form @submit.prevent="submitEdit" class="px-5 py-5 text-slate-200">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-slate-300">Jersey Image</label>
                        <ImageUpload class="mt-1" :image="editImageStyle" @imageFile="onEditImageChange" />
                        <InputError :message="editForm.errors.image" class="mt-2" />
                    </div>

                    <div class="flex flex-col gap-4">
                        <div>
                            <InputLabel for="name" value="Name" class="!text-slate-300" />
                            <TextInput v-model="editForm.name" class="mt-1 block w-full !bg-slate-900/60 !border-white/10 !text-slate-200" id="name" required />
                            <InputError :message="editForm.errors.name" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel for="quantity" value="Quantity" class="!text-slate-300" />
                            <TextInput v-model="editForm.quantity" class="mt-1 block w-full !bg-slate-900/60 !border-white/10 !text-slate-200" id="quantity" required />
                            <InputError :message="editForm.errors.quantity" class="mt-2" />
                        </div>
                        <div>
                            <InputLabel for="status" value="Status" class="!text-slate-300" />
                            <SelectInput
                                v-model="editForm.status"
                                :options="designRequestsStatus"
                                class="mt-1 block w-full !bg-slate-900/60 !border-white/10 !text-slate-200"
                                id="status"
                                required
                            />
                            <InputError :message="editForm.errors.status" class="mt-2" />
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                            <div>
                                <InputLabel for="primary" value="Primary Color" class="!text-slate-300" />
                                <div class="mt-1 flex items-center gap-1">
                                    <input type="color" v-model="editForm.primary_color" class="h-10 w-12 rounded border border-white/10 bg-slate-900" id="primary" />
                                    <TextInput v-model="editForm.primary_color" class="block w-full !bg-slate-900/60 !border-white/10 !text-slate-200" />
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-300">Secondary Color</label>
                                <div class="mt-1 flex items-center gap-1">
                                    <input type="color" v-model="editForm.secondary_color" class="h-10 w-12 rounded border border-white/10 bg-slate-900" />
                                    <TextInput v-model="editForm.secondary_color" class="block w-full !bg-slate-900/60 !border-white/10 !text-slate-200" />
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-300">Accent Color</label>
                                <div class="mt-1 flex items-center gap-1">
                                    <input type="color" v-model="editForm.accent_color" class="h-10 w-12 rounded border border-white/10 bg-slate-900" />
                                    <TextInput v-model="editForm.accent_color" class="block w-full !bg-slate-900/60 !border-white/10 !text-slate-200" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-5 pt-4 border-t border-white/10 flex flex-col-reverse gap-2 sm:flex-row sm:justify-between">
                    <SecondaryButton type="button" class="flex items-center justify-center" @click="closeModal()">
                        Cancel
                    </SecondaryButton>

                    <PrimaryButton
                        type="submit"
                        class="flex items-center justify-center gap-1"
                        :disabled="cancelling"
                        :class="{ 'opacity-25': editForm.processing }"
                    >
                        <div class="text-sm" v-if="editForm.processing">
                            <font-awesome-icon icon="fa-solid fa-spinner" spin />
                        </div>
                        Save
                        <font-awesome-icon icon="fa-solid fa-circle-down" />
                    </PrimaryButton>
                </div>
            </form>
        </Modal>

        <!-- Cancel Modal -->
        <Modal :show="modal.type.value === 'Cancel'" @close="closeModal()" :maxWidth="'sm'">
            <ModalHeader
                :icon="modal.icon.value"
                icon-class="text-rose-400 bg-rose-500/15 border-rose-500/25"
                :title="modal.title.value"
                @close="closeModal()"
            />
            <div class="px-5 py-5 text-slate-200">
                <p class="text-slate-400">Are you sure you want to cancel this request?</p>

                <div class="mt-6 flex justify-between">
                    <SecondaryButton class="flex items-center" :disabled="cancelling" @click="closeModal()">
                        Close
                    </SecondaryButton>

                    <DangerButton
                        class="flex items-center gap-1"
                        :disabled="cancelling"
                        @click="confirmCancel()"
                        :class="{ 'opacity-25': cancelling }"
                    >
                        <div class="text-sm" v-if="cancelling">
                            <font-awesome-icon icon="fa-solid fa-spinner" spin />
                        </div>
                        Confirm
                        <font-awesome-icon icon="fa-solid fa-thumbs-up" />
                    </DangerButton>
                </div>
            </div>
        </Modal>

        <!-- View Payment Modal -->
        <Modal :show="modal.type.value === 'Payment'" @close="closeModal" :maxWidth="'md'">
            <div v-if="selectedRequest" class="overflow-y-auto">
                <ModalHeader
                    :icon="modal.icon.value"
                    icon-class="text-emerald-400 bg-emerald-500/15 border-emerald-500/25"
                    :title="modal.title.value"
                    @close="closeModal"
                />
                <div class="px-5 py-5 text-slate-200">
                <div class="flex flex-col gap-4">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-slate-300">Customer's GCash Number:</span>
                        <p class="text-md font-medium text-white italic">{{ selectedRequest.gcash_number ?? "—" }}</p>
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-slate-300">Transaction Reference Number:</span>
                        <p class="text-md font-medium text-white italic">{{ selectedRequest.reference_number ?? "—" }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-1">Uploaded Transaction Screenshot:</label>
                        <div class="flex aspect-square w-full items-center justify-center overflow-hidden rounded-lg border border-white/10 bg-slate-950/50">
                            <img
                                v-if="selectedRequest.proof_image_url"
                                :src="selectedRequest.proof_image_url"
                                alt="Payment proof"
                                class="h-full w-full object-contain p-4"
                            />
                            <span v-else class="text-sm text-slate-500">No screenshot uploaded.</span>
                        </div>
                    </div>

                    <div class="mt-2 flex justify-between gap-2">
                        <DangerButton
                            class="flex flex-1 items-center justify-center gap-1"
                            :disabled="paymentActionProcessing"
                            @click="rejectPayment()"
                            :class="{ 'opacity-25': paymentActionProcessing }"
                        >
                            Reject
                            <font-awesome-icon icon="fa-solid fa-xmark" />
                        </DangerButton>
                        <PrimaryButton
                            class="flex flex-1 items-center justify-center gap-1"
                            :disabled="paymentActionProcessing"
                            @click="approvePayment()"
                            :class="{ 'opacity-25': paymentActionProcessing }"
                        >
                            Approve
                            <font-awesome-icon icon="fa-solid fa-check" />
                        </PrimaryButton>
                    </div>
                </div>
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
