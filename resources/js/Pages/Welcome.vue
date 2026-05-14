<script setup>
import ApplicationLogo from '@/Components/Branding/ApplicationLogo.vue';
import AddToCartButton from '@/Components/Cart/AddToCartButton.vue';
import PaginationLinks from '@/Components/Navigation/PaginationLinks.vue';
import PublicSeoHead from '@/Components/Seo/PublicSeoHead.vue';
import { useDiscoveryFilterState } from '@/Composables/useDiscoveryFilterState';
import { toAbsoluteUrl } from '@/Support/seo';
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    canLogin: {
        type: Boolean,
        default: false,
    },
    canRegister: {
        type: Boolean,
        default: false,
    },
    cuisines: {
        type: Array,
        default: () => [],
    },
    foodCategories: {
        type: Array,
        default: () => [],
    },
    foods: {
        type: Array,
        default: () => [],
    },
    foodsPagination: {
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
    restaurants: {
        type: Array,
        default: () => [],
    },
    stats: {
        type: Object,
        default: () => ({
            restaurantsCount: 0,
            foodsCount: 0,
            averageDeliveryMinutes: 0,
            highestRatedRestaurant: null,
        }),
    },
    filters: {
        type: Object,
        default: () => ({
            search: '',
            cuisine: '',
            category: '',
        }),
    },
});

const { search, activeCuisine, activeCategory } = useDiscoveryFilterState(props.filters);
const page = usePage();
const shoppingCart = computed(() => page.props.shoppingCart ?? {
    itemsCount: 0,
});

const topPicks = computed(() => props.foods.slice(0, 3));
const featuredRestaurants = computed(() => props.restaurants.slice(0, 4));
const homepageRestaurantMenus = computed(() => props.restaurants
    .filter((restaurant) => Array.isArray(restaurant.menuPreview) && restaurant.menuPreview.length > 0)
    .slice(0, 3));
const heroHighlight = computed(() => topPicks.value[0] ?? null);
const heroImage = computed(() => heroHighlight.value?.imageUrl ?? featuredRestaurants.value[0]?.featuredImageUrl ?? '/images/demo-foods/loaded-rice-bowl.svg');
const visibleFoodCategories = computed(() => {
    if (props.foodCategories.length) {
        return props.foodCategories;
    }

    return props.foods
        .map((food) => food.category)
        .filter((category, index, categories) => category && categories.indexOf(category) === index);
});
const cuisineOptions = computed(() => ['All', ...props.cuisines]);
const categoryOptions = computed(() => ['All', ...visibleFoodCategories.value]);
const categoryCards = computed(() => visibleFoodCategories.value
    .slice(0, 8)
    .map((category) => {
        const matchedFood = props.foods.find((food) => food.category === category)
            ?? topPicks.value.find((food) => food.category === category)
            ?? props.foods[0]
            ?? topPicks.value[0]
            ?? null;

        return {
            label: category,
            imageUrl: matchedFood?.imageUrl ?? '/images/bizlami_icon.png',
            restaurantName: matchedFood?.restaurantName ?? 'Browse dishes',
            isActive: activeCategory.value === category,
        };
    }));
