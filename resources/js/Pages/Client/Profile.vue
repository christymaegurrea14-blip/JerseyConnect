<script setup>
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import { Head, useForm, usePage } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { computed, ref } from "vue";

const props = defineProps({
    stats: {
        type: Object,
        default: () => ({
            design_requests_count: 0,
            orders_count: 0,
            total_sets: 0,
            teams_count: 0,
        }),
    },
});

const page = usePage();
const user = computed(() => page.props.auth.user);

const userName = computed(() => {
    const info = user.value.user_info;
    const name = [info?.first_name, info?.last_name].filter(Boolean).join(" ");
    return name || user.value.email || "Client";
});
const userInitials = computed(() =>
    userName.value
        .split(" ")
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0]?.toUpperCase())
        .join("") || "C",
);

const memberSince = computed(() => {
    if (!user.value.created_at) return null;
    return new Date(user.value.created_at).toLocaleDateString("en-PH", {
        year: "numeric",
        month: "long",
    });
});

// ── Avatar ───────────────────────────────────────────────────────────────────
const avatarForm = useForm({ avatar: null });
const avatarPreview = ref(null);
const avatarInput = ref(null);

function pickAvatar() {
    avatarInput.value?.click();
}

function handleAvatarChange(e) {
    const file = e.target.files?.[0];
    if (!file) return;

    avatarForm.avatar = file;
    avatarPreview.value = URL.createObjectURL(file);

    avatarForm.post(route("update-avatar"), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            avatarForm.reset();
            avatarPreview.value = null;
        },
        onError: () => {
            avatarPreview.value = null;
        },
    });

    e.target.value = ""; // allow re-selecting the same file later
}

// ── Information / Credentials ───────────────────────────────────────────────
const user_information = useForm({
    first_name: user.value.user_info?.first_name,
    middle_name: user.value.user_info?.middle_name ?? "",
    last_name: user.value.user_info?.last_name,
    birth_date: user.value.user_info?.birth_date,
    phone: user.value.user_info?.phone,
    address: user.value.user_info?.address,
});

const user_credentials = useForm({
    email: user.value.email,
    password: "",
    password_confirmation: "",
});

const submitInformation = () => {
    user_information.put(route("update-information"));
};

const submitCredentials = () => {
    user_credentials.put(route("update-credentials"), {
        onSuccess: () =>
            user_credentials.reset("password", "password_confirmation"),
    });
};
</script>

