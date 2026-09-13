<script setup lang="ts">
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import Breadcrumbs from "@/Components/Breadcrumbs.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import InputError from "@/Components/InputError.vue";
import type { DesignRequest, RosterPlayer } from "@/types/jersey";
import { Head, useForm, router } from "@inertiajs/vue3";
import { computed, ref } from "vue";

const props = defineProps<{ request: DesignRequest }>();

const rosterPlayers = computed<RosterPlayer[]>(() => props.request.players ?? []);

const PLAYER_SIZES = ["XS", "S", "M", "L", "XL", "XXL", "3XL"] as const;

const sizeDistribution = computed(() => {
    const counts: Record<string, number> = {};
    for (const p of rosterPlayers.value) {
        if (!p.size) continue;
        counts[p.size] = (counts[p.size] ?? 0) + 1;
    }
    return PLAYER_SIZES.map((size) => ({ size, count: counts[size] ?? 0 })).filter((s) => s.count > 0);
});

const editingPlayerId = ref<number | null>(null);
const playerForm = useForm({
    name: "",
    number: "",
    position: "",
    size: "",
});

function resetPlayerForm() {
    editingPlayerId.value = null;
    playerForm.reset();
    playerForm.clearErrors();
}

function startEditPlayer(player: RosterPlayer) {
    editingPlayerId.value = player.id;
    playerForm.clearErrors();
    playerForm.name = player.name;
    playerForm.number = player.number ?? "";
    playerForm.position = player.position ?? "";
    playerForm.size = player.size ?? "";
}

function submitPlayer() {
    if (editingPlayerId.value) {
        playerForm.put(route("client.design.players.update", editingPlayerId.value), {
            preserveScroll: true,
            onSuccess: () => resetPlayerForm(),
        });
    } else {
        playerForm.post(route("client.design.players.store", props.request.id), {
            preserveScroll: true,
            onSuccess: () => resetPlayerForm(),
        });
    }
}

function deletePlayer(player: RosterPlayer) {
    router.delete(route("client.design.players.destroy", player.id), { preserveScroll: true });
}

// --- Bulk import ---
const importForm = useForm({
    file: null as File | null,
});
const importFileName = ref<string | null>(null);
const importSucceeded = ref(false);

function handleImportFileChange(e: Event) {
    const target = e.target as HTMLInputElement;
    const file = target.files?.[0] ?? null;
    importForm.file = file;
    importFileName.value = file?.name ?? null;
    importSucceeded.value = false;
}

function submitImport() {
    if (!importForm.file) return;

    importForm.post(route("client.design.players.import", props.request.id), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            importForm.reset();
            importFileName.value = null;
            importSucceeded.value = true;
            setTimeout(() => (importSucceeded.value = false), 4000);
        },
    });
}
</script>

