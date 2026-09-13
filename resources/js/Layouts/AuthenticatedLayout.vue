<script setup lang="ts">
import { ref, computed } from "vue";
import { Link, router, usePage, usePoll } from "@inertiajs/vue3";

const page = usePage();

const unreadMessagesCount = computed(
    () => (page.props.unreadMessagesCount as number) ?? 0,
);

usePoll(3000, { only: ["unreadMessagesCount"] });

const user = computed(() => page.props.auth?.user as any);
const userName = computed(() => {
    const info = user.value?.user_info;
    const name = [info?.first_name, info?.last_name].filter(Boolean).join(" ");
    return name || user.value?.email || "Client";
});
const userInitials = computed(() =>
    userName.value
        .split(" ")
        .filter(Boolean)
        .slice(0, 2)
        .map((part: string) => part[0]?.toUpperCase())
        .join("") || "C",
);

const navLinks = [
    { menuName: "Catalogue", route: route("client.home.index"), icon: "fa-solid fa-shirt" },
    { menuName: "My Orders", route: route("client.orders.index"), icon: "fa-solid fa-shopping-basket" },
    { menuName: "Team Rosters", route: route("client.rosters.index"), icon: "fa-solid fa-users" },
    { menuName: "Artist Chat", route: route("client.chat.index"), icon: "fa-solid fa-message", hasBadge: true },
];

// Design-request detail/payment pages live under /client/design/{id} but
// belong to "My Orders" in the nav; the roster sub-page belongs to "Team
// Rosters" instead — both need a bit more than a plain prefix match.
const isActive = (href: string | URL) => {
    const path = new URL(href).pathname;
    if (path === route("client.orders.index")) {
        return page.url.startsWith("/client/orders") || (page.url.startsWith("/client/design") && !page.url.includes("/roster"));
    }
    if (path === route("client.rosters.index")) {
        return page.url.startsWith("/client/rosters") || page.url.includes("/roster");
    }
    return page.url.startsWith(path);
};

const mobileMenuOpen = ref(false);

// ── Logout confirmation ─────────────────────────────────────────────────────
const showLogoutConfirm = ref(false);
const loggingOut = ref(false);

function confirmLogout() {
    loggingOut.value = true;
    router.post(
        route("logout"),
        {},
        {
            onFinish: () => {
                loggingOut.value = false;
                showLogoutConfirm.value = false;
            },
        },
    );
}
</script>

