<script setup lang="ts">
import { ref, computed } from "vue";
import { Link, usePage, usePoll } from "@inertiajs/vue3";

const unreadMessagesCount = computed(
    () => (page.props.unreadMessagesCount as number) ?? 0,
);

usePoll(3000, { only: ["unreadMessagesCount"] });

const isShowSideBar = ref(true);
const page = usePage();

const sidebarMenus = [
    {
        menuName: "Home",
        route: route("client.home.index"),
        icon: "fa-solid fa-tachograph-digital",
    },
    {
        menuName: "All Designs",
        route: route("client.design.index"),
        icon: "fa-solid fa-tshirt",
    },
    {
        menuName: "My Orders",
        route: route("client.orders.index"),
        icon: "fa-solid fa-shopping-basket",
    },
    {
        menuName: "Messages",
        route: route("client.chat.index"),
        icon: "fa-solid fa-message",
        hasBadge: true,
    },
    {
        menuName: "Profile",
        route: route("client.profile.index"),
        icon: "fa-solid fa-user-circle",
    },
];

const isActive = (href: string | URL) =>
    page.url.startsWith(new URL(href).pathname);
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
                'fixed inset-y-0 left-0 z-30 w-64 bg-ink text-white transition-all duration-300 ease-in-out shadow-xl',
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
                        :href="route('client.home.index')"
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
                <nav class="p-4 space-y-2">
                    <Link
                        v-for="menu in sidebarMenus"
                        :key="menu.menuName"
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
                </nav>
            </div>
        </aside>

        <!-- Main Content -->
        <div class="flex flex-col flex-1 overflow-hidden">
            <!-- Navbar -->
            <header
                class="h-16 bg-navy shadow-sm bg-paper border-b border-gray-200 flex items-center justify-between px-6"
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

            <main class="flex-1 overflow-y-auto p-6 relative bg-gray-100">
                <div class="relative z-10">
                    <slot />
                </div>
            </main>
        </div>
    </div>
</template>
