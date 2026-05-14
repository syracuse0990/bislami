<script setup>
import SelectField from '@/Components/Forms/Fields/SelectField.vue';
import TextField from '@/Components/Forms/Fields/TextField.vue';
import MerchantLayout from '@/Layouts/MerchantLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed, reactive } from 'vue';

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
                            <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">
                                Keep the list simple, then open the create or edit page only when you actually need the full form.
                            </p>
                        </div>

                        <div class="rounded-full border border-white/80 bg-white/90 px-4 py-2 text-sm font-medium text-slate-700 shadow-[0_20px_48px_-36px_rgba(11,77,89,0.35)]">
                            {{ filteredMenuItems.length }} visible item{{ filteredMenuItems.length === 1 ? '' : 's' }}
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

                    <div class="mt-5 flex flex-wrap gap-3">
                        <button
                            type="button"
                            class="inline-flex items-center justify-center rounded-full border border-[#d6e7e7] bg-white px-5 py-3 text-xs font-semibold uppercase tracking-[0.18em] text-slate-700 transition duration-200 hover:-translate-y-0.5"
                            @click="resetFilters"
                        >
                            Reset filters
                        </button>

                        <Link
                            :href="route('merchant.menu.create')"
                            class="inline-flex items-center justify-center rounded-full border border-[#d0e2e3] bg-[#f7fbfb] px-5 py-3 text-xs font-semibold uppercase tracking-[0.18em] text-[var(--brand-teal)] transition duration-200 hover:-translate-y-0.5"
                        >
                            Create menu item
                        </Link>
                    </div>
                </section>

                <section class="overflow-hidden rounded-[32px] border border-white/80 bg-white/90 shadow-[0_30px_75px_-50px_rgba(11,77,89,0.5)]">
                    <div class="flex flex-col gap-4 border-b border-[#e7efef] px-6 py-5 sm:flex-row sm:items-end sm:justify-between sm:px-8">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-[var(--brand-teal)]">Menu list</p>
                            <h3 class="mt-2 text-2xl font-semibold text-slate-900">Everything in one place.</h3>
                            <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">
                                Each row keeps the essentials visible: pricing, restaurant, status, option counts, and a direct edit action.
                            </p>
                        </div>

                        <div class="rounded-full border border-[#dceced] bg-[#f8fbfb] px-4 py-2 text-sm font-medium text-slate-700">
                            Table-first workflow
                        </div>
                    </div>

                    <div v-if="filteredMenuItems.length" class="overflow-x-auto">
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
                                <tr v-for="menuItem in filteredMenuItems" :key="menuItem.id" class="align-top">
                                    <td class="px-6 py-5 sm:px-8">
                                        <div class="flex min-w-[280px] items-start gap-4">
                                            <div class="flex h-16 w-16 shrink-0 items-center justify-center overflow-hidden rounded-[20px] bg-[#f4fbfb] ring-1 ring-[#dceced]">
                                                <img
                                                    v-if="menuItem.imageUrl"
                                                    :src="menuItem.imageUrl"
                                                    :alt="`${menuItem.name} image`"
                                                    class="h-full w-full object-cover"
                                                >
                                                <img
                                                    v-else
                                                    src="/images/bizlami_icon.png"
                                                    alt="BizLami icon"
                                                    class="h-10 w-10 object-contain"
                                                >
                                            </div>

                                            <div class="min-w-0">
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <p class="text-sm font-semibold text-slate-900">{{ menuItem.name }}</p>
                                                    <span class="rounded-full bg-[#fff8f1] px-2.5 py-1 text-[11px] font-semibold uppercase tracking-[0.16em] text-[var(--brand-orange-deep)] ring-1 ring-[#f6dcc5]">
                                                        {{ menuItem.category }}
                                                    </span>
                                                </div>
                                                <p class="mt-2 max-w-xl text-sm leading-6 text-slate-600">{{ menuItem.description }}</p>
                                                <p class="mt-2 text-xs text-slate-500">{{ menuItem.updatedAt ? `Updated ${menuItem.updatedAt}` : 'Recently added' }}</p>
                                            </div>
                                        </div>
                                    </td>

                                    <td class="px-6 py-5">
                                        <p class="text-sm font-medium text-slate-900">{{ menuItem.restaurantName }}</p>
                                        <p class="mt-1 text-xs leading-5 text-slate-500">{{ menuItem.restaurantCuisine }}</p>
                                    </td>

                                    <td class="px-6 py-5">
                                        <p class="text-sm font-semibold text-slate-900">{{ menuItem.effectivePrice }}</p>
                                        <p class="mt-1 text-xs text-slate-500">Base {{ menuItem.basePrice }}</p>
                                        <p v-if="menuItem.promoPrice" class="mt-1 text-xs font-medium text-[var(--brand-orange-deep)]">Promo {{ menuItem.promoPrice }}</p>
                                    </td>

                                    <td class="px-6 py-5">
                                        <span
                                            class="inline-flex rounded-full px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.16em] ring-1"
                                            :class="menuItem.isAvailable
                                                ? 'bg-emerald-50 text-emerald-700 ring-emerald-200'
                                                : 'bg-slate-100 text-slate-600 ring-slate-200'"
                                        >
                                            {{ menuItem.availability }}
                                        </span>
                                        <p class="mt-2 text-xs leading-5 text-slate-500">{{ menuItem.availabilityWindowLabel || 'All day availability' }}</p>
                                    </td>

                                    <td class="px-6 py-5">
                                        <div class="flex min-w-[220px] flex-wrap gap-2">
                                            <span class="rounded-full bg-[#f8fbfb] px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.16em] text-[var(--brand-teal)] ring-1 ring-[#dceced]">
                                                {{ menuItem.counts.variants }} variants
                                            </span>
                                            <span class="rounded-full bg-[#f8fbfb] px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.16em] text-[var(--brand-teal)] ring-1 ring-[#dceced]">
                                                {{ menuItem.counts.addOns }} add-ons
                                            </span>
                                            <span class="rounded-full bg-[#f8fbfb] px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.16em] text-[var(--brand-teal)] ring-1 ring-[#dceced]">
                                                {{ menuItem.counts.modifiers }} modifiers
                                            </span>
                                            <span class="rounded-full bg-[#f8fbfb] px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.16em] text-[var(--brand-teal)] ring-1 ring-[#dceced]">
                                                {{ menuItem.counts.bundleItems }} bundles
                                            </span>
                                        </div>
                                    </td>

                                    <td class="px-6 py-5 sm:px-8">
                                        <Link
                                            :href="route('merchant.menu.edit', menuItem.id)"
                                            class="inline-flex items-center justify-center rounded-full border border-[#d0e2e3] bg-[#f7fbfb] px-4 py-2 text-[11px] font-semibold uppercase tracking-[0.18em] text-[var(--brand-teal)] transition duration-200 hover:-translate-y-0.5"
                                        >
                                            Edit item
                                        </Link>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
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