<script setup lang="ts">
import type { JerseyTemplate } from "@/types/jersey";

const props = defineProps<{ template: JerseyTemplate }>();

const emit = defineEmits<{ (e: "select", id: number): void }>();

const badgeStyles: Record<string, string> = {
    New: "bg-cobalt text-white",
    Bestseller: "bg-amber-500 text-white",
    Hot: "bg-gradient-to-r from-accent to-orange-500 text-white",
};

function formatPrice(value: number) {
    return `₱${value.toLocaleString("en-PH")}`;
}
</script>

<template>
    <article
        class="group flex flex-col rounded-2xl bg-white border border-ink/10 hover:border-cobalt/40 shadow-sm hover:shadow-xl transition-all duration-300 overflow-hidden"
    >
        <!-- Thumbnail -->
        <div class="relative w-full aspect-[4/5] bg-gradient-to-b from-ink/[0.03] to-ink/[0.06] overflow-hidden p-3">
            <img
                class="w-full h-full object-contain transition-transform duration-500 group-hover:scale-105"
                :src="template.imagePath"
                :alt="template.name"
            />
            <span
                v-if="template.badge"
                :class="badgeStyles[template.badge]"
                class="absolute top-3 left-3 z-10 px-2.5 py-0.5 rounded text-[10px] font-black uppercase tracking-wider shadow-sm"
            >
                {{ template.badge }}
            </span>
            <span class="absolute top-3 right-3 z-10 px-2 py-0.5 rounded bg-white/90 backdrop-blur-sm text-ink text-[10px] font-bold shadow-xs">
                {{ template.sport }}
            </span>
        </div>

        <!-- Body -->
        <div class="p-4 flex-1 flex flex-col justify-between gap-3">
            <div class="space-y-1">
                <div class="flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-wide text-cobalt/70">
                    <font-awesome-icon icon="fa-solid fa-bolt" class="text-[9px]" />
                    <span>Full-Dye Sublimation</span>
                </div>
                <h3 class="text-base font-black text-ink group-hover:text-cobalt transition-colors truncate">
                    {{ template.name }}
                </h3>
                <p v-if="template.description" class="text-xs text-ink/50 leading-snug line-clamp-2">
                    {{ template.description }}
                </p>
            </div>

            <div class="flex items-center justify-between pt-2 border-t border-ink/10">
                <div class="flex items-center gap-1.5">
                    <span class="w-3.5 h-3.5 rounded-full ring-1 ring-white shadow-xs cursor-help" :title="template.primaryColor" :style="{ backgroundColor: template.primaryColor }"></span>
                    <span class="w-3.5 h-3.5 rounded-full ring-1 ring-white shadow-xs cursor-help" :title="template.secondaryColor" :style="{ backgroundColor: template.secondaryColor }"></span>
                    <span class="w-3.5 h-3.5 rounded-full ring-1 ring-ink/10 shadow-xs cursor-help" :title="template.accentColor" :style="{ backgroundColor: template.accentColor }"></span>
                </div>
                <span class="text-base font-black text-ink">{{ formatPrice(template.price) }}<span class="text-xs font-semibold text-ink/40"> /set</span></span>
            </div>

            <button
                type="button"
                class="flex items-center justify-center gap-1.5 w-full py-2.5 rounded-xl bg-cobalt hover:bg-cobalt-dark text-white text-xs font-bold transition-all shadow-xs"
                @click="emit('select', template.id)"
            >
                <font-awesome-icon icon="fa-solid fa-edit" class="text-[13px]" />
                Customize Template
            </button>
        </div>
    </article>
</template>
