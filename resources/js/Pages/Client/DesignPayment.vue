<script setup lang="ts">
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import Breadcrumbs from "@/Components/Breadcrumbs.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import InputError from "@/Components/InputError.vue";
import type { DesignRequest } from "@/types/jersey";
import { Head, Link, useForm } from "@inertiajs/vue3";
import { computed, ref } from "vue";

interface GcashSetting {
    account_name: string;
    account_number: string;
    instructions: string | null;
    qr_image_url: string | null;
}

const props = defineProps<{ request: DesignRequest; gcash: GcashSetting }>();

const gcashQrImage = "https://placehold.co/300x300?text=GCash+QR";
const rosterPlayers = computed(() => props.request.players ?? []);

const totalAmount = computed(() => props.request.estimated_quantity * props.request.template_price);
const depositAmount = computed(() => totalAmount.value / 2);
const balanceAmount = computed(() => totalAmount.value - depositAmount.value);

function formatPeso(value: number) {
    return `₱${value.toLocaleString("en-PH", { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
}

const copiedGcashNumber = ref(false);
async function copyGcashNumber() {
    try {
        await navigator.clipboard.writeText(props.gcash.account_number);
        copiedGcashNumber.value = true;
        setTimeout(() => (copiedGcashNumber.value = false), 1500);
    } catch {
        // Clipboard API unavailable — the number is still visible to copy manually.
    }
}

const paymentPreview = ref<string | null>(null);
const paymentForm = useForm({
    gcash_number: "",
    reference_number: "",
    proof_image: null as File | null,
});

function handleProofUpload(e: Event) {
    const target = e.target as HTMLInputElement;
    const file = target.files?.[0];
    if (!file) return;
    paymentForm.proof_image = file;
    paymentPreview.value = URL.createObjectURL(file);
}

function removeProofImage() {
    paymentForm.proof_image = null;
    paymentPreview.value = null;
}

function submitPayment() {
    paymentForm.post(route("client.design.pay", props.request.id), {
        forceFormData: true,
    });
}
</script>

<template>
    <Head title="GCash Payment" />

    <AuthenticatedLayout>
        <Breadcrumbs
            :items="[
                { label: 'My Orders', href: route('client.orders.index') },
                { label: request.team_name, href: route('client.design.show', request.id) },
                { label: '50% Down Payment' },
            ]"
        />

        <!-- Header banner -->
        <section v-reveal class="rounded-2xl bg-gradient-to-r from-cobalt to-cobalt-dark text-white shadow-lg p-5 sm:p-6 mb-6">
            <div class="flex items-center gap-3">
                <div class="w-11 h-11 rounded-xl bg-white/15 flex items-center justify-center shrink-0">
                    <font-awesome-icon icon="fa-solid fa-shield-halved" class="text-lg" />
                </div>
                <div>
                    <h1 class="text-xl font-black tracking-tight">50% Down Payment</h1>
                    <p class="text-sm text-white/70 mt-0.5">{{ request.team_name }} — {{ request.template_name }}</p>
                </div>
            </div>
        </section>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <!-- LEFT: order summary + roster -->
            <section v-reveal="80" class="lg:col-span-5 flex flex-col gap-6">
                <div class="rounded-2xl bg-white border border-ink/10 shadow-sm p-5">
                    <h2 class="text-sm font-black uppercase tracking-wide text-ink/70 mb-3">Order Summary</h2>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-ink/60">{{ request.estimated_quantity }} sets × ₱{{ request.template_price }}</span>
                        <span class="font-semibold text-ink">{{ formatPeso(totalAmount) }}</span>
                    </div>
                    <div v-if="rosterPlayers.length" class="flex items-center gap-1.5 mt-2 text-xs text-ink/50">
                        <font-awesome-icon icon="fa-solid fa-users" class="text-cobalt/60" />
                        <span>{{ rosterPlayers.length }} player{{ rosterPlayers.length === 1 ? "" : "s" }} on roster</span>
                    </div>
                    <hr class="my-3 border-ink/10" />
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-bold text-ink/70 uppercase tracking-wide">Amount Due Now (50%)</p>
                            <p class="text-[11px] text-ink/40">Balance of {{ formatPeso(balanceAmount) }} due once production starts.</p>
                        </div>
                        <span class="text-xl font-black text-cobalt">{{ formatPeso(depositAmount) }}</span>
                    </div>
                </div>

                <div class="rounded-2xl border border-cobalt/20 bg-cobalt/5 p-5">
                    <p class="text-sm font-semibold text-ink mb-3 flex items-center gap-1.5">
                        <font-awesome-icon icon="fa-solid fa-qrcode" class="text-cobalt" />
                        Send payment via GCash
                    </p>
                    <div class="flex flex-col items-center gap-4 sm:flex-row sm:items-start">
                        <div class="flex flex-shrink-0 flex-col items-center gap-1.5">
                            <div class="flex h-32 w-32 items-center justify-center overflow-hidden rounded-lg border border-ink/10 bg-white p-1.5">
                                <img :src="gcash.qr_image_url ?? gcashQrImage" alt="GCash QR code" class="h-full w-full object-contain" />
                            </div>
                            <span class="text-[10px] font-medium uppercase tracking-wide text-ink/40">Scan to pay</span>
                        </div>
                        <div class="flex-1 text-sm w-full">
                            <p class="text-[10px] font-medium uppercase tracking-wide text-ink/40">Account Name</p>
                            <p class="text-ink font-medium">{{ gcash.account_name }}</p>
                            <p class="mt-2 text-[10px] font-medium uppercase tracking-wide text-ink/40">Account Number</p>
                            <div class="flex items-center gap-2">
                                <p class="text-ink font-medium">{{ gcash.account_number }}</p>
                                <button type="button" @click="copyGcashNumber" class="text-[11px] font-bold text-cobalt hover:text-cobalt-dark flex items-center gap-1">
                                    <font-awesome-icon :icon="copiedGcashNumber ? 'fa-solid fa-check' : 'fa-regular fa-copy'" />
                                    {{ copiedGcashNumber ? "Copied" : "Copy" }}
                                </button>
                            </div>
                            <p class="mt-2 text-xs text-ink/60 leading-relaxed">{{ gcash.instructions }}</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- RIGHT: payment proof form -->
            <section v-reveal="140" class="lg:col-span-7 rounded-2xl bg-white border border-ink/10 shadow-sm p-5 sm:p-6">
                <h2 class="text-sm font-black uppercase tracking-wide text-ink/70 mb-4">Submit Payment Proof</h2>
                <form @submit.prevent="submitPayment" class="flex flex-col gap-4">
                    <div>
                        <label for="gcash_number" class="block text-sm font-medium text-ink mb-1">Your GCash Number</label>
                        <input
                            id="gcash_number"
                            v-model="paymentForm.gcash_number"
                            type="text"
                            inputmode="numeric"
                            placeholder="09XX XXX XXXX"
                            class="w-full rounded-md border border-ink/15 px-3 py-2 text-sm outline-none focus:border-cobalt focus:ring-1 focus:ring-cobalt"
                            required
                        />
                        <InputError :message="paymentForm.errors.gcash_number" class="mt-1" />
                    </div>

                    <div>
                        <label for="reference_number" class="block text-sm font-medium text-ink mb-1">Transaction Reference Number</label>
                        <input
                            id="reference_number"
                            v-model="paymentForm.reference_number"
                            type="text"
                            placeholder="e.g. 1234567890123"
                            class="w-full rounded-md border border-ink/15 px-3 py-2 text-sm outline-none focus:border-cobalt focus:ring-1 focus:ring-cobalt"
                            required
                        />
                        <InputError :message="paymentForm.errors.reference_number" class="mt-1" />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-ink mb-1">Upload Transaction Screenshot</label>
                        <div v-if="!paymentPreview">
                            <label class="flex flex-col items-center justify-center gap-2 rounded-lg border-2 border-dashed border-cobalt/20 bg-cobalt/[0.02] py-8 cursor-pointer hover:border-cobalt/40 hover:bg-cobalt/5 transition-colors">
                                <font-awesome-icon icon="fa-solid fa-cloud-arrow-up" class="text-2xl text-cobalt/50" />
                                <span class="text-xs text-ink/60">Click to upload (JPG, PNG)</span>
                                <input type="file" accept="image/*" class="hidden" @change="handleProofUpload" />
                            </label>
                        </div>
                        <div v-else class="relative w-full">
                            <img :src="paymentPreview" alt="Payment proof preview" class="w-full max-h-72 object-contain rounded-lg border border-ink/10 bg-ink/[0.02]" />
                            <button type="button" @click="removeProofImage" class="absolute top-2 right-2 bg-accent text-white rounded-full h-7 w-7 flex items-center justify-center hover:bg-accent-dark">
                                <font-awesome-icon icon="fa-solid fa-xmark" />
                            </button>
                        </div>
                        <InputError :message="paymentForm.errors.proof_image" class="mt-1" />
                    </div>

                    <div class="mt-2 flex justify-between">
                        <Link :href="route('client.design.show', request.id)">
                            <SecondaryButton type="button">Cancel</SecondaryButton>
                        </Link>
                        <PrimaryButton
                            type="submit"
                            :disabled="paymentForm.processing || !paymentForm.proof_image"
                            class="flex items-center gap-2"
                            :class="{ 'opacity-25': paymentForm.processing }"
                        >
                            <font-awesome-icon v-if="paymentForm.processing" icon="fa-solid fa-spinner" spin />
                            <font-awesome-icon v-else icon="fa-solid fa-paper-plane" />
                            {{ paymentForm.processing ? "Submitting..." : "Submit Payment" }}
                        </PrimaryButton>
                    </div>
                </form>
            </section>
        </div>
    </AuthenticatedLayout>
</template>
