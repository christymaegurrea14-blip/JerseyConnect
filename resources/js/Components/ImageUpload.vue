<script setup lang="ts">
import { computed, ref, watch } from "vue";

interface ImageStyle {
    backgroundImage: string;
}

const props = withDefaults(
    defineProps<{
        image?: ImageStyle;
    }>(),
    {
        image: () => ({ backgroundImage: "" }),
    },
);

const emit = defineEmits<{
    imageFile: [file: File];
}>();

const isInvalidImage = ref(false);
const previewImage = ref<ImageStyle>(props.image);

const hasImage = computed(() => !!previewImage.value.backgroundImage);

watch(
    () => props.image,
    (newImage) => {
        previewImage.value = newImage;
        isInvalidImage.value = false;
    },
);

const uploadImage = (e: Event): void => {
    const target = e.target as HTMLInputElement;
    const image = target.files?.[0];

    isInvalidImage.value = false;

    if (!image) return;

    const allowed = ["image/png", "image/jpeg"];

    if (!allowed.includes(image.type)) {
        isInvalidImage.value = true;
        return;
    }

    previewImage.value = {
        backgroundImage: `url(${URL.createObjectURL(image)})`,
    };

    emit("imageFile", image);
};
</script>

<template>
    <div class="flex flex-col gap-1.5">
        <label
            for="image"
            class="group relative flex h-[250px] md:h-[320px] cursor-pointer items-center justify-center overflow-hidden rounded-xl border border-slate-300 bg-white bg-contain bg-center bg-no-repeat shadow-inner"
            :style="previewImage"
        >
            <div
                class="pointer-events-none absolute inset-0 bg-[linear-gradient(to_right,#0000000d_1px,transparent_1px),linear-gradient(to_bottom,#0000000d_1px,transparent_1px)] bg-[size:16px_16px]"
            ></div>

            <div
                v-if="!hasImage"
                class="relative z-10 flex flex-col items-center gap-2 text-slate-400 group-hover:text-slate-600 transition-colors"
            >
                <font-awesome-icon icon="fa-solid fa-image" class="text-3xl" />
                <span class="text-xs font-medium">Upload an image</span>
            </div>

            <div
                v-else
                class="absolute inset-0 z-10 flex flex-col items-center justify-center gap-2 bg-white/80 opacity-0 backdrop-blur-[2px] transition-opacity group-hover:opacity-100"
            >
                <span
                    class="flex items-center gap-2 rounded-lg border border-indigo-400/40 bg-indigo-600 px-3.5 py-2 text-xs font-semibold text-white shadow-lg shadow-indigo-500/30"
                >
                    <font-awesome-icon icon="fa-solid fa-cloud-arrow-up" />
                    Replace image
                </span>
            </div>

            <input
                id="image"
                class="hidden"
                type="file"
                name="image"
                accept="image/*"
                @change="uploadImage"
            />
        </label>
        <span class="text-xs text-rose-400 italic" v-if="isInvalidImage">Please upload a valid image!</span>
    </div>
</template>
