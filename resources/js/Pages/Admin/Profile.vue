<script setup>
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import { Head, useForm, usePage } from "@inertiajs/vue3";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { computed, ref } from "vue";

const props = defineProps({
    stats: {
        type: Object,
        default: () => ({
            jerseys_count: 0,
            orders_count: 0,
            clients_count: 0,
            pending_reviews: 0,
        }),
    },
});

const user = usePage().props.auth.user;

const userInitials = computed(() => {
    const first = user.user_info?.first_name?.[0] ?? "";
    const last = user.user_info?.last_name?.[0] ?? "";
    return `${first}${last}`.toUpperCase() || "AD";
});

const fullName = computed(() =>
    [user.user_info?.first_name, user.user_info?.middle_name, user.user_info?.last_name]
        .filter(Boolean)
        .join(" "),
);

const memberSince = computed(() => {
    if (!user.created_at) return null;
    return new Date(user.created_at).toLocaleDateString("en-PH", {
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

const user_information = useForm({
    first_name: user.user_info?.first_name,
    middle_name: user.user_info?.middle_name ?? "",
    last_name: user.user_info?.last_name,
    birth_date: user.user_info?.birth_date,
    phone: user.user_info?.phone,
    address: user.user_info?.address,
});

const user_credentials = useForm({
    email: user.email,
    password: "",
    password_confirmation: "",
});

const showPassword = ref(false);
const showPasswordConfirmation = ref(false);

const passwordChecks = computed(() => ({
    length: user_credentials.password.length >= 8,
    number: /\d/.test(user_credentials.password),
    upper: /[A-Z]/.test(user_credentials.password),
    symbol: /[^A-Za-z0-9]/.test(user_credentials.password),
}));

const passwordScore = computed(
    () => Object.values(passwordChecks.value).filter(Boolean).length,
);

const passwordStrengthLabel = computed(() => {
    if (user_credentials.password.length === 0) return "";
    if (passwordScore.value <= 1) return "Weak";
    if (passwordScore.value <= 3) return "Fair";
    return "Strong";
});

const passwordStrengthClass = computed(() => {
    if (passwordScore.value <= 1) return "bg-rose-500";
    if (passwordScore.value <= 3) return "bg-amber-500";
    return "bg-emerald-500";
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

    <AdminLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div v-reveal class="flex items-center gap-3">
                <div class="w-11 h-11 rounded-xl bg-indigo-500/10 border border-indigo-500/20 flex items-center justify-center text-indigo-400 shrink-0">
                    <font-awesome-icon icon="fa-solid fa-shield-halved" />
                </div>
                <div class="flex flex-col gap-1">
                    <h1 class="text-2xl font-extrabold text-white tracking-tight">Profile &amp; Security</h1>
                    <p class="text-sm text-slate-400">
                        Manage your administrative account details and login credentials.
                    </p>
                </div>
            </div>

            <!-- Identity summary -->
            <div v-reveal="80" class="glass-panel rounded-2xl p-5 flex flex-col xl:flex-row xl:items-center justify-between gap-5">
                <div class="flex flex-col sm:flex-row sm:items-center gap-5">
                    <button
                        type="button"
                        class="group relative w-16 h-16 rounded-2xl shadow-sm shrink-0"
                        :disabled="avatarForm.processing"
                        @click="pickAvatar"
                    >
                        <img
                            v-if="avatarPreview || user.user_info?.avatar_url"
                            :src="avatarPreview || user.user_info?.avatar_url"
                            alt="Profile picture"
                            class="w-16 h-16 rounded-2xl object-cover ring-1 ring-white/20"
                            :class="{ 'opacity-50': avatarForm.processing }"
                        />
                        <div
                            v-else
                            class="w-16 h-16 rounded-2xl bg-gradient-to-tr from-indigo-600 to-violet-500 flex items-center justify-center font-bold text-xl text-white shadow-sm ring-1 ring-white/20"
                        >
                            {{ userInitials }}
                        </div>
                        <div
                            class="absolute inset-0 rounded-2xl bg-black/60 opacity-0 group-hover:opacity-100 flex items-center justify-center transition-opacity"
                            :class="{ 'opacity-100': avatarForm.processing }"
                        >
                            <font-awesome-icon
                                :icon="avatarForm.processing ? 'fa-solid fa-spinner' : 'fa-solid fa-camera'"
                                :spin="avatarForm.processing"
                                class="text-white text-sm"
                            />
                        </div>
                    </button>
                    <input
                        ref="avatarInput"
                        type="file"
                        accept="image/*"
                        class="hidden"
                        @change="handleAvatarChange"
                    />

                    <div class="flex-1 min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="text-lg font-bold text-white">{{ fullName }}</h2>
                            <span class="px-2.5 py-0.5 rounded-full bg-indigo-500/15 border border-indigo-500/30 text-indigo-300 text-[11px] font-bold uppercase tracking-wider">
                                Administrator
                            </span>
                        </div>
                        <div class="flex flex-wrap items-center gap-x-4 gap-y-1 mt-1.5 text-xs text-slate-400">
                            <span class="flex items-center gap-1.5">
                                <font-awesome-icon icon="fa-solid fa-envelope" class="text-[10px]" />
                                {{ user.email }}
                            </span>
                            <span v-if="user.user_info?.phone" class="flex items-center gap-1.5">
                                <font-awesome-icon icon="fa-solid fa-phone" class="text-[10px]" />
                                {{ user.user_info.phone }}
                            </span>
                            <span v-if="user.user_info?.address" class="flex items-center gap-1.5">
                                <font-awesome-icon icon="fa-solid fa-location-dot" class="text-[10px]" />
                                {{ user.user_info.address }}
                            </span>
                            <span v-if="memberSince" class="flex items-center gap-1.5">
                                <font-awesome-icon icon="fa-solid fa-calendar-xmark" class="text-[10px]" />
                                Member since {{ memberSince }}
                            </span>
                        </div>
                        <button
                            type="button"
                            class="mt-2 text-[11px] font-bold text-indigo-400 hover:text-indigo-300"
                            :disabled="avatarForm.processing"
                            @click="pickAvatar"
                        >
                            Change Photo
                        </button>
                        <InputError :message="avatarForm.errors.avatar" class="mt-1" />
                    </div>
                </div>

                <!-- Stat mosaic — real shop-wide counts, not fabricated metrics -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 shrink-0 w-full xl:w-auto xl:min-w-[380px]">
                    <div class="p-3.5 rounded-xl bg-slate-950/50 border border-white/10 flex flex-col">
                        <span class="text-[10px] font-bold uppercase tracking-wide text-slate-500">Jerseys</span>
                        <span class="text-xl font-extrabold text-white mt-1">{{ stats.jerseys_count }}</span>
                    </div>
                    <div class="p-3.5 rounded-xl bg-slate-950/50 border border-white/10 flex flex-col">
                        <span class="text-[10px] font-bold uppercase tracking-wide text-slate-500">Orders</span>
                        <span class="text-xl font-extrabold text-white mt-1">{{ stats.orders_count }}</span>
                    </div>
                    <div class="p-3.5 rounded-xl bg-slate-950/50 border border-white/10 flex flex-col">
                        <span class="text-[10px] font-bold uppercase tracking-wide text-slate-500">Clients</span>
                        <span class="text-xl font-extrabold text-white mt-1">{{ stats.clients_count }}</span>
                    </div>
                    <div class="p-3.5 rounded-xl bg-slate-950/50 border border-white/10 flex flex-col">
                        <span class="text-[10px] font-bold uppercase tracking-wide text-slate-500">Pending Review</span>
                        <span class="text-xl font-extrabold mt-1" :class="stats.pending_reviews > 0 ? 'text-amber-400' : 'text-white'">{{ stats.pending_reviews }}</span>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 items-start">
                <!-- Personal identity -->
                <div v-reveal="140" class="glass-panel rounded-2xl overflow-hidden">
                    <div class="flex items-center gap-2.5 border-b border-white/5 px-5 py-4 bg-black/10">
                        <div class="w-7 h-7 rounded-lg bg-indigo-500/20 text-indigo-400 border border-indigo-500/30 flex items-center justify-center text-xs">
                            <font-awesome-icon icon="fa-solid fa-id-card" />
                        </div>
                        <h3 class="text-sm font-bold text-white">Personal Identity</h3>
                    </div>

                    <form @submit.prevent="submitInformation" class="p-5 space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <InputLabel for="first_name" value="First Name" class="!text-slate-300 !text-xs !font-semibold !uppercase !tracking-wide" />
                                <TextInput
                                    id="first_name"
                                    type="text"
                                    class="mt-1.5 block w-full !bg-slate-950/70 !border-white/10 !text-slate-100 !rounded-lg"
                                    v-model="user_information.first_name"
                                    required
                                    autocomplete="first_name"
                                    placeholder="e.g. Alex"
                                />
                                <InputError class="mt-1.5" :message="user_information.errors.first_name" />
                            </div>
                            <div>
                                <InputLabel for="middle_name" class="!text-slate-300 !text-xs !font-semibold !uppercase !tracking-wide">
                                    Middle Name <span class="text-slate-500 font-normal normal-case">(optional)</span>
                                </InputLabel>
                                <TextInput
                                    id="middle_name"
                                    type="text"
                                    class="mt-1.5 block w-full !bg-slate-950/70 !border-white/10 !text-slate-100 !rounded-lg"
                                    v-model="user_information.middle_name"
                                    autocomplete="middle_name"
                                    placeholder="e.g. Alexey"
                                />
                                <InputError class="mt-1.5" :message="user_information.errors.middle_name" />
                            </div>
                            <div>
                                <InputLabel for="last_name" value="Last Name" class="!text-slate-300 !text-xs !font-semibold !uppercase !tracking-wide" />
                                <TextInput
                                    id="last_name"
                                    type="text"
                                    class="mt-1.5 block w-full !bg-slate-950/70 !border-white/10 !text-slate-100 !rounded-lg"
                                    v-model="user_information.last_name"
                                    required
                                    autocomplete="last_name"
                                    placeholder="e.g. Doe"
                                />
                                <InputError class="mt-1.5" :message="user_information.errors.last_name" />
                            </div>
                            <div>
                                <InputLabel for="birth_date" value="Birth Date" class="!text-slate-300 !text-xs !font-semibold !uppercase !tracking-wide" />
                                <TextInput
                                    id="birth_date"
                                    type="date"
                                    class="mt-1.5 block w-full !bg-slate-950/70 !border-white/10 !text-slate-100 !rounded-lg [color-scheme:dark]"
                                    v-model="user_information.birth_date"
                                    required
                                    autocomplete="birth_date"
                                />
                                <InputError class="mt-1.5" :message="user_information.errors.birth_date" />
                            </div>
                            <div>
                                <InputLabel for="phone" value="Phone" class="!text-slate-300 !text-xs !font-semibold !uppercase !tracking-wide" />
                                <TextInput
                                    id="phone"
                                    type="tel"
                                    class="mt-1.5 block w-full !bg-slate-950/70 !border-white/10 !text-slate-100 !rounded-lg"
                                    v-model="user_information.phone"
                                    required
                                    autocomplete="phone"
                                    placeholder="e.g. 9123456789"
                                    pattern="^9\d{9}$"
                                    maxlength="10"
                                />
                                <InputError class="mt-1.5" :message="user_information.errors.phone" />
                            </div>
                            <div>
                                <InputLabel for="address" value="Address" class="!text-slate-300 !text-xs !font-semibold !uppercase !tracking-wide" />
                                <TextInput
                                    id="address"
                                    type="text"
                                    class="mt-1.5 block w-full !bg-slate-950/70 !border-white/10 !text-slate-100 !rounded-lg"
                                    v-model="user_information.address"
                                    required
                                    autocomplete="address"
                                    placeholder="e.g. Pob. Talibon, Bohol"
                                />
                                <InputError class="mt-1.5" :message="user_information.errors.address" />
                            </div>
                        </div>
                        <div class="pt-2 flex justify-end">
                            <PrimaryButton
                                :class="{ 'opacity-25': user_information.processing }"
                                :disabled="user_information.processing || user_credentials.processing"
                            >
                                <font-awesome-icon v-if="user_information.processing" icon="fa-solid fa-spinner" spin class="me-1.5" />
                                <font-awesome-icon v-else icon="fa-solid fa-paper-plane" class="me-1.5" />
                                Save Identity
                            </PrimaryButton>
                        </div>
                    </form>
                </div>

                <!-- Security & password -->
                <div v-reveal="200" class="glass-panel rounded-2xl overflow-hidden">
                    <div class="flex items-center gap-2.5 border-b border-white/5 px-5 py-4 bg-black/10">
                        <div class="w-7 h-7 rounded-lg bg-rose-500/20 text-rose-400 border border-rose-500/30 flex items-center justify-center text-xs">
                            <font-awesome-icon icon="fa-solid fa-lock" />
                        </div>
                        <h3 class="text-sm font-bold text-white">Security &amp; Password</h3>
                    </div>

                    <form @submit.prevent="submitCredentials" class="p-5 space-y-4">
                        <div>
                            <InputLabel for="email" value="Login Email" class="!text-slate-300 !text-xs !font-semibold !uppercase !tracking-wide" />
                            <TextInput
                                id="email"
                                type="email"
                                class="mt-1.5 block w-full !bg-slate-950/70 !border-white/10 !text-slate-100 !rounded-lg"
                                v-model="user_credentials.email"
                                required
                                autocomplete="username"
                                placeholder="e.g. alex@gmail.com"
                            />
                            <InputError class="mt-1.5" :message="user_credentials.errors.email" />
                        </div>

                        <div>
                            <InputLabel for="password" value="New Password" class="!text-slate-300 !text-xs !font-semibold !uppercase !tracking-wide" />
                            <div class="relative flex items-center mt-1.5">
                                <input
                                    id="password"
                                    v-model="user_credentials.password"
                                    :type="showPassword ? 'text' : 'password'"
                                    autocomplete="new-password"
                                    placeholder="Leave blank to keep current password"
                                    class="w-full pr-11 px-3 py-2 rounded-lg bg-slate-950/70 border border-white/10 text-slate-100 placeholder-slate-500 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none shadow-inner transition-all"
                                />
                                <button
                                    type="button"
                                    class="absolute right-2.5 p-1.5 rounded-md text-slate-500 hover:text-slate-300 transition-colors"
                                    @click="showPassword = !showPassword"
                                >
                                    <font-awesome-icon :icon="showPassword ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye'" class="text-sm" />
                                </button>
                            </div>
                            <InputError class="mt-1.5" :message="user_credentials.errors.password" />
                        </div>

                        <div>
                            <InputLabel for="password_confirmation" value="Confirm New Password" class="!text-slate-300 !text-xs !font-semibold !uppercase !tracking-wide" />
                            <div class="relative flex items-center mt-1.5">
                                <input
                                    id="password_confirmation"
                                    v-model="user_credentials.password_confirmation"
                                    :type="showPasswordConfirmation ? 'text' : 'password'"
                                    autocomplete="new-password"
                                    placeholder="Repeat new password"
                                    class="w-full pr-11 px-3 py-2 rounded-lg bg-slate-950/70 border border-white/10 text-slate-100 placeholder-slate-500 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none shadow-inner transition-all"
                                />
                                <button
                                    type="button"
                                    class="absolute right-2.5 p-1.5 rounded-md text-slate-500 hover:text-slate-300 transition-colors"
                                    @click="showPasswordConfirmation = !showPasswordConfirmation"
                                >
                                    <font-awesome-icon :icon="showPasswordConfirmation ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye'" class="text-sm" />
                                </button>
                            </div>
                            <InputError class="mt-1.5" :message="user_credentials.errors.password_confirmation" />
                        </div>

                        <!-- Real-time password strength feedback -->
                        <div v-if="user_credentials.password.length > 0" class="rounded-xl bg-slate-950/50 border border-white/10 p-3.5">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Password Strength</span>
                                <span
                                    class="text-xs font-semibold"
                                    :class="{
                                        'text-rose-400': passwordStrengthLabel === 'Weak',
                                        'text-amber-400': passwordStrengthLabel === 'Fair',
                                        'text-emerald-400': passwordStrengthLabel === 'Strong',
                                    }"
                                >
                                    {{ passwordStrengthLabel }}
                                </span>
                            </div>
                            <div class="grid grid-cols-4 gap-1.5 mb-2.5">
                                <span
                                    v-for="n in 4"
                                    :key="n"
                                    class="h-1 rounded-full transition-colors"
                                    :class="n <= passwordScore ? passwordStrengthClass : 'bg-slate-800'"
                                ></span>
                            </div>
                            <div class="grid grid-cols-2 gap-x-4 gap-y-1.5 text-[11px]">
                                <span class="flex items-center gap-1.5" :class="passwordChecks.length ? 'text-emerald-400' : 'text-slate-500'">
                                    <font-awesome-icon :icon="passwordChecks.length ? 'fa-solid fa-circle-check' : 'fa-solid fa-xmark-circle'" />
                                    8+ characters
                                </span>
                                <span class="flex items-center gap-1.5" :class="passwordChecks.number ? 'text-emerald-400' : 'text-slate-500'">
                                    <font-awesome-icon :icon="passwordChecks.number ? 'fa-solid fa-circle-check' : 'fa-solid fa-xmark-circle'" />
                                    Number
                                </span>
                                <span class="flex items-center gap-1.5" :class="passwordChecks.upper ? 'text-emerald-400' : 'text-slate-500'">
                                    <font-awesome-icon :icon="passwordChecks.upper ? 'fa-solid fa-circle-check' : 'fa-solid fa-xmark-circle'" />
                                    Uppercase
                                </span>
                                <span class="flex items-center gap-1.5" :class="passwordChecks.symbol ? 'text-emerald-400' : 'text-slate-500'">
                                    <font-awesome-icon :icon="passwordChecks.symbol ? 'fa-solid fa-circle-check' : 'fa-solid fa-xmark-circle'" />
                                    Symbol
                                </span>
                            </div>
                        </div>

                        <div class="pt-2 flex justify-end">
                            <PrimaryButton
                                :class="{ 'opacity-25': user_credentials.processing }"
                                :disabled="user_credentials.processing || user_information.processing"
                            >
                                <font-awesome-icon v-if="user_credentials.processing" icon="fa-solid fa-spinner" spin class="me-1.5" />
                                <font-awesome-icon v-else icon="fa-solid fa-shield-halved" class="me-1.5" />
                                Update Security
                            </PrimaryButton>
                        </div>
                    </form>
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