<template>
    <Head title="Team Roster" />

    <AuthenticatedLayout>
        <Breadcrumbs
            :items="[
                { label: 'Team Rosters', href: route('client.rosters.index') },
                { label: request.team_name, href: route('client.design.show', request.id) },
                { label: 'Team Roster' },
            ]"
        />

        <!-- Header banner -->
        <section v-reveal class="rounded-2xl bg-white border border-ink/10 shadow-sm p-5 sm:p-6 mb-6">
            <div class="flex items-center gap-3.5">
                <div class="w-11 h-11 rounded-xl bg-cobalt/10 text-cobalt flex items-center justify-center border border-cobalt/20 shrink-0">
                    <font-awesome-icon icon="fa-solid fa-users" class="text-lg" />
                </div>
                <div>
                    <h1 class="text-xl font-black tracking-tight text-ink">{{ request.team_name }} — Team Roster</h1>
                    <p class="text-sm text-ink/50 mt-0.5">
                        {{ rosterPlayers.length }} player{{ rosterPlayers.length === 1 ? "" : "s" }} registered • {{ request.estimated_quantity }} sets ordered
                    </p>
                </div>
            </div>

            <div v-if="sizeDistribution.length" class="flex flex-wrap items-center gap-2 mt-4 pt-4 border-t border-ink/10">
                <span class="text-xs font-bold text-ink/50 uppercase tracking-wide mr-1">Size distribution</span>
                <span
                    v-for="s in sizeDistribution"
                    :key="s.size"
                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-cobalt/10 text-cobalt"
                >
                    {{ s.size }}
                    <span class="px-1.5 py-0.5 rounded-full bg-cobalt text-white text-[10px]">{{ s.count }}</span>
                </span>
            </div>
        </section>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 mb-6">
            <!-- Design Blueprint -->
            <section v-reveal="80" class="lg:col-span-4 rounded-2xl bg-white border border-ink/10 shadow-sm p-5 sm:p-6">
                <h2 class="text-sm font-black uppercase tracking-wide text-ink/70 mb-4">Design Blueprint</h2>
                <div class="relative aspect-square rounded-xl bg-ink/[0.03] border border-ink/10 overflow-hidden mb-4">
                    <img :src="request.template_image_url" :alt="request.template_name" class="w-full h-full object-contain p-4" />
                </div>
                <div class="space-y-0 text-xs">
                    <div class="flex items-center justify-between py-2 border-b border-ink/10">
                        <span class="text-ink/50">Template</span>
                        <span class="font-bold text-ink">{{ request.template_name }}</span>
                    </div>
                    <div class="flex items-center justify-between py-2 border-b border-ink/10">
                        <span class="text-ink/50">Team Name Print</span>
                        <span class="font-bold text-ink">{{ request.team_name }}</span>
                    </div>
                    <div class="flex items-center justify-between py-2 border-b border-ink/10">
                        <span class="text-ink/50">Font Style</span>
                        <span class="font-bold text-ink">{{ request.font_style || "Default" }}</span>
                    </div>
                    <div class="flex items-center justify-between py-2 border-b border-ink/10">
                        <span class="text-ink/50">Colorway</span>
                        <div class="flex items-center gap-1">
                            <span class="w-4 h-4 rounded-full border border-ink/15" :style="{ backgroundColor: request.primary_color }"></span>
                            <span class="w-4 h-4 rounded-full border border-ink/15" :style="{ backgroundColor: request.secondary_color }"></span>
                            <span class="w-4 h-4 rounded-full border border-ink/15" :style="{ backgroundColor: request.accent_color }"></span>
                        </div>
                    </div>
                    <div class="flex items-center justify-between py-2">
                        <span class="text-ink/50">Team Logo</span>
                        <span class="font-bold" :class="request.logo_url ? 'text-good' : 'text-ink/40'">
                            {{ request.logo_url ? "Uploaded" : "Not uploaded" }}
                        </span>
                    </div>
                </div>
            </section>

            <!-- Squad Roster Matrix -->
            <section v-reveal="140" class="lg:col-span-8 rounded-2xl bg-white border border-ink/10 shadow-sm p-5 sm:p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-sm font-black uppercase tracking-wide text-ink/70">Squad Roster Matrix</h2>
                    <span class="text-xs text-ink/40">{{ rosterPlayers.length }} of {{ request.estimated_quantity }} sets assigned</span>
                </div>
                <div v-if="rosterPlayers.length" class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b-2 border-ink/10 text-left text-[11px] uppercase tracking-wide text-ink/40">
                                <th class="py-2 pr-3 font-bold">#</th>
                                <th class="py-2 px-3 font-bold">Player</th>
                                <th class="py-2 px-3 font-bold">Position</th>
                                <th class="py-2 px-3 font-bold">Size</th>
                                <th class="py-2 pl-3 font-bold text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="p in rosterPlayers" :key="p.id" class="border-b border-ink/5 hover:bg-ink/[0.02]">
                                <td class="py-2.5 pr-3">
                                    <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-cobalt/10 text-cobalt text-xs font-bold">
                                        {{ p.number ?? "—" }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-3 font-semibold text-ink">{{ p.name }}</td>
                                <td class="py-2.5 px-3 text-ink/60">{{ p.position ?? "—" }}</td>
                                <td class="py-2.5 px-3">
                                    <span v-if="p.size" class="px-2 py-0.5 rounded-full bg-ink/5 text-ink/70 text-xs font-bold">{{ p.size }}</span>
                                    <span v-else class="text-ink/30">—</span>
                                </td>
                                <td class="py-2.5 pl-3 text-right whitespace-nowrap">
                                    <button type="button" class="text-ink/40 hover:text-cobalt p-1.5" @click="startEditPlayer(p)">
                                        <font-awesome-icon icon="fa-solid fa-edit" />
                                    </button>
                                    <button type="button" class="text-ink/40 hover:text-accent p-1.5" @click="deletePlayer(p)">
                                        <font-awesome-icon icon="fa-solid fa-trash" />
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p v-else class="text-sm text-ink/40 text-center py-10">No players added yet.</p>
            </section>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Add/edit form -->
                <section v-reveal="200" class="rounded-2xl bg-white border border-ink/10 shadow-sm p-5 sm:p-6 h-fit">
                    <h2 class="text-sm font-black uppercase tracking-wide text-ink/70 mb-4">
                        {{ editingPlayerId ? "Edit Player" : "Add Player" }}
                    </h2>
                    <form @submit.prevent="submitPlayer" class="space-y-3">
                        <input
                            v-model="playerForm.name"
                            type="text"
                            placeholder="Player name"
                            class="w-full rounded-md border border-ink/15 px-3 py-2 text-sm outline-none focus:border-cobalt focus:ring-1 focus:ring-cobalt"
                            required
                        />
                        <div class="grid grid-cols-2 gap-2">
                            <input
                                v-model="playerForm.number"
                                type="text"
                                inputmode="numeric"
                                maxlength="3"
                                placeholder="Jersey No."
                                class="w-full rounded-md border border-ink/15 px-3 py-2 text-sm outline-none focus:border-cobalt focus:ring-1 focus:ring-cobalt"
                            />
                            <select
                                v-model="playerForm.size"
                                class="w-full rounded-md border border-ink/15 px-3 py-2 text-sm outline-none focus:border-cobalt focus:ring-1 focus:ring-cobalt"
                            >
                                <option value="">Size</option>
                                <option v-for="s in PLAYER_SIZES" :key="s" :value="s">{{ s }}</option>
                            </select>
                        </div>
                        <input
                            v-model="playerForm.position"
                            type="text"
                            placeholder="Position / role (e.g. Point Guard, Captain)"
                            class="w-full rounded-md border border-ink/15 px-3 py-2 text-sm outline-none focus:border-cobalt focus:ring-1 focus:ring-cobalt"
                        />
                        <InputError :message="playerForm.errors.name" />
                        <InputError :message="playerForm.errors.number" />
                        <InputError :message="playerForm.errors.position" />
                        <InputError :message="playerForm.errors.size" />
                        <div class="flex items-center gap-2 pt-1">
                            <PrimaryButton
                                type="submit"
                                class="flex items-center gap-1.5"
                                :class="{ 'opacity-25': playerForm.processing }"
                                :disabled="playerForm.processing"
                            >
                                <font-awesome-icon v-if="playerForm.processing" icon="fa-solid fa-spinner" spin />
                                <font-awesome-icon v-else-if="!editingPlayerId" icon="fa-solid fa-plus" />
                                {{ editingPlayerId ? "Save Changes" : "Add Player" }}
                            </PrimaryButton>
                            <SecondaryButton v-if="editingPlayerId" type="button" @click="resetPlayerForm">
                                Cancel
                            </SecondaryButton>
                        </div>
                    </form>
                </section>

                <!-- Bulk import -->
                <section v-reveal="240" class="rounded-2xl bg-white border border-ink/10 shadow-sm p-5 sm:p-6 h-fit">
                    <div class="flex items-center justify-between mb-1">
                        <h2 class="text-sm font-black uppercase tracking-wide text-ink/70">Bulk Import</h2>
                        <a
                            :href="route('client.design.players.template', request.id)"
                            class="text-xs font-bold text-cobalt hover:text-cobalt-dark flex items-center gap-1"
                        >
                            <font-awesome-icon icon="fa-solid fa-circle-down" />
                            Download Template
                        </a>
                    </div>
                    <p class="text-xs text-ink/50 mb-3">
                        Fill in the template in Excel or Google Sheets, then upload it here to add many players at once.
                    </p>

                    <form @submit.prevent="submitImport" class="space-y-3">
                        <label
                            class="flex items-center gap-2.5 rounded-xl border-2 border-dashed px-3 py-3 cursor-pointer transition-colors"
                            :class="importFileName ? 'border-cobalt/40 bg-cobalt/5' : 'border-ink/15 hover:border-cobalt/30 hover:bg-cobalt/[0.02]'"
                        >
                            <font-awesome-icon icon="fa-solid fa-cloud-arrow-up" class="text-cobalt/60" />
                            <span class="text-xs text-ink/70 truncate">
                                {{ importFileName ?? "Click to choose a CSV file..." }}
                            </span>
                            <input type="file" accept=".csv,text/csv" class="hidden" @change="handleImportFileChange" />
                        </label>
                        <InputError :message="importForm.errors.file" />
                        <p v-if="importSucceeded" class="text-xs font-bold text-good flex items-center gap-1.5">
                            <font-awesome-icon icon="fa-solid fa-circle-check" />
                            Roster updated.
                        </p>
                        <PrimaryButton
                            type="submit"
                            class="flex items-center gap-1.5"
                            :class="{ 'opacity-25': importForm.processing || !importForm.file }"
                            :disabled="importForm.processing || !importForm.file"
                        >
                            <font-awesome-icon v-if="importForm.processing" icon="fa-solid fa-spinner" spin />
                            <font-awesome-icon v-else icon="fa-solid fa-upload" />
                            {{ importForm.processing ? "Importing..." : "Import Roster" }}
                        </PrimaryButton>
                    </form>
                </section>
        </div>
    </AuthenticatedLayout>
</template>