<template>
    <Head title="Profile" />

    <AuthenticatedLayout>
        <!-- Header -->
        <section v-reveal class="rounded-2xl bg-white border border-ink/10 shadow-sm p-5 sm:p-6 mb-6">
            <div class="flex items-center gap-3.5">
                <div class="w-11 h-11 rounded-xl bg-cobalt/10 text-cobalt flex items-center justify-center border border-cobalt/20 shrink-0">
                    <font-awesome-icon icon="fa-solid fa-id-card" class="text-lg" />
                </div>
                <div>
                    <h1 class="text-xl font-black tracking-tight text-ink">My Profile</h1>
                    <p class="text-sm text-ink/50 mt-0.5">Manage your personal details and login credentials.</p>
                </div>
            </div>
        </section>

        <!-- Hero identity card -->
        <section v-reveal="80" class="relative overflow-hidden rounded-2xl bg-white border border-ink/10 shadow-sm p-5 sm:p-6 mb-6">
            <div class="absolute -right-16 -top-16 w-64 h-64 rounded-full bg-cobalt/5 blur-3xl pointer-events-none"></div>
            <div class="relative flex flex-col xl:flex-row xl:items-center justify-between gap-6">
                <!-- Identity -->
                <div class="flex flex-col sm:flex-row items-start sm:items-center gap-5">
                    <button
                        type="button"
                        class="group relative w-24 h-24 sm:w-28 sm:h-28 rounded-2xl shadow-sm shrink-0"
                        :disabled="avatarForm.processing"
                        @click="pickAvatar"
                    >
                        <img
                            v-if="avatarPreview || user.user_info?.avatar_url"
                            :src="avatarPreview || user.user_info?.avatar_url"
                            alt="Profile picture"
                            class="w-24 h-24 sm:w-28 sm:h-28 rounded-2xl object-cover ring-2 ring-white"
                            :class="{ 'opacity-50': avatarForm.processing }"
                        />
                        <div
                            v-else
                            class="w-24 h-24 sm:w-28 sm:h-28 rounded-2xl bg-gradient-to-tr from-cobalt to-accent flex items-center justify-center font-black text-2xl text-white ring-2 ring-white"
                        >
                            {{ userInitials }}
                        </div>

                        <div
                            class="absolute inset-0 rounded-2xl bg-ink/50 opacity-0 group-hover:opacity-100 flex items-center justify-center transition-opacity"
                            :class="{ 'opacity-100': avatarForm.processing }"
                        >
                            <font-awesome-icon
                                :icon="avatarForm.processing ? 'fa-solid fa-spinner' : 'fa-solid fa-camera'"
                                :spin="avatarForm.processing"
                                class="text-white text-lg"
                            />
                        </div>

                        <span class="absolute -bottom-1.5 -right-1.5 w-8 h-8 rounded-full bg-cobalt text-white flex items-center justify-center ring-2 ring-white shadow-sm">
                            <font-awesome-icon icon="fa-solid fa-camera" class="text-xs" />
                        </span>
                    </button>
                    <input
                        ref="avatarInput"
                        type="file"
                        accept="image/*"
                        class="hidden"
                        @change="handleAvatarChange"
                    />

                    <div class="flex flex-col gap-1.5 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="text-lg font-black text-ink">{{ userName }}</span>
                            <span class="px-2 py-0.5 rounded bg-ink/5 text-ink/60 text-[10px] font-bold uppercase tracking-wide">Client</span>
                        </div>
                        <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-ink/50">
                            <span class="inline-flex items-center gap-1.5">
                                <font-awesome-icon icon="fa-solid fa-envelope" class="text-cobalt text-[11px]" />
                                {{ user.email }}
                            </span>
                            <span v-if="user.user_info?.phone" class="inline-flex items-center gap-1.5">
                                <font-awesome-icon icon="fa-solid fa-phone" class="text-cobalt text-[11px]" />
                                {{ user.user_info.phone }}
                            </span>
                            <span v-if="user.user_info?.address" class="inline-flex items-center gap-1.5">
                                <font-awesome-icon icon="fa-solid fa-location-dot" class="text-cobalt text-[11px]" />
                                {{ user.user_info.address }}
                            </span>
                        </div>
                        <p v-if="memberSince" class="text-xs text-ink/40">Member since {{ memberSince }}</p>
                        <button
                            type="button"
                            class="self-start mt-1 text-xs font-bold text-cobalt hover:text-cobalt-dark"
                            :disabled="avatarForm.processing"
                            @click="pickAvatar"
                        >
                            Change Photo
                        </button>
                        <InputError :message="avatarForm.errors.avatar" />
                    </div>
                </div>

                <!-- Stat mosaic (real account activity, no fabricated figures) -->
                <div class="grid grid-cols-2 sm:grid-cols-4 xl:grid-cols-4 gap-3 shrink-0 w-full xl:w-auto xl:min-w-[380px]">
                    <div class="p-3.5 rounded-xl bg-ink/[0.02] border border-ink/10 flex flex-col">
                        <span class="text-[10px] font-bold uppercase tracking-wide text-ink/40">Design Requests</span>
                        <span class="text-xl font-black text-ink mt-1">{{ stats.design_requests_count }}</span>
                    </div>
                    <div class="p-3.5 rounded-xl bg-ink/[0.02] border border-ink/10 flex flex-col">
                        <span class="text-[10px] font-bold uppercase tracking-wide text-ink/40">Orders</span>
                        <span class="text-xl font-black text-ink mt-1">{{ stats.orders_count }}</span>
                    </div>
                    <div class="p-3.5 rounded-xl bg-ink/[0.02] border border-ink/10 flex flex-col">
                        <span class="text-[10px] font-bold uppercase tracking-wide text-ink/40">Sets Ordered</span>
                        <span class="text-xl font-black text-ink mt-1">{{ stats.total_sets }}</span>
                    </div>
                    <div class="p-3.5 rounded-xl bg-ink/[0.02] border border-ink/10 flex flex-col">
                        <span class="text-[10px] font-bold uppercase tracking-wide text-ink/40">Teams</span>
                        <span class="text-xl font-black text-ink mt-1">{{ stats.teams_count }}</span>
                    </div>
                </div>
            </div>
        </section>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <div class="lg:col-span-12 grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Information -->
                <section v-reveal="140" class="rounded-2xl bg-white border border-ink/10 shadow-sm p-5 sm:p-6 h-fit">
                    <h2 class="text-sm font-black uppercase tracking-wide text-ink/70 mb-4 flex items-center gap-2">
                        <font-awesome-icon icon="fa-solid fa-circle-info" class="text-cobalt" />
                        Information
                    </h2>
                    <form @submit.prevent="submitInformation" class="space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <InputLabel for="first_name" value="First Name" />
                                <TextInput
                                    id="first_name"
                                    type="text"
                                    class="mt-1.5 block w-full"
                                    v-model="user_information.first_name"
                                    required
                                    autocomplete="first_name"
                                    placeholder="e.g. Alex"
                                />
                                <InputError class="mt-1.5" :message="user_information.errors.first_name" />
                            </div>
                            <div>
                                <InputLabel for="middle_name" value="Middle Name" />
                                <TextInput
                                    id="middle_name"
                                    type="text"
                                    class="mt-1.5 block w-full"
                                    v-model="user_information.middle_name"
                                    autocomplete="middle_name"
                                    placeholder="e.g. Alexey"
                                />
                                <InputError class="mt-1.5" :message="user_information.errors.middle_name" />
                            </div>
                        </div>
                        <div>
                            <InputLabel for="last_name" value="Last Name" />
                            <TextInput
                                id="last_name"
                                type="text"
                                class="mt-1.5 block w-full"
                                v-model="user_information.last_name"
                                required
                                autocomplete="last_name"
                                placeholder="e.g. Doe"
                            />
                            <InputError class="mt-1.5" :message="user_information.errors.last_name" />
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <InputLabel for="birth_date" value="Birth Date" />
                                <TextInput
                                    id="birth_date"
                                    type="date"
                                    class="mt-1.5 block w-full"
                                    v-model="user_information.birth_date"
                                    required
                                    autocomplete="birth_date"
                                />
                                <InputError class="mt-1.5" :message="user_information.errors.birth_date" />
                            </div>
                            <div>
                                <InputLabel for="phone" value="Phone" />
                                <TextInput
                                    id="phone"
                                    type="tel"
                                    class="mt-1.5 block w-full"
                                    v-model="user_information.phone"
                                    required
                                    autocomplete="phone"
                                    placeholder="e.g. 9123456789"
                                    pattern="^9\d{9}$"
                                    maxlength="10"
                                />
                                <InputError class="mt-1.5" :message="user_information.errors.phone" />
                            </div>
                        </div>
                        <div>
                            <InputLabel for="address" value="Address" />
                            <TextInput
                                id="address"
                                type="text"
                                class="mt-1.5 block w-full"
                                v-model="user_information.address"
                                required
                                autocomplete="address"
                                placeholder="e.g. Pob. Talibon, Bohol"
                            />
                            <InputError class="mt-1.5" :message="user_information.errors.address" />
                        </div>
                        <div class="pt-2 flex justify-end">
                            <PrimaryButton
                                class="flex items-center justify-center gap-1.5"
                                :class="{ 'opacity-25': user_information.processing }"
                                :disabled="user_information.processing || user_credentials.processing"
                            >
                                <font-awesome-icon
                                    v-if="user_information.processing"
                                    icon="fa-solid fa-spinner"
                                    spin
                                />
                                <font-awesome-icon v-else icon="fa-solid fa-paper-plane" />
                                Save Information
                            </PrimaryButton>
                        </div>
                    </form>
                </section>

                <!-- Credentials -->
                <section v-reveal="180" class="rounded-2xl bg-white border border-ink/10 shadow-sm p-5 sm:p-6">
                    <h2 class="text-sm font-black uppercase tracking-wide text-ink/70 mb-4 flex items-center gap-2">
                        <font-awesome-icon icon="fa-solid fa-id-card" class="text-cobalt" />
                        Credentials
                    </h2>
                    <form @submit.prevent="submitCredentials" class="space-y-4">
                        <div>
                            <InputLabel for="email" value="Email" />
                            <TextInput
                                id="email"
                                type="email"
                                class="mt-1.5 block w-full"
                                v-model="user_credentials.email"
                                required
                                autocomplete="username"
                                placeholder="e.g. alex@gmail.com"
                            />
                            <InputError class="mt-1.5" :message="user_credentials.errors.email" />
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <InputLabel for="password" value="Password" />
                                <TextInput
                                    id="password"
                                    type="password"
                                    class="mt-1.5 block w-full"
                                    v-model="user_credentials.password"
                                    autocomplete="new-password"
                                />
                                <InputError class="mt-1.5" :message="user_credentials.errors.password" />
                            </div>
                            <div>
                                <InputLabel for="password_confirmation" value="Confirm Password" />
                                <TextInput
                                    id="password_confirmation"
                                    type="password"
                                    class="mt-1.5 block w-full"
                                    v-model="user_credentials.password_confirmation"
                                    autocomplete="new-password"
                                />
                                <InputError class="mt-1.5" :message="user_credentials.errors.password_confirmation" />
                            </div>
                        </div>
                        <div class="pt-2 flex justify-end">
                            <PrimaryButton
                                class="flex items-center justify-center gap-1.5"
                                :class="{ 'opacity-25': user_credentials.processing }"
                                :disabled="user_credentials.processing || user_information.processing"
                            >
                                <font-awesome-icon
                                    v-if="user_credentials.processing"
                                    icon="fa-solid fa-spinner"
                                    spin
                                />
                                <font-awesome-icon v-else icon="fa-solid fa-paper-plane" />
                                Save Credentials
                            </PrimaryButton>
                        </div>
                    </form>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
