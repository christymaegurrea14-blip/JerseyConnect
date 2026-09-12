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
        <div class="mx-auto bg-white p-4 rounded-lg">
            <div>
                <h1 class="text-xl font-semibold text-gray-900">
                    <font-awesome-icon icon="fa-solid fa-money-bill-wave" />
                    GCash
                </h1>
                <p class="mt-1 text-gray-500">
                    Manage the GCash account details and QR code shown to
                    customers at checkout.
                </p>
            </div>
            <hr class="my-2" />
            <div class="grid grid-cols-1 gap-2">
                <!-- GCash Details Card -->
                <div
                    class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm"
                >
                    <div
                        class="flex items-center justify-between border-b border-gray-200 bg-ink/5 px-4 py-2"
                    >
                        <h2 class="font-semibold text-gray-900">
                            GCash Details
                        </h2>

                        <button
                            v-if="!isEditingDetails"
                            type="button"
                            class="inline-flex items-center gap-1.5 rounded-md px-2 py-1 font-medium border border-blue-600 text-blue-600 hover:bg-blue-50"
                            @click="startEditDetails"
                        >
                            <font-awesome-icon icon="fa-solid fa-edit" />
                            Edit
                        </button>

                        <div v-else class="flex items-center gap-2">
                            <button
                                type="button"
                                class="inline-flex items-center gap-1.5 rounded-md px-2 py-1 font-medium text-gray-500 hover:bg-gray-50"
                                :disabled="detailsForm.processing"
                                @click="cancelEditDetails"
                            >
                                <font-awesome-icon icon="fa-solid fa-xmark" />
                                Cancel
                            </button>
                            <button
                                type="button"
                                class="inline-flex items-center gap-1.5 rounded-md bg-blue-600 px-2 py-1 font-medium text-white hover:bg-blue-700 disabled:opacity-50"
                                :disabled="detailsForm.processing"
                                @click="saveDetails"
                            >
                                <font-awesome-icon icon="fa-solid fa-check" />
                                {{
                                    detailsForm.processing
                                        ? "Saving..."
                                        : "Save"
                                }}
                            </button>
                        </div>
                    </div>

                    <div class="space-y-4 p-4">
                        <template v-if="!isEditingDetails">
                            <div>
                                <p
                                    class="text-xs font-medium uppercase tracking-wide text-gray-400"
                                >
                                    Account Name
                                </p>
                                <p class="mt-1 text-gray-900">
                                    {{ gcash.account_name }}
                                </p>
                            </div>
                            <div>
                                <p
                                    class="text-xs font-medium uppercase tracking-wide text-gray-400"
                                >
                                    Account Number
                                </p>
                                <p class="mt-1 text-gray-900">
                                    {{ gcash.account_number }}
                                </p>
                            </div>
                            <div>
                                <p
                                    class="text-xs font-medium uppercase tracking-wide text-gray-400"
                                >
                                    Instructions
                                </p>
                                <p class="mt-1 leading-relaxed text-gray-600">
                                    {{ gcash.instructions }}
                                </p>
                            </div>
                        </template>

                        <template v-else>
                            <div>
                                <label
                                    for="account_name"
                                    class="text-xs font-medium uppercase tracking-wide text-gray-400"
                                    >Account Name</label
                                >
                                <input
                                    id="account_name"
                                    v-model="detailsForm.account_name"
                                    type="text"
                                    name="account_name"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                />
                                <p
                                    v-if="detailsForm.errors.account_name"
                                    class="mt-1 text-xs text-red-600"
                                >
                                    {{ detailsForm.errors.account_name }}
                                </p>
                            </div>
                            <div>
                                <label
                                    for="account_number"
                                    class="text-xs font-medium uppercase tracking-wide text-gray-400"
                                    >Account Number</label
                                >
                                <input
                                    id="account_number"
                                    v-model="detailsForm.account_number"
                                    type="text"
                                    name="account_number"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                />
                                <p
                                    v-if="detailsForm.errors.account_number"
                                    class="mt-1 text-xs text-red-600"
                                >
                                    {{ detailsForm.errors.account_number }}
                                </p>
                            </div>
                            <div>
                                <label
                                    class="text-xs font-medium uppercase tracking-wide text-gray-400"
                                    >Instructions</label
                                >
                                <textarea
                                    v-model="detailsForm.instructions"
                                    rows="3"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                ></textarea>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- GCash QR Scanner Card -->
                <div
                    class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm"
                >
                    <div
                        class="flex items-center justify-between border-b border-gray-200 bg-ink/5 px-4 py-2"
                    >
                        <h2 class="font-semibold text-gray-900">
                            GCash QR Scanner
                        </h2>

                        <button
                            v-if="!isEditingQr"
                            type="button"
                            class="inline-flex items-center gap-1.5 rounded-md px-2 py-1 font-medium border border-blue-600 text-blue-600 hover:bg-blue-50"
                            @click="startEditQr"
                        >
                            <font-awesome-icon icon="fa-solid fa-edit" />
                            Edit
                        </button>

                        <div v-else class="flex items-center gap-2">
                            <button
                                type="button"
                                class="inline-flex items-center gap-1.5 rounded-md px-2 py-1 font-medium text-gray-500 hover:bg-gray-50"
                                :disabled="qrForm.processing"
                                @click="cancelEditQr"
                            >
                                <font-awesome-icon icon="fa-solid fa-xmark" />
                                Cancel
                            </button>
                            <button
                                type="button"
                                :disabled="
                                    !qrForm.qr_image || qrForm.processing
                                "
                                class="inline-flex items-center gap-1.5 rounded-md bg-blue-600 px-2 py-1 font-medium text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:bg-blue-300"
                                @click="saveQr"
                            >
                                <font-awesome-icon icon="fa-solid fa-check" />
                                {{ qrForm.processing ? "Saving..." : "Save" }}
                            </button>
                        </div>
                    </div>

                    <div class="flex flex-col items-center gap-4 p-4">
                        <div
                            class="flex h-auto w-60 items-center justify-center overflow-hidden rounded-lg border border-gray-200 bg-gray-50"
                        >
                            <img
                                v-if="qrPreview || gcash.qr_image_url"
                                :src="qrPreview ?? gcash.qr_image_url ?? ''"
                                alt="GCash QR code"
                                class="h-full w-full object-contain"
                            />
                            <font-awesome-icon
                                v-else
                                icon="fa-solid fa-image"
                                class="h-60 w-60 text-gray-300"
                            />
                        </div>

                        <label
                            v-if="isEditingQr"
                            class="inline-flex cursor-pointer items-center gap-1.5 rounded-md border border-gray-300 px-3 py-1.5 font-medium text-gray-700 hover:bg-gray-50"
                        >
                            <font-awesome-icon icon="fa-solid fa-upload" class="text-xs" />
                            Choose image
                            <input
                                type="file"
                                accept="image/*"
                                class="hidden"
                                @change="onQrFileChange"
                            />
                        </label>
                        <p
                            v-if="qrForm.errors.qr_image"
                            class="text-xs text-red-600"
                        >
                            {{ qrForm.errors.qr_image }}
                        </p>

                        <p class="text-center text-xs text-gray-500">
                            This QR code is shown to customers.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
