<script setup>
import SelectField from '@/Components/Forms/Fields/SelectField.vue';
import TextField from '@/Components/Forms/Fields/TextField.vue';
import MerchantLayout from '@/Layouts/MerchantLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed, reactive, ref, watch } from 'vue';

const props = defineProps({
    menuItems: {
        type: Array,
        default: () => [],
    },
    restaurantOptions: {
        type: Array,
        default: () => [],
    },
    categoryOptions: {
        type: Array,
        default: () => [],
    },
});

const filters = reactive({
    search: '',
    restaurantId: 'all',
    category: 'all',
    availability: 'all',
});

const restaurantFilterOptions = computed(() => ([
    { value: 'all', label: 'All restaurants' },
    ...props.restaurantOptions.map((option) => ({
        value: String(option.value),
        label: option.label,
    })),
]));

const categoryFilterOptions = computed(() => ([
    { value: 'all', label: 'All categories' },
    ...props.categoryOptions.map((option) => ({
        value: option.value,
        label: option.label,
    })),
]));

const availabilityOptions = [
    { value: 'all', label: 'Live and paused' },
    { value: 'live', label: 'Live only' },
    { value: 'paused', label: 'Paused only' },
];

const filteredMenuItems = computed(() => {
    const searchTerm = filters.search.trim().toLowerCase();

    return props.menuItems.filter((menuItem) => {
        const matchesSearch = searchTerm === ''
            || [menuItem.name, menuItem.description, menuItem.category, menuItem.restaurantName]
                .filter(Boolean)
                .some((value) => value.toLowerCase().includes(searchTerm));
        const matchesRestaurant = filters.restaurantId === 'all' || String(menuItem.restaurantId) === filters.restaurantId;
        const matchesCategory = filters.category === 'all' || menuItem.category === filters.category;
        const matchesAvailability = filters.availability === 'all'
            || (filters.availability === 'live' && menuItem.isAvailable)
            || (filters.availability === 'paused' && !menuItem.isAvailable);

        return matchesSearch && matchesRestaurant && matchesCategory && matchesAvailability;
    });
});

function resetFilters() {
    filters.search = '';
    filters.restaurantId = 'all';
    filters.category = 'all';
    filters.availability = 'all';
}

const expandedDescriptions = ref([]);

function toggleDescription(id) {
    if (expandedDescriptions.value.includes(id)) {
        expandedDescriptions.value = expandedDescriptions.value.filter((i) => i !== id);
    } else {
        expandedDescriptions.value.push(id);
    }
}

function setupBadges(menuItem) {
    return [
        { key: 'variants', label: 'Variants', count: menuItem.counts.variants },
        { key: 'add-ons', label: 'Add-ons', count: menuItem.counts.addOns },
        { key: 'modifiers', label: 'Modifiers', count: menuItem.counts.modifiers },
        { key: 'bundles', label: 'Bundles', count: menuItem.counts.bundleItems },
    ].filter((badge) => badge.count > 0);
}

// ── Category tabs ──────────────────────────────────────────────────────────

const itemsExcludingCategoryFilter = computed(() => {
    const searchTerm = filters.search.trim().toLowerCase();
    return props.menuItems.filter((item) => {
        const matchesSearch = searchTerm === ''
            || [item.name, item.description, item.category, item.restaurantName]
                .filter(Boolean)
                .some((value) => value.toLowerCase().includes(searchTerm));
        const matchesRestaurant = filters.restaurantId === 'all' || String(item.restaurantId) === filters.restaurantId;
        const matchesAvailability = filters.availability === 'all'
            || (filters.availability === 'live' && item.isAvailable)
            || (filters.availability === 'paused' && !item.isAvailable);
        return matchesSearch && matchesRestaurant && matchesAvailability;
    });
});

const categoryCountMap = computed(() => {
    const map = {};
    itemsExcludingCategoryFilter.value.forEach((item) => {
        if (item.category) map[item.category] = (map[item.category] || 0) + 1;
    });
    return map;
});

const tabCategories = computed(() =>
    [...new Set(props.menuItems.map((i) => i.category).filter(Boolean))]
        .sort()
        .filter((cat) => categoryCountMap.value[cat] > 0),
);

