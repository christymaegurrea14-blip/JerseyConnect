<script setup lang="ts">
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import type { DesignRequest } from "@/types/jersey";
import { Head, Link } from "@inertiajs/vue3";
import { computed, ref } from "vue";

const props = defineProps<{
    data?: DesignRequest[];
}>();

const requests = computed(() => props.data ?? []);

const search = ref("");

const filtered = computed(() => {
    const query = search.value.trim().toLowerCase();
    if (!query) return requests.value;
    return requests.value.filter(
        (r) => r.team_name.toLowerCase().includes(query) || r.template_name.toLowerCase().includes(query),
    );
});

function sizeDistribution(request: DesignRequest) {
    const counts: Record<string, number> = {};
    for (const p of request.players ?? []) {
        if (!p.size) continue;
        counts[p.size] = (counts[p.size] ?? 0) + 1;
    }
    return Object.entries(counts);
}

const totalPlayers = computed(() =>
    requests.value.reduce((sum, r) => sum + (r.players?.length ?? 0), 0),
);
</script>

<template>
    <Head title="Team Rosters" />

    <AuthenticatedLayout>
        <!-- Header -->
        <section v-reveal class="rounded-2xl bg-white border border-ink/10 shadow-sm p-5 sm:p-6 mb-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-3.5">
                    <div class="w-11 h-11 rounded-xl bg-cobalt/10 text-cobalt flex items-center justify-center border border-cobalt/20 shrink-0">
                        <font-awesome-icon icon="fa-solid fa-users" class="text-lg" />
                    </div>
                    <div>
                        <h1 class="text-xl font-black tracking-tight text-ink">Team Rosters</h1>
                        <p class="text-sm text-ink/50 mt-0.5">
                            {{ requests.length }} team{{ requests.length === 1 ? "" : "s" }} • {{ totalPlayers }} player{{ totalPlayers === 1 ? "" : "s" }} total
                        </p>
                    </div>
                </div>
                <div class="relative w-full sm:max-w-xs">
                    <font-awesome-icon icon="fa-solid fa-magnifying-glass" class="absolute left-3 top-1/2 -translate-y-1/2 text-ink/30 text-xs" />
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Search team or design..."
                        class="w-full pl-8 pr-3 py-2 text-sm rounded-xl border border-ink/15 outline-none focus:border-cobalt focus:ring-1 focus:ring-cobalt"
                    />
                </div>
            </div>
        </section>

        <div v-if="filtered.length" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
            <Link
                v-for="(request, i) in filtered"
                :key="request.id"
                :href="route('client.design.roster', request.id)"
                v-reveal="(i % 3) * 70"
                class="flex flex-col rounded-2xl bg-white border border-ink/10 shadow-sm overflow-hidden hover:border-cobalt/30 hover:shadow-md transition-all"
            >
                <div class="aspect-square bg-ink/[0.03] flex items-center justify-center p-4">
                    <img
                        :src="request.template_image_url"
                        :alt="request.template_name"
                        class="w-full h-full object-contain"
                    />
                </div>

                <div class="p-5 flex flex-col flex-1">
                <div class="min-w-0">
                    <p class="font-bold text-ink truncate">{{ request.team_name }}</p>
                    <p class="text-xs text-ink/50 truncate">{{ request.template_name }}</p>
                </div>

                <div class="flex items-center gap-1.5 mt-4">
                    <font-awesome-icon icon="fa-solid fa-users" class="text-cobalt/60 text-xs" />
                    <span class="text-sm font-bold text-ink">{{ request.players?.length ?? 0 }}</span>
                    <span class="text-xs text-ink/40">player{{ (request.players?.length ?? 0) === 1 ? "" : "s" }}</span>
                    <span class="text-xs text-ink/30 mx-1">•</span>
                    <span class="text-xs text-ink/40">{{ request.estimated_quantity }} sets ordered</span>
                </div>

                <div v-if="sizeDistribution(request).length" class="flex flex-wrap gap-1.5 mt-3">
                    <span
                        v-for="[size, count] in sizeDistribution(request)"
                        :key="size"
                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-bold bg-cobalt/10 text-cobalt"
                    >
                        {{ size }}
                        <span class="px-1 rounded-full bg-cobalt text-white text-[9px]">{{ count }}</span>
                    </span>
                </div>

                <div class="mt-4 pt-3 border-t border-ink/10 flex items-center justify-between">
                    <span class="text-xs font-bold text-cobalt">Manage Roster</span>
                    <font-awesome-icon icon="fa-solid fa-arrow-right" class="text-[11px] text-cobalt" />
                </div>
                </div>
            </Link>
        </div>
        <div v-else class="rounded-2xl bg-white border border-dashed border-ink/15 py-16 text-center text-sm text-ink/40">
            <font-awesome-icon icon="fa-solid fa-users" class="text-2xl mb-2 block mx-auto text-ink/20" />
            No teams yet. Start a design request from the Catalogue to add a roster.
        </div>
    </AuthenticatedLayout>
</template>
