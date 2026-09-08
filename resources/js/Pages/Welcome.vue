<script setup lang="ts">
import { Head, Link } from "@inertiajs/vue3";
import { ref, watch } from "vue";

defineProps<{
    canLogin?: boolean;
    canRegister?: boolean;
    laravelVersion: string;
    phpVersion: string;
}>();

const mobileMenuOpen = ref(false);

function closeMobileMenu() {
    mobileMenuOpen.value = false;
}

function toggleMobileMenu() {
    mobileMenuOpen.value = !mobileMenuOpen.value;
}

// Lock body scroll while the mobile menu is open, and allow Escape to close it.
watch(mobileMenuOpen, (isOpen) => {
    if (typeof document === "undefined") return;
    document.body.style.overflow = isOpen ? "hidden" : "";
});

function onKeydown(e: KeyboardEvent) {
    if (e.key === "Escape") closeMobileMenu();
}
</script>

<template>
    <Head title="Welcome">
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" />
        <link
            href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Inter:wght@400;500;600;700;800&family=IBM+Plex+Mono:wght@400;500;600&display=swap"
            rel="stylesheet"
        />
    </Head>

    <div class="bg-paper text-ink antialiased font-sans" @keydown="onKeydown">
        <!-- NAV -->
        <header
            class="sticky top-0 z-50 bg-paper/90 backdrop-blur border-b border-line"
        >
            <div
                class="mx-auto px-6 lg:px-8 h-16 flex items-center justify-between"
            >
                <Link
                    href="/"
                    class="flex items-center gap-2.5"
                    @click="closeMobileMenu"
                >
                    <div>
                        <img
                            class="h-8 w-auto"
                            src="/images/printcode.png"
                            alt="logo"
                        />
                    </div>
                    <span class="font-semibold text-[15px] tracking-tight"
                        >PrintCode</span
                    >
                </Link>

                <!-- Desktop nav -->
                <nav
                    class="hidden md:flex items-center gap-8 text-sm font-medium text-ink/70"
                >
                    <a href="#features" class="hover:text-ink transition-colors"
                        >Platform</a
                    >
                    <a href="#workflow" class="hover:text-ink transition-colors"
                        >How it works</a
                    >
                    <a href="#audience" class="hover:text-ink transition-colors"
                        >Who it's for</a
                    >
                </nav>

                <!-- Desktop auth actions -->
                <div v-if="canLogin" class="hidden md:flex items-center gap-2">
                    <Link
                        v-if="$page.props.auth.user"
                        :href="route('client.home.index')"
                        class="text-sm font-semibold bg-ink text-paper px-4 py-2 rounded-md hover:bg-ink/90 transition-colors"
                    >
                        Back
                        <font-awesome-icon icon="fa-solid fa-angles-right" />
                    </Link>
                    <template v-else>
                        <Link
                            :href="route('login')"
                            class="text-sm font-medium bg-ink text-paper px-4 py-2 rounded-md hover:bg-ink/90 hover:text-warn transition-colors"
                        >
                            Log in
                        </Link>
                        <Link
                            v-if="canRegister"
                            :href="route('register')"
                            class="text-sm font-semibold bg-warn text-ink px-4 py-2 rounded-md hover:bg-ink/90 hover:text-warn transition-colors"
                        >
                            <font-awesome-icon icon="fa-solid fa-user-plus" />
                            Create account
                        </Link>
                    </template>
                </div>

                <!-- Mobile burger button -->
                <button
                    type="button"
                    class="md:hidden relative w-10 h-10 flex items-center justify-center rounded-md text-ink hover:bg-ink/5 transition-colors"
                    :aria-expanded="mobileMenuOpen"
                    aria-controls="mobile-menu"
                    aria-label="Toggle navigation menu"
                    @click="toggleMobileMenu"
                >
                    <span class="sr-only">Toggle navigation menu</span>
                    <svg
                        class="w-6 h-6"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                    >
                        <path
                            :class="[
                                'origin-center transition-all duration-200',
                                mobileMenuOpen
                                    ? 'opacity-0 scale-75'
                                    : 'opacity-100 scale-100',
                            ]"
                            d="M4 7h16M4 12h16M4 17h16"
                        />
                        <path
                            :class="[
                                'origin-center transition-all duration-200 absolute',
                                mobileMenuOpen
                                    ? 'opacity-100 scale-100'
                                    : 'opacity-0 scale-75',
                            ]"
                            d="M6 6l12 12M18 6L6 18"
                        />
                    </svg>
                </button>
            </div>

            <!-- Mobile menu panel -->
            <Transition
                enter-active-class="transition ease-out duration-200"
                enter-from-class="opacity-0 -translate-y-2"
                enter-to-class="opacity-100 translate-y-0"
                leave-active-class="transition ease-in duration-150"
                leave-from-class="opacity-100 translate-y-0"
                leave-to-class="opacity-0 -translate-y-2"
            >
                <nav
                    v-if="mobileMenuOpen"
                    id="mobile-menu"
                    class="md:hidden border-t border-line bg-paper px-6 py-6 flex flex-col gap-1"
                >
                    <a
                        href="#features"
                        class="px-2 py-3 text-base font-medium text-ink/80 hover:text-ink hover:bg-ink/5 rounded-md transition-colors"
                        @click="closeMobileMenu"
                        >Platform</a
                    >
                    <a
                        href="#workflow"
                        class="px-2 py-3 text-base font-medium text-ink/80 hover:text-ink hover:bg-ink/5 rounded-md transition-colors"
                        @click="closeMobileMenu"
                        >How it works</a
                    >
                    <a
                        href="#audience"
                        class="px-2 py-3 text-base font-medium text-ink/80 hover:text-ink hover:bg-ink/5 rounded-md transition-colors"
                        @click="closeMobileMenu"
                        >Who it's for</a
                    >

                    <div
                        v-if="canLogin"
                        class="mt-3 pt-4 border-t border-line flex flex-col gap-3"
                    >
                        <Link
                            v-if="$page.props.auth.user"
                            :href="route('client.home.index')"
                            class="text-center text-sm font-semibold bg-ink text-paper px-4 py-3 rounded-md hover:bg-ink/90 transition-colors"
                            @click="closeMobileMenu"
                        >
                            Back
                            <font-awesome-icon
                                icon="fa-solid fa-angles-right"
                            />
                        </Link>
                        <template v-else>
                            <Link
                                :href="route('login')"
                                class="text-center text-sm font-medium bg-warn text-ink hover:text-ink px-4 py-3 rounded-md hover:bg-ink/5 transition-colors"
                                @click="closeMobileMenu"
                            >
                                Log in
                            </Link>
                            <Link
                                v-if="canRegister"
                                :href="route('register')"
                                class="text-center text-sm font-semibold bg-ink text-paper px-4 py-3 rounded-md hover:bg-ink/90 transition-colors"
                                @click="closeMobileMenu"
                            >
                                Register
                            </Link>
                        </template>
                    </div>
                </nav>
            </Transition>
        </header>

        <!-- HERO -->
        <section id="top" class="relative overflow-hidden border-b border-line">
            <div
                class="max-w-7xl mx-auto px-6 lg:px-8 pt-16 pb-20 lg:pt-24 lg:pb-28 grid lg:grid-cols-2 gap-14 items-center"
            >
                <div>
                    <h1
                        class="text-6xl sm:text-7xl leading-[0.95] mt-5 tracking-tight font-display"
                    >
                        PICK IT.<br />
                        DESCRIBE IT.<br />
                        <span class="text-accent">TRACK IT HOME.</span>
                    </h1>
                    <p
                        class="mt-6 text-lg text-ink/70 max-w-md leading-relaxed"
                    >
                        You can log in, tap a jersey, and type what to change.
                        From there PrintCode carries the order through quoting,
                        payment, chat, and delivery no spreadsheets, no separate
                        courier app.
                    </p>
                    <div class="mt-8 flex flex-wrap items-center gap-4">
                        <a
                            :href="route('login')"
                            class="inline-flex items-center justify-center bg-accent text-white font-semibold px-6 py-3.5 rounded-md hover:bg-accent-dark transition-colors"
                        >
                            Order a jersey
                        </a>
                        <a
                            href="#workflow"
                            class="inline-flex items-center gap-2 font-medium text-ink/80 hover:text-ink transition-colors"
                        >
                            See how it works
                            <span aria-hidden="true">→</span>
                        </a>
                    </div>
                    <div
                        class="mt-10 flex items-center gap-6 text-sm text-ink/50"
                    >
                        <span>No app install required</span>
                        <span class="w-1 h-1 rounded-full bg-ink/20"></span>
                        <span>Runs in the browser</span>
                    </div>
                </div>

                <!-- Jersey builder mockup -->
                <div class="relative">
                    <div
                        class="relative bg-panel border border-line rounded-2xl shadow-[0_20px_60px_-15px_rgba(11,18,32,0.18)] p-6 sm:p-8"
                    >
                        <div class="flex items-center justify-between mb-6">
                            <p
                                class="text-xs font-semibold uppercase tracking-wide text-ink/40"
                            >
                                Home — tap a jersey to start
                            </p>
                        </div>

                        <p class="text-xs font-medium text-ink/50 mb-2">
                            Catalog
                        </p>
                        <div class="grid grid-cols-4 gap-3 mb-6">
                            <div
                                class="aspect-[4/5] rounded-lg ring-2 ring-offset-2 ring-ink/10 flex items-center justify-center bg-cover bg-center cursor-pointer"
                                style="
                                    background-image: url(/images/image1.png);
                                "
                            >
                                <span
                                    class="px-2 py-1 bg-panel text-ink font-semibold backdrop-blur rounded"
                                    >A</span
                                >
                            </div>

                            <div
                                class="aspect-[4/5] rounded-lg ring-2 ring-offset-2 ring-ink/10 flex items-center justify-center bg-cover bg-center cursor-pointer"
                                style="
                                    background-image: url(/images/image2.png);
                                "
                            >
                                <span
                                    class="px-2 py-1 bg-panel text-ink font-semibold backdrop-blur rounded"
                                    >B</span
                                >
                            </div>

                            <div
                                class="aspect-[4/5] rounded-lg ring-2 ring-offset-2 ring-ink/10 flex items-center justify-center bg-cover bg-center cursor-pointer"
                                style="
                                    background-image: url(/images/image3.png);
                                "
                            >
                                <span
                                    class="px-2 py-1 bg-panel text-ink font-semibold backdrop-blur rounded"
                                    >C</span
                                >
                            </div>

                            <div
                                class="aspect-[4/5] rounded-lg ring-2 ring-offset-2 ring-ink/10 flex items-center justify-center bg-cover bg-center cursor-pointer"
                                style="
                                    background-image: url(/images/image4.png);
                                "
                            >
                                <span
                                    class="px-2 py-1 bg-panel text-ink font-semibold backdrop-blur rounded"
                                    >D</span
                                >
                            </div>
                        </div>

                        <div class="rounded-xl bg-paper border border-line p-4">
                            <label
                                class="text-xs font-medium text-ink/50 mb-2 block"
                                >Jersey A selected — what would you like
                                changed?</label
                            >
                            <div
                                class="rounded-md bg-panel border border-line px-3 py-2.5 text-sm text-ink/70 leading-relaxed font-mono"
                            >
                                "Sky blue and white. Name MARTINEZ, number 12.
                                18 sets, mixed adult sizes."
                            </div>
                            <div class="mt-3 flex justify-end">
                                <span
                                    class="inline-flex items-center bg-ink text-white text-xs font-semibold px-4 py-2 rounded-md"
                                    >Submit for quote</span
                                >
                            </div>
                        </div>

                        <!-- Status pill row -->
                        <div
                            class="mt-6 pt-5 border-t border-line flex items-center justify-between"
                        >
                            <div class="flex items-center gap-2">
                                <span
                                    class="w-2 h-2 rounded-full bg-warn"
                                ></span>
                                <span class="text-xs text-ink/60 font-mono"
                                    >Delivered to supplier · 2:41 PM</span
                                >
                            </div>
                            <span class="text-xs font-semibold text-ink/50"
                                >Quote pending</span
                            >
                        </div>
                    </div>

                    <!-- floating chat card -->
                    <div
                        class="hidden sm:block absolute -bottom-8 -left-8 bg-panel border border-line rounded-xl shadow-lg p-4 w-56"
                    >
                        <div class="flex items-center gap-2 mb-2">
                            <span
                                class="w-6 h-6 rounded-full bg-ink text-paper text-[10px] font-bold flex items-center justify-center"
                                >CS</span
                            >
                            <p class="text-xs font-semibold">Coach Reyes</p>
                        </div>
                        <p class="text-xs text-ink/60 leading-snug">
                            "Can we push the crest 1cm higher on all 18
                            jerseys?"
                        </p>
                        <div
                            class="mt-2 flex items-center gap-1 text-[10px] text-ink/40"
                        >
                            <span>Delivered</span>
                            <span
                                class="w-1.5 h-1.5 rounded-full bg-warn"
                            ></span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- TRUST / STRIP -->
        <section class="border-b border-line bg-panel">
            <div
                class="mx-auto px-6 lg:px-8 py-6 flex flex-wrap items-center justify-center gap-x-10 gap-y-3 text-sm font-medium text-ink/50"
            >
                <span>Local sportswear suppliers</span>
                <span class="w-1 h-1 rounded-full bg-ink/20"></span>
                <span>School athletics programs</span>
                <span class="w-1 h-1 rounded-full bg-ink/20"></span>
                <span>Corporate team-building orders</span>
                <span class="w-1 h-1 rounded-full bg-ink/20"></span>
                <span>Amateur & club leagues</span>
            </div>
        </section>

        <!-- FEATURES -->
        <section id="features" class="py-24 px-6 lg:px-8 mx-auto">
            <div class="max-w-2xl mb-14">
                <span
                    class="text-xs font-semibold tracking-wide uppercase text-accent"
                    >The platform</span
                >
                <h2 class="text-5xl mt-3 tracking-tight font-display">
                    EVERYTHING ON ONE ROSTER
                </h2>
                <p class="mt-4 text-ink/70 text-lg leading-relaxed">
                    Six connected modules cover the full order lifecycle, so
                    nothing gets handled over a separate spreadsheet, group
                    chat, or courier form.
                </p>
            </div>

            <div class="grid sm:grid-cols-2 gap-6">
                <div
                    class="border border-line bg-panel rounded-xl p-6 hover:shadow-md transition-shadow"
                >
                    <span class="text-3xl text-accent font-display">01</span>
                    <h3 class="font-semibold text-lg mt-3">
                        Design customization
                    </h3>
                    <p class="text-sm text-ink/60 mt-2 leading-relaxed">
                        On the home page, you tap a jersey from the catalog to
                        select it, then type the changes you want in a single
                        field colors, name, number, sizing. The supplier reviews
                        the request and replies with a quote before anything is
                        produced.
                    </p>
                </div>
                <div
                    class="border border-line bg-panel rounded-xl p-6 hover:shadow-md transition-shadow"
                >
                    <span class="text-3xl text-cobalt font-display">02</span>
                    <h3 class="font-semibold text-lg mt-3">
                        Orders & payments
                    </h3>
                    <p class="text-sm text-ink/60 mt-2 leading-relaxed">
                        Once a quote is accepted, you confirm sizes and
                        quantities per player and pay securely with a clear
                        record for every transaction.
                    </p>
                </div>
                <div
                    class="border border-line bg-panel rounded-xl p-6 hover:shadow-md transition-shadow"
                >
                    <span class="text-3xl text-good font-display">03</span>
                    <h3 class="font-semibold text-lg mt-3">Live chat</h3>
                    <p class="text-sm text-ink/60 mt-2 leading-relaxed">
                        You can message the supplier directly about mockups,
                        sizing, or changes no more losing conversations across
                        email and social media.
                    </p>
                </div>
                <div
                    class="border border-line bg-panel rounded-xl p-6 hover:shadow-md transition-shadow"
                >
                    <span class="text-3xl text-accent font-display">04</span>
                    <h3 class="font-semibold text-lg mt-3">
                        Logistics & delivery
                    </h3>
                    <p class="text-sm text-ink/60 mt-2 leading-relaxed">
                        Manage saved addresses, ship beyond the local area, and
                        calculate shipping fees automatically. Once a courier
                        picks up an order, the supplier logs the receipt number
                        and you gets a direct link to that courier's own
                        tracking page no separate courier application required.
                    </p>
                </div>
            </div>
        </section>

        <!-- WORKFLOW -->
        <section id="workflow" class="py-24 px-6 lg:px-8 bg-ink text-paper">
            <div class="mx-auto">
                <div class="max-w-2xl mb-16">
                    <span
                        class="text-xs font-semibold tracking-wide uppercase text-warn"
                        >The order lifecycle</span
                    >
                    <h2 class="text-5xl mt-3 tracking-tight font-display">
                        FROM SKETCH TO SIDELINE
                    </h2>
                    <p class="mt-4 text-paper/60 text-lg leading-relaxed">
                        Every order moves through the same five stages visible
                        to you and the supplier at every step.
                    </p>
                </div>

                <div class="grid md:grid-cols-5 gap-8 md:gap-4">
                    <div class="md:border-t-2 border-accent pt-5">
                        <p class="text-xs text-paper/40 font-mono">STAGE 1</p>
                        <h3 class="font-semibold mt-2">Pick & describe</h3>
                        <p class="text-sm text-paper/60 mt-2 leading-relaxed">
                            Tap a jersey on the home page and type the
                            customization you want.
                        </p>
                    </div>
                    <div class="md:border-t-2 border-accent/70 pt-5">
                        <p class="text-xs text-paper/40 font-mono">STAGE 2</p>
                        <h3 class="font-semibold mt-2">Quote & pay</h3>
                        <p class="text-sm text-paper/60 mt-2 leading-relaxed">
                            Supplier confirms the quote, customer sets
                            quantities and sizes, then pays securely.
                        </p>
                    </div>
                    <div class="md:border-t-2 border-accent/50 pt-5">
                        <p class="text-xs text-paper/40 font-mono">STAGE 3</p>
                        <h3 class="font-semibold mt-2">Confirm details</h3>
                        <p class="text-sm text-paper/60 mt-2 leading-relaxed">
                            Chat directly with the supplier to lock in final
                            adjustments.
                        </p>
                    </div>
                    <div class="md:border-t-2 border-accent/30 pt-5">
                        <p class="text-xs text-paper/40 font-mono">STAGE 4</p>
                        <h3 class="font-semibold mt-2">Production</h3>
                        <p class="text-sm text-paper/60 mt-2 leading-relaxed">
                            Status updates move from sent to delivered as work
                            progresses.
                        </p>
                    </div>
                    <div class="md:border-t-2 border-accent/10 pt-5">
                        <p class="text-xs text-paper/40 font-mono">STAGE 5</p>
                        <h3 class="font-semibold mt-2">Ship & track</h3>
                        <p class="text-sm text-paper/60 mt-2 leading-relaxed">
                            Fees calculate automatically; the courier receipt
                            number links straight to tracking.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- NOTIFICATIONS + LOGISTICS SHOWCASE -->
        <section class="bg-panel border-y border-line">
            <div
                class="py-24 px-6 lg:px-8 mx-auto grid lg:grid-cols-2 gap-16 items-center"
            >
                <div>
                    <span
                        class="text-xs font-semibold tracking-wide uppercase text-cobalt"
                        >Messaging & notifications</span
                    >
                    <h2
                        class="text-4xl sm:text-5xl mt-3 tracking-tight font-display"
                    >
                        NO MESSAGE LEFT ON READ — LITERALLY
                    </h2>
                    <p class="mt-4 text-ink/70 leading-relaxed">
                        Chat messages and order updates carry the same
                        three-state status customers already understand: sent,
                        delivered, and seen. Suppliers know exactly when a
                        customer has viewed a design proof or shipping update.
                    </p>
                    <ul class="mt-6 space-y-3 text-sm text-ink/70">
                        <li class="flex items-center gap-3">
                            <span class="w-2 h-2 rounded-full bg-ink/30"></span>
                            Sent — left the supplier or customer's device
                        </li>
                        <li class="flex items-center gap-3">
                            <span class="w-2 h-2 rounded-full bg-warn"></span>
                            Delivered — reached the recipient's inbox
                        </li>
                        <li class="flex items-center gap-3">
                            <span class="w-2 h-2 rounded-full bg-good"></span>
                            Seen — opened and read
                        </li>
                    </ul>
                </div>

                <div
                    class="bg-panel border border-line rounded-2xl shadow-sm p-6"
                >
                    <div class="space-y-3">
                        <div class="flex justify-end">
                            <div
                                class="bg-cobalt text-white text-sm rounded-xl rounded-br-sm px-4 py-2.5 max-w-[75%]"
                            >
                                Proof for the away kit is ready — please review.
                            </div>
                        </div>
                        <div class="flex justify-end">
                            <span
                                class="text-[11px] text-ink/40 flex items-center gap-1 font-mono"
                            >
                                Seen · 9:12 AM
                                <span
                                    class="w-1.5 h-1.5 rounded-full bg-good"
                                ></span>
                            </span>
                        </div>
                        <div class="flex justify-start">
                            <div
                                class="bg-paper border border-line text-sm rounded-xl rounded-bl-sm px-4 py-2.5 max-w-[75%]"
                            >
                                Looks great. Please proceed to production.
                            </div>
                        </div>
                        <div class="flex justify-start">
                            <span
                                class="text-[11px] text-ink/40 flex items-center gap-1 font-mono"
                            >
                                Delivered · 9:15 AM
                                <span
                                    class="w-1.5 h-1.5 rounded-full bg-warn"
                                ></span>
                            </span>
                        </div>
                    </div>

                    <div class="mt-6 pt-6 border-t border-line">
                        <p
                            class="text-xs font-semibold uppercase tracking-wide text-ink/40 mb-3"
                        >
                            Courier receipt
                        </p>
                        <div
                            class="rounded-lg bg-paper border border-line px-4 py-3 flex items-center justify-between gap-3"
                        >
                            <div>
                                <p class="text-xs text-ink/50">
                                    J&amp;T Express · Cebu → Davao
                                </p>
                                <p class="text-sm text-ink/70 mt-0.5 font-mono">
                                    TRK-88213-PH
                                </p>
                            </div>
                            <span
                                class="inline-flex items-center gap-1 text-xs font-semibold text-cobalt shrink-0"
                            >
                                Track shipment
                                <span aria-hidden="true">↗</span>
                            </span>
                        </div>
                        <p class="text-xs text-ink/40 mt-2">
                            Delivery is tracked directly on the courier's own
                            tracking page — the supplier only stores the receipt
                            number and pastes in the tracking link.
                        </p>
                        <div
                            class="mt-4 flex items-center justify-between text-sm"
                        >
                            <span class="text-ink/60"
                                >Auto-calculated shipping fee</span
                            >
                            <span class="font-semibold">₱220.00</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- AUDIENCE -->
        <section id="audience" class="py-24 px-6 lg:px-8 mx-auto">
            <div class="text-center mb-14">
                <span
                    class="text-xs font-semibold tracking-wide uppercase text-cobalt"
                    >Who it's for</span
                >
                <h2 class="text-5xl mt-3 tracking-tight font-display">
                    BUILT FOR BOTH SIDES OF THE ORDER
                </h2>
            </div>
            <div class="grid md:grid-cols-3 gap-6">
                <div class="rounded-xl border border-line bg-panel p-7">
                    <h3 class="font-semibold text-lg">Jersey suppliers</h3>
                    <p class="text-sm text-ink/60 mt-3 leading-relaxed">
                        Small to medium sportswear businesses managing custom
                        orders, inventory, and delivery without a dedicated ops
                        team.
                    </p>
                </div>
                <div class="rounded-xl border border-line bg-panel p-7">
                    <h3 class="font-semibold text-lg">Teams & schools</h3>
                    <p class="text-sm text-ink/60 mt-3 leading-relaxed">
                        Sports teams and school organizations ordering full
                        rosters at once, with names, numbers, and sizes per
                        player.
                    </p>
                </div>
                <div class="rounded-xl border border-line bg-panel p-7">
                    <h3 class="font-semibold text-lg">Corporate clients</h3>
                    <p class="text-sm text-ink/60 mt-3 leading-relaxed">
                        Companies ordering branded jerseys for events or
                        team-building, with a single point of contact
                        throughout.
                    </p>
                </div>
            </div>
        </section>

        <!-- CTA -->
        <section id="cta" class="py-24 px-6 lg:px-8 bg-ink text-paper">
            <div class="max-w-3xl mx-auto text-center">
                <h2 class="text-5xl sm:text-6xl tracking-tight font-display">
                    READY TO RUN YOUR ORDERS ON ONE PLATFORM?
                </h2>
                <p
                    class="mt-4 text-paper/60 text-lg leading-relaxed max-w-xl mx-auto"
                >
                    See how your request moves from a tap on a jersey to a
                    tracking link in your orders.
                </p>
                <div
                    v-if="$page.props.auth.user"
                    class="mt-8 flex flex-wrap items-center justify-center gap-4"
                >
                    <Link
                        :href="route('client.home.index')"
                        class="inline-flex items-center justify-center bg-accent text-white font-semibold px-6 py-3.5 rounded-md hover:bg-accent-dark transition-colors"
                    >
                        Back
                        <font-awesome-icon icon="fa-solid fa-angles-right" />
                    </Link>
                </div>
            </div>
        </section>

        <!-- FOOTER -->
        <footer class="border-t border-line px-6 lg:px-8 py-10">
            <div
                class="mx-auto flex flex-col sm:flex-row items-center justify-between gap-4 text-sm text-ink/50"
            >
                <div class="flex items-center gap-2">
                    <div>
                        <img
                            class="h-8 w-auto"
                            src="/images/printcode.png"
                            alt="logo"
                        />
                    </div>
                    <span class="font-medium text-ink/70">PrintCode</span>
                </div>
                <p>© 2026 PrintCode. All rights reserved.</p>
            </div>
        </footer>
    </div>
</template>
