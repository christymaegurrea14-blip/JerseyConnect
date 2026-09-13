<script setup lang="ts">
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';

defineProps<{
    status?: string;
}>();

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('password.email'));
};
</script>

<template>
    <GuestLayout>
        <Head title="Forgot Password" />

        <div class="p-6 sm:p-8">
            <div class="mb-4 text-sm text-slate-400">
                Forgot your password? No problem. Just let us know your email
                address and we will email you a password reset link that will allow
                you to choose a new one.
            </div>

            <div
                v-if="status"
                class="mb-4 text-sm font-medium text-emerald-400 bg-emerald-500/10 border border-emerald-500/20 rounded-lg px-3 py-2"
            >
                {{ status }}
            </div>

            <form @submit.prevent="submit">
                <div>
                    <InputLabel for="email" value="Email" class="!text-slate-300" />

                    <TextInput
                        id="email"
                        type="email"
                        class="mt-1 block w-full !bg-slate-950/70 !border-white/10 !text-slate-100"
                        v-model="form.email"
                        required
                        autofocus
                        autocomplete="username"
                    />

                    <InputError class="mt-2" :message="form.errors.email" />
                </div>

                <div class="mt-4 flex items-center justify-between">
                    <Link
                        href="/login"
                        class="rounded-md text-sm sm:text-md text-slate-400 underline hover:text-slate-200"
                    >
                        <font-awesome-icon icon="fa-solid fa-angles-left" />
                        Back
                    </Link>

                    <PrimaryButton
                        :class="{ 'opacity-25': form.processing }"
                        :disabled="form.processing"
                    >
                        Email Password Reset Link
                    </PrimaryButton>
                </div>
            </form>
        </div>
    </GuestLayout>
</template>
