<script setup>
import { ref, computed } from "vue";
import { Link, usePage, usePoll } from "@inertiajs/vue3";

const page = usePage();

const unreadMessagesCount = computed(() => page.props.unreadMessagesCount ?? 0);

usePoll(3000, { only: ["unreadMessagesCount"] });

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
        menuName: "Jersey Templates",
        route: route("admin.jersey.index"),
        icon: "fa-solid fa-tshirt",
        hasBadge: true,
    },
    {
        menuName: "Designs",
        route: route("admin.design.index"),
        icon: "fa-solid fa-spray-can-sparkles",
        hasBadge: true,
        badgeColor: "fuchsia",
    },
    {
        menuName: "Order Management",
        icon: "fa-solid fa-shopping-basket",
        children: [
            { menuName: "Orders", route: route("admin.orders.index") },
            {
                menuName: "Couriers",
                route: route("admin.couriers.index"),
            },
        ],
    },
    {
        menuName: "Gcash Details",
        route: route("admin.gcash.index"),
        icon: "fa-solid fa-wallet",
        hasBadge: true,
    },
    {
        menuName: "Messages",
        route: route("admin.messages.index"),
        icon: "fa-solid fa-envelope",
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

const overviewMenus = byName("Dashboard", "Messages");
const catalogMenus = byName("Jersey Templates", "Designs");
const orderManagementMenu = sidebarMenus.find((m) => m.children);
const adminMenus = byName("Gcash Details", "Users");

const isActive = (href) => page.url.startsWith(new URL(href).pathname);

const openSubmenus = ref(
    Object.fromEntries(
        sidebarMenus
            .filter((menu) => menu.children)
            .map((menu) => [
                menu.menuName,
                menu.children.some((child) => isActive(child.route)),
            ]),
    ),
);

const toggleSubmenu = (menuName) => {
    if (isCollapsed.value) {
        isCollapsed.value = false;
        openSubmenus.value[menuName] = true;
        return;
    }
    openSubmenus.value[menuName] = !openSubmenus.value[menuName];
};

const badgeClasses = (color) =>
    color === "fuchsia"
        ? "bg-fuchsia-500/20 text-fuchsia-300 border-fuchsia-500/30"
        : "bg-indigo-500/20 text-indigo-300 border-indigo-500/30";

const badgeDotClasses = (color) =>
    color === "fuchsia" ? "bg-fuchsia-400" : "bg-indigo-500";
</script>

<template>
    <div class="flex h-screen bg-surface-base font-jakarta overflow-hidden">
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
                        <font-awesome-icon
                            icon="fa-solid fa-chevron-left"
                            class="text-[11px] transition-transform"
                            :class="isCollapsed && 'rotate-180'"
                        />
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
                                        v-if="menu.hasBadge"
                                        class="text-xs px-2 py-0.5 rounded-full font-semibold border"
                                        :class="[badgeClasses(menu.badgeColor), isCollapsed && 'lg:hidden']"
                                    >1</span>
                                    <span
                                        v-if="menu.hasBadge && isCollapsed"
                                        class="hidden lg:block lg:absolute lg:top-1 lg:right-1 w-2 h-2 rounded-full"
                                        :class="badgeDotClasses(menu.badgeColor)"
                                    ></span>
                                </Link>
                            </template>
                        </div>

                        <!-- Order Management (has children) -->
                        <div class="space-y-1" v-if="orderManagementMenu">
                            <button
                                @click="toggleSubmenu(orderManagementMenu.menuName)"
                                :title="isCollapsed ? orderManagementMenu.menuName : null"
                                :class="[
                                    'w-full flex items-center rounded-xl text-sm font-medium transition-colors',
                                    isCollapsed ? 'lg:justify-center lg:h-11 lg:w-11 lg:mx-auto' : 'gap-3 px-3.5 py-2.5',
                                    orderManagementMenu.children.some((child) => isActive(child.route))
                                        ? 'bg-gradient-to-r from-indigo-950/70 to-slate-900/60 border border-indigo-500/20 text-indigo-200 font-semibold shadow-sm'
                                        : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/40 border border-transparent',
                                ]"
                            >
                                <font-awesome-icon :icon="orderManagementMenu.icon" />
                                <span class="flex-1 text-left" :class="isCollapsed && 'lg:hidden'">{{ orderManagementMenu.menuName }}</span>
                                <font-awesome-icon
                                    icon="fa-solid fa-chevron-down"
                                    class="text-xs text-slate-500 transition-transform duration-200"
                                    :class="[openSubmenus[orderManagementMenu.menuName] ? 'rotate-180' : '', isCollapsed && 'lg:hidden']"
                                />
                            </button>

                            <div
                                v-show="openSubmenus[orderManagementMenu.menuName] && !isCollapsed"
                                class="overflow-hidden mt-1 space-y-1"
                            >
                                <Link
                                    v-for="child in orderManagementMenu.children"
                                    :key="child.menuName"
                                    :href="child.route"
                                    :class="[
                                        'flex items-center gap-3 pl-11 pr-4 py-2 rounded-lg text-sm transition-colors',
                                        isActive(child.route)
                                            ? 'text-indigo-200 bg-slate-800/60'
                                            : 'text-slate-400 hover:bg-slate-800/40 hover:text-slate-200',
                                    ]"
                                >
                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-400 flex-shrink-0" />
                                    {{ child.menuName }}
                                </Link>
                            </div>
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
                                        v-if="menu.hasBadge"
                                        class="text-xs px-2 py-0.5 rounded-full font-semibold border"
                                        :class="[badgeClasses(menu.badgeColor), isCollapsed && 'lg:hidden']"
                                    >1</span>
                                </Link>
                            </template>
                        </div>
                    </nav>
                </div>

                <!-- Bottom profile -->
                <div class="p-4 border-t border-surface-border bg-slate-900/40">
                    <div
                        class="flex items-center justify-between p-2 rounded-xl bg-slate-800/50 border border-white/5 hover:border-indigo-500/30 transition-all group"
                        :class="isCollapsed && 'lg:justify-center'"
                    >
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="w-9 h-9 shrink-0 rounded-lg bg-gradient-to-tr from-indigo-600 to-violet-500 flex items-center justify-center font-bold text-xs text-white shadow-sm ring-1 ring-white/20">
                                {{ userInitials }}
                            </div>
                            <div class="truncate" :class="isCollapsed && 'lg:hidden'">
                                <h4 class="text-xs font-semibold text-slate-100 truncate group-hover:text-indigo-200">{{ userName }}</h4>
                                <span class="text-[11px] text-slate-400 block truncate">Admin</span>
                            </div>
                        </div>
                        <Link
                            :href="route('logout')"
                            method="post"
                            as="button"
                            :title="isCollapsed ? 'Sign out' : null"
                            class="p-1.5 text-slate-400 hover:text-rose-400 rounded-lg transition-colors"
                            :class="isCollapsed && 'lg:hidden'"
                        >
                            <font-awesome-icon icon="fa-solid fa-right-from-bracket" class="text-sm" />
                        </Link>
                    </div>
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

                <div class="flex items-center gap-2">
                    <Link
                        :href="route('admin.profile')"
                        method="get"
                        as="button"
                        class="px-4 py-2 text-sm font-medium text-slate-300 bg-surface-card hover:bg-slate-800/80 border border-surface-border hover:border-surface-borderHover rounded-xl transition-all"
                    >
                        <font-awesome-icon icon="fa-solid fa-user-tie" class="mr-1.5" />
                        Profile
                    </Link>
                    <Link
                        :href="route('landing-page')"
                        method="get"
                        as="button"
                        class="px-4 py-2 text-sm font-medium text-slate-300 bg-surface-card hover:bg-slate-800/80 border border-surface-border hover:border-surface-borderHover rounded-xl transition-all"
                    >
                        <font-awesome-icon icon="fa-solid fa-newspaper" class="mr-1.5" />
                        Website
                    </Link>
                    <Link
                        :href="route('logout')"
                        method="post"
                        as="button"
                        class="px-3 py-2 text-sm font-medium text-slate-300 bg-surface-card hover:bg-rose-500/10 hover:text-rose-400 border border-surface-border hover:border-rose-500/30 rounded-xl transition-all"
                    >
                        <font-awesome-icon icon="fa-solid fa-right-from-bracket" />
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
    </div>
</template>
