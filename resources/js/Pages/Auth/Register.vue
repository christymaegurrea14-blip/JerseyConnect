<script setup lang="ts">
import GuestLayout from "@/Layouts/GuestLayout.vue";
import { Head, Link, useForm } from "@inertiajs/vue3";
import { computed, ref } from "vue";

const form = useForm({
    first_name: "",
    middle_name: "",
    last_name: "",
    birth_date: "",
    phone: "",
    address: "",
    email: "",
    password: "",
    password_confirmation: "",
});

const showPassword = ref(false);
const showPasswordConfirmation = ref(false);

const passwordChecks = computed(() => ({
    length: form.password.length >= 8,
    number: /\d/.test(form.password),
    upper: /[A-Z]/.test(form.password),
    symbol: /[^A-Za-z0-9]/.test(form.password),
}));

const passwordScore = computed(
    () => Object.values(passwordChecks.value).filter(Boolean).length,
);

const passwordStrengthLabel = computed(() => {
    if (form.password.length === 0) return "";
    if (passwordScore.value <= 1) return "Weak";
    if (passwordScore.value <= 3) return "Fair";
    return "Strong";
});

const passwordStrengthClass = computed(() => {
    if (passwordScore.value <= 1) return "bg-rose-500";
    if (passwordScore.value <= 3) return "bg-amber-500";
    return "bg-emerald-500";
});

