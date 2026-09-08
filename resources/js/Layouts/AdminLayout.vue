<script setup>
import { ref, computed } from "vue";
import { Link, usePage, usePoll } from "@inertiajs/vue3";

const unreadMessagesCount = computed(() => page.props.unreadMessagesCount ?? 0);

usePoll(3000, { only: ["unreadMessagesCount"] });

const isShowSideBar = ref(true);
const page = usePage();

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
    openSubmenus.value[menuName] = !openSubmenus.value[menuName];
};
</script>

<template>
    <div class="flex h-screen bg-gray-100 overflow-hidden">
        <!-- Mobile Overlay (only on small screens) -->
        <div
            v-if="isShowSideBar"
            @click="isShowSideBar = false"
            class="fixed inset-0 bg-black/50 z-20 lg:hidden"
        ></div>

        <!-- Sidebar -->
        <aside
            :class="[
                isShowSideBar
                    ? 'translate-x-0 lg:w-64'
                    : '-translate-x-full lg:translate-x-0 lg:w-0',
                'fixed inset-y-0 left-0 z-30 w-64 bg-slate-900 text-white transition-all duration-300 ease-in-out shadow-xl',
                'lg:relative lg:inset-y-auto lg:left-auto lg:z-auto lg:flex-shrink-0 lg:overflow-hidden',
            ]"
        >
            <div class="w-64 h-full flex flex-col">
                <!-- Logo -->
                <div
                    class="h-16 flex justify-between items-center ps-8 pe-5 border-b border-slate-800"
                >
                    <Link
                        class="flex items-center gap-2 no-underline flex-shrink-0"
                        :href="route('admin.dashboard')"
                    >
                        <div class="py-1 rounded bg-white">
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
                    <button
                        @click="isShowSideBar = false"
                        class="lg:hidden text-gray-400 hover:text-white"
                    >
                        <font-awesome-icon icon="fa-solid fa-xmark" />
                    </button>
                </div>

                <!-- Navigation -->
                <nav class="p-4 space-y-1">
                    <template v-for="menu in sidebarMenus" :key="menu.menuName">
                        <!-- Menu with children -->
                        <template v-if="menu.children">
                            <button
                                @click="toggleSubmenu(menu.menuName)"
                                :class="[
                                    'w-full flex items-center gap-3 px-4 py-3 rounded-lg transition-all duration-200',
                                    menu.children.some((child) =>
                                        isActive(child.route),
                                    )
                                        ? 'bg-slate-800 text-white font-semibold'
                                        : 'text-gray-300 hover:bg-slate-800 hover:text-white',
                                ]"
                            >
                                <font-awesome-icon :icon="menu.icon" />
                                <span class="flex-1 text-left">{{
                                    menu.menuName
                                }}</span>
                                <font-awesome-icon
                                    icon="fa-solid fa-chevron-down"
                                    :class="[
                                        'text-xs text-gray-500 transition-transform duration-200',
                                        openSubmenus[menu.menuName]
                                            ? 'rotate-180'
                                            : '',
                                    ]"
                                />
                            </button>

                            <!-- Submenu -->
                            <div
                                v-show="openSubmenus[menu.menuName]"
                                class="overflow-hidden transition-all duration-200 mt-1 space-y-1"
                            >
                                <Link
                                    v-for="child in menu.children"
                                    :key="child.menuName"
                                    :href="child.route"
                                    :class="[
                                        'flex items-center gap-3 pl-11 pr-4 py-2 rounded-lg text-sm transition-all duration-200',
                                        isActive(child.route)
                                            ? 'text-white bg-slate-800/60'
                                            : 'text-gray-400 hover:bg-slate-800 hover:text-gray-200',
                                    ]"
                                >
                                    <span
                                        class="w-1.5 h-1.5 rounded-full bg-warn flex-shrink-0"
                                    />
                                    {{ child.menuName }}
                                </Link>
                            </div>
                        </template>

                        <!-- Regular menu item -->
                        <Link
                            v-else
                            :href="menu.route"
                            :class="[
                                'flex items-center gap-3 px-4 py-3 rounded-lg transition-all duration-200',
                                isActive(menu.route)
                                    ? 'bg-slate-800 text-white font-semibold'
                                    : 'text-gray-300 hover:bg-slate-800 hover:text-white',
                            ]"
                        >
                            <div class="relative">
                                <font-awesome-icon :icon="menu.icon" />
                                <span
                                    v-if="
                                        menu.menuName === 'Messages' &&
                                        unreadMessagesCount > 0
                                    "
                                    class="absolute -top-1 -right-1 w-2 h-2 bg-red-500 rounded-full"
                                />
                            </div>
                            <span>{{ menu.menuName }}</span>
                        </Link>
                    </template>
                </nav>
            </div>
        </aside>

        <!-- Main Content -->
        <div class="flex flex-col flex-1 overflow-hidden">
            <!-- Navbar -->
            <header
                class="h-16 bg-navy shadow-sm border-b border-gray-200 flex items-center justify-between px-6"
            >
                <div class="flex items-center gap-4">
                    <button
                        @click="isShowSideBar = !isShowSideBar"
                        class="text-gray-400 hover:text-white transition"
                    >
                        <font-awesome-icon
                            icon="fa-solid fa-bars"
                            class="text-xl text-ink"
                        />
                    </button>
                </div>

                <div class="flex items-center gap-2">
                    <Link
                        :href="route('admin.profile')"
                        method="get"
                        as="button"
                        class="px-4 py-2 text-paper bg-ink hover:bg-warn hover:text-ink rounded-lg transition"
                    >
                        <font-awesome-icon icon="fa-solid fa-user-tie" />
                        Profile
                    </Link>
                    <Link
                        :href="route('landing-page')"
                        method="get"
                        as="button"
                        class="px-4 py-2 text-paper bg-ink hover:bg-warn hover:text-ink rounded-lg transition"
                    >
                        <font-awesome-icon icon="fa-solid fa-newspaper" />
                        Website
                    </Link>
                    <Link
                        :href="route('logout')"
                        method="post"
                        as="button"
                        class="px-4 py-2 text-paper bg-ink hover:bg-warn hover:text-ink rounded-lg transition"
                    >
                        <font-awesome-icon
                            icon="fa-solid fa-right-from-bracket"
                        />
                    </Link>
                </div>
            </header>

            <main class="flex-1 overflow-y-auto p-6 relative">
                <div class="relative z-10">
                    <slot />
                </div>
            </main>
        </div>
    </div>
</template>