<template>
    <div class="min-h-screen bg-[#f7f9fe] font-sans antialiased">
        <!-- Top nav -->
        <header class="sticky top-0 z-50 glass-nav border-b border-ink/10">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex h-16 items-center justify-between gap-4">
                    <!-- Brand -->
                    <Link :href="route('client.home.index')" class="flex items-center gap-2.5 shrink-0 group">
                        <div class="py-1 px-1 rounded bg-white shadow-sm">
                            <img class="h-7 w-auto" src="/images/printcode.png" alt="PrintCode" />
                        </div>
                        <div class="hidden sm:flex flex-col leading-none">
                            <span class="flex items-center gap-1.5">
                                <span class="text-base font-black tracking-tight text-ink">PRINT<span class="text-cobalt">CODE</span></span>
                                <span class="px-1.5 py-0.5 rounded text-[9px] font-extrabold uppercase bg-cobalt/10 text-cobalt tracking-wider">Studio</span>
                            </span>
                            <span class="text-[10px] text-ink/40 font-semibold uppercase tracking-widest mt-0.5">Client Portal</span>
                        </div>
                    </Link>

                    <!-- Nav links -->
                    <nav class="hidden md:flex items-center gap-1 text-sm">
                        <Link
                            v-for="link in navLinks"
                            :key="link.menuName"
                            :href="link.route"
                            class="relative px-3 py-1.5 rounded-lg font-bold transition-all"
                            :class="isActive(link.route) ? 'bg-cobalt/10 text-cobalt' : 'text-ink/60 hover:bg-ink/5 hover:text-ink'"
                        >
                            {{ link.menuName }}
                            <span
                                v-if="link.hasBadge && unreadMessagesCount > 0"
                                class="absolute -top-0.5 -right-0.5 w-2 h-2 rounded-full bg-accent"
                            />
                        </Link>
                    </nav>

                    <!-- Right actions -->
                    <div class="flex items-center gap-2 sm:gap-3">
                        <a
                            :href="route('landing-page')"
                            class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-white border border-ink/10 hover:border-ink/20 text-xs font-semibold text-ink/70 hover:text-ink transition-all shadow-xs"
                        >
                            <font-awesome-icon icon="fa-solid fa-newspaper" class="text-[11px]" />
                            Website
                        </a>
                        <Link
                            :href="route('client.profile.index')"
                            class="flex items-center gap-2 pl-1 group"
                            title="Profile"
                        >
                            <img
                                v-if="user?.user_info?.avatar_url"
                                :src="user.user_info.avatar_url"
                                alt="Profile picture"
                                class="w-8 h-8 rounded-full object-cover ring-2 ring-white shadow-sm group-hover:ring-cobalt/30 transition-all"
                            />
                            <div
                                v-else
                                class="w-8 h-8 rounded-full bg-gradient-to-tr from-cobalt to-accent flex items-center justify-center font-black text-[11px] text-white ring-2 ring-white shadow-sm group-hover:ring-cobalt/30 transition-all"
                            >
                                {{ userInitials }}
                            </div>
                        </Link>
                        <button
                            type="button"
                            class="p-2 rounded-full bg-white border border-ink/10 hover:border-accent/40 hover:bg-accent/5 text-ink/50 hover:text-accent transition-all"
                            title="Sign out"
                            @click="showLogoutConfirm = true"
                        >
                            <font-awesome-icon icon="fa-solid fa-right-from-bracket" class="text-sm" />
                        </button>
                        <button
                            type="button"
                            class="md:hidden p-2 rounded-full text-ink/60 hover:bg-ink/5 transition-colors"
                            @click="mobileMenuOpen = !mobileMenuOpen"
                        >
                            <font-awesome-icon :icon="mobileMenuOpen ? 'fa-solid fa-xmark' : 'fa-solid fa-bars'" />
                        </button>
                    </div>
                </div>

                <!-- Mobile nav -->
                <nav v-if="mobileMenuOpen" class="md:hidden pb-4 flex flex-col gap-1">
                    <Link
                        v-for="link in navLinks"
                        :key="link.menuName"
                        :href="link.route"
                        class="relative flex items-center gap-3 px-3 py-2.5 rounded-lg font-bold text-sm transition-all"
                        :class="isActive(link.route) ? 'bg-cobalt/10 text-cobalt' : 'text-ink/70 hover:bg-ink/5'"
                        @click="mobileMenuOpen = false"
                    >
                        <font-awesome-icon :icon="link.icon" class="w-4" />
                        {{ link.menuName }}
                        <span v-if="link.hasBadge && unreadMessagesCount > 0" class="w-2 h-2 rounded-full bg-accent" />
                    </Link>
                </nav>
            </div>
        </header>

        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-6 pb-16">
            <slot />
        </main>

        <!-- Logout confirm -->
        <div
            v-if="showLogoutConfirm"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-ink/40 backdrop-blur-sm"
            @click.self="!loggingOut && (showLogoutConfirm = false)"
        >
            <div class="bg-white rounded-2xl shadow-2xl max-w-sm w-full p-5">
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-10 h-10 rounded-xl bg-accent/10 text-accent flex items-center justify-center shrink-0">
                        <font-awesome-icon icon="fa-solid fa-right-from-bracket" />
                    </div>
                    <h3 class="text-base font-bold text-ink">Sign out?</h3>
                </div>
                <p class="text-sm text-ink/60 mb-5">You'll need to sign in again to access your account.</p>
                <div class="flex justify-between gap-2">
                    <button
                        type="button"
                        class="px-4 py-2 rounded-xl border border-ink/10 text-sm font-bold text-ink/70 hover:bg-ink/5 transition-colors disabled:opacity-50"
                        :disabled="loggingOut"
                        @click="showLogoutConfirm = false"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        class="px-4 py-2 rounded-xl bg-accent text-white text-sm font-bold hover:bg-accent-dark transition-colors disabled:opacity-50 flex items-center gap-1.5"
                        :disabled="loggingOut"
                        @click="confirmLogout"
                    >
                        <font-awesome-icon v-if="loggingOut" icon="fa-solid fa-spinner" spin />
                        Sign Out
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.glass-nav {
    background: rgba(255, 255, 255, 0.88);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
}
</style>
