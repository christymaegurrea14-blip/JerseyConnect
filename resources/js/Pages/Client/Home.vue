<script setup lang="ts">
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import Modal from "@/Components/Modal.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import TextAreaInput from "@/Components/TextAreaInput.vue";
import Card from "@/Components/Card.vue";
import type { JerseyTemplate } from "@/types/jersey";
import { useModal } from "@/Composables/useModal";
import { Head, useForm } from "@inertiajs/vue3";
import { ref, computed } from "vue";

// Templates come from the admin-managed catalog (only "active" ones are sent).
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

const modal = useModal();

// Track which template the customer clicked so the modal can show its image
const selectedTemplate = ref<JerseyTemplate | null>(null);

const form = useForm({
    template_id: "",
    team_name: "",
    primary_color: "#2E7D4F",
    secondary_color: "#14202B",
    accent_color: "#FFFFFF",
    font_style: "",
    estimated_quantity: "",
    notes: "",
    logo: null as File | null,
});

const filtered = computed<JerseyTemplate[]>(() => {
    return jerseyTemplates.value.filter((t) => {
        const matchesSport =
            activeSport.value === "All" || t.sport === activeSport.value;
        const matchesSearch = t.name
            .toLowerCase()
            .includes(search.value.toLowerCase());
        return matchesSport && matchesSearch;
    });
});

function handleSelect(id: number) {
    const template = jerseyTemplates.value.find((t) => t.id === id) ?? null;
    selectedTemplate.value = template;

    form.reset();
    form.clearErrors();

    form.template_id = template ? String(template.id) : "";
    form.primary_color = template?.primaryColor ?? "#2E7D4F";
    form.secondary_color = template?.secondaryColor ?? "#14202B";
    form.accent_color = template?.accentColor ?? "#FFFFFF";

    modal.title.value = "Customize This Design";
    modal.type.value = "selected";
    modal.icon.value = "fa-solid fa-tshirt";
    modal.openModal();
}

function handleLogoUpload(event: Event) {
    const target = event.target as HTMLInputElement;
    form.logo = target.files?.[0] ?? null;
}

const modalClose = () => {
    form.reset();
    form.clearErrors();
    selectedTemplate.value = null;
    modal.closeModal();
};

const submit = () => {
    form.post(route("client.home.store"), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => modalClose(),
    });
};
</script>

