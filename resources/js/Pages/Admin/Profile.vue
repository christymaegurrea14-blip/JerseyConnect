<script setup>
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import { Head, useForm, usePage } from "@inertiajs/vue3";
import AdminLayout from "@/Layouts/AdminLayout.vue";

const user = usePage().props.auth.user;

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
        <div class="flex flex-col sm:flex-row gap-5 mx-auto">
            <div class="w-full space-y-5">
                <div class="glass-panel rounded-2xl">
                    <div class="p-6 text-slate-200">
                        <h1 class="sm:text-xl font-bold text-white">
                            <font-awesome-icon icon="fa-solid fa-circle-info" class="text-indigo-400" />
                            Information
                        </h1>
                        <form @submit.prevent="submitInformation">
                            <div class="mt-3">
                                <InputLabel for="first_name" value="First Name" class="!text-slate-300" />
                                <TextInput
                                    id="first_name"
                                    type="text"
                                    class="mt-1 block w-full !bg-slate-900/60 !border-white/10 !text-slate-200"
                                    v-model="user_information.first_name"
                                    required
                                    autocomplete="first_name"
                                    placeholder="e.g. Alex"
                                />
                                <InputError class="mt-2" :message="user_information.errors.first_name" />
                            </div>
                            <div class="mt-3">
                                <InputLabel for="middle_name" value="Middle Name" class="!text-slate-300" />
                                <TextInput
                                    id="middle_name"
                                    type="text"
                                    class="mt-1 block w-full !bg-slate-900/60 !border-white/10 !text-slate-200"
                                    v-model="user_information.middle_name"
                                    autocomplete="middle_name"
                                    placeholder="e.g. Alexey"
                                />
                                <InputError class="mt-2" :message="user_information.errors.middle_name" />
                            </div>
                            <div class="mt-3">
                                <InputLabel for="last_name" value="Last Name" class="!text-slate-300" />
                                <TextInput
                                    id="last_name"
                                    type="text"
                                    class="mt-1 block w-full !bg-slate-900/60 !border-white/10 !text-slate-200"
                                    v-model="user_information.last_name"
                                    required
                                    autocomplete="last_name"
                                    placeholder="e.g. Doe"
                                />
                                <InputError class="mt-2" :message="user_information.errors.last_name" />
                            </div>
                            <div class="mt-3">
                                <InputLabel for="birth_date" value="Birth Date" class="!text-slate-300" />
                                <TextInput
                                    id="birth_date"
                                    type="date"
                                    class="mt-1 block w-full !bg-slate-900/60 !border-white/10 !text-slate-200"
                                    v-model="user_information.birth_date"
                                    required
                                    autocomplete="birth_date"
                                />
                                <InputError class="mt-2" :message="user_information.errors.birth_date" />
                            </div>
                            <div class="mt-3">
                                <InputLabel for="phone" value="Phone" class="!text-slate-300" />
                                <TextInput
                                    id="phone"
                                    type="tel"
                                    class="mt-1 block w-full !bg-slate-900/60 !border-white/10 !text-slate-200"
                                    v-model="user_information.phone"
                                    required
                                    autocomplete="phone"
                                    placeholder="e.g. 9123456789"
                                    pattern="^9\d{9}$"
                                    maxlength="10"
                                />
                                <InputError class="mt-2" :message="user_information.errors.phone" />
                            </div>
                            <div class="mt-3">
                                <InputLabel for="address" value="Address" class="!text-slate-300" />
                                <TextInput
                                    id="address"
                                    type="text"
                                    class="mt-1 block w-full !bg-slate-900/60 !border-white/10 !text-slate-200"
                                    v-model="user_information.address"
                                    required
                                    autocomplete="address"
                                    placeholder="e.g. Pob. Talibon, Bohol"
                                />
                                <InputError class="mt-2" :message="user_information.errors.address" />
                            </div>
                            <div class="mt-3 flex items-center justify-center">
                                <PrimaryButton
                                    :class="{ 'opacity-25': user_information.processing }"
                                    :disabled="user_information.processing || user_credentials.processing"
                                >
                                    <div class="text-sm" v-if="user_information.processing">
                                        <font-awesome-icon icon="fa-solid fa-spinner" spin />
                                    </div>
                                    <font-awesome-icon class="mx-1" icon="fa-solid fa-paper-plane" />
                                    Save Information
                                </PrimaryButton>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="glass-panel rounded-2xl">
                    <div class="p-6 text-slate-200">
                        <h1 class="sm:text-xl font-bold text-white">
                            <font-awesome-icon icon="fa-solid fa-id-card" class="text-indigo-400" />
                            Credentials
                        </h1>
                        <form @submit.prevent="submitCredentials">
                            <div class="mt-3">
                                <InputLabel for="email" value="Email" class="!text-slate-300" />
                                <TextInput
                                    id="email"
                                    type="email"
                                    class="mt-1 block w-full !bg-slate-900/60 !border-white/10 !text-slate-200"
                                    v-model="user_credentials.email"
                                    required
                                    autocomplete="username"
                                    placeholder="e.g. alex@gmail.com"
                                />
                                <InputError class="mt-2" :message="user_credentials.errors.email" />
                            </div>
                            <div class="mt-3">
                                <InputLabel for="password" value="Password" class="!text-slate-300" />
                                <TextInput
                                    id="password"
                                    type="password"
                                    class="mt-1 block w-full !bg-slate-900/60 !border-white/10 !text-slate-200"
                                    v-model="user_credentials.password"
                                    autocomplete="new-password"
                                />
                                <InputError class="mt-2" :message="user_credentials.errors.password" />
                            </div>
                            <div class="mt-3">
                                <InputLabel for="password_confirmation" value="Confirm Password" class="!text-slate-300" />
                                <TextInput
                                    id="password_confirmation"
                                    type="password"
                                    class="mt-1 block w-full !bg-slate-900/60 !border-white/10 !text-slate-200"
                                    v-model="user_credentials.password_confirmation"
                                    autocomplete="new-password"
                                />
                                <InputError class="mt-2" :message="user_credentials.errors.password_confirmation" />
                            </div>
                            <div class="mt-3 flex items-center justify-center">
                                <PrimaryButton
                                    :class="{ 'opacity-25': user_credentials.processing }"
                                    :disabled="user_credentials.processing || user_information.processing"
                                >
                                    <div class="text-sm" v-if="user_credentials.processing">
                                        <font-awesome-icon icon="fa-solid fa-spinner" spin />
                                    </div>
                                    <font-awesome-icon class="me-1" icon="fa-solid fa-paper-plane" />
                                    Save Credentials
                                </PrimaryButton>
                            </div>
                        </form>
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
