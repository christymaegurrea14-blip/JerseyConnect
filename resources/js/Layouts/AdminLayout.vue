<script setup>
import { ref, computed } from "vue";
import { Link, usePage, usePoll, router } from "@inertiajs/vue3";
import Modal from "@/Components/Modal.vue";
import ModalHeader from "@/Components/ModalHeader.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";

const page = usePage();

const showLogoutConfirm = ref(false);
const loggingOut = ref(false);

function confirmLogout() {
    loggingOut.value = true;
    router.post(route("logout"), {}, {
        onFinish: () => {
            loggingOut.value = false;
            showLogoutConfirm.value = false;
        },
    });
}

const unreadMessagesCount = computed(() => page.props.unreadMessagesCount ?? 0);
const pendingDesignRequestsCount = computed(() => page.props.pendingDesignRequestsCount ?? 0);
const pendingGcashCount = computed(() => page.props.pendingGcashCount ?? 0);

usePoll(3000, { only: ["unreadMessagesCount", "pendingDesignRequestsCount", "pendingGcashCount"] });

const isShowSideBar = ref(true);
const isCollapsed = ref(false);

const user = computed(() => page.props.auth?.user);
const userName = computed(() => {
    const info = user.value?.user_info;
    const name = [info?.first_name, info?.last_name].filter(Boolean).join(" ");
    return name || user.value?.email || "Admin";
});
const userInitials = computed(() =>
    userName.value
        .split(" ")
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0]?.toUpperCase())
        .join("") || "A",
);

const sidebarMenus = [
    {
        menuName: "Dashboard",
        route: route("admin.dashboard"),
        icon: "fa-solid fa-tachograph-digital",
    },
    {
        menuName: "Orders",
        route: route("admin.orders.index"),
        icon: "fa-solid fa-box",
    },
    {
        menuName: "Messages",
        route: route("admin.messages.index"),
        icon: "fa-solid fa-envelope",
    },
    {
        menuName: "Jersey Templates",
        route: route("admin.jersey.index"),
        icon: "fa-solid fa-tshirt",
    },
    {
        menuName: "Designs",
        route: route("admin.design.index"),
        icon: "fa-solid fa-spray-can-sparkles",
        hasBadge: true,
        badgeColor: "fuchsia",
    },
    {
        menuName: "Couriers",
        route: route("admin.couriers.index"),
        icon: "fa-solid fa-truck-fast",
    },
    {
        menuName: "Gcash Details",
        route: route("admin.gcash.index"),
        icon: "fa-solid fa-wallet",
        hasBadge: true,
    },
    {
        menuName: "Users",
        route: route("admin.users.index"),
        icon: "fa-solid fa-users",
    },
];

const byName = (...names) =>
    names.map((n) => sidebarMenus.find((m) => m.menuName === n)).filter(Boolean);

const overviewMenus = byName("Dashboard", "Orders", "Messages");
const catalogMenus = byName("Jersey Templates", "Designs");
const adminMenus = byName("Couriers", "Gcash Details", "Users");

const isActive = (href) => page.url.startsWith(new URL(href).pathname);

const badgeClasses = (color) =>
    color === "fuchsia"
        ? "bg-fuchsia-500/20 text-fuchsia-300 border-fuchsia-500/30"
        : "bg-indigo-500/20 text-indigo-300 border-indigo-500/30";

const badgeDotClasses = (color) =>
    color === "fuchsia" ? "bg-fuchsia-400" : "bg-indigo-500";

function menuBadgeCount(menuName) {
    if (menuName === "Designs") return pendingDesignRequestsCount.value;
    if (menuName === "Gcash Details") return pendingGcashCount.value;
    return 0;
}
</script>