const cartItemsCount = computed(() => shoppingCart.value.itemsCount ?? 0);
const siteName = computed(() => page.props.seo?.siteName ?? 'BizLami');
const appUrl = computed(() => page.props.seo?.appUrl ?? '');
const seoDescription = computed(() => `Browse ${props.stats.foodsCount} live dishes from ${props.stats.restaurantsCount} kitchens on ${siteName.value} with one clear search card, simple category shortcuts, per-kitchen menu previews, and foods listed where they matter.`);
const seoImageUrl = computed(() => heroImage.value);
const seoStructuredData = computed(() => [
    {
        '@context': 'https://schema.org',
        '@type': 'WebSite',
        name: siteName.value,
        url: route('home'),
        potentialAction: {
            '@type': 'SearchAction',
            target: `${route('home')}?search={search_term_string}`,
            'query-input': 'required name=search_term_string',
        },
    },
    {
        '@context': 'https://schema.org',
        '@type': 'CollectionPage',
        name: `${siteName.value} Food Discovery`,
        url: route('home'),
        description: seoDescription.value,
        mainEntity: {
            '@type': 'ItemList',
            itemListElement: topPicks.value.map((food, index) => ({
                '@type': 'ListItem',
                position: index + 1,
                url: route('foods.show', food.slug),
                name: food.name,
                image: toAbsoluteUrl(appUrl.value, food.imageUrl),
            })),
        },
    },
]);
const hasActiveFilters = computed(() => Boolean(
    search.value.trim()
    || activeCuisine.value !== 'All'
    || activeCategory.value !== 'All',
));
const featuredKitchenLine = computed(() => {
    if (featuredRestaurants.value.length === 0) {
        return 'Search for a dish first, then jump into the kitchen menu when you are ready.';
    }

    return `Popular kitchens include ${featuredRestaurants.value.slice(0, 2).map((restaurant) => restaurant.name).join(' and ')}.`;
});
const resultLabel = computed(() => {
    const { total, from, to } = props.foodsPagination;

    if (! total) {
        return 'No live dishes match this filter set.';
    }

    if (from !== null && to !== null) {
        return `Showing ${from}-${to} of ${total} live dishes`;
    }

    return `${total} live dishes available`;
});

function resetFilters() {
    search.value = '';
    activeCuisine.value = 'All';
    activeCategory.value = 'All';
}

function activateCategory(category) {
    activeCategory.value = category;

    if (typeof window !== 'undefined') {
        document.getElementById('foods')?.scrollIntoView({
            behavior: 'smooth',
            block: 'start',
        });
    }
}
</script>

