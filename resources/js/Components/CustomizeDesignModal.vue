<script setup lang="ts">
import PrimaryButton from "@/Components/PrimaryButton.vue";
import InputError from "@/Components/InputError.vue";
import Modal from "@/Components/Modal.vue";
import ModalHeader from "@/Components/ModalHeader.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import type { JerseyTemplate } from "@/types/jersey";
import { Link, useForm } from "@inertiajs/vue3";
import { computed, ref, watch } from "vue";

const props = defineProps<{
    show: boolean;
    template: JerseyTemplate | null;
}>();

const emit = defineEmits<{ (e: "close"): void }>();

function formatPrice(value: number) {
    return `₱${value.toLocaleString("en-PH")}`;
}

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

// Quick-fill presets for the font-style text field — these just fill the
// same real `font_style` text input, they don't restrict it to an enum.
const FONT_PRESETS = [
    { label: "Block", sampleClass: "font-black uppercase tracking-wide" },
    { label: "Script", sampleClass: "font-bold italic" },
    { label: "Italic", sampleClass: "font-black italic tracking-tight" },
    { label: "Modern", sampleClass: "font-extrabold font-mono" },
] as const;

// Quick-pick swatches for the color fields — cosmetic shortcuts that write
// into the same real primary/secondary/accent hex fields.
const COLOR_PRESETS = ["#FFFFFF", "#0B1220", "#2547E0", "#FF4B2B", "#F5A524", "#16A34A"];

function resetColorsToTemplate() {
    if (!props.template) return;
    form.primary_color = props.template.primaryColor;
    form.secondary_color = props.template.secondaryColor;
    form.accent_color = props.template.accentColor;
}

const logoPreviewUrl = ref<string | null>(null);

function setLogoFile(file: File | null) {
    if (logoPreviewUrl.value) URL.revokeObjectURL(logoPreviewUrl.value);
    form.logo = file;
    logoPreviewUrl.value = file ? URL.createObjectURL(file) : null;
}

function handleLogoUpload(event: Event) {
    const target = event.target as HTMLInputElement;
    setLogoFile(target.files?.[0] ?? null);
}

const isDraggingLogo = ref(false);

function handleLogoDrop(event: DragEvent) {
    event.preventDefault();
    isDraggingLogo.value = false;
    const file = event.dataTransfer?.files?.[0] ?? null;
    if (file && file.type.startsWith("image/")) setLogoFile(file);
}

function removeLogo() {
    setLogoFile(null);
}

// Real subtotal preview (quantity x base price). Final total, including any
// shipping fee, is confirmed by our team — same caveat used on My Orders.
const estimatedSubtotal = computed(() => {
    const qty = Number(form.estimated_quantity);
    if (!props.template || !qty || qty <= 0) return null;
    return qty * props.template.price;
});

function stepQuantity(delta: number) {
    const next = (Number(form.estimated_quantity) || 0) + delta;
    form.estimated_quantity = String(Math.max(1, next));
}

// Re-seed the form whenever the caller assigns a new template — this is what
// opens the modal from the parent's perspective (v-model-style via props).
watch(
    () => props.template,
    (template) => {
        if (!template) return;
        form.reset();
        form.clearErrors();
        setLogoFile(null);
        form.template_id = String(template.id);
        form.primary_color = template.primaryColor;
        form.secondary_color = template.secondaryColor;
        form.accent_color = template.accentColor;
    },
);

function modalClose() {
    form.reset();
    form.clearErrors();
    setLogoFile(null);
    emit("close");
}

function submit() {
    form.post(route("client.home.store"), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => modalClose(),
    });
}
</script>

