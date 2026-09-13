<script setup lang="ts">
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import Breadcrumbs from "@/Components/Breadcrumbs.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import type { DesignRequest } from "@/types/jersey";
import { statusBadge, statusMeaning, formatDate } from "@/utils/designRequestStatus";
import { Head, Link, router } from "@inertiajs/vue3";
import { computed, ref } from "vue";

const props = defineProps<{ request: DesignRequest }>();

const rosterPlayers = computed(() => props.request.players ?? []);

const nonCancellable = ["revision_requested", "waiting_for_down_payment", "pending_down_payment_review", "approved", "cancelled"];
const canCancel = computed(() => !nonCancellable.includes(props.request.status));

const cancelling = ref(false);
const showCancelConfirm = ref(false);

function confirmCancel() {
    cancelling.value = true;
    router.delete(route("client.design.cancel", props.request.id), {
        onFinish: () => {
            cancelling.value = false;
            showCancelConfirm.value = false;
        },
    });
}
</script>

<template>
    <Head title="Design Request Details" />

    <AuthenticatedLayout>
        <Breadcrumbs
            :items="[
                { label: 'My Orders', href: route('client.orders.index') },
                { label: request.team_name },
            ]"
        />

        <!-- Header banner -->
        <section v-reveal class="rounded-2xl bg-white border border-ink/10 shadow-sm p-5 sm:p-6 mb-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-3.5">
                    <div class="w-11 h-11 rounded-xl bg-cobalt/10 text-cobalt flex items-center justify-center border border-cobalt/20 shrink-0">
                        <font-awesome-icon icon="fa-solid fa-tshirt" class="text-lg" />
                    </div>
                    <div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <h1 class="text-xl font-black tracking-tight text-ink">{{ request.team_name }}</h1>
                            <span
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold"
                                :class="statusBadge[request.status].class"
                            >
                                {{ statusBadge[request.status].label }}
                            </span>
                        </div>
                        <p class="text-sm text-ink/50 mt-0.5">
                            {{ request.template_name }} • {{ request.estimated_quantity }} sets • Submitted {{ formatDate(request.created_at) }}
                        </p>
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <Link
                        v-if="request.status === 'waiting_for_down_payment'"
                        :href="route('client.design.pay.show', request.id)"
                        class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-good text-white text-sm font-bold hover:bg-good/90 transition-colors"
                    >
                        <font-awesome-icon icon="fa-solid fa-credit-card" />
                        Pay Now
                    </Link>
                    <Link
                        :href="route('client.design.roster', request.id)"
                        class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-cobalt/10 text-cobalt text-sm font-bold hover:bg-cobalt/20 transition-colors"
                    >
                        <font-awesome-icon icon="fa-solid fa-users" />
                        Team Roster
                    </Link>
                    <Link
                        v-if="request.status !== 'approved' && request.status !== 'cancelled'"
                        :href="route('client.chat.index')"
                        class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-ink/5 text-ink text-sm font-bold hover:bg-ink/10 transition-colors"
                    >
                        <font-awesome-icon icon="fa-solid fa-message" />
                        Message
                    </Link>
                    <button
                        v-if="canCancel"
                        type="button"
                        class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-accent/10 text-accent text-sm font-bold hover:bg-accent/20 transition-colors"
                        @click="showCancelConfirm = true"
                    >
                        <font-awesome-icon icon="fa-solid fa-xmark-circle" />
                        Cancel
                    </button>
                </div>
            </div>
        </section>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <!-- LEFT: design comparison -->
            <section v-reveal="80" class="lg:col-span-8 rounded-2xl bg-white border border-ink/10 shadow-sm p-5 sm:p-6">
                <h2 class="text-sm font-black uppercase tracking-wide text-ink/70 mb-4">Design Preview</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="flex flex-col gap-2">
                        <p class="text-xs font-bold text-ink/50 text-center uppercase tracking-wide">Previous Design</p>
                        <div class="aspect-square rounded-xl border border-ink/10 bg-ink/[0.02] flex items-center justify-center overflow-hidden">
                            <img
                                v-if="request.original_template_image"
                                :src="request.original_template_image"
                                :alt="request.template_name"
                                class="w-full h-full object-contain p-4"
                            />
                            <span v-else class="text-xs text-ink/40 px-4 text-center">Original template no longer available.</span>
                        </div>
                    </div>
                    <div class="flex flex-col gap-2">
                        <p class="text-xs font-bold text-cobalt text-center uppercase tracking-wide">Current Design</p>
                        <div class="aspect-square rounded-xl border border-cobalt/20 bg-cobalt/[0.03] flex items-center justify-center overflow-hidden">
                            <img
                                :src="request.template_image_url"
                                :alt="request.template_name"
                                class="w-full h-full object-contain p-4"
                            />
                        </div>
                    </div>
                </div>
            </section>

            <!-- RIGHT: status + roster -->
            <section v-reveal="140" class="lg:col-span-4 flex flex-col gap-6">
                <div class="rounded-2xl bg-white border border-ink/10 shadow-sm p-5">
                    <h2 class="text-sm font-black uppercase tracking-wide text-ink/70 mb-3">Status</h2>
                    <span
                        class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold mb-2"
                        :class="statusBadge[request.status].class"
                    >
                        {{ statusBadge[request.status].label }}
                    </span>
                    <p class="text-sm text-ink/60 leading-relaxed">{{ statusMeaning[request.status] }}</p>
                </div>

                <div class="rounded-2xl bg-white border border-ink/10 shadow-sm p-5">
                    <div class="flex items-center justify-between mb-3">
                        <h2 class="text-sm font-black uppercase tracking-wide text-ink/70">
                            <font-awesome-icon icon="fa-solid fa-users" class="text-cobalt" />
                            Team Roster
                        </h2>
                        <span class="text-xs text-ink/40">{{ rosterPlayers.length }} player{{ rosterPlayers.length === 1 ? "" : "s" }}</span>
                    </div>
                    <div v-if="rosterPlayers.length" class="flex flex-wrap gap-1.5">
                        <span
                            v-for="p in rosterPlayers.slice(0, 10)"
                            :key="p.id"
                            class="inline-flex items-center gap-1 px-2 py-1 rounded-lg bg-ink/[0.03] text-xs text-ink/70"
                        >
                            <span class="font-bold text-cobalt">{{ p.number ?? "—" }}</span> {{ p.name }}
                        </span>
                        <span v-if="rosterPlayers.length > 10" class="text-xs text-ink/40 self-center">
                            +{{ rosterPlayers.length - 10 }} more
                        </span>
                    </div>
                    <p v-else class="text-xs text-ink/40">No roster submitted yet.</p>
                    <Link
                        :href="route('client.design.roster', request.id)"
                        class="mt-3 inline-flex items-center gap-1 text-xs font-bold text-cobalt hover:text-cobalt-dark"
                    >
                        Manage Roster
                        <font-awesome-icon icon="fa-solid fa-arrow-right" class="text-[10px]" />
                    </Link>
                </div>
            </section>
        </div>

        <!-- Cancel confirm -->
        <div v-if="showCancelConfirm" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-ink/40 backdrop-blur-sm" @click.self="showCancelConfirm = false">
            <div class="bg-white rounded-2xl shadow-2xl max-w-sm w-full p-5">
                <h3 class="text-base font-bold text-ink mb-2">Cancel Request</h3>
                <p class="text-sm text-ink/60 mb-5">Are you sure you want to cancel this request?</p>
                <div class="flex justify-between gap-2">
                    <SecondaryButton :disabled="cancelling" @click="showCancelConfirm = false">Close</SecondaryButton>
                    <PrimaryButton class="!bg-accent hover:!bg-accent-dark" :disabled="cancelling" @click="confirmCancel">
                        Confirm Cancel
                    </PrimaryButton>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
