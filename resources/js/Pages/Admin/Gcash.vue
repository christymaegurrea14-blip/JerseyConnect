<script setup lang="ts">
import AdminLayout from "@/Layouts/AdminLayout.vue";
import Modal from "@/Components/Modal.vue";
import ModalHeader from "@/Components/ModalHeader.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import { Head, useForm, router, Link } from "@inertiajs/vue3";
import { ref } from "vue";

interface GcashSetting {
    account_name: string;
    account_number: string;
    instructions: string | null;
    qr_image_url: string | null;
}

type SubmissionStatus =
    | "pending_review"
    | "in_discussion"
    | "revision_requested"
    | "waiting_for_down_payment"
    | "pending_down_payment_review"
    | "approved"
    | "cancelled";

interface GcashSubmission {
    id: number;
    team_name: string;
    template_price: number;
    estimated_quantity: number | null;
    gcash_number: string | null;
    reference_number: string | null;
    proof_image_url: string | null;
    status: SubmissionStatus;
    updated_at: string;
    customer_name: string;
}

interface GcashStats {
    submitted: number;
    pending_review: number;
    approved: number;
}

const props = defineProps<{
    gcash: GcashSetting;
    submissions: GcashSubmission[];
    stats: GcashStats;
}>();

const submissionStatus: Record<SubmissionStatus, { label: string; class: string }> = {
    pending_review: { label: "Pending Review", class: "bg-slate-700/40 text-slate-300 border-slate-600/40" },
    in_discussion: { label: "In Discussion", class: "bg-cyan-500/10 text-cyan-400 border-cyan-500/20" },
    revision_requested: { label: "Revision Requested", class: "bg-amber-500/10 text-amber-400 border-amber-500/20" },
    waiting_for_down_payment: { label: "Awaiting Payment", class: "bg-amber-500/10 text-amber-400 border-amber-500/20" },
    pending_down_payment_review: { label: "Awaiting Verification", class: "bg-amber-500/10 text-amber-400 border-amber-500/20" },
    approved: { label: "Verified", class: "bg-emerald-500/10 text-emerald-400 border-emerald-500/20" },
    cancelled: { label: "Cancelled", class: "bg-rose-500/10 text-rose-400 border-rose-500/20" },
};

const reviewingId = ref<number | null>(null);
const confirmAction = ref<{ type: "approve" | "reject"; row: GcashSubmission } | null>(null);

function askApprove(row: GcashSubmission) {
    confirmAction.value = { type: "approve", row };
}

function askReject(row: GcashSubmission) {
    confirmAction.value = { type: "reject", row };
}

function runConfirmedAction() {
    if (!confirmAction.value) return;
    const { type, row } = confirmAction.value;

    reviewingId.value = row.id;
    router.post(
        route(type === "approve" ? "admin.design.approve-payment" : "admin.design.reject-payment", row.id),
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                reviewingId.value = null;
                confirmAction.value = null;
            },
        },
    );
}

function formatDate(value: string) {
    return new Date(value).toLocaleDateString("en-PH", {
        year: "numeric",
        month: "short",
        day: "numeric",
    });
}

function formatCurrency(value: number) {
    return `₱${value.toLocaleString("en-PH")}`;
}

const isEditingDetails = ref(false);
const isEditingQr = ref(false);
const qrPreview = ref<string | null>(null);
const copied = ref(false);

const detailsForm = useForm({
    account_name: props.gcash.account_name,
    account_number: props.gcash.account_number,
    instructions: props.gcash.instructions ?? "",
});

const qrForm = useForm<{ qr_image: File | null }>({
    qr_image: null,
});

function startEditDetails() {
    detailsForm.reset();
    detailsForm.account_name = props.gcash.account_name;
    detailsForm.account_number = props.gcash.account_number;
    detailsForm.instructions = props.gcash.instructions ?? "";
    isEditingDetails.value = true;
}

function cancelEditDetails() {
    detailsForm.clearErrors();
    isEditingDetails.value = false;
}

function saveDetails() {
    detailsForm.put(route("admin.gcash.details-update"), {
        preserveScroll: true,
        onSuccess: () => (isEditingDetails.value = false),
    });
}

function startEditQr() {
    qrPreview.value = null;
    qrForm.reset();
    isEditingQr.value = true;
}

