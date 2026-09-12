<script setup lang="ts" generic="T extends { id?: string | number; ref?: string | number }">
import { ref, computed, watch, useSlots, useId } from "vue";

interface Column {
    key: string;
    label: string;
    slot?: string;
}

interface Actions {
    isDateFilterShow?: boolean;
    isPerPageShow?: boolean;
    isSearchShow?: boolean;
}

interface FilteredChangePayload<T> {
    rows: T[];
    dateFrom: string;
    dateTo: string;
    hasFilters: boolean;
}

const props = withDefaults(
    defineProps<{
        data: T[];
        columns: Column[];
        actions?: Actions;
        dateKey?: string;
        initialEmpty?: boolean;
        emptyStateMessage?: string | null;
    }>(),
    {
        actions: () => ({
            isDateFilterShow: true,
            isPerPageShow: true,
            isSearchShow: true,
        }),
        dateKey: "date",
        initialEmpty: false,
        emptyStateMessage: null,
    },
);

const emit = defineEmits<{
    (e: "filtered-change", payload: FilteredChangePayload<T>): void;
}>();

const slots = useSlots();

const uid = useId();
const dateFromId = `${uid}-date-from`;
const dateToId = `${uid}-date-to`;
const perPageId = `${uid}-per-page`;
const searchId = `${uid}-search`;

function getValue(row: T, key: string): unknown {
    return (row as Record<string, unknown>)[key];
}

// --- Search & Filter ---
const searchQuery = ref<string>("");
const dateFrom = ref<string>("");
const dateTo = ref<string>("");

// --- Pagination ---
const currentPage = ref<number>(1);
const perPage = ref<number>(5);
const perPageOptions: number[] = [5, 10, 25];

const filteredBookings = computed<T[]>(() => {
    return props.data.filter((b) => {
        const query = searchQuery.value.toLowerCase();
        const matchesSearch =
            !query ||
            props.columns.some((col) =>
                String(getValue(b, col.key) ?? "")
                    .toLowerCase()
                    .includes(query),
            );

        const date = new Date(getValue(b, props.dateKey) as string | number | Date);
        const matchesFrom = !dateFrom.value || date >= new Date(dateFrom.value);
        const matchesTo = !dateTo.value || date <= new Date(dateTo.value);

        return matchesSearch && matchesFrom && matchesTo;
    });
});

const hasActiveFilters = computed<boolean>(() => {
    return Boolean(searchQuery.value || dateFrom.value || dateTo.value);
});

watch([searchQuery, dateFrom, dateTo, perPage], () => {
    currentPage.value = 1;
});

watch(
    [filteredBookings, dateFrom, dateTo, hasActiveFilters],
    () => {
        emit("filtered-change", {
            rows: filteredBookings.value,
            dateFrom: dateFrom.value,
            dateTo: dateTo.value,
            hasFilters: hasActiveFilters.value,
        });
    },
    { immediate: true },
);

const totalPages = computed<number>(
    () => Math.ceil(filteredBookings.value.length / perPage.value) || 1,
);

const paginatedData = computed<T[]>(() => {
    if (props.initialEmpty && !hasActiveFilters.value) return [];
    const start = (currentPage.value - 1) * perPage.value;
    return filteredBookings.value.slice(start, start + perPage.value);
});

type PageEntry = number | "...";

const visiblePages = computed<PageEntry[]>(() => {
    const total = totalPages.value;
    const current = currentPage.value;
    const delta = 2;
    const pages: PageEntry[] = [];

    const start = Math.max(1, current - delta);
    const end = Math.min(total, current + delta);

    if (start > 1) pages.push(1);
    if (start > 2) pages.push("...");
    for (let i = start; i <= end; i++) pages.push(i);
    if (end < total - 1) pages.push("...");
    if (end < total) pages.push(total);

    return pages;
});

const goToPage = (page: PageEntry): void => {
    if (typeof page === "number") currentPage.value = page;
};

const clearFilters = (): void => {
    searchQuery.value = "";
    dateFrom.value = "";
    dateTo.value = "";
};

const rangeStart = computed<number>(() =>
    filteredBookings.value.length === 0
        ? 0
        : (currentPage.value - 1) * perPage.value + 1,
);
const rangeEnd = computed<number>(() =>
    Math.min(currentPage.value * perPage.value, filteredBookings.value.length),
);

