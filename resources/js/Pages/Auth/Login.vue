<script setup lang="ts">
import GuestLayout from "@/Layouts/GuestLayout.vue";
import { Head, Link, useForm } from "@inertiajs/vue3";
import { ref } from "vue";

defineProps<{
    canResetPassword?: boolean;
    status?: string;
}>();

const form = useForm({
    email: "",
    password: "",
    remember: false,
});

const showPassword = ref(false);

const submit = () => {
    form.post(route("login"), {
        onFinish: () => {
            form.reset("password");
        },
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Log in" />

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
                            Sign In
                        </span>
                    </div>
                </div>
                <p class="text-sm text-slate-400 leading-relaxed">
                    Sign in to manage your custom jersey orders, designs, and GCash payments.
                </p>
            </div>

            <div v-if="status" class="text-sm font-medium text-emerald-400 bg-emerald-500/10 border border-emerald-500/20 rounded-lg px-3 py-2">
                {{ status }}
            </div>

            <form @submit.prevent="submit" class="flex flex-col gap-4">
                <div class="flex flex-col gap-1.5">
                    <label for="email" class="text-xs font-semibold text-slate-300">Email</label>
                    <div class="relative flex items-center">
                        <font-awesome-icon icon="fa-solid fa-envelope" class="absolute left-3.5 text-slate-500 text-sm pointer-events-none" />
                        <input
                            id="email"
                            v-model="form.email"
                            type="email"
                            required
                            autofocus
                            autocomplete="username"
                            placeholder="you@example.com"
                            class="w-full pl-11 pr-4 py-3 rounded-lg bg-slate-950/70 border border-white/10 text-slate-100 placeholder-slate-500 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none shadow-inner transition-all"
                        />
                    </div>
                    <p v-if="form.errors.email" class="text-xs text-rose-400">{{ form.errors.email }}</p>
                </div>

                <div class="flex flex-col gap-1.5">
                    <div class="flex items-center justify-between">
                        <label for="password" class="text-xs font-semibold text-slate-300">Password</label>
                        <Link
                            v-if="canResetPassword"
                            :href="route('password.request')"
                            class="text-xs font-medium text-indigo-300 hover:text-indigo-200 transition-colors"
                        >
                            Forgot password?
                        </Link>
                    </div>
                    <div class="relative flex items-center">
                        <font-awesome-icon icon="fa-solid fa-lock" class="absolute left-3.5 text-slate-500 text-sm pointer-events-none" />
                        <input
                            id="password"
                            v-model="form.password"
                            :type="showPassword ? 'text' : 'password'"
                            required
                            autocomplete="current-password"
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

                <label class="flex items-center gap-2 cursor-pointer select-none pt-1">
                    <input
                        v-model="form.remember"
                        type="checkbox"
                        class="w-4 h-4 rounded border-white/20 bg-slate-950/70 text-indigo-600 focus:ring-indigo-500 focus:ring-offset-0 cursor-pointer"
                    />
                    <span class="text-xs text-slate-300">Remember me</span>
                </label>

                <button
                    type="submit"
                    :disabled="form.processing"
                    class="w-full mt-1 py-3 px-6 rounded-lg bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white text-sm font-bold flex items-center justify-center gap-2 shadow-lg shadow-indigo-500/25 active:scale-[0.99] transition-all disabled:opacity-50"
                >
                    <font-awesome-icon v-if="form.processing" icon="fa-solid fa-spinner" spin />
                    <span>{{ form.processing ? "Signing in..." : "Sign In" }}</span>
                    <font-awesome-icon v-if="!form.processing" icon="fa-solid fa-arrow-right" class="text-sm" />
                </button>
            </form>

            <div class="flex flex-col items-center gap-3 pt-2 border-t border-white/5">
                <p class="text-xs text-slate-400 text-center pt-4">
                    Don't have an account?
                    <Link :href="route('register')" class="text-indigo-300 hover:text-indigo-200 font-semibold transition-colors">
                        Register
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