const submit = () => {
    form.post(route("register"), {
        onFinish: () => {
            form.reset("password", "password_confirmation");
        },
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Register" />

        <div class="flex flex-col gap-6 p-6 sm:p-8">
            <!-- Brand header -->
            <div class="flex flex-col items-start gap-3">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 rounded-xl bg-indigo-500/10 border border-indigo-500/20 flex items-center justify-center shrink-0 overflow-hidden">
                        <img src="/images/printcode.png" alt="PrintCode" class="h-7 w-auto" />
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-2xl font-extrabold text-white tracking-tight leading-none">PrintCode</span>
                        <span class="px-2.5 py-0.5 rounded-full bg-indigo-500/15 border border-indigo-500/30 text-indigo-300 text-[11px] font-bold uppercase tracking-wider">
                            Create Account
                        </span>
                    </div>
                </div>
                <p class="text-sm text-slate-400 leading-relaxed">
                    Join PrintCode to order custom sublimated jerseys for your team.
                </p>
            </div>

            <form @submit.prevent="submit" class="flex flex-col gap-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="flex flex-col gap-1.5">
                        <label for="first_name" class="text-xs font-semibold text-slate-300">First Name</label>
                        <div class="relative flex items-center">
                            <font-awesome-icon icon="fa-solid fa-user-circle" class="absolute left-3.5 text-slate-500 text-sm pointer-events-none" />
                            <input
                                id="first_name"
                                v-model="form.first_name"
                                type="text"
                                required
                                autofocus
                                autocomplete="given-name"
                                placeholder="e.g. John"
                                class="w-full pl-11 pr-4 py-3 rounded-lg bg-slate-950/70 border border-white/10 text-slate-100 placeholder-slate-500 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none shadow-inner transition-all"
                            />
                        </div>
                        <p v-if="form.errors.first_name" class="text-xs text-rose-400">{{ form.errors.first_name }}</p>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label for="middle_name" class="text-xs font-semibold text-slate-300">Middle Name <span class="text-slate-500 font-normal normal-case">(optional)</span></label>
                        <div class="relative flex items-center">
                            <font-awesome-icon icon="fa-solid fa-user-circle" class="absolute left-3.5 text-slate-500 text-sm pointer-events-none" />
                            <input
                                id="middle_name"
                                v-model="form.middle_name"
                                type="text"
                                autocomplete="additional-name"
                                placeholder="e.g. Alexey"
                                class="w-full pl-11 pr-4 py-3 rounded-lg bg-slate-950/70 border border-white/10 text-slate-100 placeholder-slate-500 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none shadow-inner transition-all"
                            />
                        </div>
                        <p v-if="form.errors.middle_name" class="text-xs text-rose-400">{{ form.errors.middle_name }}</p>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label for="last_name" class="text-xs font-semibold text-slate-300">Last Name</label>
                        <div class="relative flex items-center">
                            <font-awesome-icon icon="fa-solid fa-user-circle" class="absolute left-3.5 text-slate-500 text-sm pointer-events-none" />
                            <input
                                id="last_name"
                                v-model="form.last_name"
                                type="text"
                                required
                                autocomplete="family-name"
                                placeholder="e.g. Doe"
                                class="w-full pl-11 pr-4 py-3 rounded-lg bg-slate-950/70 border border-white/10 text-slate-100 placeholder-slate-500 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none shadow-inner transition-all"
                            />
                        </div>
                        <p v-if="form.errors.last_name" class="text-xs text-rose-400">{{ form.errors.last_name }}</p>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label for="birth_date" class="text-xs font-semibold text-slate-300">Birth Date</label>
                        <div class="relative flex items-center">
                            <font-awesome-icon icon="fa-solid fa-calendar-xmark" class="absolute left-3.5 text-slate-500 text-sm pointer-events-none" />
                            <input
                                id="birth_date"
                                v-model="form.birth_date"
                                type="date"
                                required
                                autocomplete="bday"
                                class="w-full pl-11 pr-4 py-3 rounded-lg bg-slate-950/70 border border-white/10 text-slate-100 placeholder-slate-500 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none shadow-inner transition-all [color-scheme:dark]"
                            />
                        </div>
                        <p v-if="form.errors.birth_date" class="text-xs text-rose-400">{{ form.errors.birth_date }}</p>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label for="phone" class="text-xs font-semibold text-slate-300">Phone</label>
                        <div class="relative flex items-center">
                            <font-awesome-icon icon="fa-solid fa-phone" class="absolute left-3.5 text-slate-500 text-sm pointer-events-none" />
                            <input
                                id="phone"
                                v-model="form.phone"
                                type="tel"
                                required
                                autocomplete="tel-national"
                                placeholder="9123456789"
                                pattern="^9\d{9}$"
                                maxlength="10"
                                class="w-full pl-11 pr-4 py-3 rounded-lg bg-slate-950/70 border border-white/10 text-slate-100 placeholder-slate-500 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none shadow-inner transition-all"
                            />
                        </div>
                        <p v-if="form.errors.phone" class="text-xs text-rose-400">{{ form.errors.phone }}</p>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label for="address" class="text-xs font-semibold text-slate-300">Address</label>
                        <div class="relative flex items-center">
                            <font-awesome-icon icon="fa-solid fa-location-dot" class="absolute left-3.5 text-slate-500 text-sm pointer-events-none" />
                            <input
                                id="address"
                                v-model="form.address"
                                type="text"
                                required
                                autocomplete="street-address"
                                placeholder="e.g. Pob. Talibon, Bohol"
                                class="w-full pl-11 pr-4 py-3 rounded-lg bg-slate-950/70 border border-white/10 text-slate-100 placeholder-slate-500 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none shadow-inner transition-all"
                            />
                        </div>
                        <p v-if="form.errors.address" class="text-xs text-rose-400">{{ form.errors.address }}</p>
                    </div>
                </div>

                <div class="flex flex-col gap-1.5">
                    <label for="email" class="text-xs font-semibold text-slate-300">Email</label>
                    <div class="relative flex items-center">
                        <font-awesome-icon icon="fa-solid fa-envelope" class="absolute left-3.5 text-slate-500 text-sm pointer-events-none" />
                        <input
                            id="email"
                            v-model="form.email"
                            type="email"
                            required
                            autocomplete="username"
                            placeholder="e.g. john@gmail.com"
                            class="w-full pl-11 pr-4 py-3 rounded-lg bg-slate-950/70 border border-white/10 text-slate-100 placeholder-slate-500 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none shadow-inner transition-all"
                        />
                    </div>
                    <p v-if="form.errors.email" class="text-xs text-rose-400">{{ form.errors.email }}</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="flex flex-col gap-1.5">
                        <label for="password" class="text-xs font-semibold text-slate-300">Password</label>
                        <div class="relative flex items-center">
                            <font-awesome-icon icon="fa-solid fa-lock" class="absolute left-3.5 text-slate-500 text-sm pointer-events-none" />
                            <input
                                id="password"
                                v-model="form.password"
                                :type="showPassword ? 'text' : 'password'"
                                required
                                autocomplete="new-password"
                                placeholder="••••••••"
                                class="w-full pl-11 pr-11 py-3 rounded-lg bg-slate-950/70 border border-white/10 text-slate-100 placeholder-slate-500 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none shadow-inner transition-all"
                            />
                            <button
                                type="button"
                                class="absolute right-2.5 p-1.5 rounded-md text-slate-500 hover:text-slate-300 transition-colors"
                                @click="showPassword = !showPassword"
                            >
                                <font-awesome-icon :icon="showPassword ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye'" class="text-sm" />
                            </button>
                        </div>
                        <p v-if="form.errors.password" class="text-xs text-rose-400">{{ form.errors.password }}</p>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label for="password_confirmation" class="text-xs font-semibold text-slate-300">Confirm Password</label>
                        <div class="relative flex items-center">
                            <font-awesome-icon icon="fa-solid fa-shield-halved" class="absolute left-3.5 text-slate-500 text-sm pointer-events-none" />
                            <input
                                id="password_confirmation"
                                v-model="form.password_confirmation"
                                :type="showPasswordConfirmation ? 'text' : 'password'"
                                required
                                autocomplete="new-password"
                                placeholder="••••••••"
                                class="w-full pl-11 pr-11 py-3 rounded-lg bg-slate-950/70 border border-white/10 text-slate-100 placeholder-slate-500 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none shadow-inner transition-all"
                            />
                            <button
                                type="button"
                                class="absolute right-2.5 p-1.5 rounded-md text-slate-500 hover:text-slate-300 transition-colors"
                                @click="showPasswordConfirmation = !showPasswordConfirmation"
                            >
                                <font-awesome-icon :icon="showPasswordConfirmation ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye'" class="text-sm" />
                            </button>
                        </div>
                        <p v-if="form.errors.password_confirmation" class="text-xs text-rose-400">{{ form.errors.password_confirmation }}</p>
                    </div>
                </div>

                <!-- Real-time password strength feedback -->
                <div v-if="form.password.length > 0" class="rounded-xl bg-slate-950/50 border border-white/10 p-3.5">
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

                <button
                    type="submit"
                    :disabled="form.processing"
                    class="w-full mt-1 py-3 px-6 rounded-lg bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white text-sm font-bold flex items-center justify-center gap-2 shadow-lg shadow-indigo-500/25 active:scale-[0.99] transition-all disabled:opacity-50"
                >
                    <font-awesome-icon v-if="form.processing" icon="fa-solid fa-spinner" spin />
                    <font-awesome-icon v-else icon="fa-solid fa-user-plus" class="text-sm" />
                    <span>{{ form.processing ? "Creating account..." : "Create Account" }}</span>
                </button>
            </form>

            <div class="flex flex-col items-center gap-3 pt-2 border-t border-white/5">
                <p class="text-xs text-slate-400 text-center pt-4">
                    Already registered?
                    <Link :href="route('login')" class="text-indigo-300 hover:text-indigo-200 font-semibold transition-colors">
                        Sign in
                    </Link>
                </p>
                <Link href="/" class="text-xs text-slate-500 hover:text-slate-300 transition-colors">
                    <font-awesome-icon icon="fa-solid fa-angles-left" class="text-[10px]" />
                    Back to site
                </Link>
            </div>
        </div>
    </GuestLayout>
</template>