function cancelEditQr() {
    qrForm.clearErrors();
    qrPreview.value = null;
    isEditingQr.value = false;
}

function onQrFileChange(e: Event) {
    const file = (e.target as HTMLInputElement).files?.[0];
    if (!file) return;
    qrForm.qr_image = file;
    qrPreview.value = URL.createObjectURL(file);
}

function saveQr() {
    qrForm.post(route("admin.gcash.qr-update"), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            isEditingQr.value = false;
            qrPreview.value = null;
        },
    });
}

async function copyAccountNumber() {
    try {
        await navigator.clipboard.writeText(props.gcash.account_number);
        copied.value = true;
        setTimeout(() => (copied.value = false), 1500);
    } catch {
        // clipboard unavailable — silently ignore
    }
}

function printQr() {
    if (!props.gcash.qr_image_url) return;
    const win = window.open("", "_blank");
    if (!win) return;
    win.document.write(`
        <html>
            <head><title>GCash QR — ${props.gcash.account_name}</title></head>
            <body style="margin:0;display:flex;align-items:center;justify-content:center;height:100vh;">
                <img src="${props.gcash.qr_image_url}" style="max-width:90vw;max-height:90vh;" onload="window.print();" />
            </body>
        </html>
    `);
    win.document.close();
}
</script>