<template>
    <div class="admin-scrollbar flex h-screen bg-surface-base font-jakarta overflow-hidden">
        <!-- Mobile Overlay (only on small screens) -->
        <div
            v-if="isShowSideBar"
            @click="isShowSideBar = false"
            class="fixed inset-0 bg-black/50 z-20 lg:hidden"
        ></div>

        <!-- Sidebar -->
        <aside
            :class="[
                isShowSideBar ? 'translate-x-0' : '-translate-x-full lg:translate-x-0',
                isCollapsed ? 'lg:w-24' : 'lg:w-72',
                'fixed inset-y-0 left-0 z-30 w-72 bg-surface-sidebar/95 backdrop-blur-md border-r border-surface-border transition-all duration-300 ease-in-out',
                'lg:relative lg:inset-y-auto lg:left-auto lg:z-auto lg:flex-shrink-0',
            ]"
        >
            <div class="h-full flex flex-col justify-between overflow-y-auto">
                <div class="p-5 flex flex-col gap-6">
                    <!-- Brand -->
                    <div
                        class="flex items-center gap-3.5 px-2 py-1"
                        :class="isCollapsed && 'lg:justify-center lg:px-0'"
                    >
                        <Link :href="route('admin.dashboard')" class="relative shrink-0 group">
                            <div class="absolute -inset-1 bg-gradient-to-r from-blue-600 via-indigo-500 to-fuchsia-500 rounded-xl blur-sm opacity-50 group-hover:opacity-80 transition duration-300"></div>
                            <div class="relative w-10 h-10 rounded-xl bg-slate-900 border border-white/10 flex items-center justify-center overflow-hidden p-1 shadow-inner">
                                <img class="w-full h-full object-contain rounded-lg" src="/images/printcode.png" alt="PrintCode" />
                            </div>
                        </Link>
                        <div class="flex flex-col min-w-0" :class="isCollapsed && 'lg:hidden'">
                            <Link :href="route('admin.dashboard')" class="font-bold text-base tracking-tight text-white flex items-center gap-1.5 no-underline">
                                PrintCode
                                <span class="inline-block w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                            </Link>
                            <span class="text-xs text-slate-400 font-medium tracking-tight truncate">jerseyconnect.shop</span>
                        </div>
                        <button
                            @click="isShowSideBar = false"
                            class="lg:hidden ml-auto text-slate-400 hover:text-white"
                        >
                            <font-awesome-icon icon="fa-solid fa-xmark" />
                        </button>
                    </div>

                    <!-- Collapse toggle (desktop only) -->
                    <button
                        @click="isCollapsed = !isCollapsed"
                        class="hidden lg:flex items-center justify-center h-8 rounded-lg border border-surface-border text-slate-400 hover:text-slate-200 hover:border-surface-borderHover transition-colors"
                        :class="isCollapsed ? 'w-10 mx-auto' : 'w-full gap-2 text-xs font-medium'"
                    >
                        <font-awesome-icon icon="fa-solid fa-bars" class="text-[13px]" />
                        <span v-if="!isCollapsed">Collapse</span>
                    </button>

                    <!-- Navigation -->
                    <nav class="space-y-5">
                        <div class="space-y-1">
                            <div
                                class="px-3 pb-2 text-[10px] font-bold uppercase tracking-wider text-slate-500"
                                :class="isCollapsed && 'lg:hidden'"
                            >
                                Overview
                            </div>

                            <template v-for="menu in overviewMenus" :key="menu.menuName">
                                <Link
                                    :href="menu.route"
                                    :title="isCollapsed ? menu.menuName : null"
                                    :class="[
                                        'flex items-center rounded-xl text-sm font-medium transition-colors group relative',
                                        isCollapsed ? 'lg:justify-center lg:h-11 lg:w-11 lg:mx-auto' : 'justify-between px-3.5 py-2.5',
                                        isActive(menu.route)
                                            ? 'bg-gradient-to-r from-indigo-950/70 to-slate-900/60 border border-indigo-500/20 text-indigo-200 font-semibold shadow-sm'
                                            : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/40 border border-transparent',
                                    ]"
                                >
                                    <div class="flex items-center gap-3">
                                        <font-awesome-icon
                                            :icon="menu.icon"
                                            :class="isActive(menu.route) ? 'text-indigo-400' : 'text-slate-400 group-hover:text-slate-200'"
                                        />
                                        <span :class="isCollapsed && 'lg:hidden'">{{ menu.menuName }}</span>
                                    </div>
                                    <span
                                        v-if="menu.menuName === 'Dashboard' && isActive(menu.route)"
                                        class="w-1.5 h-1.5 rounded-full bg-indigo-400 shadow-[0_0_8px_#818cf8]"
                                        :class="isCollapsed && 'lg:absolute lg:top-1.5 lg:right-1.5'"
                                    ></span>
                                    <span
                                        v-if="menu.menuName === 'Messages' && unreadMessagesCount > 0"
                                        class="w-2 h-2 rounded-full bg-red-500"
                                        :class="isCollapsed ? 'lg:absolute lg:top-1 lg:right-1' : ''"
                                    ></span>
                                </Link>
                            </template>
                        </div>

                        <div class="space-y-1">
                            <div
                                class="px-3 pb-2 text-[10px] font-bold uppercase tracking-wider text-slate-500"
                                :class="isCollapsed && 'lg:hidden'"
                            >
                                Catalog
                            </div>

                            <template v-for="menu in catalogMenus" :key="'catalog-' + menu.menuName">
                                <Link
                                    :href="menu.route"
                                    :title="isCollapsed ? menu.menuName : null"
                                    :class="[
                                        'flex items-center rounded-xl text-sm font-medium transition-colors group relative',
                                        isCollapsed ? 'lg:justify-center lg:h-11 lg:w-11 lg:mx-auto' : 'justify-between px-3.5 py-2.5',
                                        isActive(menu.route)
                                            ? 'bg-gradient-to-r from-indigo-950/70 to-slate-900/60 border border-indigo-500/20 text-indigo-200 font-semibold shadow-sm'
                                            : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/40 border border-transparent',
                                    ]"
                                >
                                    <div class="flex items-center gap-3">
                                        <font-awesome-icon
                                            :icon="menu.icon"
                                            :class="isActive(menu.route) ? 'text-indigo-400' : 'text-slate-400 group-hover:text-slate-200'"
                                        />
                                        <span :class="isCollapsed && 'lg:hidden'">{{ menu.menuName }}</span>
                                    </div>
                                    <span
                                        v-if="menu.hasBadge && menuBadgeCount(menu.menuName) > 0"
                                        class="text-xs px-2 py-0.5 rounded-full font-semibold border"
                                        :class="[badgeClasses(menu.badgeColor), isCollapsed && 'lg:hidden']"
                                    >{{ menuBadgeCount(menu.menuName) > 9 ? '9+' : menuBadgeCount(menu.menuName) }}</span>
                                    <span
                                        v-if="menu.hasBadge && menuBadgeCount(menu.menuName) > 0 && isCollapsed"
                                        class="hidden lg:block lg:absolute lg:top-1 lg:right-1 w-2 h-2 rounded-full"
                                        :class="badgeDotClasses(menu.badgeColor)"
                                    ></span>
                                </Link>
                            </template>
                        </div>

                        <div class="space-y-1">
                            <div
                                class="px-3 pb-2 text-[10px] font-bold uppercase tracking-wider text-slate-500"
                                :class="isCollapsed && 'lg:hidden'"
                            >
                                Admin
                            </div>

                            <template v-for="menu in adminMenus" :key="'admin-' + menu.menuName">
                                <Link
                                    :href="menu.route"
                                    :title="isCollapsed ? menu.menuName : null"
                                    :class="[
                                        'flex items-center rounded-xl text-sm font-medium transition-colors group relative',
                                        isCollapsed ? 'lg:justify-center lg:h-11 lg:w-11 lg:mx-auto' : 'justify-between px-3.5 py-2.5',
                                        isActive(menu.route)
                                            ? 'bg-gradient-to-r from-indigo-950/70 to-slate-900/60 border border-indigo-500/20 text-indigo-200 font-semibold shadow-sm'
                                            : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/40 border border-transparent',
                                    ]"
                                >
                                    <div class="flex items-center gap-3">
                                        <font-awesome-icon
                                            :icon="menu.icon"
                                            :class="isActive(menu.route) ? 'text-indigo-400' : 'text-slate-400 group-hover:text-slate-200'"
                                        />
                                        <span :class="isCollapsed && 'lg:hidden'">{{ menu.menuName }}</span>
                                    </div>
                                    <span
                                        v-if="menu.hasBadge && menuBadgeCount(menu.menuName) > 0"
                                        class="text-xs px-2 py-0.5 rounded-full font-semibold border"
                                        :class="[badgeClasses(menu.badgeColor), isCollapsed && 'lg:hidden']"
                                    >{{ menuBadgeCount(menu.menuName) > 9 ? '9+' : menuBadgeCount(menu.menuName) }}</span>
                                    <span
                                        v-if="menu.hasBadge && menuBadgeCount(menu.menuName) > 0 && isCollapsed"
                                        class="hidden lg:block lg:absolute lg:top-1 lg:right-1 w-2 h-2 rounded-full"
                                        :class="badgeDotClasses(menu.badgeColor)"
                                    ></span>
                                </Link>
                            </template>
                        </div>
                    </nav>
                </div>

            </div>
        </aside>

        <!-- Main Content -->
        <div class="flex flex-col flex-1 overflow-hidden">
            <!-- Navbar -->
            <header class="h-16 bg-surface-sidebar/80 backdrop-blur-md shadow-sm border-b border-surface-border flex items-center justify-between px-6">
                <div class="flex items-center gap-4">
                    <button
                        @click="isShowSideBar = !isShowSideBar"
                        class="lg:hidden text-slate-400 hover:text-white transition"
                    >
                        <font-awesome-icon icon="fa-solid fa-bars" class="text-xl" />
                    </button>
                </div>

                <div class="flex items-center gap-3">
                    <a
                        :href="route('landing-page')"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="hidden sm:flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium text-slate-300 bg-surface-card border border-surface-border hover:border-surface-borderHover hover:text-white transition-all"
                    >
                        jerseyconnect.shop
                        <font-awesome-icon icon="fa-solid fa-arrow-up-right-from-square" class="text-[10px] text-slate-500" />
                    </a>

                    <Link
                        :href="route('admin.messages.index')"
                        class="relative p-2 rounded-lg text-slate-300 bg-surface-card border border-surface-border hover:border-surface-borderHover hover:text-white transition-all"
                        title="Messages"
                    >
                        <font-awesome-icon icon="fa-solid fa-bell" />
                        <span
                            v-if="unreadMessagesCount > 0"
                            class="absolute -top-1 -right-1 w-4 h-4 rounded-full bg-rose-500 text-white text-[9px] font-bold flex items-center justify-center ring-2 ring-surface-sidebar"
                        >
                            {{ unreadMessagesCount > 9 ? '9+' : unreadMessagesCount }}
                        </span>
                    </Link>

                    <div class="w-px h-6 bg-surface-border mx-0.5"></div>

                    <Link
                        :href="route('admin.profile')"
                        method="get"
                        as="button"
                        class="p-2 rounded-lg text-slate-300 bg-surface-card border border-surface-border hover:border-surface-borderHover hover:text-white transition-all"
                        title="Profile"
                    >
                        <font-awesome-icon icon="fa-solid fa-user-tie" />
                    </Link>
                    <button
                        type="button"
                        class="p-2 rounded-lg text-slate-300 bg-surface-card border border-surface-border hover:bg-rose-500/10 hover:text-rose-400 hover:border-rose-500/30 transition-all"
                        title="Sign out"
                        @click="showLogoutConfirm = true"
                    >
                        <font-awesome-icon icon="fa-solid fa-right-from-bracket" />
                    </button>

                    <Link
                        :href="route('admin.profile')"
                        class="hidden sm:block shrink-0 rounded-lg ring-1 ring-white/20 hover:ring-2 hover:ring-indigo-400/50 transition-all"
                        title="Profile"
                    >
                        <img
                            v-if="user?.user_info?.avatar_url"
                            :src="user.user_info.avatar_url"
                            alt="Profile picture"
                            class="w-9 h-9 rounded-lg object-cover"
                        />
                        <div v-else class="w-9 h-9 flex items-center justify-center font-bold text-xs text-white rounded-lg bg-gradient-to-tr from-indigo-600 to-violet-500">
                            {{ userInitials }}
                        </div>
                    </Link>
                </div>
            </header>

            <main
                class="flex-1 overflow-y-auto p-6 relative"
                style="background-color: #0a0d14; background-image: radial-gradient(at 0% 0%, rgba(37,99,235,0.08) 0px, transparent 50%), radial-gradient(at 90% 10%, rgba(147,51,234,0.07) 0px, transparent 40%), radial-gradient(at 50% 100%, rgba(14,165,233,0.05) 0px, transparent 50%);"
            >
                <div class="relative z-10 max-w-7xl mx-auto">
                    <slot />
                </div>
            </main>
        </div>

        <!-- Sign out confirmation -->
        <Modal :show="showLogoutConfirm" @close="showLogoutConfirm = false" :maxWidth="'sm'">
            <ModalHeader
                icon="fa-solid fa-right-from-bracket"
                icon-class="text-rose-400 bg-rose-500/15 border-rose-500/25"
                title="Sign out?"
                @close="showLogoutConfirm = false"
            />
            <div class="px-5 py-5 text-slate-200">
                <p class="text-sm text-slate-400">You'll need to sign back in to access the admin dashboard.</p>
                <div class="mt-5 pt-4 border-t border-white/10 flex flex-col-reverse gap-2 sm:flex-row sm:justify-between">
                    <SecondaryButton type="button" class="flex items-center justify-center" @click="showLogoutConfirm = false">Cancel</SecondaryButton>
                    <PrimaryButton
                        type="button"
                        class="flex items-center justify-center gap-1 !bg-rose-600 hover:!bg-rose-500"
                        :disabled="loggingOut"
                        :class="{ 'opacity-25': loggingOut }"
                        @click="confirmLogout"
                    >
                        <div class="text-sm" v-if="loggingOut">
                            <font-awesome-icon icon="fa-solid fa-spinner" spin />
                        </div>
                        Sign Out
                        <font-awesome-icon icon="fa-solid fa-right-from-bracket" />
                    </PrimaryButton>
                </div>
            </div>
        </Modal>
    </div>
</template>

<style>
.admin-scrollbar ::-webkit-scrollbar {
    width: 6px;
    height: 6px;
}
.admin-scrollbar ::-webkit-scrollbar-track {
    background: transparent;
}
.admin-scrollbar ::-webkit-scrollbar-thumb {
    background: #1e293b;
    border-radius: 9999px;
}
.admin-scrollbar ::-webkit-scrollbar-thumb:hover {
    background: #334155;
}
.admin-scrollbar {
    scrollbar-width: thin;
    scrollbar-color: #1e293b transparent;
}

/* Native date/time inputs default to a near-invisible dark picker icon on
   our dark surfaces — force the browser's dark UI so the calendar icon and
   popup are actually visible. */
.admin-scrollbar input[type="date"],
.admin-scrollbar input[type="time"],
.admin-scrollbar input[type="datetime-local"] {
    color-scheme: dark;
}
.admin-scrollbar input[type="date"]::-webkit-calendar-picker-indicator,
.admin-scrollbar input[type="time"]::-webkit-calendar-picker-indicator,
.admin-scrollbar input[type="datetime-local"]::-webkit-calendar-picker-indicator {
    filter: invert(0.75) brightness(1.3);
    cursor: pointer;
}
</style>