const isShowingResults = computed<boolean>(
    () => !props.initialEmpty || hasActiveFilters.value,
);
</script>
<template>
    <!-- Search & Filters -->
    <div class="space-y-2 mb-2">
        <div
            class="flex flex-col gap-2 sm:flex-row sm:items-center justify-between"
        >
            <!-- Filter Row -->
            <div class="flex flex-wrap gap-2" v-if="actions.isDateFilterShow">
                <div class="flex items-center gap-1 w-full sm:w-auto">
                    <label :for="dateFromId" class="text-sm text-gray-500">From</label>
                    <input
                        :id="dateFromId"
                        v-model="dateFrom"
                        type="date"
                        name="dateFrom"
                        class="text-sm border border-gray-300 rounded-md py-1.5 w-full sm:w-auto focus:outline-none focus:ring-1 focus:ring-blue-500 bg-white text-gray-700"
                    />
                </div>

                <div class="flex items-center gap-1 w-full sm:w-auto">
                    <label :for="dateToId" class="text-sm text-gray-500">To</label>
                    <input
                        :id="dateToId"
                        v-model="dateTo"
                        type="date"
                        name="dateTo"
                        class="text-sm border border-gray-300 rounded-md py-1.5 w-full sm:w-auto focus:outline-none focus:ring-1 focus:ring-blue-500 bg-white text-gray-700"
                    />
                </div>

                <button
                    v-if="hasActiveFilters"
                    @click="clearFilters"
                    class="text-sm text-red-500 hover:text-red-700 gap-1 px-2 py-1.5 w-full sm:w-auto rounded-md border border-red-200 hover:bg-red-50 transition-colors"
                >
                    <font-awesome-icon icon="fa-solid fa-xmark" />
                    Clear
                </button>
            </div>

            <!-- Per-page selector -->
            <div class="flex items-center gap-2" v-if="actions.isPerPageShow">
                <label :for="perPageId" class="text-sm text-gray-500">Show</label>
                <select
                    :id="perPageId"
                    v-model="perPage"
                    name="perPage"
                    class="text-sm border border-gray-300 rounded-md py-1 focus:outline-none focus:ring-1 focus:ring-blue-500 bg-white text-gray-700"
                >
                    <option v-for="n in perPageOptions" :key="n" :value="n">
                        {{ n }}
                    </option>
                </select>
                <span class="text-sm text-gray-500">per page</span>
            </div>
        </div>

        <div class="flex items-center">
            <!-- Search Bar -->
            <div class="relative w-full" v-if="actions.isSearchShow">
                <span
                    class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400 pointer-events-none"
                >
                    <font-awesome-icon
                        icon="fa-solid fa-magnifying-glass"
                        class="text-sm"
                    />
                </span>
                <input
                    :id="searchId"
                    v-model="searchQuery"
                    type="text"
                    name="search"
                    placeholder="Search..."
                    aria-label="Search"
                    class="w-full pl-8 pr-4 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
                />
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="overflow-x-auto rounded-md border border-gray-200">
        <table class="w-full text-center text-sm">
            <thead>
                <tr class="bg-ink border-b border-paper">
                    <th
                        v-for="col in columns"
                        :key="col.key"
                        class="px-3 py-3 border border-stone-400 font-semibold text-paper uppercase tracking-wider whitespace-nowrap"
                    >
                        {{ col.label }}
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 bg-white text-gray-700">
                <tr
                    v-for="row in paginatedData"
                    :key="row.id ?? row.ref"
                    class="hover:bg-gray-50 transition-colors"
                >
                    <td
                        v-for="col in columns"
                        :key="col.key"
                        class="px-3 py-2.5 border border-stone-400 whitespace-nowrap"
                    >
                        <!-- If column has a slot, let the parent render it -->
                        <slot
                            v-if="col.slot"
                            :name="col.slot"
                            :row="row"
                            :value="getValue(row, col.key)"
                        />

                        <!-- Otherwise just render the value -->
                        <span v-else>{{ getValue(row, col.key) ?? "—" }}</span>
                    </td>
                </tr>

                <!-- Empty State -->
                <tr v-if="paginatedData.length === 0">
                    <td
                        :colspan="columns.length"
                        class="py-10 text-center text-gray-400"
                    >
                        <font-awesome-icon
                            icon="fa-solid fa-calendar-xmark"
                            class="text-2xl mb-2 block mx-auto"
                        />
                        <p class="text-sm font-medium">
                            {{
                                isShowingResults
                                    ? "No records found"
                                    : (emptyStateMessage ?? "Apply a filter to view records")
                            }}
                        </p>
                        <p class="text-xs mt-1" v-if="isShowingResults">
                            Try adjusting your search or filters.
                        </p>
                    </td>
                </tr>
            </tbody>

            <!-- Optional summary row (e.g. a grand total), supplied by the parent -->
            <tfoot v-if="slots.summary && isShowingResults && filteredBookings.length > 0">
                <slot name="summary" />
            </tfoot>
        </table>
    </div>

    <!-- Pagination Footer -->
    <div
        v-if="filteredBookings.length > 0 && isShowingResults"
        class="flex flex-col sm:flex-row items-center justify-between gap-2 mt-3"
    >
        <!-- Range info -->
        <p class="text-sm text-gray-500">
            Showing
            <span class="font-semibold text-gray-700"
                >{{ rangeStart }}–{{ rangeEnd }}</span
            >
            of
            <span class="font-semibold text-gray-700">{{
                filteredBookings.length
            }}</span>
            bookings
        </p>

        <!-- Page buttons -->
        <div class="flex items-center gap-1">
            <!-- Prev -->
            <button
                @click="currentPage--"
                :disabled="currentPage === 1"
                class="px-2 py-1 rounded-md border text-sm transition-colors"
                :class="
                    currentPage === 1
                        ? 'border-gray-200 text-gray-300 cursor-not-allowed'
                        : 'border-gray-300 text-gray-600 hover:bg-gray-100'
                "
            >
                <font-awesome-icon icon="fa-solid fa-chevron-left" />
            </button>

            <!-- Page numbers -->
            <template v-for="(page, i) in visiblePages" :key="i">
                <span
                    v-if="page === '...'"
                    class="px-1.5 py-1 text-sm text-gray-400"
                    >…</span
                >
                <button
                    v-else
                    @click="goToPage(page)"
                    class="min-w-[28px] px-2 py-1 rounded-md border text-sm font-medium transition-colors"
                    :class="
                        page === currentPage
                            ? 'bg-ink text-paper border-ink'
                            : 'border-gray-300 text-gray-600 hover:bg-gray-100'
                    "
                >
                    {{ page }}
                </button>
            </template>

            <!-- Next -->
            <button
                @click="currentPage++"
                :disabled="currentPage === totalPages"
                class="px-2 py-1 rounded-md border text-sm transition-colors"
                :class="
                    currentPage === totalPages
                        ? 'border-gray-200 text-gray-300 cursor-not-allowed'
                        : 'border-gray-300 text-gray-600 hover:bg-gray-100'
                "
            >
                <font-awesome-icon icon="fa-solid fa-chevron-right" />
            </button>
        </div>
    </div>
</template>