<template>
    <div
        class="flex min-h-screen flex-col items-center bg-[url('/images/login-bg.png')] bg-cover bg-center py-10 sm:justify-center"
    >
        <div
            class="w-full px-4 sm:max-w-xl"
        >
            <div class="w-full overflow-hidden rounded-2xl border border-white/10 bg-[#0d1424]/95 shadow-2xl shadow-black/60 backdrop-blur-md">
                <slot />
            </div>
        </div>
    </div>
</template>