<template>
    <PublicSeoHead
        title="Food Discovery"
        :description="seoDescription"
        :canonical-url="route('home')"
        :image-url="seoImageUrl"
        :structured-data="seoStructuredData"
    />

    <div class="relative min-h-screen overflow-hidden bg-[linear-gradient(180deg,#faf7f1_0%,#ffffff_45%,#f7fbfb_100%)] text-slate-900">
        <div class="pointer-events-none absolute inset-0">
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,rgba(11,77,89,0.08),transparent_28%),radial-gradient(circle_at_bottom_right,rgba(217,107,18,0.08),transparent_24%)]"></div>
        </div>

        <div class="relative mx-auto max-w-7xl px-6 py-8 lg:px-8">
            <header class="flex flex-col gap-4 py-4 lg:flex-row lg:items-center lg:justify-between">
                <Link :href="route('home')" class="flex items-center gap-3">
                    <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-white/92 p-2 shadow-[0_20px_48px_-32px_rgba(11,77,89,0.45)]">
                        <img
                            src="/images/bizlami_icon.png"
                            alt="BizLami icon"
                            class="h-full w-full object-contain"
                        >
                    </div>

                    <div class="hidden h-14 w-[224px] items-center rounded-[24px] bg-white/92 px-3 py-2 shadow-[0_20px_48px_-32px_rgba(11,77,89,0.45)] sm:flex">
                        <ApplicationLogo class="h-full w-full" />
                    </div>
                </Link>

                <nav class="flex flex-wrap items-center gap-3">
                    <Link
                        :href="route('restaurants.index')"
                        class="inline-flex items-center rounded-full border border-white/80 bg-white/80 px-5 py-3 text-sm font-semibold text-slate-700 transition duration-200 hover:text-[var(--brand-teal)]"
                    >
                        All kitchens
                    </Link>

                    <Link
                        :href="route('cart.index')"
                        class="inline-flex items-center gap-2 rounded-full border border-white/80 bg-white/90 px-5 py-3 text-sm font-semibold text-slate-700 shadow-[0_18px_42px_-30px_rgba(11,77,89,0.22)] transition duration-200 hover:text-[var(--brand-teal)]"
                    >
                        Cart

                        <span class="inline-flex min-w-6 items-center justify-center rounded-full bg-[var(--brand-teal)] px-2 py-0.5 text-xs font-semibold text-white">
                            {{ cartItemsCount }}
                        </span>
                    </Link>

                    <Link
                        v-if="canLogin"
                        :href="route('login')"
                        class="inline-flex items-center rounded-full border border-white/80 bg-white/80 px-5 py-3 text-sm font-semibold text-slate-700 transition duration-200 hover:text-[var(--brand-teal)]"
                    >
                        Log in
                    </Link>

                    <Link
                        v-if="canRegister"
                        :href="route('register')"
                        class="inline-flex items-center rounded-full bg-gradient-to-r from-[var(--brand-teal)] to-[var(--brand-orange)] px-5 py-3 text-sm font-semibold text-white shadow-[0_20px_45px_-24px_rgba(11,77,89,0.85)] transition duration-200 hover:-translate-y-0.5"
                    >
                        Create account
                    </Link>
                </nav>
            </header>

            <main class="space-y-14 pb-16 pt-8">
                <section class="overflow-hidden rounded-[38px] border border-white/80 bg-white shadow-[0_36px_90px_-60px_rgba(15,23,42,0.32)]">
                    <div class="relative min-h-[460px]">
                        <img
                            :src="heroImage"
                            alt="Featured BizLami dish"
                            class="absolute inset-0 h-full w-full object-cover"
                        >
                        <div class="absolute inset-0 bg-[linear-gradient(90deg,rgba(255,255,255,0.96)_0%,rgba(255,255,255,0.82)_30%,rgba(15,23,42,0.18)_70%,rgba(15,23,42,0.08)_100%)]"></div>

                        <div class="relative flex min-h-[460px] flex-col justify-between gap-6 p-5 sm:p-8 lg:p-10">
                            <div class="flex flex-wrap gap-2">
                                <span class="rounded-full border border-white/70 bg-white/88 px-4 py-2 text-[11px] font-semibold uppercase tracking-[0.22em] text-[var(--brand-orange-deep)] shadow-[0_14px_28px_-22px_rgba(197,92,24,0.3)]">
                                    Food delivery made simple
                                </span>

                                <span class="rounded-full border border-white/70 bg-white/88 px-4 py-2 text-sm font-medium text-slate-600 shadow-[0_14px_28px_-22px_rgba(11,77,89,0.18)]">
                                    {{ resultLabel }}
                                </span>
                            </div>

                            <div class="grid gap-6 lg:grid-cols-[420px_minmax(0,1fr)] lg:items-end">
                                <div class="rounded-[32px] bg-white/95 p-6 shadow-[0_28px_70px_-42px_rgba(15,23,42,0.35)] backdrop-blur sm:p-7">
                                    <p class="text-xs font-semibold uppercase tracking-[0.22em] text-[var(--brand-teal)]">Start with food</p>
                                    <h1 class="mt-3 text-4xl font-semibold leading-tight text-slate-900 sm:text-[2.7rem]">
                                        What should we deliver today?
                                    </h1>
                                    <p class="mt-3 text-sm leading-7 text-slate-600">
                                        Search dishes, choose a cuisine or dish type, and add to cart now. BizLami only asks you to log in or create an account when you checkout.
                                    </p>

                                    <div class="mt-6 space-y-3">
                                        <div class="relative">
                                            <span class="pointer-events-none absolute inset-y-0 left-4 flex items-center text-slate-400">
                                                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M14.1667 14.1667L17.5 17.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                                                    <circle cx="8.75" cy="8.75" r="5.75" stroke="currentColor" stroke-width="1.8" />
                                                </svg>
                                            </span>

                                            <input
                                                id="homepage-search"
                                                v-model="search"
                                                type="search"
                                                placeholder="Search dishes or kitchens"
                                                class="w-full rounded-2xl border border-[#dde7e8] bg-white px-12 py-4 text-sm text-slate-700 shadow-[0_18px_40px_-30px_rgba(11,77,89,0.22)] outline-none transition placeholder:text-slate-400 focus:border-[#c4e0e4] focus:ring-2 focus:ring-[#d8edf0]"
                                            >
                                        </div>

                                        <div class="grid gap-3 sm:grid-cols-2">
                                            <label class="space-y-2 text-sm font-medium text-slate-600">
                                                <span class="block text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-500">Cuisine</span>
                                                <select
                                                    v-model="activeCuisine"
                                                    class="w-full rounded-2xl border border-[#dde7e8] bg-white px-4 py-4 text-sm text-slate-700 outline-none transition focus:border-[#c4e0e4] focus:ring-2 focus:ring-[#d8edf0]"
                                                >
                                                    <option v-for="cuisine in cuisineOptions" :key="cuisine" :value="cuisine">
                                                        {{ cuisine }}
                                                    </option>
                                                </select>
                                            </label>

                                            <label class="space-y-2 text-sm font-medium text-slate-600">
                                                <span class="block text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-500">Dish type</span>
                                                <select
                                                    v-model="activeCategory"
                                                    class="w-full rounded-2xl border border-[#dde7e8] bg-white px-4 py-4 text-sm text-slate-700 outline-none transition focus:border-[#c4e0e4] focus:ring-2 focus:ring-[#d8edf0]"
                                                >
                                                    <option v-for="category in categoryOptions" :key="category" :value="category">
                                                        {{ category }}
                                                    </option>
                                                </select>
                                            </label>
                                        </div>

                                        <div class="flex flex-col gap-3 sm:flex-row">
                                            <a
                                                href="#foods"
                                                class="inline-flex items-center justify-center rounded-2xl bg-[var(--brand-teal)] px-5 py-4 text-sm font-semibold text-white shadow-[0_18px_42px_-24px_rgba(11,77,89,0.62)] transition duration-200 hover:-translate-y-0.5"
                                            >
                                                Search food
                                            </a>

                                            <button
                                                v-if="hasActiveFilters"
                                                type="button"
                                                class="inline-flex items-center justify-center rounded-2xl border border-white/80 bg-white px-5 py-4 text-sm font-semibold text-slate-700 shadow-[0_16px_34px_-28px_rgba(11,77,89,0.18)] transition duration-200 hover:text-[var(--brand-teal)]"
                                                @click="resetFilters"
                                            >
                                                Reset
                                            </button>
                                        </div>

                                        <p class="text-xs leading-6 text-slate-500">
                                            {{ featuredKitchenLine }}
                                        </p>
                                    </div>
                                </div>

                                <div v-if="heroHighlight" class="hidden justify-end lg:flex">
                                    <div class="max-w-sm rounded-[28px] bg-slate-950/55 p-5 text-white backdrop-blur">
                                        <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-white/70">Featured now</p>
                                        <h2 class="mt-2 text-2xl font-semibold">{{ heroHighlight.name }}</h2>
                                        <p class="mt-2 text-sm leading-6 text-white/80">{{ heroHighlight.restaurantName }} · {{ heroHighlight.eta }}</p>

                                        <div class="mt-4 flex flex-wrap gap-2">
                                            <span class="rounded-full bg-white/12 px-3 py-1 text-xs font-semibold text-white">{{ heroHighlight.price }}</span>
                                            <span class="rounded-full bg-white/12 px-3 py-1 text-xs font-semibold text-white/90">{{ heroHighlight.category }}</span>
                                        </div>

                                        <AddToCartButton
                                            :menu-item-id="heroHighlight.id"
                                            :restaurant-id="heroHighlight.restaurantId"
                                            :restaurant-name="heroHighlight.restaurantName"
                                            :item-name="heroHighlight.name"
                                            route-name="cart.store"
                                            label="Add to cart"
                                            button-class="mt-5 inline-flex items-center justify-center rounded-full bg-white px-5 py-3 text-sm font-semibold text-slate-900 shadow-[0_18px_42px_-28px_rgba(15,23,42,0.45)] transition duration-200 hover:-translate-y-0.5"
                                        />

                                        <p class="mt-3 text-xs leading-6 text-white/75">
                                            Build the cart first. Account setup only happens at checkout.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="space-y-6">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-[var(--brand-teal)]">Categories</p>
                            <h2 class="mt-2 text-3xl font-semibold text-slate-900">There's something for everyone.</h2>
                            <p class="mt-2 max-w-2xl text-sm leading-7 text-slate-600">
                                Use a category shortcut when you already know the type of food you want.
                            </p>
                        </div>

                        <div class="flex flex-wrap gap-3">
                            <button
                                v-if="hasActiveFilters"
                                type="button"
                                class="inline-flex items-center justify-center rounded-full border border-white/80 bg-white px-5 py-3 text-sm font-semibold text-slate-700 shadow-[0_16px_34px_-28px_rgba(11,77,89,0.18)] transition duration-200 hover:text-[var(--brand-teal)]"
                                @click="resetFilters"
                            >
                                Show all
                            </button>

                            <Link
                                :href="route('restaurants.index')"
                                class="inline-flex items-center justify-center rounded-full border border-white/80 bg-white px-5 py-3 text-sm font-semibold text-slate-700 shadow-[0_16px_34px_-28px_rgba(11,77,89,0.18)] transition duration-200 hover:text-[var(--brand-teal)]"
                            >
                                View all kitchens
                            </Link>
                        </div>
                    </div>

                    <div v-if="categoryCards.length" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                        <button
                            v-for="card in categoryCards"
                            :key="card.label"
                            type="button"
                            class="group rounded-[26px] border border-white/85 bg-white p-4 text-left shadow-[0_24px_60px_-44px_rgba(15,23,42,0.24)] transition duration-200 hover:-translate-y-1 hover:shadow-[0_28px_70px_-42px_rgba(15,23,42,0.3)]"
                            @click="activateCategory(card.label)"
                        >
                            <div class="aspect-[16/10] overflow-hidden rounded-[20px] bg-[#eef5f6]">
                                <img
                                    v-if="card.imageUrl"
                                    :src="card.imageUrl"
                                    :alt="card.label"
                                    class="h-full w-full object-cover transition duration-500 group-hover:scale-105"
                                >
                                <img
                                    v-else
                                    src="/images/bizlami_icon.png"
                                    alt="BizLami icon"
                                    class="h-full w-full object-contain p-6"
                                >
                            </div>

                            <div class="mt-4 flex items-start justify-between gap-3">
                                <div>
                                    <h3 class="text-base font-semibold text-slate-900">{{ card.label }}</h3>
                                    <p class="mt-1 text-xs leading-5 text-slate-500">{{ card.restaurantName }}</p>
                                </div>

                                <span
                                    :class="card.isActive ? 'bg-[var(--brand-teal)] text-white' : 'bg-[#f4fbfb] text-[var(--brand-teal)]'"
                                    class="rounded-full px-3 py-1 text-xs font-semibold"
                                >
                                    {{ card.isActive ? 'Active' : 'Filter' }}
                                </span>
                            </div>
                        </button>
                    </div>
                </section>

                <section v-if="homepageRestaurantMenus.length" id="restaurant-menus" class="space-y-6">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-[var(--brand-teal)]">Kitchen menus</p>
                            <h2 class="mt-2 text-3xl font-semibold text-slate-900">Browse dishes by restaurant, right on the homepage.</h2>
                            <p class="mt-2 max-w-3xl text-sm leading-7 text-slate-600">
                                Compare a few live dishes from each kitchen without leaving discovery. Open the full menu when you want more choice, or add a dish straight into the cart from here.
                            </p>
                        </div>

                        <Link
                            :href="route('restaurants.index')"
                            class="inline-flex items-center justify-center rounded-full border border-white/80 bg-white px-5 py-3 text-sm font-semibold text-slate-700 shadow-[0_16px_34px_-28px_rgba(11,77,89,0.18)] transition duration-200 hover:text-[var(--brand-teal)]"
                        >
                            View all kitchens
                        </Link>
                    </div>

                    <div class="space-y-6">
                        <article
                            v-for="restaurant in homepageRestaurantMenus"
                            :key="restaurant.slug"
                            class="overflow-hidden rounded-[32px] border border-white/85 bg-white shadow-[0_24px_60px_-44px_rgba(15,23,42,0.24)]"
                        >
                            <div class="grid gap-6 p-5 lg:grid-cols-[320px_minmax(0,1fr)] lg:p-6">
                                <div class="rounded-[28px] bg-[linear-gradient(135deg,#f7fcfc_0%,#fff8f1_60%,#ffffff_100%)] p-5 ring-1 ring-[#edf2f2]">
                                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-[var(--brand-orange-deep)]">{{ restaurant.category }}</p>
                                    <div class="mt-3 flex items-start justify-between gap-4">
                                        <div>
                                            <h3 class="text-2xl font-semibold text-slate-900">{{ restaurant.name }}</h3>
                                            <p class="mt-2 text-sm leading-6 text-slate-600">{{ restaurant.cuisine }}</p>
                                        </div>

                                        <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-medium text-emerald-700 shadow-sm">
                                            {{ restaurant.rating }}
                                        </span>
                                    </div>

                                    <p class="mt-4 text-sm leading-7 text-slate-600">
                                        {{ restaurant.featured }}
                                    </p>

                                    <div class="mt-5 flex flex-wrap gap-2">
                                        <span class="rounded-full bg-[#f4fbfb] px-3 py-1 text-xs font-medium text-[var(--brand-teal)] ring-1 ring-[#dceced]">{{ restaurant.eta }}</span>
                                        <span class="rounded-full bg-white px-3 py-1 text-xs font-medium text-slate-600 ring-1 ring-[#e4eded]">{{ restaurant.deliveryFee }}</span>
                                        <span class="rounded-full bg-[#fffaf3] px-3 py-1 text-xs font-medium text-slate-600 ring-1 ring-[#f0dfcf]">{{ restaurant.menuItemsCount }} dishes</span>
                                    </div>

                                    <Link
                                        :href="route('restaurants.show', restaurant.slug)"
                                        class="mt-6 inline-flex items-center justify-center rounded-full border border-white/80 bg-white px-5 py-3 text-sm font-semibold text-slate-700 shadow-[0_18px_42px_-32px_rgba(11,77,89,0.35)] transition duration-200 hover:text-[var(--brand-teal)]"
                                    >
                                        Open full menu
                                    </Link>
                                </div>

                                <div class="grid gap-4 md:grid-cols-3">
                                    <article
                                        v-for="menuItem in restaurant.menuPreview"
                                        :key="`${restaurant.slug}-${menuItem.id}`"
                                        class="overflow-hidden rounded-[26px] border border-[#edf2f2] bg-[#fffcf8] shadow-[0_18px_45px_-36px_rgba(15,23,42,0.28)]"
                                    >
                                        <Link :href="route('foods.show', menuItem.slug)" class="block">
                                            <div class="aspect-[16/10] overflow-hidden bg-[#eef5f6]">
                                                <img
                                                    v-if="menuItem.imageUrl"
                                                    :src="menuItem.imageUrl"
                                                    :alt="menuItem.name"
                                                    class="h-full w-full object-cover transition duration-500 hover:scale-105"
                                                >
                                                <img
                                                    v-else
                                                    src="/images/bizlami_icon.png"
                                                    alt="BizLami icon"
                                                    class="h-full w-full object-contain p-8"
                                                >
                                            </div>
                                        </Link>

                                        <div class="p-4">
                                            <div class="flex items-start justify-between gap-3">
                                                <div>
                                                    <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-[var(--brand-orange-deep)]">{{ menuItem.category }}</p>
                                                    <h3 class="mt-2 text-lg font-semibold text-slate-900">{{ menuItem.name }}</h3>
                                                </div>

                                                <span class="rounded-full bg-[#f4fbfb] px-3 py-1.5 text-sm font-semibold text-[var(--brand-teal)] ring-1 ring-[#dceced]">
                                                    {{ menuItem.price }}
                                                </span>
                                            </div>

                                            <div class="mt-4 flex flex-wrap gap-3 border-t border-[#edf2f2] pt-4">
                                                <AddToCartButton
                                                    :menu-item-id="menuItem.id"
                                                    :restaurant-id="restaurant.id"
                                                    :restaurant-name="restaurant.name"
                                                    :item-name="menuItem.name"
                                                    :redirect-to="`${page.url}#restaurant-menus`"
                                                    route-name="cart.store"
                                                    label="Add to cart"
                                                    button-class="inline-flex items-center justify-center rounded-full bg-gradient-to-r from-[var(--brand-teal)] to-[var(--brand-orange)] px-4 py-2.5 text-sm font-semibold text-white shadow-[0_20px_45px_-24px_rgba(11,77,89,0.85)] transition duration-200 hover:-translate-y-0.5"
                                                />

                                                <Link
                                                    :href="route('foods.show', menuItem.slug)"
                                                    class="inline-flex items-center justify-center rounded-full border border-white/80 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-[0_16px_34px_-28px_rgba(11,77,89,0.18)] transition duration-200 hover:text-[var(--brand-teal)]"
                                                >
                                                    View dish
                                                </Link>
                                            </div>
                                        </div>
                                    </article>
                                </div>
                            </div>
                        </article>
                    </div>
                </section>

                <section id="foods" class="space-y-6">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-[var(--brand-teal)]">Foods</p>
                            <h2 class="mt-2 text-3xl font-semibold text-slate-900">Ready to browse.</h2>
                            <p class="mt-2 max-w-2xl text-sm leading-7 text-slate-600">
                                Simple cards, clear pricing, and only the details that help you decide.
                            </p>
                        </div>

                        <div class="flex flex-wrap items-center gap-3">
                            <div class="rounded-full border border-white/85 bg-white px-4 py-2 text-sm font-medium text-slate-600 shadow-[0_16px_34px_-28px_rgba(11,77,89,0.18)]">
                                {{ resultLabel }}
                            </div>

                            <Link
                                :href="route('restaurants.index')"
                                class="inline-flex items-center justify-center rounded-full border border-white/80 bg-white px-5 py-3 text-sm font-semibold text-slate-700 shadow-[0_16px_34px_-28px_rgba(11,77,89,0.18)] transition duration-200 hover:text-[var(--brand-teal)]"
                            >
                                Browse kitchens
                            </Link>
                        </div>
                    </div>

                    <div v-if="foods.length" class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
                        <article
                            v-for="food in foods"
                            :key="food.id"
                            class="overflow-hidden rounded-[28px] border border-white/85 bg-white shadow-[0_24px_60px_-44px_rgba(15,23,42,0.24)] transition duration-200 hover:-translate-y-1 hover:shadow-[0_28px_70px_-42px_rgba(15,23,42,0.3)]"
                        >
                            <Link :href="route('foods.show', food.slug)" class="block">
                                <div class="aspect-[16/10] overflow-hidden bg-[#eef5f6]">
                                    <img
                                        v-if="food.imageUrl"
                                        :src="food.imageUrl"
                                        :alt="food.name"
                                        class="h-full w-full object-cover transition duration-500 hover:scale-105"
                                    >
                                    <img
                                        v-else
                                        src="/images/bizlami_icon.png"
                                        alt="BizLami icon"
                                        class="h-full w-full object-contain p-8"
                                    >
                                </div>
                            </Link>

                            <div class="p-5">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-[var(--brand-orange-deep)]">{{ food.category }}</p>
                                        <h3 class="mt-2 text-xl font-semibold text-slate-900">{{ food.name }}</h3>
                                        <p class="mt-1 text-sm text-slate-500">{{ food.restaurantName }}</p>
                                    </div>

                                    <span class="rounded-full bg-[#f4fbfb] px-3 py-1.5 text-sm font-semibold text-[var(--brand-teal)] ring-1 ring-[#dceced]">
                                        {{ food.price }}
                                    </span>
                                </div>

                                <div class="mt-4 flex flex-wrap gap-2">
                                    <span class="rounded-full bg-[#f4fbfb] px-3 py-1 text-xs font-medium text-[var(--brand-teal)] ring-1 ring-[#dceced]">{{ food.eta }}</span>
                                    <span class="rounded-full bg-white px-3 py-1 text-xs font-medium text-slate-600 ring-1 ring-[#e4eded]">{{ food.rating }}</span>
                                    <span class="rounded-full bg-[#fffaf3] px-3 py-1 text-xs font-medium text-slate-600 ring-1 ring-[#f0dfcf]">{{ food.deliveryFee }}</span>
                                </div>

                                <p class="mt-4 text-sm leading-6 text-slate-600">
                                    {{ food.description }}
                                </p>

                                <div class="mt-5 flex flex-wrap gap-3 border-t border-[#edf2f2] pt-5">
                                    <AddToCartButton
                                        :menu-item-id="food.id"
                                        :restaurant-id="food.restaurantId"
                                        :restaurant-name="food.restaurantName"
                                        :item-name="food.name"
                                        route-name="cart.store"
                                        label="Add to cart"
                                    />

                                    <Link
                                        :href="route('foods.show', food.slug)"
                                        class="inline-flex items-center justify-center rounded-full border border-white/80 bg-white px-5 py-3 text-sm font-semibold text-slate-700 shadow-[0_16px_34px_-28px_rgba(11,77,89,0.18)] transition duration-200 hover:text-[var(--brand-teal)]"
                                    >
                                        View dish
                                    </Link>
                                </div>
                            </div>
                        </article>
                    </div>

                    <PaginationLinks
                        v-if="foods.length"
                        route-name="home"
                        :filters="filters"
                        :pagination="foodsPagination"
                        item-label="dishes"
                    />

                    <section v-else class="rounded-[32px] border border-white/85 bg-white p-8 shadow-[0_24px_60px_-44px_rgba(15,23,42,0.24)]">
                        <h3 class="text-2xl font-semibold text-slate-900">No dishes match that search yet.</h3>
                        <p class="mt-3 max-w-2xl text-sm leading-7 text-slate-600">
                            Try a broader search term, switch the cuisine or dish type, or reset everything to bring the full food list back.
                        </p>

                        <div class="mt-6 flex flex-wrap gap-3">
                            <button
                                v-if="hasActiveFilters"
                                type="button"
                                class="inline-flex items-center justify-center rounded-full bg-[var(--brand-teal)] px-5 py-3 text-sm font-semibold text-white shadow-[0_18px_42px_-28px_rgba(11,77,89,0.55)] transition duration-200 hover:-translate-y-0.5"
                                @click="resetFilters"
                            >
                                Reset filters
                            </button>

                            <Link
                                :href="route('restaurants.index')"
                                class="inline-flex items-center justify-center rounded-full border border-white/80 bg-white px-5 py-3 text-sm font-semibold text-slate-700 shadow-[0_16px_34px_-28px_rgba(11,77,89,0.18)] transition duration-200 hover:text-[var(--brand-teal)]"
                            >
                                Explore kitchens instead
                            </Link>
                        </div>
                    </section>
                </section>
            </main>
        </div>
    </div>
</template>
