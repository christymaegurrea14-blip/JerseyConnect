<script setup lang="ts">
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import Breadcrumbs from "@/Components/Breadcrumbs.vue";
import Card from "@/Components/Card.vue";
import CustomizeDesignModal from "@/Components/CustomizeDesignModal.vue";
import type { JerseyTemplate } from "@/types/jersey";
import { Head } from "@inertiajs/vue3";
import { ref, computed } from "vue";

const props = defineProps<{
    data?: JerseyTemplate[];
}>();

const jerseyTemplates = computed<JerseyTemplate[]>(() => props.data ?? []);

const sports = [
    "All",
    "Basketball",
    "Soccer",
    "Baseball",
    "Volleyball",
    "Esports",
] as const;
const activeSport = ref<(typeof sports)[number]>("All");
const search = ref("");

const sportCounts = computed(() => {
    const counts: Record<string, number> = {};
    for (const t of jerseyTemplates.value) counts[t.sport] = (counts[t.sport] ?? 0) + 1;
    return counts;
});

const filtered = computed<JerseyTemplate[]>(() => {
    return jerseyTemplates.value.filter((t) => {
        const matchesSport = activeSport.value === "All" || t.sport === activeSport.value;
        const matchesSearch = t.name.toLowerCase().includes(search.value.toLowerCase());
        return matchesSport && matchesSearch;
    });
});

const selectedTemplate = ref<JerseyTemplate | null>(null);
const showCustomizeModal = ref(false);

function handleSelect(id: number) {
    selectedTemplate.value = jerseyTemplates.value.find((t) => t.id === id) ?? null;
    showCustomizeModal.value = true;
}

function closeCustomizeModal() {
    showCustomizeModal.value = false;
    selectedTemplate.value = null;
}
</script>

<template>
    <Head title="Full Catalogue" />

    <AuthenticatedLayout>
        <Breadcrumbs
            :items="[
                { label: 'Catalogue', href: route('client.home.index') },
                { label: 'Full Catalogue' },
            ]"
        />

        <section class="rounded-2xl bg-white border border-ink/10 shadow-sm p-5 sm:p-6 mb-6">
            <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 mb-5">
                <div>
                    <div class="flex items-center gap-1.5 text-accent font-black uppercase text-xs tracking-wider">
                        <font-awesome-icon icon="fa-solid fa-shirt" class="text-[13px]" />
                        <span>Live Catalog</span>
                    </div>
                    <h1 class="text-2xl sm:text-3xl font-black text-ink tracking-tight">Full-Dye Sublimation Catalog</h1>
                    <p class="text-sm text-ink/50 mt-1">{{ jerseyTemplates.length }} template{{ jerseyTemplates.length === 1 ? "" : "s" }} available.</p>
                </div>
                <input
                    id="jersey-search"
                    v-model="search"
                    type="text"
                    name="search"
                    placeholder="Search templates..."
                    aria-label="Search templates"
                    class="w-full sm:w-64 rounded-xl border border-ink/10 bg-white px-3.5 py-2 text-sm shadow-xs focus:border-cobalt focus:outline-none focus:ring-1 focus:ring-cobalt"
                />
            </div>

            <div class="flex items-center gap-2 overflow-x-auto pb-1">
                <button
                    v-for="sport in sports"
                    :key="sport"
                    type="button"
                    class="flex items-center gap-2 px-4 py-2 rounded-full text-xs font-bold whitespace-nowrap transition-all shadow-xs"
                    :class="activeSport === sport
                        ? 'bg-cobalt text-white shadow-cobalt/20'
                        : 'bg-white border border-ink/10 text-ink/70 hover:border-cobalt/40'"
                    @click="activeSport = sport"
                >
                    <span>{{ sport }}</span>
                    <span
                        v-if="sport !== 'All'"
                        class="px-1.5 py-0.5 rounded-full text-[10px]"
                        :class="activeSport === sport ? 'bg-white/20 text-white' : 'bg-ink/5 text-ink/50'"
                    >
                        {{ sportCounts[sport] ?? 0 }}
                    </span>
                    <span
                        v-else
                        class="px-1.5 py-0.5 rounded-full text-[10px]"
                        :class="activeSport === sport ? 'bg-white/20 text-white' : 'bg-ink/5 text-ink/50'"
                    >
                        {{ jerseyTemplates.length }}
                    </span>
                </button>
            </div>
        </section>

        <div
            v-if="filtered.length"
            class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
        >
            <Card
                v-for="(template, i) in filtered"
                :key="template.id"
                v-reveal="(i % 4) * 70"
                :template="template"
                @select="handleSelect"
            />
        </div>

        <div v-else class="py-16 text-center text-sm text-ink/50">
            No templates match that search. Try a different sport or keyword.
        </div>

        <CustomizeDesignModal :show="showCustomizeModal" :template="selectedTemplate" @close="closeCustomizeModal" />
    </AuthenticatedLayout>
</template>
