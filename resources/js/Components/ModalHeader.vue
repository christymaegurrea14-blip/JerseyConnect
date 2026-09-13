<script setup>
defineProps({
    icon: { type: String, default: "fa-solid fa-circle-info" },
    iconClass: { type: String, default: "text-indigo-400 bg-indigo-500/15 border-indigo-500/25" },
    title: { type: String, required: true },
    subtitle: { type: String, default: "" },
    badge: { type: String, default: "" },
    badgeClass: { type: String, default: "bg-emerald-500/10 text-emerald-400 border-emerald-500/20" },
    // Admin pages use the dark "surface" theme; client-portal pages pass
    // :dark="false" to get matching light text/border colors.
    dark: { type: Boolean, default: true },
});

defineEmits(["close"]);
</script>

<template>
    <div
        class="flex items-start justify-between gap-4 border-b px-5 py-4"
        :class="dark ? 'border-white/10' : 'border-ink/10'"
    >
        <div class="flex items-center gap-3 min-w-0">
            <div
                class="w-9 h-9 rounded-lg border flex items-center justify-center shrink-0"
                :class="iconClass"
            >
                <font-awesome-icon :icon="icon" />
            </div>
            <div class="min-w-0">
                <div class="flex items-center gap-2 flex-wrap">
                    <h2 class="text-base sm:text-lg font-bold truncate" :class="dark ? 'text-white' : 'text-ink'">{{ title }}</h2>
                    <span
                        v-if="badge"
                        class="px-2 py-0.5 rounded-full text-[10px] font-semibold border shrink-0"
                        :class="badgeClass"
                    >
                        {{ badge }}
                    </span>
                </div>
                <p v-if="subtitle" class="text-xs mt-0.5 truncate" :class="dark ? 'text-slate-400' : 'text-ink/50'">{{ subtitle }}</p>
            </div>
        </div>
        <button
            type="button"
            class="p-1.5 rounded-lg transition-colors shrink-0"
            :class="dark ? 'text-slate-500 hover:text-slate-200 hover:bg-white/5' : 'text-ink/40 hover:text-ink hover:bg-ink/5'"
            @click="$emit('close')"
        >
            <font-awesome-icon icon="fa-solid fa-xmark" />
        </button>
    </div>
</template>
