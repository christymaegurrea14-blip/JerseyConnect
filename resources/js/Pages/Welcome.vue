<script setup lang="ts">
import { Head, Link, usePage } from "@inertiajs/vue3";
import { computed, onMounted, onUnmounted, ref, watch } from "vue";

interface JerseyCard {
    id: number;
    name: string;
    sport: string;
    price: number;
    badge: "New" | "Bestseller" | "Hot" | null;
    primaryColor: string;
    secondaryColor: string;
    accentColor: string;
    imagePath: string | null;
}

const props = defineProps<{
    canLogin?: boolean;
    canRegister?: boolean;
    jerseys?: JerseyCard[];
}>();

const page = usePage();
const authUser = computed(() => page.props.auth?.user ?? null);
const isAdmin = computed(() => (authUser.value as { role?: string } | null)?.role === "admin");

const jerseys = computed(() => props.jerseys ?? []);
const heroJerseys = computed(() => jerseys.value.slice(0, 4));

// Featured strip is a fixed 4-up showcase (badged templates first), never a
// filtered/variable count — keeps the grid full with no dangling gaps.
const featuredJerseys = computed(() => {
    const badged = jerseys.value.filter((j) => j.badge);
    const rest = jerseys.value.filter((j) => !j.badge);
    return [...badged, ...rest].slice(0, 4);
});

const remainingCatalogCount = computed(() => Math.max(jerseys.value.length - featuredJerseys.value.length, 0));

// Solid fills — this badge sits directly over the white jersey photo now,
// so the old light-text-on-dark-backdrop treatment lost all contrast.
const badgeClass: Record<string, string> = {
    New: "bg-cyan-600 text-white border-cyan-700",
    Hot: "bg-orange-600 text-white border-orange-700",
    Bestseller: "bg-amber-600 text-white border-amber-700",
};

function formatPrice(value: number) {
    return `₱${value.toLocaleString("en-PH")}`;
}

const mobileMenuOpen = ref(false);

function closeMobileMenu() {
    mobileMenuOpen.value = false;
}

function toggleMobileMenu() {
    mobileMenuOpen.value = !mobileMenuOpen.value;
}

watch(mobileMenuOpen, (isOpen) => {
    if (typeof document === "undefined") return;
    document.body.style.overflow = isOpen ? "hidden" : "";
});

function onKeydown(e: KeyboardEvent) {
    if (e.key === "Escape") closeMobileMenu();
}

// Header condenses slightly and gains a stronger shadow once the page scrolls.
const isScrolled = ref(false);

function onScroll() {
    isScrolled.value = window.scrollY > 24;
}

onMounted(() => {
    window.addEventListener("scroll", onScroll, { passive: true });
    onScroll();
});

onUnmounted(() => {
    window.removeEventListener("scroll", onScroll);
});
</script>

