<script setup lang="ts">
import { Link } from "@inertiajs/vue3";

defineProps<{
    items: { label: string; href?: string }[];
}>();
</script>

<template>
    <nav class="flex items-center gap-1.5 text-xs font-semibold text-ink/50 mb-4 flex-wrap">
        <template v-for="(item, i) in items" :key="i">
            <Link v-if="item.href" :href="item.href" class="hover:text-cobalt transition-colors">
                {{ item.label }}
            </Link>
            <span v-else class="text-ink">{{ item.label }}</span>
            <font-awesome-icon v-if="i < items.length - 1" icon="fa-solid fa-chevron-right" class="text-[9px] text-ink/30" />
        </template>
    </nav>
</template>