<template>
    <Head title="Jersey Templates" />

    <AuthenticatedLayout>
        <div>
            <!-- Filters -->
            <div
                class="flex flex-col gap-3 border-b border-[#14202B]/10 bg-white px-6 py-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div class="flex flex-wrap gap-2">
                    <button
                        v-for="sport in sports"
                        :key="sport"
                        type="button"
                        class="rounded-full px-3 py-1.5 text-sm font-medium transition-colors"
                        :class="
                            activeSport === sport
                                ? 'bg-[#2E7D4F] text-white'
                                : 'bg-[#14202B]/5 text-[#14202B]/70 hover:bg-[#14202B]/10'
                        "
                        @click="activeSport = sport"
                    >
                        {{ sport }}
                    </button>
                </div>

                <input
                    id="jersey-search"
                    v-model="search"
                    type="text"
                    name="search"
                    placeholder="Search templates..."
                    aria-label="Search templates"
                    class="w-full rounded border border-[#14202B]/15 px-3 py-1.5 text-sm focus:border-[#2E7D4F] focus:outline-none focus:ring-1 focus:ring-[#2E7D4F] sm:w-64"
                />
            </div>

            <!-- Grid -->
            <div class="py-4">
                <div
                    v-if="filtered.length"
                    class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
                >
                    <Card
                        v-for="template in filtered"
                        :key="template.id"
                        :template="template"
                        @select="handleSelect"
                    />
                </div>

                <div v-else class="py-16 text-center text-sm text-[#14202B]/50">
                    No templates match that search. Try a different sport or
                    keyword.
                </div>
            </div>
        </div>

        <!-- Modal -->
        <Modal
            :show="modal.type.value === 'selected'"
            @close="!form.processing && modalClose()"
            :maxWidth="'7xl'"
        >
            <div class="max-h-[85vh] overflow-y-auto px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <h2 class="text-lg font-medium text-gray-900">
                    <font-awesome-icon :icon="modal.icon.value" />
                    {{ modal.title.value }}
                </h2>
                <hr class="my-2" />
                <div class="mt-4 flex flex-col gap-6 sm:flex-row">
                    <!-- Template preview -->
                    <div
                        class="flex w-full flex-col items-center gap-2 sm:w-72 flex-shrink-0"
                    >
                        <p
                            v-if="selectedTemplate"
                            class="font-medium text-[#14202B]"
                        >
                            {{ selectedTemplate.name }}
                        </p>
                        <p
                            v-if="selectedTemplate"
                            class="text-sm text-[#14202B]/50"
                        >
                            {{ selectedTemplate.sport }} · Base price ₱{{
                                selectedTemplate.price
                            }}
                        </p>
                        <div
                            class="flex h-48 w-full items-center justify-center overflow-hidden rounded-lg border border-[#14202B]/10 bg-[#14202B]/5 sm:h-full"
                        >
                            <img
                                v-if="selectedTemplate"
                                :src="selectedTemplate.imagePath"
                                :alt="selectedTemplate.name"
                                class="h-full w-full object-contain p-4"
                            />
                        </div>
                    </div>

                    <!-- Design request fields -->
                    <div class="flex w-full flex-col">
                        <div>
                            <InputLabel for="team_name" value="Team Name" />
                            <TextInput
                                id="team_name"
                                type="text"
                                class="mt-1 block w-full outline-none"
                                v-model="form.team_name"
                                required
                                placeholder="e.g. Iloilo Thunderbolts"
                            />
                            <InputError
                                :message="form.errors.team_name"
                                class="mt-2"
                            />
                        </div>

                        <div class="mt-4 grid grid-cols-1 sm:grid-cols-3 gap-2 sm:gap-3">
                            <div>
                                <InputLabel
                                    for="primary_color"
                                    value="Primary Color"
                                />
                                <input
                                    id="primary_color"
                                    type="color"
                                    v-model="form.primary_color"
                                    class="mt-1 h-10 w-full rounded outline-none border border-[#14202B]/15 p-1"
                                />
                            </div>
                            <div>
                                <InputLabel
                                    for="secondary_color"
                                    value="Secondary Color"
                                />
                                <input
                                    id="secondary_color"
                                    type="color"
                                    v-model="form.secondary_color"
                                    class="mt-1 h-10 w-full rounded outline-none border border-[#14202B]/15 p-1"
                                />
                            </div>
                            <div>
                                <InputLabel
                                    for="accent_color"
                                    value="Accent Color"
                                />
                                <input
                                    id="accent_color"
                                    type="color"
                                    v-model="form.accent_color"
                                    class="mt-1 h-10 w-full rounded outline-none border border-[#14202B]/15 p-1"
                                />
                            </div>
                        </div>

                        <div class="mt-4">
                            <InputLabel
                                for="font_style"
                                value="Name/Number Font Style"
                            />
                            <TextInput
                                id="font_style"
                                type="text"
                                class="mt-1 block w-full outline-none"
                                v-model="form.font_style"
                                placeholder="e.g. Block, Script, Italic"
                            />
                            <InputError
                                :message="form.errors.font_style"
                                class="mt-2"
                            />
                        </div>

                        <div class="mt-4">
                            <InputLabel
                                for="logo"
                                value="Team Logo (optional)"
                            />
                            <input
                                id="logo"
                                type="file"
                                accept="image/*"
                                @change="handleLogoUpload"
                                class="mt-1 block w-full text-sm text-[#14202B]/70 file:mr-3 file:rounded file:border-0 file:bg-[#2E7D4F] file:px-3 file:py-1.5 file:text-sm file:text-white hover:file:bg-[#2E7D4F]/90"
                            />
                            <InputError
                                :message="form.errors.logo"
                                class="mt-2"
                            />
                        </div>

                        <div class="mt-4">
                            <InputLabel
                                for="estimated_quantity"
                                value="Estimated Quantity"
                            />
                            <TextInput
                                id="estimated_quantity"
                                type="number"
                                class="mt-1 block w-full outline-none"
                                v-model="form.estimated_quantity"
                                placeholder="e.g. 15"
                            />
                            <InputError
                                :message="form.errors.estimated_quantity"
                                class="mt-2"
                            />
                        </div>

                        <div class="mt-4">
                            <InputLabel for="notes" value="Design Notes" />
                            <TextAreaInput
                                id="notes"
                                class="mt-1 block w-full outline-none"
                                v-model="form.notes"
                                rows="3"
                                placeholder="e.g. Keep sleeves solid black, sponsor logo on lower back"
                            />
                            <InputError
                                :message="form.errors.notes"
                                class="mt-2"
                            />
                        </div>
                    </div>
                </div>
                <hr class="mt-5" />
                <div
                    class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-between"
                >
                    <SecondaryButton
                        class="flex w-full items-center justify-center sm:w-auto"
                        :class="{ 'opacity-25': form.processing }"
                        @click="modalClose"
                        :disabled="form.processing"
                    >
                        Cancel
                    </SecondaryButton>

                    <PrimaryButton
                        class="flex w-full items-center justify-center gap-1 sm:w-auto"
                        :class="{ 'opacity-25': form.processing }"
                        @click="submit"
                        :disabled="form.processing"
                    >
                        <div class="text-sm" v-if="form.processing">
                            <font-awesome-icon
                                icon="fa-solid fa-spinner"
                                spin
                            />
                        </div>
                        Submit Design Request
                        <font-awesome-icon icon="fa-solid fa-paper-plane" />
                    </PrimaryButton>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>