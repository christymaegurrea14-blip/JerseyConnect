<script setup lang="ts">
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { Head, useForm } from "@inertiajs/vue3";
import { ref, reactive } from "vue";

interface GcashSetting {
    account_name: string;
    account_number: string;
    instructions: string | null;
    qr_image_url: string | null;
}

const props = defineProps<{ gcash: GcashSetting }>();

const isEditingDetails = ref(false);
const isEditingQr = ref(false);
const qrPreview = ref<string | null>(null);

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
</script>

<template>
    <Head title="Gcash" />

    <AdminLayout>
        <div class="space-y-6">
            <div class="flex flex-col gap-1">
                <h1 class="text-2xl font-extrabold text-white tracking-tight">GCash</h1>
                <p class="text-sm text-slate-400">
                    Manage the GCash account details and QR code shown to customers at checkout.
                </p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                <!-- GCash Details Card -->
                <div class="glass-panel rounded-2xl overflow-hidden">
                    <div class="flex items-center justify-between border-b border-white/5 px-5 py-3.5">
                        <h2 class="font-semibold text-white">GCash Details</h2>

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

                    <div class="space-y-4 p-5">
                        <template v-if="!isEditingDetails">
                            <div>
                                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Account Name</p>
                                <p class="mt-1 text-white">{{ gcash.account_name }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Account Number</p>
                                <p class="mt-1 text-white">{{ gcash.account_number }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Instructions</p>
                                <p class="mt-1 leading-relaxed text-slate-300">{{ gcash.instructions }}</p>
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
                                    rows="3"
                                    class="mt-1 block w-full rounded-md bg-slate-900/60 border border-white/10 text-slate-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                ></textarea>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- GCash QR Scanner Card -->
                <div class="glass-panel rounded-2xl overflow-hidden">
                    <div class="flex items-center justify-between border-b border-white/5 px-5 py-3.5">
                        <h2 class="font-semibold text-white">GCash QR Scanner</h2>

                        <button
                            v-if="!isEditingQr"
                            type="button"
                            class="inline-flex items-center gap-1.5 rounded-md px-2.5 py-1.5 text-xs font-medium border border-indigo-500/30 text-indigo-300 hover:bg-indigo-600/10 transition-colors"
                            @click="startEditQr"
                        >
                            <font-awesome-icon icon="fa-solid fa-edit" />
                            Edit
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

                    <div class="flex flex-col items-center gap-4 p-5">
                        <div class="flex h-auto w-60 items-center justify-center overflow-hidden rounded-lg border border-white/10 bg-slate-950/50">
                            <img
                                v-if="qrPreview || gcash.qr_image_url"
                                :src="qrPreview ?? gcash.qr_image_url ?? ''"
                                alt="GCash QR code"
                                class="h-full w-full object-contain"
                            />
                            <font-awesome-icon v-else icon="fa-solid fa-image" class="h-60 w-60 text-slate-700" />
                        </div>

                        <label
                            v-if="isEditingQr"
                            class="inline-flex cursor-pointer items-center gap-1.5 rounded-md border border-white/10 px-3 py-1.5 text-sm font-medium text-slate-300 hover:bg-slate-800/60 transition-colors"
                        >
                            <font-awesome-icon icon="fa-solid fa-upload" class="text-xs" />
                            Choose image
                            <input type="file" accept="image/*" class="hidden" @change="onQrFileChange" />
                        </label>
                        <p v-if="qrForm.errors.qr_image" class="text-xs text-rose-400">{{ qrForm.errors.qr_image }}</p>

                        <p class="text-center text-xs text-slate-500">This QR code is shown to customers.</p>
                    </div>
                </div>
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