<template>
    <Modal :show="show" @close="!form.processing && modalClose()" :maxWidth="'7xl'" :dark="false">
        <ModalHeader
            :dark="false"
            icon="fa-solid fa-tshirt"
            icon-class="text-cobalt bg-cobalt/10 border-cobalt/20"
            title="Customize This Design"
            @close="!form.processing && modalClose()"
        />
        <div v-if="template" class="grid grid-cols-1 lg:grid-cols-12 lg:divide-x divide-ink/10">
            <!-- LEFT: visual showcase -->
            <section class="lg:col-span-5 p-5 sm:p-6 bg-ink/[0.02] flex flex-col gap-4">
                <div class="relative w-full aspect-square rounded-2xl bg-white border border-ink/10 shadow-sm overflow-hidden flex items-center justify-center p-5 jersey-mesh">
                    <span
                        v-if="template.badge"
                        class="absolute top-3 left-3 z-10 px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider shadow-sm"
                        :class="{
                            'bg-cobalt text-white': template.badge === 'New',
                            'bg-amber-500 text-white': template.badge === 'Bestseller',
                            'bg-gradient-to-r from-accent to-orange-500 text-white': template.badge === 'Hot',
                        }"
                    >
                        {{ template.badge }}
                    </span>
                    <img :src="template.imagePath" :alt="template.name" class="w-full h-full object-contain drop-shadow-lg" />
                </div>

                <!-- Real color chips for the picked template -->
                <div class="flex items-center justify-center gap-2">
                    <span class="w-5 h-5 rounded-full ring-1 ring-ink/10 shadow-xs" :style="{ backgroundColor: template.primaryColor }"></span>
                    <span class="w-5 h-5 rounded-full ring-1 ring-ink/10 shadow-xs" :style="{ backgroundColor: template.secondaryColor }"></span>
                    <span class="w-5 h-5 rounded-full ring-1 ring-ink/10 shadow-xs" :style="{ backgroundColor: template.accentColor }"></span>
                    <span class="text-xs text-ink/40 ml-1">Template default palette</span>
                </div>

                <!-- Real, non-fabricated status pipeline preview -->
                <div class="rounded-xl p-3.5 bg-cobalt/5 border border-cobalt/10">
                    <p class="text-xs font-bold text-ink flex items-center gap-1.5 mb-2">
                        <font-awesome-icon icon="fa-solid fa-circle-info" class="text-cobalt" />
                        What happens after you submit
                    </p>
                    <ol class="space-y-1.5 text-[11px] text-ink/60 leading-relaxed">
                        <li class="flex gap-2"><span class="font-bold text-cobalt shrink-0">1.</span> Our team reviews your request</li>
                        <li class="flex gap-2"><span class="font-bold text-cobalt shrink-0">2.</span> We discuss and refine details with you via chat</li>
                        <li class="flex gap-2"><span class="font-bold text-cobalt shrink-0">3.</span> Pay the down payment via GCash to start production</li>
                    </ol>
                </div>
            </section>

            <!-- RIGHT: sectioned request form -->
            <div class="lg:col-span-7 p-5 sm:p-6 space-y-5">
                <!-- 1. Team Identity -->
                <fieldset class="space-y-3">
                    <legend class="text-xs font-black uppercase tracking-wider text-ink flex items-center gap-2">
                        <span class="w-5 h-5 rounded-full bg-cobalt text-white flex items-center justify-center text-[10px] font-black">1</span>
                        Team Identity
                    </legend>
                    <div>
                        <label for="team_name" class="block text-xs font-bold text-ink/70 mb-1.5">Team Name <span class="text-accent">*</span></label>
                        <input
                            id="team_name"
                            v-model="form.team_name"
                            type="text"
                            required
                            placeholder="e.g. Iloilo Thunderbolts"
                            class="w-full rounded-xl border border-ink/15 px-3.5 py-2.5 text-sm font-semibold text-ink focus:border-cobalt focus:ring-2 focus:ring-cobalt/20 outline-none transition-all"
                        />
                        <InputError :message="form.errors.team_name" class="mt-1.5" />
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-ink/70 mb-1.5">Name/Number Font Style</label>
                        <div class="grid grid-cols-4 gap-2 mb-2">
                            <button
                                v-for="preset in FONT_PRESETS"
                                :key="preset.label"
                                type="button"
                                @click="form.font_style = preset.label"
                                class="p-2 rounded-xl border-2 text-center transition-all"
                                :class="form.font_style === preset.label ? 'border-cobalt bg-cobalt/5' : 'border-ink/10 hover:border-ink/20'"
                            >
                                <div class="text-ink text-xs" :class="preset.sampleClass">08</div>
                                <div class="text-[9px] text-ink/50 font-semibold mt-0.5">{{ preset.label }}</div>
                            </button>
                        </div>
                        <input
                            id="font_style"
                            v-model="form.font_style"
                            type="text"
                            placeholder="e.g. Block, Script, Italic — or type your own"
                            class="w-full rounded-xl border border-ink/15 px-3.5 py-2 text-sm text-ink focus:border-cobalt focus:ring-2 focus:ring-cobalt/20 outline-none transition-all"
                        />
                        <InputError :message="form.errors.font_style" class="mt-1.5" />
                    </div>
                </fieldset>

                <hr class="border-ink/10" />

                <!-- 2. Color Profile -->
                <fieldset class="space-y-3">
                    <div class="flex items-center justify-between">
                        <legend class="text-xs font-black uppercase tracking-wider text-ink flex items-center gap-2">
                            <span class="w-5 h-5 rounded-full bg-cobalt text-white flex items-center justify-center text-[10px] font-black">2</span>
                            Color Profile
                        </legend>
                        <button type="button" @click="resetColorsToTemplate" class="text-[11px] font-bold text-cobalt hover:text-cobalt-dark flex items-center gap-1">
                            <font-awesome-icon icon="fa-solid fa-rotate-left" class="text-[10px]" /> Reset to Default
                        </button>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
                        <div class="p-2.5 rounded-xl border border-ink/10 bg-white space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="text-[11px] font-bold text-ink/70">Primary</span>
                                <span class="text-[10px] font-mono text-ink/40 uppercase">{{ form.primary_color }}</span>
                            </div>
                            <input type="color" v-model="form.primary_color" class="w-full h-8 rounded-lg cursor-pointer border border-ink/15 p-0.5" />
                            <div class="flex gap-1">
                                <button
                                    v-for="c in COLOR_PRESETS"
                                    :key="c"
                                    type="button"
                                    @click="form.primary_color = c"
                                    class="w-4 h-4 rounded-full border border-ink/15 transition-all"
                                    :class="form.primary_color === c ? 'ring-2 ring-cobalt ring-offset-1' : ''"
                                    :style="{ backgroundColor: c }"
                                />
                            </div>
                        </div>
                        <div class="p-2.5 rounded-xl border border-ink/10 bg-white space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="text-[11px] font-bold text-ink/70">Secondary</span>
                                <span class="text-[10px] font-mono text-ink/40 uppercase">{{ form.secondary_color }}</span>
                            </div>
                            <input type="color" v-model="form.secondary_color" class="w-full h-8 rounded-lg cursor-pointer border border-ink/15 p-0.5" />
                            <div class="flex gap-1">
                                <button
                                    v-for="c in COLOR_PRESETS"
                                    :key="c"
                                    type="button"
                                    @click="form.secondary_color = c"
                                    class="w-4 h-4 rounded-full border border-ink/15 transition-all"
                                    :class="form.secondary_color === c ? 'ring-2 ring-cobalt ring-offset-1' : ''"
                                    :style="{ backgroundColor: c }"
                                />
                            </div>
                        </div>
                        <div class="p-2.5 rounded-xl border border-ink/10 bg-white space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="text-[11px] font-bold text-ink/70">Accent</span>
                                <span class="text-[10px] font-mono text-ink/40 uppercase">{{ form.accent_color }}</span>
                            </div>
                            <input type="color" v-model="form.accent_color" class="w-full h-8 rounded-lg cursor-pointer border border-ink/15 p-0.5" />
                            <div class="flex gap-1">
                                <button
                                    v-for="c in COLOR_PRESETS"
                                    :key="c"
                                    type="button"
                                    @click="form.accent_color = c"
                                    class="w-4 h-4 rounded-full border border-ink/15 transition-all"
                                    :class="form.accent_color === c ? 'ring-2 ring-cobalt ring-offset-1' : ''"
                                    :style="{ backgroundColor: c }"
                                />
                            </div>
                        </div>
                    </div>
                </fieldset>

                <hr class="border-ink/10" />

                <!-- 3. Team Logo -->
                <fieldset class="space-y-3">
                    <legend class="text-xs font-black uppercase tracking-wider text-ink flex items-center gap-2">
                        <span class="w-5 h-5 rounded-full bg-cobalt text-white flex items-center justify-center text-[10px] font-black">3</span>
                        Team Logo <span class="text-ink/40 font-semibold normal-case tracking-normal">(optional)</span>
                    </legend>
                    <div
                        v-if="!logoPreviewUrl"
                        class="relative border-2 border-dashed rounded-2xl p-5 text-center cursor-pointer transition-all"
                        :class="isDraggingLogo ? 'border-cobalt bg-cobalt/5' : 'border-cobalt/20 hover:border-cobalt/40 bg-cobalt/[0.02] hover:bg-cobalt/5'"
                        @dragover.prevent="isDraggingLogo = true"
                        @dragleave.prevent="isDraggingLogo = false"
                        @drop="handleLogoDrop"
                    >
                        <input
                            id="logo"
                            type="file"
                            accept="image/*"
                            @change="handleLogoUpload"
                            class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                        />
                        <div class="flex flex-col items-center gap-1.5 pointer-events-none">
                            <div class="w-10 h-10 rounded-full bg-cobalt/10 text-cobalt flex items-center justify-center">
                                <font-awesome-icon icon="fa-solid fa-cloud-arrow-up" class="text-lg" />
                            </div>
                            <p class="text-xs font-bold text-ink"><span class="text-cobalt underline">Click to upload</span> or drag and drop</p>
                            <p class="text-[11px] text-ink/40">PNG or JPG, up to 4MB</p>
                        </div>
                    </div>
                    <div v-else class="flex items-center gap-3 p-3 rounded-xl border border-ink/10 bg-white">
                        <img :src="logoPreviewUrl" alt="Logo preview" class="w-14 h-14 rounded-lg object-contain border border-ink/10 bg-ink/[0.02]" />
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-bold text-ink truncate">{{ form.logo?.name }}</p>
                            <p class="text-[11px] text-ink/40">Ready to upload</p>
                        </div>
                        <button type="button" @click="removeLogo" class="w-7 h-7 rounded-full bg-accent/10 text-accent flex items-center justify-center hover:bg-accent/20 transition-colors shrink-0">
                            <font-awesome-icon icon="fa-solid fa-xmark" class="text-xs" />
                        </button>
                    </div>
                    <InputError :message="form.errors.logo" />
                </fieldset>

                <hr class="border-ink/10" />

                <!-- 4. Quantity & Notes -->
                <fieldset class="space-y-3">
                    <legend class="text-xs font-black uppercase tracking-wider text-ink flex items-center gap-2">
                        <span class="w-5 h-5 rounded-full bg-cobalt text-white flex items-center justify-center text-[10px] font-black">4</span>
                        Quantity &amp; Notes
                    </legend>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 items-stretch">
                        <div>
                            <label for="estimated_quantity" class="block text-xs font-bold text-ink/70 mb-1.5">Estimated Quantity</label>
                            <div class="flex items-center rounded-xl border border-ink/15 bg-white overflow-hidden">
                                <button type="button" @click="stepQuantity(-1)" class="w-10 h-10 flex items-center justify-center text-ink/50 hover:bg-ink/5 transition-colors shrink-0">
                                    <font-awesome-icon icon="fa-solid fa-minus" class="text-xs" />
                                </button>
                                <input
                                    id="estimated_quantity"
                                    v-model="form.estimated_quantity"
                                    type="number"
                                    min="1"
                                    placeholder="15"
                                    class="w-full text-center border-0 font-bold text-ink text-sm focus:ring-0"
                                />
                                <button type="button" @click="stepQuantity(1)" class="w-10 h-10 flex items-center justify-center text-ink/50 hover:bg-ink/5 transition-colors shrink-0">
                                    <font-awesome-icon icon="fa-solid fa-plus" class="text-xs" />
                                </button>
                            </div>
                            <InputError :message="form.errors.estimated_quantity" class="mt-1.5" />
                        </div>
                        <div v-if="estimatedSubtotal !== null" class="rounded-xl bg-ink text-white p-3 flex flex-col justify-center">
                            <span class="text-[10px] text-white/50 font-semibold uppercase tracking-wide">Estimated Subtotal</span>
                            <span class="text-lg font-black">{{ formatPrice(estimatedSubtotal) }}</span>
                            <span class="text-[10px] text-white/40">Final total confirmed by our team</span>
                        </div>
                    </div>
                    <div>
                        <label for="notes" class="block text-xs font-bold text-ink/70 mb-1.5">Design Notes</label>
                        <textarea
                            id="notes"
                            v-model="form.notes"
                            rows="3"
                            placeholder="e.g. Keep sleeves solid black, sponsor logo on lower back"
                            class="w-full rounded-xl border border-ink/15 px-3.5 py-2.5 text-sm text-ink focus:border-cobalt focus:ring-2 focus:ring-cobalt/20 outline-none transition-all resize-none"
                        ></textarea>
                        <InputError :message="form.errors.notes" class="mt-1.5" />
                    </div>
                </fieldset>

                <hr class="border-ink/10" />

                <div class="flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <Link
                        :href="route('client.chat.index')"
                        class="inline-flex items-center justify-center gap-1.5 text-xs font-bold text-cobalt hover:text-cobalt-dark"
                    >
                        <font-awesome-icon icon="fa-solid fa-message" />
                        Need help? Message our team
                    </Link>
                    <div class="flex flex-col-reverse gap-3 sm:flex-row">
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
                                <font-awesome-icon icon="fa-solid fa-spinner" spin />
                            </div>
                            Submit Design Request
                            <font-awesome-icon icon="fa-solid fa-paper-plane" />
                        </PrimaryButton>
                    </div>
                </div>
            </div>
        </div>
    </Modal>
</template>

<style scoped>
.jersey-mesh {
    background-image: radial-gradient(rgba(11, 18, 32, 0.08) 0.8px, transparent 0.8px);
    background-size: 14px 14px;
}
</style>