<template>
    <Head title="PrintCode — Custom Team Jerseys, Made Effortless">
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" />
        <link
            href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Inter:wght@400;500;600;700;800&family=IBM+Plex+Mono:wght@400;500;600&display=swap"
            rel="stylesheet"
        />
    </Head>

    <div
        class="bg-obsidian-950 text-slate-100 antialiased font-sans selection:bg-cyan-500 selection:text-black subtle-grid min-h-screen"
        @keydown="onKeydown"
    >
        <!-- NAV -->
        <header
            class="sticky top-0 z-50 glass-panel border-b transition-all duration-300"
            :class="isScrolled ? 'border-white/15 shadow-lg shadow-black/40' : 'border-white/10'"
        >
            <div
                class="max-w-7xl mx-auto px-6 lg:px-8 flex items-center justify-between transition-all duration-300"
                :class="isScrolled ? 'h-16' : 'h-20'"
            >
                <Link href="/" class="flex items-center gap-3 group" @click="closeMobileMenu">
                    <div class="relative flex items-center justify-center w-11 h-11 rounded-xl bg-gradient-to-tr from-electric-600 via-neonCyan to-amber-400 p-[2px] shadow-glowCyan group-hover:scale-105 group-hover:rotate-3 transition-transform duration-300">
                        <div class="w-full h-full bg-obsidian-900 rounded-[10px] flex items-center justify-center overflow-hidden">
                            <img src="/images/printcode.png" alt="PrintCode" class="w-6 h-6 object-contain" />
                        </div>
                    </div>
                    <div class="flex flex-col">
                        <span class="font-display text-2xl tracking-wider text-white leading-none">PRINT<span class="text-neonCyan">CODE</span></span>
                        <span class="text-[11px] text-slate-400 font-mono -mt-0.5 tracking-tight">jerseyconnect.shop</span>
                    </div>
                </Link>

                <nav class="hidden md:flex items-center gap-8 text-sm font-medium text-slate-300">
                    <a href="#catalog" class="nav-link hover:text-neonCyan transition-colors">Catalog</a>
                    <a href="#platform" class="nav-link hover:text-neonCyan transition-colors">The Platform</a>
                    <a href="#how-it-works" class="nav-link hover:text-neonCyan transition-colors">How It Works</a>
                    <a href="#audience" class="nav-link hover:text-neonCyan transition-colors">Who It's For</a>
                </nav>

                <div v-if="canLogin" class="hidden md:flex items-center gap-3">
                    <template v-if="authUser">
                        <Link
                            :href="isAdmin ? route('admin.dashboard') : route('client.home.index')"
                            class="inline-flex items-center gap-1.5 px-5 py-2.5 rounded-lg bg-gradient-to-r from-electric-600 to-neonCyan text-obsidian-950 font-display text-lg font-bold tracking-wider hover:opacity-95 hover:-translate-y-0.5 active:scale-95 shadow-glowCyan transition-all"
                        >
                            {{ isAdmin ? "Admin Dashboard" : "My Orders" }}
                        </Link>
                    </template>
                    <template v-else>
                        <Link
                            :href="route('login')"
                            class="inline-flex items-center px-4 py-2.5 rounded-lg border border-slate-700 bg-obsidian-850/80 text-xs font-semibold uppercase tracking-wider text-slate-200 hover:border-slate-500 hover:bg-obsidian-800 active:scale-95 transition-all"
                        >
                            Log in
                        </Link>
                        <Link
                            v-if="canRegister"
                            :href="route('register')"
                            class="inline-flex items-center px-5 py-2.5 rounded-lg bg-gradient-to-r from-electric-600 to-neonCyan text-obsidian-950 font-display text-lg font-bold tracking-wider hover:opacity-95 hover:-translate-y-0.5 active:scale-95 shadow-glowCyan transition-all"
                        >
                            Create Account
                        </Link>
                    </template>
                </div>

                <button
                    type="button"
                    class="md:hidden relative w-10 h-10 flex items-center justify-center rounded-md text-slate-200 hover:bg-white/5 transition-colors"
                    :aria-expanded="mobileMenuOpen"
                    aria-label="Toggle navigation menu"
                    @click="toggleMobileMenu"
                >
                    <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                        <path v-if="!mobileMenuOpen" d="M4 7h16M4 12h16M4 17h16" />
                        <path v-else d="M6 6l12 12M18 6L6 18" />
                    </svg>
                </button>
            </div>

            <Transition
                enter-active-class="transition ease-out duration-200"
                enter-from-class="opacity-0 -translate-y-2"
                enter-to-class="opacity-100 translate-y-0"
                leave-active-class="transition ease-in duration-150"
                leave-from-class="opacity-100 translate-y-0"
                leave-to-class="opacity-0 -translate-y-2"
            >
                <nav v-if="mobileMenuOpen" class="md:hidden border-t border-white/10 bg-obsidian-950 px-6 py-6 flex flex-col gap-1">
                    <a href="#catalog" class="px-2 py-3 text-base font-medium text-slate-200 hover:bg-white/5 rounded-md transition-colors" @click="closeMobileMenu">Catalog</a>
                    <a href="#platform" class="px-2 py-3 text-base font-medium text-slate-200 hover:bg-white/5 rounded-md transition-colors" @click="closeMobileMenu">The Platform</a>
                    <a href="#how-it-works" class="px-2 py-3 text-base font-medium text-slate-200 hover:bg-white/5 rounded-md transition-colors" @click="closeMobileMenu">How It Works</a>
                    <a href="#audience" class="px-2 py-3 text-base font-medium text-slate-200 hover:bg-white/5 rounded-md transition-colors" @click="closeMobileMenu">Who It's For</a>

                    <div v-if="canLogin" class="mt-3 pt-4 border-t border-white/10 flex flex-col gap-3">
                        <template v-if="authUser">
                            <Link
                                :href="isAdmin ? route('admin.dashboard') : route('client.home.index')"
                                class="text-center text-sm font-semibold bg-gradient-to-r from-electric-600 to-neonCyan text-obsidian-950 px-4 py-3 rounded-md transition-colors"
                                @click="closeMobileMenu"
                            >
                                {{ isAdmin ? "Admin Dashboard" : "My Orders" }}
                            </Link>
                        </template>
                        <template v-else>
                            <Link :href="route('login')" class="text-center text-sm font-medium border border-slate-700 text-slate-200 px-4 py-3 rounded-md hover:bg-white/5 transition-colors" @click="closeMobileMenu">
                                Log in
                            </Link>
                            <Link v-if="canRegister" :href="route('register')" class="text-center text-sm font-semibold bg-gradient-to-r from-electric-600 to-neonCyan text-obsidian-950 px-4 py-3 rounded-md transition-colors" @click="closeMobileMenu">
                                Create Account
                            </Link>
                        </template>
                    </div>
                </nav>
            </Transition>
        </header>

        <!-- HERO -->
        <section class="relative overflow-hidden pt-10 pb-24 lg:pt-16 lg:pb-32 hero-glow-radial">
            <div class="absolute top-1/4 left-1/2 -translate-x-1/2 w-[700px] h-[350px] bg-electric-600/15 blur-[120px] rounded-full pointer-events-none animate-blob-pulse"></div>
            <div class="max-w-7xl mx-auto px-6 lg:px-8 relative z-10">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-14 lg:gap-8 items-center">
                    <!-- Left -->
                    <div class="lg:col-span-6 space-y-6">
                        <div v-reveal class="inline-flex items-center space-x-2 px-3 py-1.5 rounded-full bg-obsidian-800/90 border border-electric-500/30 text-xs font-semibold uppercase tracking-wider text-slate-300">
                            <span class="w-2 h-2 rounded-full bg-neonCyan animate-pulse"></span>
                            <span>Custom Sublimation Studio</span>
                            <span class="text-slate-600">•</span>
                            <span class="text-neonCyan">Bohol, Philippines</span>
                        </div>

                        <h1 v-reveal="90" class="font-display text-6xl sm:text-7xl xl:text-8xl leading-[0.88] tracking-tight text-white">
                            PICK IT.<br />
                            DESCRIBE IT.<br />
                            <span class="text-gradient-fire">TRACK IT HOME.</span>
                        </h1>

                        <p v-reveal="180" class="text-base sm:text-lg text-slate-300 leading-relaxed max-w-xl">
                            Custom team jerseys made effortless. Pick a base template, describe your colors, roster, and logo in plain text, and track your sublimated uniforms from quote to doorstep. No spreadsheets, no chaotic group chats.
                        </p>

                        <div v-reveal="270" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-4 pt-2">
                            <Link
                                :href="authUser ? (isAdmin ? route('admin.dashboard') : route('client.home.index')) : route('register')"
                                class="inline-flex items-center justify-center px-8 py-4 rounded-xl bg-gradient-to-r from-orange-500 via-rose-500 to-orange-600 text-white font-display text-2xl font-bold tracking-wider hover:brightness-110 active:scale-95 shadow-lg shadow-orange-500/25 transition-all transform hover:-translate-y-0.5"
                            >
                                Order a jersey →
                            </Link>
                            <a href="#how-it-works" class="inline-flex items-center justify-center px-6 py-4 rounded-xl glass-panel text-slate-200 text-sm font-semibold hover:border-slate-400 active:scale-95 transition-all group">
                                <svg class="w-5 h-5 mr-2 text-neonCyan group-hover:scale-110 group-hover:translate-x-0.5 transition-transform" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" clip-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" />
                                </svg>
                                See how it works
                            </a>
                        </div>

                        <div v-reveal="340" class="pt-6 border-t border-white/10 text-xs text-slate-400 flex flex-wrap items-center gap-y-2 gap-x-4">
                            <div class="flex items-center space-x-1.5">
                                <svg class="w-4 h-4 text-neonCyan" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" clip-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" /></svg>
                                <span>No app install required</span>
                            </div>
                            <span class="text-slate-600">•</span>
                            <div class="flex items-center space-x-1.5">
                                <svg class="w-4 h-4 text-neonGreen" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" clip-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" /></svg>
                                <span>Runs in the browser</span>
                            </div>
                            <span class="text-slate-600">•</span>
                            <div class="flex items-center space-x-1.5">
                                <svg class="w-4 h-4 text-amber-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" clip-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" /></svg>
                                <span>Delivered via your courier of choice</span>
                            </div>
                        </div>
                    </div>

                    <!-- Right: real catalog preview card -->
                    <div v-reveal="220" class="lg:col-span-6 relative">
                        <div class="bg-white rounded-3xl p-6 sm:p-8 text-slate-900 shadow-2xl border border-slate-200 relative transition-transform duration-500 hover:-translate-y-1">
                            <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                                <div class="flex items-center space-x-2">
                                    <span class="h-2.5 w-2.5 rounded-full bg-emerald-500 inline-block"></span>
                                    <span class="text-xs font-bold tracking-widest text-slate-500 uppercase font-mono">Tap a jersey to start</span>
                                </div>
                                <span class="text-[11px] font-semibold bg-slate-100 text-slate-600 px-2 py-0.5 rounded">{{ jerseys.length }} in catalog</span>
                            </div>

                            <div class="mt-4">
                                <div v-if="heroJerseys.length > 0" class="grid grid-cols-4 gap-2.5 sm:gap-3">
                                    <a
                                        v-for="(jersey, i) in heroJerseys"
                                        :key="jersey.id"
                                        href="#catalog"
                                        class="group relative rounded-xl overflow-hidden border-2 border-slate-200 hover:border-cyan-400 hover:shadow-lg hover:shadow-cyan-500/20 transition-all duration-300 aspect-[3/4] flex flex-col bg-white"
                                    >
                                        <div class="relative flex-1 min-h-0 bg-white">
                                            <div
                                                class="absolute inset-1.5 bg-contain bg-center bg-no-repeat transition-transform duration-500 group-hover:scale-110"
                                                :style="{ backgroundImage: jersey.imagePath ? `url(${jersey.imagePath})` : undefined }"
                                            ></div>
                                        </div>
                                        <div class="relative z-10 w-full p-1.5 flex items-center justify-between bg-ink shrink-0">
                                            <span class="text-[9px] font-bold text-white truncate">{{ jersey.name }}</span>
                                            <span class="w-5 h-5 rounded bg-white text-slate-900 font-bold text-[10px] flex items-center justify-center shadow shrink-0">
                                                {{ String.fromCharCode(65 + i) }}
                                            </span>
                                        </div>
                                    </a>
                                </div>
                                <p v-else class="text-sm text-slate-500 py-8 text-center">Catalog coming soon.</p>
                            </div>

                            <div class="mt-5 bg-slate-50 border border-slate-200 rounded-2xl p-4">
                                <label class="text-xs font-semibold text-slate-700 block mb-2">Describe what you need — colors, names, numbers, sizes</label>
                                <div class="relative bg-white rounded-xl border border-slate-200 p-3 shadow-inner">
                                    <p class="font-mono text-xs sm:text-sm text-slate-800 leading-relaxed">
                                        "Sky blue and white. Name MARTINEZ, number 12. 18 sets, mixed adult sizes. Add our club crest on the left chest."
                                    </p>
                                </div>
                                <p class="mt-3 text-xs text-slate-500">
                                    Your request goes straight to our sublimation team for a design proof and quote — no separate form to fill out.
                                </p>
                            </div>
                        </div>

                        <!-- Floating chat preview — real messaging feature, generic content -->
                        <div v-reveal="480" class="hidden sm:block absolute -bottom-8 -left-6 max-w-[300px] glass-panel bg-obsidian-900/95 border border-white/20 p-3.5 rounded-2xl shadow-2xl text-white z-20 backdrop-blur-md animate-float">
                            <div class="flex items-start space-x-3">
                                <div class="w-8 h-8 rounded-full bg-gradient-to-tr from-cyan-500 to-blue-600 flex items-center justify-center font-black text-xs text-white shrink-0">
                                    PC
                                </div>
                                <div class="flex-1 min-w-0">
                                    <span class="text-xs font-bold text-white">Sublimation Team</span>
                                    <p class="text-xs text-slate-200 leading-snug mt-0.5">
                                        "Your design proof is ready — take a look and let us know if it's good to print."
                                    </p>
                                    <div class="mt-2 flex items-center gap-1 text-[10px] text-emerald-400 border-t border-white/10 pt-1.5">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                                        <span>Sent straight to your order chat</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- TRUST STRIP -->
        <section class="border-y border-white/10 bg-obsidian-900/60 py-6">
            <div class="max-w-7xl mx-auto px-6 lg:px-8">
                <div v-reveal class="flex flex-wrap items-center justify-center gap-x-8 gap-y-3 text-xs sm:text-sm text-slate-300 font-medium">
                    <span class="hover:text-neonCyan transition cursor-default">Local sportswear suppliers</span>
                    <span class="text-slate-600 hidden sm:inline">•</span>
                    <span class="hover:text-neonCyan transition cursor-default">School athletics programs</span>
                    <span class="text-slate-600 hidden sm:inline">•</span>
                    <span class="hover:text-neonCyan transition cursor-default">Corporate team-building orders</span>
                    <span class="text-slate-600 hidden sm:inline">•</span>
                    <span class="hover:text-neonCyan transition cursor-default">Amateur &amp; club leagues</span>
                </div>
            </div>
        </section>

        <!-- PLATFORM MODULES -->
        <section id="platform" class="py-20 lg:py-28 relative">
            <div class="max-w-7xl mx-auto px-6 lg:px-8">
                <div v-reveal class="max-w-3xl mb-16">
                    <div class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold tracking-widest uppercase bg-orange-500/10 text-orange-400 border border-orange-500/20 mb-3">
                        The Platform
                    </div>
                    <h2 class="font-display text-5xl sm:text-6xl lg:text-7xl tracking-tight text-white leading-none">EVERYTHING ON ONE ROSTER</h2>
                    <p class="mt-4 text-base sm:text-lg text-slate-300">
                        Five connected modules cover the full order lifecycle, so nothing gets handled over a separate spreadsheet, group chat, or courier form.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 lg:gap-8">
                    <div v-reveal="0" class="glass-panel rounded-2xl p-7 group hover:border-orange-500/40 hover:-translate-y-1 transition-all duration-300">
                        <div class="flex items-center justify-between mb-6">
                            <span class="font-display text-4xl font-black text-orange-500 leading-none">01</span>
                            <span class="px-2.5 py-1 rounded-md text-[10px] font-mono uppercase tracking-wider bg-orange-500/10 text-orange-300 border border-orange-500/20">Design Requests</span>
                        </div>
                        <h3 class="text-xl font-bold text-white mb-2.5 group-hover:text-orange-400 transition-colors">Design Customization</h3>
                        <p class="text-sm text-slate-300 leading-relaxed">
                            Tap a jersey from the catalog to select it, then describe the changes you want in one message — colors, name, number, sizing. Our team reviews and replies before anything is produced.
                        </p>
                    </div>

                    <div v-reveal="80" class="glass-panel rounded-2xl p-7 group hover:border-electric-500/40 hover:-translate-y-1 transition-all duration-300">
                        <div class="flex items-center justify-between mb-6">
                            <span class="font-display text-4xl font-black text-electric-400 leading-none">02</span>
                            <span class="px-2.5 py-1 rounded-md text-[10px] font-mono uppercase tracking-wider bg-electric-500/10 text-electric-300 border border-electric-500/20">GCash</span>
                        </div>
                        <h3 class="text-xl font-bold text-white mb-2.5 group-hover:text-electric-400 transition-colors">Orders &amp; Payments</h3>
                        <p class="text-sm text-slate-300 leading-relaxed">
                            Once your design is approved, lock it in with a GCash down payment. Upload your receipt and we verify it directly — a clear record for every transaction.
                        </p>
                    </div>

                    <div v-reveal="160" class="glass-panel rounded-2xl p-7 group hover:border-cyan-500/40 hover:-translate-y-1 transition-all duration-300">
                        <div class="flex items-center justify-between mb-6">
                            <span class="font-display text-4xl font-black text-neonCyan leading-none">03</span>
                            <span class="px-2.5 py-1 rounded-md text-[10px] font-mono uppercase tracking-wider bg-cyan-500/10 text-cyan-300 border border-cyan-500/20">Direct Chat</span>
                        </div>
                        <h3 class="text-xl font-bold text-white mb-2.5 group-hover:text-neonCyan transition-colors">Sublimation Direct Chat</h3>
                        <p class="text-sm text-slate-300 leading-relaxed">
                            Every design request and order gets its own chat thread with our team — discuss crest placement, sizing, or changes without losing the conversation across Messenger or email.
                        </p>
                    </div>

                    <div v-reveal="240" class="glass-panel rounded-2xl p-7 group hover:border-purple-500/40 hover:-translate-y-1 transition-all duration-300">
                        <div class="flex items-center justify-between mb-6">
                            <span class="font-display text-4xl font-black text-purple-400 leading-none">04</span>
                            <span class="px-2.5 py-1 rounded-md text-[10px] font-mono uppercase tracking-wider bg-purple-500/10 text-purple-300 border border-purple-500/20">Live Status</span>
                        </div>
                        <h3 class="text-xl font-bold text-white mb-2.5 group-hover:text-purple-400 transition-colors">Production Tracking</h3>
                        <p class="text-sm text-slate-300 leading-relaxed">
                            Follow your order through every real stage — Processing, In Production, Ready for Delivery, Shipped, Delivered — right from your account.
                        </p>
                    </div>

                    <div v-reveal="320" class="glass-panel rounded-2xl p-7 group hover:border-pink-500/40 hover:-translate-y-1 transition-all duration-300">
                        <div class="flex items-center justify-between mb-6">
                            <span class="font-display text-4xl font-black text-pink-400 leading-none">05</span>
                            <span class="px-2.5 py-1 rounded-md text-[10px] font-mono uppercase tracking-wider bg-pink-500/10 text-pink-300 border border-pink-500/20">Delivery</span>
                        </div>
                        <h3 class="text-xl font-bold text-white mb-2.5 group-hover:text-pink-400 transition-colors">Courier Tracking</h3>
                        <p class="text-sm text-slate-300 leading-relaxed">
                            Once your order ships, we log the courier and waybill number and hand you a direct link to that courier's own tracking page — no separate app required.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- CATALOG -->
        <section id="catalog" class="py-20 bg-obsidian-900/80 border-t border-white/10">
            <div class="max-w-7xl mx-auto px-6 lg:px-8">
                <div v-reveal class="flex flex-col md:flex-row md:items-end justify-between mb-12 gap-6">
                    <div>
                        <span class="text-neonCyan font-mono text-xs uppercase tracking-widest font-bold">Live Catalog</span>
                        <h2 class="font-display text-5xl sm:text-6xl text-white tracking-tight mt-1">FEATURED SUBLIMATION STYLES</h2>
                    </div>
                    <Link
                        v-if="remainingCatalogCount > 0"
                        :href="authUser ? route('client.home.index') : route('register')"
                        class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-lg bg-obsidian-800 text-slate-300 hover:text-white border border-white/10 hover:border-slate-500 active:scale-95 text-xs font-bold uppercase tracking-wider transition-all shrink-0"
                    >
                        View full catalog ({{ jerseys.length }}) →
                    </Link>
                </div>

                <div v-if="featuredJerseys.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div
                        v-for="(jersey, i) in featuredJerseys"
                        :key="jersey.id"
                        v-reveal="i * 90"
                        class="glass-panel glass-panel-hover rounded-2xl overflow-hidden flex flex-col group transition-all duration-300 hover:-translate-y-1.5"
                    >
                        <div class="h-64 relative flex flex-col items-center justify-center text-center overflow-hidden bg-white">
                            <div
                                class="absolute inset-3 bg-contain bg-center bg-no-repeat transition-transform duration-500 group-hover:scale-110"
                                :style="{ backgroundImage: jersey.imagePath ? `url(${jersey.imagePath})` : undefined }"
                            ></div>
                            <span
                                v-if="jersey.badge"
                                class="absolute top-3 left-3 z-10 px-2.5 py-1 rounded text-[10px] font-bold uppercase border shadow-sm"
                                :class="badgeClass[jersey.badge]"
                            >
                                {{ jersey.badge }}
                            </span>
                            <span class="absolute bottom-3 right-3 z-10 text-[11px] font-mono text-ink/70 bg-white/90 backdrop-blur-sm px-2 py-0.5 rounded shadow-sm">{{ jersey.sport }}</span>
                        </div>
                        <div class="p-5 flex-1 flex flex-col justify-between space-y-4">
                            <div>
                                <div class="flex items-center justify-between">
                                    <h3 class="font-bold text-white text-lg group-hover:text-neonCyan transition-colors truncate">{{ jersey.name }}</h3>
                                    <span class="font-display text-2xl font-bold text-white shrink-0">{{ formatPrice(jersey.price) }}<span class="text-xs text-slate-400 font-normal">/set</span></span>
                                </div>
                            </div>
                            <div class="flex items-center justify-between pt-2 border-t border-white/10 text-xs">
                                <div class="flex items-center space-x-1.5">
                                    <span class="w-3.5 h-3.5 rounded-full border border-white/20" :style="{ backgroundColor: jersey.primaryColor }"></span>
                                    <span class="w-3.5 h-3.5 rounded-full border border-white/20" :style="{ backgroundColor: jersey.secondaryColor }"></span>
                                    <span class="w-3.5 h-3.5 rounded-full border border-white/20" :style="{ backgroundColor: jersey.accentColor }"></span>
                                    <span class="text-[10px] text-slate-400 ml-1">Customizable</span>
                                </div>
                                <Link :href="authUser ? route('client.home.index') : route('register')" class="text-neonCyan font-semibold hover:underline flex items-center gap-1">
                                    Customize →
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
                <p v-else class="text-center text-slate-400 py-16">No templates published yet — check back soon.</p>
            </div>
        </section>

        <!-- HOW IT WORKS -->
        <section id="how-it-works" class="py-20 lg:py-28 relative">
            <div class="max-w-7xl mx-auto px-6 lg:px-8">
                <div v-reveal class="text-center max-w-2xl mx-auto mb-16">
                    <span class="text-xs font-bold uppercase tracking-widest text-electric-400 font-mono">The Order Lifecycle</span>
                    <h2 class="font-display text-5xl sm:text-6xl text-white mt-1">HOW PRINTCODE WORKS</h2>
                    <p class="text-sm sm:text-base text-slate-300 mt-3">From picking a template to a jersey at your door, in one connected flow.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <div v-reveal="0" class="glass-panel p-6 rounded-2xl flex flex-col hover:-translate-y-1 hover:border-white/20 transition-all duration-300">
                        <div class="w-10 h-10 rounded-xl bg-white/5 border border-white/10 flex items-center justify-center font-display text-2xl font-bold text-neonCyan mb-4">1</div>
                        <h3 class="font-bold text-white text-lg mb-2">Pick &amp; Describe</h3>
                        <p class="text-xs text-slate-400 leading-relaxed">Tap a template from the catalog and describe your colors, roster, and logo in one message.</p>
                    </div>
                    <div v-reveal="90" class="glass-panel p-6 rounded-2xl flex flex-col hover:-translate-y-1 hover:border-white/20 transition-all duration-300">
                        <div class="w-10 h-10 rounded-xl bg-white/5 border border-white/10 flex items-center justify-center font-display text-2xl font-bold text-orange-400 mb-4">2</div>
                        <h3 class="font-bold text-white text-lg mb-2">Review &amp; Chat</h3>
                        <p class="text-xs text-slate-400 leading-relaxed">Our team reviews your request and messages you directly to confirm the final design.</p>
                    </div>
                    <div v-reveal="180" class="glass-panel p-6 rounded-2xl flex flex-col hover:-translate-y-1 hover:border-white/20 transition-all duration-300">
                        <div class="w-10 h-10 rounded-xl bg-white/5 border border-white/10 flex items-center justify-center font-display text-2xl font-bold text-emerald-400 mb-4">3</div>
                        <h3 class="font-bold text-white text-lg mb-2">Lock In With GCash</h3>
                        <p class="text-xs text-slate-400 leading-relaxed">Approve the design and secure your order with a GCash down payment — verified before production starts.</p>
                    </div>
                    <div v-reveal="270" class="glass-panel p-6 rounded-2xl flex flex-col hover:-translate-y-1 hover:border-white/20 transition-all duration-300">
                        <div class="w-10 h-10 rounded-xl bg-white/5 border border-white/10 flex items-center justify-center font-display text-2xl font-bold text-purple-400 mb-4">4</div>
                        <h3 class="font-bold text-white text-lg mb-2">Track To Doorstep</h3>
                        <p class="text-xs text-slate-400 leading-relaxed">Watch your order move through production, then track the courier straight to delivery.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- MESSAGING & LOGISTICS -->
        <section class="bg-obsidian-900/80 border-y border-white/10">
            <div class="max-w-7xl mx-auto px-6 lg:px-8 py-20 grid lg:grid-cols-2 gap-16 items-center">
                <div v-reveal>
                    <span class="text-xs font-bold uppercase tracking-widest text-cyan-400 font-mono">Messaging &amp; Delivery</span>
                    <h2 class="font-display text-4xl sm:text-5xl text-white mt-1 leading-tight">NO MESSAGE LEFT ON READ</h2>
                    <p class="mt-4 text-slate-300 leading-relaxed">
                        Every design request and order gets a live chat thread with our team, plus a courier receipt you can track straight from your account — no separate app, no lost messages across social media.
                    </p>
                    <ul class="mt-6 space-y-3 text-sm text-slate-300">
                        <li class="flex items-center gap-3">
                            <span class="w-2 h-2 rounded-full bg-slate-500"></span>
                            Sent — your message reaches our team instantly
                        </li>
                        <li class="flex items-center gap-3">
                            <span class="w-2 h-2 rounded-full bg-neonGreen"></span>
                            Seen — you'll know the moment it's been read
                        </li>
                    </ul>
                </div>

                <div v-reveal="150" class="glass-panel rounded-2xl p-6 transition-transform duration-500 hover:-translate-y-1">
                    <div class="space-y-3">
                        <div class="flex justify-end">
                            <div class="bg-electric-600 text-white text-sm rounded-xl rounded-br-sm px-4 py-2.5 max-w-[75%]">
                                Proof for the away kit is ready — please review.
                            </div>
                        </div>
                        <div class="flex justify-end">
                            <span class="text-[11px] text-slate-400 flex items-center gap-1 font-mono">
                                Seen
                                <span class="w-1.5 h-1.5 rounded-full bg-neonGreen"></span>
                            </span>
                        </div>
                        <div class="flex justify-start">
                            <div class="bg-obsidian-800 border border-white/10 text-slate-200 text-sm rounded-xl rounded-bl-sm px-4 py-2.5 max-w-[75%]">
                                Looks great. Please proceed to production.
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 pt-6 border-t border-white/10">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400 mb-3">Courier receipt</p>
                        <div class="rounded-lg bg-obsidian-800 border border-white/10 px-4 py-3 flex items-center justify-between gap-3">
                            <div>
                                <p class="text-xs text-slate-400">J&amp;T Express</p>
                                <p class="text-sm text-slate-200 mt-0.5 font-mono">TRK-88213-PH</p>
                            </div>
                            <span class="inline-flex items-center gap-1 text-xs font-semibold text-neonCyan shrink-0">
                                Track shipment
                                <span aria-hidden="true">↗</span>
                            </span>
                        </div>
                        <p class="text-xs text-slate-500 mt-2">
                            Delivery is tracked directly on the courier's own page — we just log the receipt and hand you the link.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- AUDIENCE -->
        <section id="audience" class="py-20 lg:py-28">
            <div class="max-w-7xl mx-auto px-6 lg:px-8">
                <div v-reveal class="text-center mb-14">
                    <span class="text-xs font-bold uppercase tracking-widest text-cyan-400 font-mono">Who It's For</span>
                    <h2 class="font-display text-5xl text-white mt-1">BUILT FOR BOTH SIDES OF THE ORDER</h2>
                </div>
                <div class="grid md:grid-cols-3 gap-6">
                    <div v-reveal="0" class="glass-panel rounded-2xl p-7 hover:-translate-y-1 hover:border-white/20 transition-all duration-300">
                        <h3 class="font-bold text-white text-lg">Jersey Suppliers</h3>
                        <p class="text-sm text-slate-300 mt-3 leading-relaxed">Sportswear businesses managing custom orders, production, and delivery without a dedicated ops team.</p>
                    </div>
                    <div v-reveal="90" class="glass-panel rounded-2xl p-7 hover:-translate-y-1 hover:border-white/20 transition-all duration-300">
                        <h3 class="font-bold text-white text-lg">Teams &amp; Schools</h3>
                        <p class="text-sm text-slate-300 mt-3 leading-relaxed">Sports teams and school organizations ordering full rosters at once, with names, numbers, and sizes per player.</p>
                    </div>
                    <div v-reveal="180" class="glass-panel rounded-2xl p-7 hover:-translate-y-1 hover:border-white/20 transition-all duration-300">
                        <h3 class="font-bold text-white text-lg">Corporate Clients</h3>
                        <p class="text-sm text-slate-300 mt-3 leading-relaxed">Companies ordering branded jerseys for events or team-building, with a single point of contact throughout.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- FINAL CTA -->
        <section class="py-20 relative overflow-hidden">
            <div class="max-w-7xl mx-auto px-6 lg:px-8">
                <div v-reveal class="relative rounded-3xl p-8 sm:p-14 overflow-hidden border border-electric-500/30 bg-gradient-to-r from-obsidian-900 via-obsidian-850 to-blue-950 shadow-2xl">
                    <div class="absolute -right-16 -top-16 w-80 h-80 bg-neonCyan/20 rounded-full blur-3xl pointer-events-none animate-blob-pulse"></div>
                    <div class="absolute -left-16 -bottom-16 w-80 h-80 bg-electric-600/20 rounded-full blur-3xl pointer-events-none animate-blob-pulse" style="animation-delay: -3s"></div>
                    <div class="relative z-10 max-w-3xl space-y-6">
                        <h2 class="font-display text-5xl sm:text-6xl lg:text-7xl tracking-tight text-white leading-none">
                            READY TO SUIT UP YOUR TEAM?
                        </h2>
                        <p class="text-slate-300 text-sm sm:text-base leading-relaxed">
                            Skip the spreadsheet chaos. Pick a template, describe what you need, and let us take it from quote to doorstep.
                        </p>
                        <div class="pt-4 flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                            <Link
                                v-if="!authUser"
                                :href="route('register')"
                                class="inline-flex items-center justify-center px-8 py-4 rounded-xl bg-gradient-to-r from-neonCyan to-electric-500 text-obsidian-950 font-display text-2xl font-black tracking-wider hover:opacity-95 active:scale-95 shadow-glowCyan transition-all transform hover:-translate-y-0.5"
                            >
                                Start Your Jersey Order →
                            </Link>
                            <Link
                                v-else
                                :href="isAdmin ? route('admin.dashboard') : route('client.home.index')"
                                class="inline-flex items-center justify-center px-8 py-4 rounded-xl bg-gradient-to-r from-neonCyan to-electric-500 text-obsidian-950 font-display text-2xl font-black tracking-wider hover:opacity-95 active:scale-95 shadow-glowCyan transition-all transform hover:-translate-y-0.5"
                            >
                                {{ isAdmin ? "Go to Dashboard →" : "Go to My Orders →" }}
                            </Link>
                            <Link
                                v-if="!authUser"
                                :href="route('login')"
                                class="inline-flex items-center justify-center px-6 py-4 rounded-xl glass-panel text-white text-xs font-bold uppercase tracking-wider hover:bg-white/10 active:scale-95 transition-all"
                            >
                                Track Existing Order
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- FOOTER -->
        <footer class="bg-obsidian-950 border-t border-white/10 pt-16 pb-10 text-slate-400 text-sm">
            <div class="max-w-7xl mx-auto px-6 lg:px-8">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-10 pb-12 border-b border-white/10">
                    <div class="md:col-span-2 space-y-4">
                        <div class="flex items-center space-x-3">
                            <div class="w-9 h-9 rounded-lg bg-gradient-to-tr from-electric-600 via-neonCyan to-amber-400 flex items-center justify-center overflow-hidden">
                                <img src="/images/printcode.png" alt="PrintCode" class="w-5 h-5 object-contain" />
                            </div>
                            <span class="font-display text-2xl text-white tracking-wider">PRINT<span class="text-neonCyan">CODE</span></span>
                        </div>
                        <p class="text-xs text-slate-400 leading-relaxed max-w-sm">
                            PrintCode (jerseyconnect.shop) is a custom sublimated jersey ordering platform for teams, schools, and clubs — from design request to doorstep, in one place.
                        </p>
                    </div>
                    <div class="space-y-3">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-white font-mono">Platform</h4>
                        <ul class="space-y-2 text-xs">
                            <li><a class="hover:text-neonCyan transition" href="#catalog">Catalog</a></li>
                            <li><a class="hover:text-neonCyan transition" href="#platform">The Platform</a></li>
                            <li><a class="hover:text-neonCyan transition" href="#how-it-works">How It Works</a></li>
                        </ul>
                    </div>
                    <div class="space-y-3">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-white font-mono">Account</h4>
                        <ul class="space-y-2 text-xs">
                            <li v-if="canRegister"><Link class="hover:text-neonCyan transition" :href="route('register')">Create Account</Link></li>
                            <li><Link class="hover:text-neonCyan transition" :href="route('login')">Log In</Link></li>
                            <li>
                                <Link class="hover:text-amber-400 transition flex items-center gap-1" :href="route('login')">
                                    <span>Admin Portal</span>
                                    <span class="text-[9px] bg-slate-800 text-amber-300 px-1 py-0.5 rounded">Staff</span>
                                </Link>
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="mt-8 flex flex-col sm:flex-row items-center justify-between text-xs text-slate-500 gap-4">
                    <div>© {{ new Date().getFullYear() }} PrintCode. All rights reserved.</div>
                </div>
            </div>
        </footer>
    </div>
