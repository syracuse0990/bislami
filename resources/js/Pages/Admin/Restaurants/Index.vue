<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import QueryPaginationLinks from '@/Components/Navigation/QueryPaginationLinks.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, reactive } from 'vue';

const props = defineProps({
    overview: {
        type: Object,
        default: () => ({
            totalRestaurants: 0,
            activeMerchants: 0,
            liveMenus: 0,
            activeOrders: 0,
        }),
    },
    categories: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({
            search: '',
            category: '',
            merchant: '',
        }),
    },
    restaurants: {
        type: Array,
        default: () => [],
    },
    restaurantsPagination: {
        type: Object,
        default: () => ({
            currentPage: 1,
            lastPage: 1,
            perPage: 0,
            total: 0,
            from: null,
            to: null,
        }),
    },
});

const filterForm = reactive({
    search: props.filters.search ?? '',
    category: props.filters.category || 'All',
    merchant: props.filters.merchant ?? '',
});

const categoryOptions = computed(() => ['All', ...props.categories]);
const appliedQuery = computed(() => ({
    search: props.filters.search || undefined,
    category: props.filters.category || undefined,
    merchant: props.filters.merchant || undefined,
}));

const applyFilters = () => {
    router.get(route('admin.restaurants.index'), {
        search: filterForm.search.trim() || undefined,
        category: filterForm.category !== 'All' ? filterForm.category : undefined,
        merchant: filterForm.merchant.trim() || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const resetFilters = () => {
    filterForm.search = '';
    filterForm.category = 'All';
    filterForm.merchant = '';
    router.get(route('admin.restaurants.index'), {}, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};
</script>

<template>
    <Head title="Admin Restaurants" />

    <AdminLayout>
        <template #header>
            <div class="space-y-3">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-[var(--brand-orange-deep)]">
                    Restaurant approvals
                </p>
                <h2 class="text-3xl font-semibold leading-tight text-slate-900">
                    Manage kitchens, menus, and merchant ownership from one place.
                </h2>
                <p class="max-w-2xl text-sm leading-6 text-slate-600">
                    Replace the old placeholder approvals list with real restaurants, live menu status, and public preview links.
                </p>
            </div>
        </template>

        <div class="py-8">
            <div class="space-y-6 px-4 sm:px-6 lg:px-0">
                <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <article class="rounded-[28px] border border-white/80 bg-white/82 p-5 shadow-[0_26px_70px_-46px_rgba(11,77,89,0.5)]">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-[var(--brand-teal)]">Restaurants</p>
                        <p class="mt-2 text-3xl font-semibold text-slate-900">{{ overview.totalRestaurants }}</p>
                        <p class="mt-2 text-sm leading-6 text-slate-600">Every kitchen currently registered in BizLami.</p>
                    </article>

                    <article class="rounded-[28px] border border-white/80 bg-white/82 p-5 shadow-[0_26px_70px_-46px_rgba(11,77,89,0.5)]">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-[var(--brand-orange-deep)]">Active merchants</p>
                        <p class="mt-2 text-3xl font-semibold text-slate-900">{{ overview.activeMerchants }}</p>
                        <p class="mt-2 text-sm leading-6 text-slate-600">Merchant accounts currently managing kitchens.</p>
                    </article>

                    <article class="rounded-[28px] border border-white/80 bg-white/82 p-5 shadow-[0_26px_70px_-46px_rgba(11,77,89,0.5)]">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-[var(--brand-orange-deep)]">Live menus</p>
                        <p class="mt-2 text-3xl font-semibold text-slate-900">{{ overview.liveMenus }}</p>
                        <p class="mt-2 text-sm leading-6 text-slate-600">Restaurants already exposed on the public browse surface.</p>
                    </article>

                    <article class="rounded-[28px] bg-[var(--brand-teal)] p-5 text-white shadow-[0_26px_70px_-40px_rgba(11,77,89,0.68)]">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-white/70">Active orders</p>
                        <p class="mt-2 text-3xl font-semibold">{{ overview.activeOrders }}</p>
                        <p class="mt-2 text-sm leading-6 text-white/80">Preparing and on-the-way workload across kitchens.</p>
                    </article>
                </section>

                <section class="rounded-[32px] border border-white/80 bg-[linear-gradient(135deg,#ffffff_0%,#fff8f1_58%,#f3fbfb_100%)] p-6 shadow-[0_34px_85px_-56px_rgba(11,77,89,0.6)] sm:p-8">
                    <form class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_220px_minmax(0,1fr)_auto_auto] lg:items-end" @submit.prevent="applyFilters">
                        <label class="space-y-2">
                            <span class="text-xs font-semibold uppercase tracking-[0.18em] text-[var(--brand-teal)]">Search kitchens</span>
                            <input
                                v-model="filterForm.search"
                                type="search"
                                placeholder="Search restaurant, cuisine, or owner"
                                class="w-full rounded-full border border-[#dde7e8] bg-white px-5 py-3 text-sm text-slate-700 shadow-[0_18px_45px_-34px_rgba(11,77,89,0.25)] outline-none transition placeholder:text-slate-400 focus:border-[#c4e0e4] focus:ring-2 focus:ring-[#d8edf0]"
                            >
                        </label>

                        <label class="space-y-2">
                            <span class="text-xs font-semibold uppercase tracking-[0.18em] text-[var(--brand-teal)]">Category</span>
                            <select
                                v-model="filterForm.category"
                                class="w-full rounded-full border border-[#dde7e8] bg-white px-5 py-3 text-sm text-slate-700 shadow-[0_18px_45px_-34px_rgba(11,77,89,0.25)] outline-none transition focus:border-[#c4e0e4] focus:ring-2 focus:ring-[#d8edf0]"
                            >
                                <option v-for="category in categoryOptions" :key="category" :value="category">
                                    {{ category === 'All' ? 'All categories' : category }}
                                </option>
                            </select>
                        </label>

                        <label class="space-y-2">
                            <span class="text-xs font-semibold uppercase tracking-[0.18em] text-[var(--brand-teal)]">Merchant</span>
                            <input
                                v-model="filterForm.merchant"
                                type="text"
                                placeholder="Filter by merchant name or email"
                                class="w-full rounded-full border border-[#dde7e8] bg-white px-5 py-3 text-sm text-slate-700 shadow-[0_18px_45px_-34px_rgba(11,77,89,0.25)] outline-none transition placeholder:text-slate-400 focus:border-[#c4e0e4] focus:ring-2 focus:ring-[#d8edf0]"
                            >
                        </label>

                        <button
                            type="submit"
                            class="inline-flex items-center justify-center rounded-full bg-[var(--brand-teal)] px-5 py-3 text-sm font-semibold text-white shadow-[0_20px_45px_-24px_rgba(11,77,89,0.85)] transition duration-200 hover:-translate-y-0.5"
                        >
                            Apply filters
                        </button>

                        <button
                            type="button"
                            class="inline-flex items-center justify-center rounded-full border border-white/80 bg-white px-5 py-3 text-sm font-semibold text-slate-700 shadow-[0_18px_42px_-32px_rgba(11,77,89,0.35)] transition duration-200 hover:text-[var(--brand-teal)]"
                            @click="resetFilters"
                        >
                            Reset
                        </button>
                    </form>
                </section>

                <section class="space-y-4">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-[var(--brand-teal)]">Restaurant directory</p>
                            <h3 class="mt-2 text-2xl font-semibold text-slate-900">Live kitchen records with menu and order health.</h3>
                        </div>

                        <div class="rounded-full border border-white/80 bg-white/82 px-4 py-2 text-sm font-medium text-slate-600 shadow-[0_18px_44px_-30px_rgba(11,77,89,0.4)]">
                            {{ restaurantsPagination.total }} restaurants found
                        </div>
                    </div>

                    <div v-if="restaurants.length" class="grid gap-4">
                        <article
                            v-for="restaurant in restaurants"
                            :key="restaurant.id"
                            class="rounded-[28px] border border-white/80 bg-white/88 p-5 shadow-[0_28px_70px_-48px_rgba(11,77,89,0.55)]"
                        >
                            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                <div class="flex items-start gap-4">
                                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-[#fff7ef] p-2 ring-1 ring-[#f6dcc5]">
                                        <img src="/images/bizlami_icon.png" alt="BizLami icon" class="h-full w-full object-contain">
                                    </div>

                                    <div class="space-y-3">
                                        <div>
                                            <div class="flex flex-wrap items-center gap-3">
                                                <h4 class="text-lg font-semibold text-slate-900">{{ restaurant.name }}</h4>
                                                <span
                                                    class="inline-flex rounded-full px-3 py-1 text-xs font-semibold ring-1"
                                                    :class="restaurant.statusAccent"
                                                >
                                                    {{ restaurant.statusLabel }}
                                                </span>
                                            </div>
                                            <p class="mt-1 text-sm text-slate-500">{{ restaurant.category }} · {{ restaurant.cuisine }}</p>
                                        </div>

                                        <div class="flex flex-wrap gap-2">
                                            <span class="rounded-full px-3 py-1 text-xs font-medium ring-1" :class="restaurant.visibilityAccent">
                                                {{ restaurant.visibilityLabel }}
                                            </span>
                                            <span class="rounded-full border border-[#dceced] bg-[#f4fbfb] px-3 py-1 text-xs font-medium text-slate-700">
                                                {{ restaurant.ratingLabel }}
                                            </span>
                                            <span class="rounded-full border border-[#ece7de] bg-white px-3 py-1 text-xs font-medium text-slate-700">
                                                {{ restaurant.deliveryFee }}
                                            </span>
                                            <span class="rounded-full border border-[#ece7de] bg-white px-3 py-1 text-xs font-medium text-slate-700">
                                                Updated {{ restaurant.updatedAt }}
                                            </span>
                                        </div>

                                        <p class="text-sm leading-6 text-slate-600">{{ restaurant.featured }}</p>

                                        <div class="rounded-[22px] bg-[#f9fbfb] px-4 py-3 ring-1 ring-[#e2eded]">
                                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[var(--brand-teal)]">Merchant owner</p>
                                            <p class="mt-2 text-sm font-semibold text-slate-900">{{ restaurant.merchantName ?? 'Unassigned merchant' }}</p>
                                            <p class="mt-1 text-sm text-slate-500">{{ restaurant.merchantEmail ?? 'No contact email available' }}</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="grid gap-3 sm:grid-cols-3 lg:min-w-[340px] lg:max-w-[360px]">
                                    <div class="rounded-[22px] bg-[#f4fbfb] px-4 py-3 ring-1 ring-[#dceced]">
                                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[var(--brand-teal)]">Menu items</p>
                                        <p class="mt-2 text-sm font-semibold text-slate-900">{{ restaurant.menuItemsCount }}</p>
                                    </div>

                                    <div class="rounded-[22px] bg-[#fff7ef] px-4 py-3 ring-1 ring-[#f6dcc5]">
                                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[var(--brand-orange-deep)]">Live dishes</p>
                                        <p class="mt-2 text-sm font-semibold text-slate-900">{{ restaurant.availableMenuItemsCount }}</p>
                                    </div>

                                    <div class="rounded-[22px] bg-white px-4 py-3 ring-1 ring-[#ececec]">
                                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Active orders</p>
                                        <p class="mt-2 text-sm font-semibold text-slate-900">{{ restaurant.activeOrdersCount }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-4 flex flex-wrap gap-3 border-t border-[#edf2f2] pt-4">
                                <Link
                                    :href="restaurant.detailUrl"
                                    class="inline-flex items-center justify-center rounded-full bg-gradient-to-r from-[var(--brand-teal)] to-[var(--brand-orange)] px-5 py-3 text-sm font-semibold text-white shadow-[0_20px_45px_-24px_rgba(11,77,89,0.85)] transition duration-200 hover:-translate-y-0.5"
                                >
                                    Open admin view
                                </Link>

                                <Link
                                    v-if="restaurant.publicMenuUrl"
                                    :href="restaurant.publicMenuUrl"
                                    class="inline-flex items-center justify-center rounded-full border border-white/80 bg-white px-5 py-3 text-sm font-semibold text-slate-700 shadow-[0_18px_42px_-32px_rgba(11,77,89,0.35)] transition duration-200 hover:text-[var(--brand-teal)]"
                                >
                                    Open public menu
                                </Link>

                                <a
                                    v-if="restaurant.merchantMailUrl"
                                    :href="restaurant.merchantMailUrl"
                                    class="inline-flex items-center justify-center rounded-full border border-white/80 bg-white px-5 py-3 text-sm font-semibold text-slate-700 shadow-[0_18px_42px_-32px_rgba(11,77,89,0.35)] transition duration-200 hover:text-[var(--brand-teal)]"
                                >
                                    Email merchant
                                </a>
                            </div>
                        </article>
                    </div>

                    <QueryPaginationLinks
                        v-if="restaurants.length"
                        route-name="admin.restaurants.index"
                        :query="appliedQuery"
                        :pagination="restaurantsPagination"
                        item-label="restaurants"
                    />

                    <section v-else class="rounded-[32px] border border-white/80 bg-white/82 p-8 shadow-[0_30px_80px_-52px_rgba(11,77,89,0.55)]">
                        <h3 class="text-2xl font-semibold text-slate-900">No restaurants match the current filters.</h3>
                        <p class="mt-3 max-w-2xl text-sm leading-7 text-slate-600">
                            Broaden the restaurant search or reset category and merchant filters to bring more kitchens back into view.
                        </p>
                    </section>
                </section>
            </div>
        </div>
    </AdminLayout>
</template>