<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from "vue";

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    maxWidth: {
        type: String,
        default: "2xl",
    },
    closeable: {
        type: Boolean,
        default: true,
    },
    // Admin pages use the dark "surface" theme; client-portal pages are
    // light, so they pass :dark="false" to get a matching light box.
    dark: {
        type: Boolean,
        default: true,
    },
});

const emit = defineEmits(["close"]);
const dialog = ref();
const showSlot = ref(props.show);

watch(
    () => props.show,
    () => {
        if (props.show) {
            document.body.style.overflow = "hidden";
            showSlot.value = true;

            dialog.value?.showModal();
        } else {
            document.body.style.overflow = "";

            setTimeout(() => {
                dialog.value?.close();
                showSlot.value = false;
            }, 200);
        }
    },
);

const close = () => {
    if (props.closeable) {
        emit("close");
    }
};

const closeOnEscape = (e) => {
    if (e.key === "Escape") {
        e.preventDefault();

        if (props.show) {
            close();
        }
    }
};

onMounted(() => document.addEventListener("keydown", closeOnEscape));

onUnmounted(() => {
    document.removeEventListener("keydown", closeOnEscape);

    document.body.style.overflow = "";
});

const maxWidthClass = computed(() => {
    return {
        sm: "sm:max-w-sm",
        md: "sm:max-w-md",
        lg: "sm:max-w-lg",
        xl: "sm:max-w-xl",
        "2xl": "sm:max-w-2xl",
        "5xl": "sm:max-w-5xl",
        "7xl": "sm:max-w-7xl",
    }[props.maxWidth];
});
</script>

<template>
    <dialog
        class="z-50 m-0 min-h-full min-w-full overflow-y-auto bg-transparent backdrop:bg-transparent"
        ref="dialog"
    >
        <div
            class="fixed inset-0 z-50 flex min-h-full items-center justify-center overflow-y-auto px-4 py-6 sm:px-0"
            scroll-region
        >
            <Transition
                enter-active-class="ease-out duration-300"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="ease-in duration-200"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div
                    v-show="show"
                    class="fixed inset-0 transform transition-all"
                    @click="close"
                >
                    <div class="absolute inset-0 bg-slate-950/80 backdrop-blur-sm" />
                </div>
            </Transition>

            <Transition
                enter-active-class="ease-out duration-300"
                enter-from-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                enter-to-class="opacity-100 translate-y-0 sm:scale-100"
                leave-active-class="ease-in duration-200"
                leave-from-class="opacity-100 translate-y-0 sm:scale-100"
                leave-to-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            >
                <div
                    v-show="show"
                    class="relative mb-6 max-h-[85vh] w-full transform overflow-y-auto rounded-2xl shadow-2xl transition-all sm:mx-auto"
                    :class="[
                        maxWidthClass,
                        dark
                            ? 'admin-scrollbar border border-white/10 bg-surface-card shadow-black/60'
                            : 'client-scrollbar border border-ink/10 bg-white shadow-ink/10',
                    ]"
                >
                    <div
                        class="absolute inset-x-0 top-0 h-[3px] rounded-t-2xl bg-gradient-to-r"
                        :class="dark ? 'from-indigo-500 via-violet-500 to-cyan-400' : 'from-cobalt via-cobalt to-accent'"
                    />
                    <slot v-if="showSlot" />
                </div>
            </Transition>
        </div>
    </dialog>
</template>