function setCategory(category) {
    filters.category = category;
    currentPage.value = 1;
}

// ── Pagination ─────────────────────────────────────────────────────────────

const perPage = ref(25);
const currentPage = ref(1);

watch(
    [() => filters.search, () => filters.restaurantId, () => filters.category, () => filters.availability, perPage],
    () => { currentPage.value = 1; },
);

const totalPages = computed(() => Math.max(1, Math.ceil(filteredMenuItems.value.length / perPage.value)));

const rangeStart = computed(() =>
    filteredMenuItems.value.length === 0 ? 0 : (currentPage.value - 1) * perPage.value + 1,
);
const rangeEnd = computed(() => Math.min(currentPage.value * perPage.value, filteredMenuItems.value.length));

const paginatedItems = computed(() => {
    const start = (currentPage.value - 1) * perPage.value;
    return filteredMenuItems.value.slice(start, start + perPage.value);
});

const visiblePages = computed(() => {
    const total = totalPages.value;
    const cur = currentPage.value;
    if (total <= 7) return Array.from({ length: total }, (_, i) => i + 1);
    const pages = new Set([1, total]);
    for (let i = Math.max(2, cur - 1); i <= Math.min(total - 1, cur + 1); i++) pages.add(i);
    return [...pages].sort((a, b) => a - b).reduce((acc, page, idx, arr) => {
        if (idx > 0 && page - arr[idx - 1] > 1) acc.push('\u2026');
        acc.push(page);
        return acc;
    }, []);
});
</script>