</template>

<style scoped>
.font-display {
    font-family: "Bebas Neue", sans-serif;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}
.text-gradient-fire {
    background: linear-gradient(135deg, #f97316 0%, #ff4d4f 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}
.glass-panel {
    background: rgba(14, 23, 42, 0.72);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    border: 1px solid rgba(255, 255, 255, 0.08);
}
.glass-panel-hover:hover {
    border-color: rgba(6, 182, 212, 0.4);
    box-shadow: 0 12px 35px -8px rgba(6, 182, 212, 0.18);
}
.subtle-grid {
    background-size: 40px 40px;
    background-image: linear-gradient(to right, rgba(255, 255, 255, 0.03) 1px, transparent 1px),
        linear-gradient(to bottom, rgba(255, 255, 255, 0.03) 1px, transparent 1px);
}
.hero-glow-radial {
    background: radial-gradient(circle at 60% 30%, rgba(37, 99, 235, 0.22) 0%, rgba(6, 182, 212, 0.12) 35%, transparent 70%);
}

/* Nav link underline sweep */
.nav-link {
    position: relative;
}
.nav-link::after {
    content: "";
    position: absolute;
    left: 0;
    bottom: -4px;
    width: 100%;
    height: 1.5px;
    background: currentColor;
    transform: scaleX(0);
    transform-origin: left;
    transition: transform 0.25s ease;
}
.nav-link:hover::after {
    transform: scaleX(1);
}

/* Gentle float loop for the hero chat card */
@keyframes float {
    0%, 100% {
        transform: translateY(0);
    }
    50% {
        transform: translateY(-8px);
    }
}
.animate-float {
    animation: float 4.5s ease-in-out infinite;
}

/* Slow ambient pulse for background glow blobs */
@keyframes blob-pulse {
    0%, 100% {
        opacity: 0.7;
        transform: scale(1);
    }
    50% {
        opacity: 1;
        transform: scale(1.12);
    }
}
.animate-blob-pulse {
    animation: blob-pulse 6s ease-in-out infinite;
}

@media (prefers-reduced-motion: reduce) {
    .animate-float,
    .animate-blob-pulse {
        animation: none;
    }
}
</style>