<template>
    <Head title="Gcash" />

    <AdminLayout>
        <div class="space-y-6">
            <div v-reveal class="flex items-center gap-3">
                <div class="w-11 h-11 rounded-xl bg-indigo-500/10 border border-indigo-500/20 flex items-center justify-center text-indigo-400 shrink-0">
                    <font-awesome-icon icon="fa-solid fa-wallet" />
                </div>
                <div class="flex flex-col gap-1">
                    <h1 class="text-2xl font-extrabold text-white tracking-tight">GCash Details</h1>
                    <p class="text-sm text-slate-400">
                        Manage the GCash account details and QR code shown to customers at checkout.
                    </p>
                </div>
            </div>

            <!-- Stat cards -->
            <div v-reveal="80" class="grid grid-cols-1 sm:grid-cols-3 gap-3.5">
                <div v-reveal="0" class="glass-panel rounded-2xl p-5 relative overflow-hidden group hover:border-indigo-500/40 transition-all">
                    <div class="absolute -right-4 -bottom-4 w-20 h-20 bg-indigo-500/10 rounded-full blur-xl group-hover:bg-indigo-400/20 transition-all"></div>
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium text-slate-400">Down payments submitted</span>
                        <span class="p-1.5 rounded-lg bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">
                            <font-awesome-icon icon="fa-solid fa-receipt" class="text-xs" />
                        </span>
                    </div>
                    <div class="text-2xl font-bold text-white mt-3">{{ stats.submitted }}</div>
                </div>
                <div v-reveal="70" class="glass-panel rounded-2xl p-5 relative overflow-hidden group hover:border-amber-500/40 transition-all">
                    <div class="absolute -right-4 -bottom-4 w-20 h-20 bg-amber-500/10 rounded-full blur-xl group-hover:bg-amber-400/20 transition-all"></div>
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium text-slate-400">Awaiting verification</span>
                        <span class="p-1.5 rounded-lg bg-amber-500/10 text-amber-400 border border-amber-500/20">
                            <font-awesome-icon icon="fa-solid fa-hourglass-half" class="text-xs" />
                        </span>
                    </div>
                    <div class="text-2xl font-bold text-amber-300 mt-3">{{ stats.pending_review }}</div>
                </div>
                <div v-reveal="140" class="glass-panel rounded-2xl p-5 relative overflow-hidden group hover:border-emerald-500/40 transition-all">
                    <div class="absolute -right-4 -bottom-4 w-20 h-20 bg-emerald-500/10 rounded-full blur-xl group-hover:bg-emerald-400/20 transition-all"></div>
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium text-slate-400">Verified</span>
                        <span class="p-1.5 rounded-lg bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                            <font-awesome-icon icon="fa-solid fa-circle-check" class="text-xs" />
                        </span>
                    </div>
                    <div class="text-2xl font-bold text-emerald-300 mt-3">{{ stats.approved }}</div>
                </div>
            </div>

            <div v-reveal="140" class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-start">
                <!-- GCash Account Configuration -->
                <div class="lg:col-span-7 glass-panel rounded-2xl overflow-hidden">
                    <div class="flex items-center justify-between border-b border-white/5 px-5 py-4 bg-black/10">
                        <div class="flex items-center gap-2.5">
                            <div class="w-7 h-7 rounded-lg bg-indigo-500/20 text-indigo-400 border border-indigo-500/30 flex items-center justify-center text-xs">
                                <font-awesome-icon icon="fa-solid fa-sliders" />
                            </div>
                            <h2 class="text-sm font-bold text-white">GCash Account Configuration</h2>
                        </div>

                        <button
                            v-if="!isEditingDetails"
                            type="button"
                            class="inline-flex items-center gap-1.5 rounded-md px-2.5 py-1.5 text-xs font-medium border border-indigo-500/30 text-indigo-300 hover:bg-indigo-600/10 transition-colors"
                            @click="startEditDetails"
                        >
                            <font-awesome-icon icon="fa-solid fa-edit" />
                            Edit
                        </button>

                        <div v-else class="flex items-center gap-2">
                            <button
                                type="button"
                                class="inline-flex items-center gap-1.5 rounded-md px-2.5 py-1.5 text-xs font-medium text-slate-400 hover:bg-slate-800/60 transition-colors"
                                :disabled="detailsForm.processing"
                                @click="cancelEditDetails"
                            >
                                <font-awesome-icon icon="fa-solid fa-xmark" />
                                Cancel
                            </button>
                            <button
                                type="button"
                                class="inline-flex items-center gap-1.5 rounded-md bg-indigo-600 px-2.5 py-1.5 text-xs font-medium text-white hover:bg-indigo-500 disabled:opacity-50 transition-colors"
                                :disabled="detailsForm.processing"
                                @click="saveDetails"
                            >
                                <font-awesome-icon icon="fa-solid fa-check" />
                                {{ detailsForm.processing ? "Saving..." : "Save" }}
                            </button>
                        </div>
                    </div>

                    <div class="space-y-5 p-5">
                        <template v-if="!isEditingDetails">
                            <div>
                                <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Account Name (Registered Merchant)</p>
                                <div class="mt-1.5 flex items-center gap-2 rounded-xl bg-slate-950/60 border border-white/10 px-3.5 py-2.5">
                                    <font-awesome-icon icon="fa-solid fa-circle-check" class="text-emerald-400 text-xs" />
                                    <span class="text-white text-sm font-medium">{{ gcash.account_name }}</span>
                                </div>
                            </div>
                            <div>
                                <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500">GCash Account Number</p>
                                <div class="mt-1.5 flex gap-2">
                                    <div class="flex-1 flex items-center gap-2 rounded-xl bg-slate-950/60 border border-white/10 px-3.5 py-2.5">
                                        <font-awesome-icon icon="fa-solid fa-mobile-screen" class="text-indigo-400 text-xs" />
                                        <span class="text-white text-sm font-mono font-bold tracking-wider">{{ gcash.account_number }}</span>
                                    </div>
                                    <button
                                        type="button"
                                        class="px-4 py-2.5 rounded-xl bg-slate-800/80 hover:bg-slate-700 border border-white/10 text-xs font-semibold text-slate-200 flex items-center gap-1.5 transition-colors shrink-0"
                                        @click="copyAccountNumber"
                                    >
                                        <font-awesome-icon :icon="copied ? 'fa-solid fa-check' : 'fa-regular fa-copy'" class="text-xs" />
                                        <span>{{ copied ? "Copied" : "Copy" }}</span>
                                    </button>
                                </div>
                                <p class="text-[11px] text-slate-500 mt-1.5">Shown to customers at checkout, formatted automatically for mobile and desktop.</p>
                            </div>
                            <div>
                                <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Instructions For Customers</p>
                                <p class="mt-1.5 leading-relaxed text-slate-300 text-sm rounded-xl bg-slate-950/60 border border-white/10 px-3.5 py-3">{{ gcash.instructions || "No instructions set." }}</p>
                            </div>
                        </template>

                        <template v-else>
                            <div>
                                <label for="account_name" class="text-xs font-medium uppercase tracking-wide text-slate-500">Account Name</label>
                                <input
                                    id="account_name"
                                    v-model="detailsForm.account_name"
                                    type="text"
                                    name="account_name"
                                    class="mt-1 block w-full rounded-md bg-slate-900/60 border border-white/10 text-slate-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                />
                                <p v-if="detailsForm.errors.account_name" class="mt-1 text-xs text-rose-400">
                                    {{ detailsForm.errors.account_name }}
                                </p>
                            </div>
                            <div>
                                <label for="account_number" class="text-xs font-medium uppercase tracking-wide text-slate-500">Account Number</label>
                                <input
                                    id="account_number"
                                    v-model="detailsForm.account_number"
                                    type="text"
                                    name="account_number"
                                    class="mt-1 block w-full rounded-md bg-slate-900/60 border border-white/10 text-slate-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                />
                                <p v-if="detailsForm.errors.account_number" class="mt-1 text-xs text-rose-400">
                                    {{ detailsForm.errors.account_number }}
                                </p>
                            </div>
                            <div>
                                <label class="text-xs font-medium uppercase tracking-wide text-slate-500">Instructions</label>
                                <textarea
                                    v-model="detailsForm.instructions"
                                    rows="4"
                                    class="mt-1 block w-full rounded-md bg-slate-900/60 border border-white/10 text-slate-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                ></textarea>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- GCash QR Standee -->
                <div class="lg:col-span-5 glass-panel rounded-2xl overflow-hidden">
                    <div class="flex items-center justify-between border-b border-white/5 px-5 py-4 bg-black/10">
                        <div class="flex items-center gap-2.5">
                            <div class="w-7 h-7 rounded-lg bg-cyan-500/20 text-cyan-400 border border-cyan-500/30 flex items-center justify-center text-xs">
                                <font-awesome-icon icon="fa-solid fa-qrcode" />
                            </div>
                            <h2 class="text-sm font-bold text-white">GCash QR Scanner &amp; Code</h2>
                        </div>

                        <button
                            v-if="!isEditingQr"
                            type="button"
                            class="inline-flex items-center gap-1.5 rounded-md px-2.5 py-1.5 text-xs font-medium border border-indigo-500/30 text-indigo-300 hover:bg-indigo-600/10 transition-colors"
                            @click="startEditQr"
                        >
                            <font-awesome-icon icon="fa-solid fa-cloud-arrow-up" />
                            Upload
                        </button>

                        <div v-else class="flex items-center gap-2">
                            <button
                                type="button"
                                class="inline-flex items-center gap-1.5 rounded-md px-2.5 py-1.5 text-xs font-medium text-slate-400 hover:bg-slate-800/60 transition-colors"
                                :disabled="qrForm.processing"
                                @click="cancelEditQr"
                            >
                                <font-awesome-icon icon="fa-solid fa-xmark" />
                                Cancel
                            </button>
                            <button
                                type="button"
                                :disabled="!qrForm.qr_image || qrForm.processing"
                                class="inline-flex items-center gap-1.5 rounded-md bg-indigo-600 px-2.5 py-1.5 text-xs font-medium text-white hover:bg-indigo-500 disabled:cursor-not-allowed disabled:opacity-40 transition-colors"
                                @click="saveQr"
                            >
                                <font-awesome-icon icon="fa-solid fa-check" />
                                {{ qrForm.processing ? "Saving..." : "Save" }}
                            </button>
                        </div>
                    </div>

                    <div class="p-6 flex flex-col items-center">
                        <!-- Merchant standee -->
                        <div class="w-full max-w-sm rounded-2xl bg-gradient-to-b from-[#005CEE] to-[#003B99] p-5 shadow-2xl text-white border border-blue-400/40">
                            <div class="flex items-center justify-between pb-3 border-b border-white/20">
                                <div class="flex items-center gap-2">
                                    <div class="w-6 h-6 rounded-full bg-white flex items-center justify-center text-[#005CEE] font-black text-xs">G</div>
                                    <span class="font-extrabold tracking-wide text-xs uppercase">GCash Merchant</span>
                                </div>
                            </div>
                            <div class="text-center my-3">
                                <p class="text-[10px] tracking-widest uppercase font-semibold text-blue-100">PrintCode</p>
                                <p class="text-base font-extrabold tracking-tight text-white">Scan to Pay via GCash App</p>
                            </div>

                            <div class="bg-white p-4 rounded-xl shadow-inner flex items-center justify-center min-h-[13rem]">
                                <img
                                    v-if="qrPreview || gcash.qr_image_url"
                                    :src="qrPreview ?? gcash.qr_image_url ?? ''"
                                    alt="GCash QR code"
                                    class="w-48 h-48 object-contain"
                                />
                                <div v-else class="flex flex-col items-center gap-2 text-slate-400">
                                    <font-awesome-icon icon="fa-solid fa-image" class="text-5xl" />
                                    <span class="text-xs font-medium">No QR uploaded yet</span>
                                </div>
                            </div>

                            <div class="mt-3 text-center">
                                <p class="text-xs font-mono font-bold tracking-wider text-white">{{ gcash.account_number }}</p>
                                <p class="text-[10px] text-blue-100 font-medium">{{ gcash.account_name }}</p>
                            </div>
                        </div>

                        <label
                            v-if="isEditingQr"
                            class="mt-4 inline-flex cursor-pointer items-center gap-1.5 rounded-md border border-white/10 px-3 py-1.5 text-sm font-medium text-slate-300 hover:bg-slate-800/60 transition-colors"
                        >
                            <font-awesome-icon icon="fa-solid fa-upload" class="text-xs" />
                            Choose image
                            <input type="file" accept="image/*" class="hidden" @change="onQrFileChange" />
                        </label>
                        <p v-if="qrForm.errors.qr_image" class="mt-2 text-xs text-rose-400">{{ qrForm.errors.qr_image }}</p>

                        <div v-if="!isEditingQr && gcash.qr_image_url" class="grid grid-cols-2 gap-2 w-full max-w-sm mt-5">
                            <a
                                :href="gcash.qr_image_url"
                                download
                                class="px-3 py-2 rounded-xl bg-slate-800/80 hover:bg-slate-700 border border-white/10 text-[11px] font-semibold text-slate-300 flex items-center justify-center gap-1.5 transition-colors"
                            >
                                <font-awesome-icon icon="fa-solid fa-download" class="text-xs" />
                                <span>Download</span>
                            </a>
                            <button
                                type="button"
                                class="px-3 py-2 rounded-xl bg-slate-800/80 hover:bg-slate-700 border border-white/10 text-[11px] font-semibold text-slate-300 flex items-center justify-center gap-1.5 transition-colors"
                                @click="printQr"
                            >
                                <font-awesome-icon icon="fa-solid fa-print" class="text-xs" />
                                <span>Print QR</span>
                            </button>
                        </div>

                        <p class="text-center text-xs text-slate-500 mt-4">This QR code is shown to customers at checkout.</p>
                    </div>
                </div>
            </div>

            <!-- Recent GCash payment submissions -->
            <div v-reveal="200" class="glass-panel rounded-2xl overflow-hidden">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-white/5 px-5 py-4 bg-black/10">
                    <div>
                        <h3 class="text-sm font-bold text-white">Recent GCash Payment Submissions</h3>
                        <p class="text-xs text-slate-400 mt-0.5">Down-payment proofs uploaded by customers on design requests</p>
                    </div>
                    <Link
                        :href="route('admin.design.index')"
                        class="inline-flex items-center gap-1.5 text-xs font-medium text-indigo-300 hover:text-indigo-200 transition-colors"
                    >
                        View all in Design Requests
                        <font-awesome-icon icon="fa-solid fa-arrow-right" class="text-[10px]" />
                    </Link>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-white/5">
                                <th class="px-4 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-slate-400">Reference</th>
                                <th class="px-4 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-slate-400">Order / Team</th>
                                <th class="px-4 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-slate-400">Amount</th>
                                <th class="px-4 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-slate-400">Sender GCash No.</th>
                                <th class="px-4 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-slate-400">Proof</th>
                                <th class="px-4 py-3 text-center text-[11px] font-bold uppercase tracking-wider text-slate-400">Status</th>
                                <th class="px-4 py-3 text-right text-[11px] font-bold uppercase tracking-wider text-slate-400">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            <tr v-if="submissions.length === 0">
                                <td colspan="7" class="px-4 py-10 text-center text-sm text-slate-500">No GCash payment proofs submitted yet.</td>
                            </tr>
                            <tr v-for="row in submissions" :key="row.id" class="hover:bg-slate-800/30 transition-colors">
                                <td class="px-4 py-3">
                                    <span class="font-mono font-bold text-indigo-400 text-xs">{{ row.reference_number || "—" }}</span>
                                    <p class="text-[10px] text-slate-500 mt-0.5">{{ formatDate(row.updated_at) }}</p>
                                </td>
                                <td class="px-4 py-3">
                                    <p class="font-semibold text-white text-xs">{{ row.team_name }}</p>
                                    <p class="text-[11px] text-slate-400 mt-0.5">{{ row.customer_name }}<span v-if="row.estimated_quantity"> • {{ row.estimated_quantity }} pcs</span></p>
                                </td>
                                <td class="px-4 py-3 font-mono font-bold text-emerald-400 text-xs">{{ formatCurrency(row.template_price) }}</td>
                                <td class="px-4 py-3 font-mono text-slate-300 text-xs">{{ row.gcash_number || "—" }}</td>
                                <td class="px-4 py-3">
                                    <a
                                        v-if="row.proof_image_url"
                                        :href="row.proof_image_url"
                                        target="_blank"
                                        class="w-8 h-8 rounded-lg bg-slate-800 border border-white/10 flex items-center justify-center text-slate-400 hover:border-indigo-400 hover:text-indigo-300 transition-colors"
                                        title="View receipt proof"
                                    >
                                        <font-awesome-icon icon="fa-solid fa-image" class="text-xs" />
                                    </a>
                                    <span v-else class="text-slate-600">—</span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold border" :class="submissionStatus[row.status].class">
                                        {{ submissionStatus[row.status].label }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div v-if="row.status === 'pending_down_payment_review'" class="flex items-center justify-end gap-1.5">
                                        <button
                                            type="button"
                                            :disabled="reviewingId === row.id"
                                            class="px-2.5 py-1 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white font-medium text-[11px] transition-colors disabled:opacity-50"
                                            @click="askApprove(row)"
                                        >
                                            Approve
                                        </button>
                                        <button
                                            type="button"
                                            :disabled="reviewingId === row.id"
                                            class="px-2.5 py-1 rounded-lg bg-rose-500/20 hover:bg-rose-500/30 text-rose-300 border border-rose-500/30 font-medium text-[11px] transition-colors disabled:opacity-50"
                                            @click="askReject(row)"
                                        >
                                            Reject
                                        </button>
                                    </div>
                                    <span v-else class="text-slate-600 text-xs">—</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Approve / Reject confirmation -->
        <Modal :show="!!confirmAction" @close="confirmAction = null" :maxWidth="'sm'">
            <ModalHeader
                :icon="confirmAction?.type === 'approve' ? 'fa-solid fa-circle-check' : 'fa-solid fa-xmark-circle'"
                :icon-class="confirmAction?.type === 'approve'
                    ? 'text-emerald-400 bg-emerald-500/15 border-emerald-500/25'
                    : 'text-rose-400 bg-rose-500/15 border-rose-500/25'"
                :title="confirmAction?.type === 'approve' ? 'Approve this payment?' : 'Reject this payment?'"
                @close="confirmAction = null"
            />
            <div v-if="confirmAction" class="px-5 py-5 text-slate-200">
                <p class="text-sm text-slate-400">
                    <template v-if="confirmAction.type === 'approve'">
                        This creates a real order for
                        <span class="font-semibold text-white">{{ confirmAction.row.team_name }}</span>
                        ({{ confirmAction.row.customer_name }}) and cannot be undone.
                    </template>
                    <template v-else>
                        <span class="font-semibold text-white">{{ confirmAction.row.customer_name }}</span>
                        will be asked to resubmit their GCash payment proof for
                        <span class="font-semibold text-white">{{ confirmAction.row.team_name }}</span>.
                    </template>
                </p>
                <div class="mt-5 pt-4 border-t border-white/10 flex flex-col-reverse gap-2 sm:flex-row sm:justify-between">
                    <SecondaryButton type="button" class="flex items-center justify-center" @click="confirmAction = null">Cancel</SecondaryButton>
                    <PrimaryButton
                        type="button"
                        class="flex items-center justify-center gap-1"
                        :class="confirmAction.type === 'approve' ? '' : '!bg-rose-600 hover:!bg-rose-500'"
                        :disabled="reviewingId === confirmAction.row.id"
                        @click="runConfirmedAction"
                    >
                        <div class="text-sm" v-if="reviewingId === confirmAction.row.id">
                            <font-awesome-icon icon="fa-solid fa-spinner" spin />
                        </div>
                        {{ confirmAction.type === "approve" ? "Approve" : "Reject" }}
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