<template>
    <Head title="Menu" />

    <MerchantLayout>
        <div class="py-8">
            <div class="space-y-6 px-4 sm:px-6 lg:px-0">
                <section class="rounded-[32px] border border-white/80 bg-[linear-gradient(145deg,#ffffff_0%,#fff8f1_58%,#f4fbfb_100%)] p-6 shadow-[0_34px_85px_-56px_rgba(11,77,89,0.6)] sm:p-8">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-[var(--brand-teal)]">List filters</p>
                            <h3 class="mt-2 text-2xl font-semibold text-slate-900">Find a dish by name, restaurant, category, or live status.</h3>
                         
                        </div>
                        <div class="flex gap-5">
                            <div class="rounded-full border border-white/80 bg-white/90 px-4 py-2 text-sm font-medium text-slate-700 shadow-[0_20px_48px_-36px_rgba(11,77,89,0.35)]">
                                {{ filteredMenuItems.length }} visible item{{ filteredMenuItems.length === 1 ? '' : 's' }}
                            </div>
                            <button
                                type="button"
                                class="inline-flex items-center justify-center rounded-full border border-[#d6e7e7] bg-[var(--brand-orange)] px-5 py-3 text-xs font-semibold uppercase tracking-[0.18em] text-slate-700 transition duration-200 hover:-translate-y-0.5"
                                @click="resetFilters"
                            >
                                Reset filters
                            </button>
                        </div>
                    </div>

                    <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-[1.6fr_repeat(3,minmax(0,1fr))]">
                        <TextField
                            id="menu_search"
                            v-model="filters.search"
                            label="Search dishes"
                            placeholder="Dish, description, restaurant"
                        />

                        <SelectField
                            id="menu_filter_restaurant"
                            v-model="filters.restaurantId"
                            label="Restaurant"
                            :options="restaurantFilterOptions"
                        />

                        <SelectField
                            id="menu_filter_category"
                            v-model="filters.category"
                            label="Category"
                            :options="categoryFilterOptions"
                        />

                        <SelectField
                            id="menu_filter_availability"
                            v-model="filters.availability"
                            label="Availability"
                            :options="availabilityOptions"
                        />
                    </div>

                </section>

                <section class="overflow-hidden rounded-[32px] border border-white/80 bg-white/90 shadow-[0_30px_75px_-50px_rgba(11,77,89,0.5)]">
                    <div class="flex flex-col gap-4 border-b border-[#e7efef] px-6 py-5 sm:flex-row sm:items-end sm:justify-between sm:px-8">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-[var(--brand-teal)]">Menu list</p>
                            <h3 class="mt-2 text-2xl font-semibold text-slate-900">Everything in one place.</h3>
                          
                        </div>

                        <Link
                            :href="route('merchant.menu.create')"
                            class="inline-flex items-center justify-center rounded-full bg-[var(--brand-orange)] px-5 py-3 text-xs font-semibold uppercase tracking-[0.2em] text-slate-950 transition duration-200 hover:-translate-y-0.5"
                        >
                            Create menu item
                        </Link>
                    </div>

                    <!-- Category Tabs -->
                    <div class="overflow-x-auto border-b border-[#e7efef]">
                        <div class="flex min-w-max px-6 sm:px-8">
                            <button
                                type="button"
                                class="relative whitespace-nowrap px-4 py-3.5 text-xs font-semibold uppercase tracking-[0.16em] transition-colors duration-200"
                                :class="filters.category === 'all'
                                    ? 'text-[var(--brand-teal)] after:absolute after:bottom-0 after:left-0 after:right-0 after:h-0.5 after:bg-[var(--brand-teal)]'
                                    : 'text-slate-500 hover:text-slate-700'"
                                @click="setCategory('all')"
                            >
                                All Items
                                <span
                                    class="ml-1.5 rounded-full px-2 py-0.5 text-[10px] font-bold"
                                    :class="filters.category === 'all' ? 'bg-[#e8f4f4] text-[var(--brand-teal)]' : 'bg-slate-100 text-slate-500'"
                                >{{ itemsExcludingCategoryFilter.length }}</span>
                            </button>
                            <button
                                v-for="cat in tabCategories"
                                :key="cat"
                                type="button"
                                class="relative whitespace-nowrap px-4 py-3.5 text-xs font-semibold uppercase tracking-[0.16em] transition-colors duration-200"
                                :class="filters.category === cat
                                    ? 'text-[var(--brand-teal)] after:absolute after:bottom-0 after:left-0 after:right-0 after:h-0.5 after:bg-[var(--brand-teal)]'
                                    : 'text-slate-500 hover:text-slate-700'"
                                @click="setCategory(cat)"
                            >
                                {{ cat }}
                                <span
                                    class="ml-1.5 rounded-full px-2 py-0.5 text-[10px] font-bold"
                                    :class="filters.category === cat ? 'bg-[#e8f4f4] text-[var(--brand-teal)]' : 'bg-slate-100 text-slate-500'"
                                >{{ categoryCountMap[cat] }}</span>
                            </button>
                        </div>
                    </div>

                    <div v-if="filteredMenuItems.length">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-[#e8efef]">
                            <thead class="bg-[#f8fbfb]">
                                <tr class="text-left text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">
                                    <th class="px-6 py-4 sm:px-8">Dish</th>
                                    <th class="px-6 py-4">Restaurant</th>
                                    <th class="px-6 py-4">Pricing</th>
                                    <th class="px-6 py-4">Availability</th>
                                    <th class="px-6 py-4">Setup</th>
                                    <th class="px-6 py-4 sm:px-8">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[#eef3f3] bg-white">
                                <tr v-for="menuItem in paginatedItems" :key="menuItem.id" class="align-top transition duration-200 hover:bg-[#fcfefe]">
                                    <td class="px-6 py-5 sm:px-8">
                                        <div class="flex min-w-[320px] items-start gap-4">
                                            <div class="flex h-16 w-16 shrink-0 items-center justify-center overflow-hidden rounded-[20px] bg-[#f4fbfb] ring-1 ring-[#dceced]">
                                                <img
                                                    v-if="menuItem.imageUrl"
                                                    :src="menuItem.imageUrl"
                                                    :alt="`${menuItem.name} image`"
                                                    class="h-full w-full object-cover"
                                                    @error="$event.target.src = '/images/bizlami_icon.png'; $event.target.classList.replace('h-full', 'h-10'); $event.target.classList.replace('w-full', 'w-10'); $event.target.classList.replace('object-cover', 'object-contain');"
                                                >
                                                <div v-else class="flex h-full w-full items-center justify-center bg-[#f8fbfb]">
                                                    <img
                                                        src="/images/bizlami_icon.png"
                                                        alt="BizLami icon"
                                                        class="h-10 w-10 object-contain"
                                                    >
                                                </div>
                                            </div>

                                            <div class="min-w-0 flex-1">
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <p class="text-sm font-semibold text-slate-900">{{ menuItem.name }}</p>
                                                    <span class="rounded-full bg-[#fff8f1] px-2.5 py-1 text-[11px] font-semibold uppercase tracking-[0.16em] text-[var(--brand-orange-deep)] ring-1 ring-[#f6dcc5]">
                                                        {{ menuItem.category }}
                                                    </span>
                                                </div>
                                                <div class="mt-2 max-w-xl">
                                                    <p
                                                        class="text-sm leading-6 text-slate-600"
                                                        :class="{ 'line-clamp-2': !expandedDescriptions.includes(menuItem.id) }"
                                                    >
                                                        {{ menuItem.description }}
                                                    </p>
                                                    <button
                                                        v-if="menuItem.description && menuItem.description.length > 120"
                                                        type="button"
                                                        class="mt-1 text-xs font-semibold text-[var(--brand-teal)] hover:text-slate-900"
                                                        @click="toggleDescription(menuItem.id)"
                                                    >
                                                        {{ expandedDescriptions.includes(menuItem.id) ? 'Show less' : 'Read more' }}
                                                    </button>
                                                </div>
                                                <p class="mt-2 text-xs text-slate-500">{{ menuItem.updatedAt ? `Updated ${menuItem.updatedAt}` : 'Recently added' }}</p>
                                            </div>
                                        </div>
                                    </td>

                                    <td class="px-6 py-5">
                                        <div class="min-w-[160px]">
                                            <p class="text-sm font-medium text-slate-900">{{ menuItem.restaurantName }}</p>
                                            <p class="mt-1 text-xs leading-5 text-slate-500">{{ menuItem.restaurantCuisine || 'Cuisine details are not set yet.' }}</p>
                                        </div>
                                    </td>

                                    <td class="px-6 py-5">
                                        <div class="min-w-[120px]">
                                            <p class="text-sm font-semibold text-slate-900">{{ menuItem.effectivePrice }}</p>
                                            <p class="mt-1 text-xs" :class="menuItem.promoPrice ? 'text-slate-400 line-through' : 'text-slate-500'">Base {{ menuItem.basePrice }}</p>
                                            <p v-if="menuItem.promoPrice" class="mt-1 text-xs font-medium text-[var(--brand-orange-deep)]">Promo {{ menuItem.promoPrice }}</p>
                                        </div>
                                    </td>

                                    <td class="px-6 py-5">
                                        <div class="min-w-[140px]">
                                            <span
                                                class="inline-flex rounded-full px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.16em] ring-1"
                                                :class="menuItem.isAvailable
                                                    ? 'bg-emerald-50 text-emerald-700 ring-emerald-200'
                                                    : 'bg-slate-100 text-slate-600 ring-slate-200'"
                                            >
                                                {{ menuItem.availability }}
                                            </span>
                                            <p class="mt-2 text-xs leading-5 text-slate-500">{{ menuItem.availabilityWindowLabel || 'All day availability' }}</p>
                                        </div>
                                    </td>

                                    <td class="px-6 py-5">
                                        <div class="flex min-w-[220px] flex-wrap gap-2">
                                            <template v-if="setupBadges(menuItem).length">
                                                <span
                                                    v-for="badge in setupBadges(menuItem)"
                                                    :key="`${menuItem.id}-${badge.key}`"
                                                    class="rounded-full bg-[#f8fbfb] px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.16em] text-[var(--brand-teal)] ring-1 ring-[#dceced]"
                                                >
                                                    {{ badge.count }} {{ badge.label }}
                                                </span>
                                            </template>
                                            <span
                                                v-else
                                                class="rounded-full bg-[#f8fbfb] px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-500 ring-1 ring-[#dceced]"
                                            >
                                                Simple item
                                            </span>
                                        </div>
                                    </td>

                                    <td class="px-6 py-5 sm:px-8">
                                        <div class="flex min-w-[120px] justify-end">
                                            <Link
                                                :href="route('merchant.menu.edit', menuItem.id)"
                                                class="inline-flex items-center justify-center rounded-full border border-[#d0e2e3] bg-[#f7fbfb] px-4 py-2 text-[11px] font-semibold uppercase tracking-[0.18em] text-[var(--brand-teal)] transition duration-200 hover:-translate-y-0.5 hover:border-[var(--brand-orange)] hover:bg-[#fffaf4]"
                                            >
                                                Edit item
                                            </Link>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="flex flex-col gap-4 border-t border-[#e7efef] px-6 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-8">
                        <div class="flex items-center gap-3 text-sm">
                            <span class="text-slate-500">Rows per page</span>
                            <div class="flex overflow-hidden rounded-full border border-[#dce7e7] bg-[#f7fbfb]">
                                <button
                                    v-for="size in [10, 25, 50]"
                                    :key="size"
                                    type="button"
                                    class="px-3 py-1.5 text-xs font-semibold transition-colors duration-150"
                                    :class="perPage === size ? 'bg-[var(--brand-teal)] text-white' : 'text-slate-500 hover:bg-[#eef5f5]'"
                                    @click="perPage = size"
                                >{{ size }}</button>
                            </div>
                            <span class="text-slate-400">·</span>
                            <span class="text-slate-500">{{ rangeStart }}–{{ rangeEnd }} of {{ filteredMenuItems.length }}</span>
                        </div>
                        <div v-if="totalPages > 1" class="flex items-center gap-1">
                            <button
                                type="button"
                                class="flex h-8 w-8 items-center justify-center rounded-full text-sm transition-colors duration-150"
                                :class="currentPage === 1 ? 'cursor-not-allowed text-slate-300' : 'text-slate-600 hover:bg-[#eef5f5]'"
                                :disabled="currentPage === 1"
                                @click="currentPage--"
                            >←</button>
                            <template v-for="page in visiblePages" :key="String(page)">
                                <span v-if="page === '\u2026'" class="px-1 text-sm text-slate-400">…</span>
                                <button
                                    v-else
                                    type="button"
                                    class="flex h-8 w-8 items-center justify-center rounded-full text-xs font-semibold transition-colors duration-150"
                                    :class="currentPage === page ? 'bg-[var(--brand-teal)] text-white' : 'text-slate-600 hover:bg-[#eef5f5]'"
                                    @click="currentPage = page"
                                >{{ page }}</button>
                            </template>
                            <button
                                type="button"
                                class="flex h-8 w-8 items-center justify-center rounded-full text-sm transition-colors duration-150"
                                :class="currentPage === totalPages ? 'cursor-not-allowed text-slate-300' : 'text-slate-600 hover:bg-[#eef5f5]'"
                                :disabled="currentPage === totalPages"
                                @click="currentPage++"
                            >→</button>
                        </div>
                    </div>
                    </div>

                    <div v-else class="p-8 sm:p-10">
                        <div class="rounded-[28px] border border-dashed border-[#d7e7e8] bg-[#fcfefe] p-6 text-sm leading-6 text-slate-500">
                            <p class="text-base font-semibold text-slate-900">No menu items match the current view.</p>
                            <p class="mt-2">Clear the filters or create a new dish to bring the table back to life.</p>

                            <div class="mt-5 flex flex-wrap gap-3">
                                <button
                                    type="button"
                                    class="inline-flex items-center justify-center rounded-full border border-[#d6e7e7] bg-white px-5 py-3 text-xs font-semibold uppercase tracking-[0.18em] text-slate-700"
                                    @click="resetFilters"
                                >
                                    Reset filters
                                </button>

                                <Link
                                    :href="route('merchant.menu.create')"
                                    class="inline-flex items-center justify-center rounded-full bg-[var(--brand-orange)] px-5 py-3 text-xs font-semibold uppercase tracking-[0.2em] text-slate-950"
                                >
                                    Create menu item
                                </Link>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </MerchantLayout>
</template>