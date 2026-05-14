<script setup>
import ApplicationLogo from '@/Components/Branding/ApplicationLogo.vue';
import AddToCartButton from '@/Components/Cart/AddToCartButton.vue';
import PaginationLinks from '@/Components/Navigation/PaginationLinks.vue';
import PublicSeoHead from '@/Components/Seo/PublicSeoHead.vue';
import { useDiscoveryFilterState } from '@/Composables/useDiscoveryFilterState';
import { toAbsoluteUrl } from '@/Support/seo';
import { Link, usePage } from '@inertiajs/vue3';
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';

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
const heroCompanionFoods = computed(() => topPicks.value.slice(1, 3));
const heroImage = computed(() => heroHighlight.value?.imageUrl ?? featuredRestaurants.value[0]?.featuredImageUrl ?? '/images/demo-foods/loaded-rice-bowl.svg');
const isScrolled = ref(false);
const headerShell = ref(null);
const headerOffset = ref(136);
let headerResizeObserver = null;
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
const navigationLinks = computed(() => {
    const links = [
        { label: 'Categories', href: '#categories' },
    ];

    if (homepageRestaurantMenus.value.length) {
        links.push({ label: 'Kitchen menus', href: '#restaurant-menus' });
    }

    links.push({ label: 'Foods', href: '#foods' });

    return links;
});
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
const heroMetrics = computed(() => [
    {
        label: 'Live kitchens',
        value: props.stats.restaurantsCount || featuredRestaurants.value.length,
        accent: 'text-[var(--brand-teal)] bg-[#f2fbfb] ring-[#d7ecef]',
    },
    {
        label: 'Live dishes',
        value: props.stats.foodsCount || props.foodsPagination.total || props.foods.length,
        accent: 'text-[var(--brand-orange-deep)] bg-[#fff7ef] ring-[#f0ddd0]',
    },
    {
        label: 'Average delivery',
        value: props.stats.averageDeliveryMinutes ? `${props.stats.averageDeliveryMinutes} min` : 'Fast',
        accent: 'text-slate-700 bg-white ring-[#e3ebec]',
    },
]);
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

function updateScrollState() {
    if (typeof window === 'undefined') {
        return;
    }

    isScrolled.value = window.scrollY > 18;
}

function updateHeaderOffset() {
    if (typeof window === 'undefined') {
        return;
    }

    const measuredHeight = headerShell.value?.offsetHeight ?? 0;
    const gap = isScrolled.value ? 16 : 36;
    headerOffset.value = measuredHeight > 0 ? measuredHeight + gap : 136;
}

function observeHeaderSize() {
    if (typeof window === 'undefined' || typeof window.ResizeObserver === 'undefined' || ! headerShell.value) {
        return;
    }

    headerResizeObserver?.disconnect();
    headerResizeObserver = new window.ResizeObserver(() => {
        updateHeaderOffset();
    });
    headerResizeObserver.observe(headerShell.value);
}

onMounted(() => {
    updateScrollState();
    updateHeaderOffset();
    observeHeaderSize();

    if (typeof window !== 'undefined') {
        window.addEventListener('scroll', updateScrollState, { passive: true });
        window.addEventListener('resize', updateHeaderOffset, { passive: true });
    }
});

onBeforeUnmount(() => {
    if (typeof window !== 'undefined') {
        window.removeEventListener('scroll', updateScrollState);
        window.removeEventListener('resize', updateHeaderOffset);
    }

    headerResizeObserver?.disconnect();
    headerResizeObserver = null;
});

watch(isScrolled, async () => {
    await nextTick();
    updateHeaderOffset();
});
</script>

<template>
    <PublicSeoHead
        title="Food Discovery"
        :description="seoDescription"
        :canonical-url="route('home')"
        :image-url="seoImageUrl"
        :structured-data="seoStructuredData"
    />

    <div class="relative min-h-screen overflow-x-hidden bg-[linear-gradient(180deg,#fbf7f1_0%,#ffffff_42%,#f4fbfb_100%)] text-slate-900">
        <div class="soft-grid pointer-events-none absolute inset-0"></div>
        <div class="pointer-events-none absolute inset-0 overflow-hidden">
            <div class="ambient-orb absolute left-[-8rem] top-[-3rem] h-72 w-72 rounded-full bg-[var(--brand-teal)]/12 blur-3xl"></div>
            <div class="ambient-orb ambient-orb-delay absolute right-[-9rem] top-24 h-80 w-80 rounded-full bg-[var(--brand-orange)]/12 blur-3xl"></div>
            <div class="ambient-orb ambient-orb-slow absolute bottom-[-8rem] left-1/3 h-96 w-96 rounded-full bg-[#d4ecef] blur-3xl"></div>
        </div>

        <div
            :class="[
                'fixed inset-x-0 top-0 z-50 px-4 transition-all duration-500 ease-out sm:px-6 lg:px-8',
                isScrolled ? 'pt-2' : 'pt-4',
            ]"
        >
            <div ref="headerShell" class="mx-auto max-w-7xl">
                <header
                    :class="[
                        'nav-shell relative overflow-hidden border px-4 backdrop-blur-xl transition-all duration-500 ease-out sm:px-6',
                        isScrolled
                            ? 'rounded-[22px] border-white/90 bg-[rgba(255,255,255,0.92)] py-3 shadow-[0_24px_72px_-44px_rgba(15,23,42,0.3)]'
                            : 'rounded-[30px] border-white/70 bg-[rgba(255,255,255,0.68)] py-4 shadow-[0_22px_70px_-52px_rgba(15,23,42,0.26)]',
                    ]"
                >
                    <div class="absolute inset-x-10 top-0 h-px bg-[linear-gradient(90deg,transparent,rgba(11,77,89,0.32),transparent)]"></div>

                    <div
                        :class="[
                            'transition-all duration-500 ease-out',
                            isScrolled
                                ? 'flex items-center justify-between gap-3'
                                : 'flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between',
                        ]"
                    >
                        <div class="flex min-w-0 items-center justify-between gap-4">
                            <Link :href="route('home')" class="flex min-w-0 items-center gap-3">
                                <div
                                    :class="[
                                        'flex items-center justify-center overflow-hidden rounded-full border border-[rgba(11,77,89,0.12)] bg-[linear-gradient(145deg,rgba(255,255,255,0.98),rgba(241,250,251,0.94))] ring-1 ring-white/80 shadow-[0_22px_56px_-34px_rgba(11,77,89,0.28)] transition-all duration-500 ease-out group-hover:scale-[1.02]',
                                        isScrolled ? 'h-11 w-11 shrink-0' : 'h-[4.5rem] w-[4.5rem] shrink-0',
                                    ]"
                                >
                                    <img
                                        src="/images/bizlami_icon.png"
                                        alt="BizLami icon"
                                        :class="[
                                            'h-full w-full max-w-none object-cover mix-blend-multiply transition-transform duration-500 ease-out',
                                            isScrolled ? 'scale-[1]' : 'scale-[1]',
                                        ]"
                                    >
                                </div>

                                <div class="min-w-0 space-y-1">
                                    <div
                                        :class="[
                                            'hidden items-center overflow-hidden rounded-[25px] border border-[rgba(11,77,89,0.08)] bg-[linear-gradient(135deg,rgba(241,250,251,0.98),rgba(255,247,239,0.96))] ring-1 ring-white/80 shadow-[0_22px_56px_-38px_rgba(11,77,89,0.24)] transition-all duration-500 ease-out sm:flex',
                                            isScrolled ? 'h-11 w-[182px]' : 'h-16 w-[210px]',
                                        ]"
                                    >
                                        <ApplicationLogo
                                            fit="cover"
                                            class="h-full w-full max-w-none mix-blend-multiply transition-transform duration-500 ease-out"
                                            :class="isScrolled ? 'scale-[1] mt-4' : 'scale-[1]'"
                                        />
                                    </div>
                                    <div
                                        :class="[
                                            'hidden overflow-hidden transition-all duration-400 ease-out lg:block',
                                            isScrolled ? 'max-h-0 -translate-y-2 opacity-0' : 'max-h-12 translate-y-0 opacity-100',
                                        ]"
                                    >
                                        <p class="text-xs font-medium text-slate-500">
                                            Search food first. Checkout only when you are ready.
                                        </p>
                                    </div>
                                </div>
                            </Link>
                        </div>

                        <div
                            :class="[
                                'xl:min-w-0 xl:flex-1 transition-all duration-500 ease-out',
                                isScrolled ? 'flex min-w-0 items-center justify-end gap-2' : 'flex flex-col gap-3 xl:items-end',
                            ]"
                        >
                            <div
                                :class="[
                                    'overflow-hidden transition-all duration-400 ease-out',
                                    isScrolled ? 'max-h-0 -translate-y-2 opacity-0' : 'max-h-16 translate-y-0 opacity-100',
                                ]"
                            >
                                <nav class="flex flex-wrap items-center gap-2">
                                    <a
                                        v-for="item in navigationLinks"
                                        :key="item.href"
                                        :href="item.href"
                                        class="inline-flex items-center rounded-full border border-white/80 bg-white/75 px-4 py-2 text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 transition duration-300 hover:-translate-y-0.5 hover:border-[#d6e7e9] hover:bg-white hover:text-[var(--brand-teal)]"
                                    >
                                        {{ item.label }}
                                    </a>
                                </nav>
                            </div>

                            <div
                                :class="[
                                    'items-center xl:justify-end transition-all duration-500 ease-out',
                                    isScrolled ? 'flex flex-nowrap gap-2' : 'flex flex-wrap gap-2 sm:gap-3',
                                ]"
                            >
                                <Link
                                    :href="route('restaurants.index')"
                                    :class="[
                                        'inline-flex shrink-0 items-center rounded-full border border-white/80 bg-white/88 font-semibold text-slate-700 transition-all duration-300 hover:-translate-y-0.5 hover:text-[var(--brand-teal)]',
                                        isScrolled ? 'px-4 py-2.5 text-[13px]' : 'px-5 py-3 text-sm',
                                    ]"
                                >
                                    All kitchens
                                </Link>

                                <Link
                                    :href="route('cart.index')"
                                    :class="[
                                        'inline-flex shrink-0 items-center gap-2 rounded-full border border-white/85 bg-white font-semibold text-slate-700 shadow-[0_18px_42px_-30px_rgba(11,77,89,0.24)] transition-all duration-300 hover:-translate-y-0.5 hover:text-[var(--brand-teal)]',
                                        isScrolled ? 'px-4 py-2.5 text-[13px]' : 'px-5 py-3 text-sm',
                                    ]"
                                >
                                    Cart

                                    <span class="inline-flex min-w-6 items-center justify-center rounded-full bg-[var(--brand-teal)] px-2 py-0.5 text-xs font-semibold text-white">
                                        {{ cartItemsCount }}
                                    </span>
                                </Link>

                                <Link
                                    v-if="canLogin"
                                    :href="route('login')"
                                    :class="[
                                        'inline-flex shrink-0 items-center rounded-full border border-white/80 bg-white/88 font-semibold text-slate-700 transition-all duration-300 hover:-translate-y-0.5 hover:text-[var(--brand-teal)]',
                                        isScrolled ? 'px-4 py-2.5 text-[13px]' : 'px-5 py-3 text-sm',
                                    ]"
                                >
                                    Log in
                                </Link>

                                <Link
                                    v-if="canRegister"
                                    :href="route('register')"
                                    :class="[
                                        'inline-flex shrink-0 items-center rounded-full bg-gradient-to-r from-[var(--brand-teal)] via-[var(--brand-teal-deep)] to-[var(--brand-orange)] font-semibold text-white shadow-[0_24px_50px_-24px_rgba(11,77,89,0.72)] transition-all duration-300 hover:-translate-y-0.5 hover:shadow-[0_28px_60px_-24px_rgba(11,77,89,0.82)]',
                                        isScrolled ? 'px-4 py-2.5 text-[13px]' : 'px-5 py-3 text-sm',
                                    ]"
                                >
                                    Create account
                                </Link>
                            </div>
                        </div>
                    </div>
                </header>
            </div>
        </div>

        <div class="relative mx-auto max-w-7xl px-4 pb-16 sm:px-6 lg:px-8" :style="{ paddingTop: `${headerOffset}px` }">
            <main class="space-y-8 pb-12">
                <section class="reveal-rise overflow-hidden rounded-[40px] border border-white/80 bg-[linear-gradient(145deg,rgba(255,255,255,0.85),rgba(247,251,251,0.96))] p-4 shadow-[0_42px_110px_-68px_rgba(15,23,42,0.42)] sm:p-5 lg:p-6">
                    <div class="grid gap-5 xl:grid-cols-[minmax(0,1.02fr)_400px]">
                        <div class="space-y-5 rounded-[34px] bg-white/84 p-5 shadow-[0_28px_90px_-64px_rgba(15,23,42,0.28)] backdrop-blur sm:p-6">
                            <div class="flex flex-wrap justify-between gap-2">
                                <span class="rounded-full border border-white/70 bg-white/92 px-4 py-2 text-[11px] font-semibold uppercase tracking-[0.22em] text-[var(--brand-orange-deep)] shadow-[0_14px_28px_-22px_rgba(197,92,24,0.3)]">
                                    Bislig City discovery, hassle-free
                                </span>

                                <span class="rounded-full border border-[#d9eaec] bg-[#f2fbfb] px-4 py-2 text-sm font-medium text-[var(--brand-teal)] shadow-[0_14px_28px_-22px_rgba(11,77,89,0.18)]">
                                    {{ resultLabel }}
                                </span>
                            </div>

                            <div class="max-w-3xl space-y-3">
                                <h1 class="max-w-3xl text-4xl font-semibold leading-[1.02] text-slate-950 sm:text-[2.85rem]">
                                    Find your food fast, compare kitchens easily, and order with less effort.
                                </h1>

                                <p class="max-w-2xl text-base leading-7 text-slate-600 sm:text-[1.02rem]">
                                    BizLami is made for hungry people in Bislig City who want fast and easy food delivery. Search your favorite meals in one place, filter only when needed, add items to your cart right away, and create an account only when you're ready to order.
                                </p>
                            </div>

                            <div class="grid gap-3 sm:grid-cols-3">
                                <article
                                    v-for="metric in heroMetrics"
                                    :key="metric.label"
                                    :class="metric.accent"
                                    class="rounded-[24px] border border-white/80 p-4 shadow-[0_22px_48px_-42px_rgba(15,23,42,0.24)] ring-1"
                                >
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-500">{{ metric.label }}</p>
                                    <p class="mt-3 text-2xl font-semibold text-slate-950">{{ metric.value }}</p>
                                </article>
                            </div>

                            <div class="rounded-[30px] border border-white/80 bg-[linear-gradient(145deg,#ffffff_0%,#f8fcfc_100%)] p-4 shadow-[0_24px_58px_-46px_rgba(11,77,89,0.3)] ring-1 ring-[#e7f1f2] sm:p-4">
                                <div class="space-y-3">
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
                                            placeholder="Search dishes, kitchens, or cravings"
                                            class="w-full rounded-[22px] border border-[#dde7e8] bg-white px-12 py-4 text-sm text-slate-700 shadow-[0_18px_40px_-30px_rgba(11,77,89,0.16)] outline-none transition placeholder:text-slate-400 focus:border-[#c4e0e4] focus:ring-2 focus:ring-[#d8edf0]"
                                        >
                                    </div>

                                    <div class="grid gap-3 sm:grid-cols-2">
                                        <label class="space-y-2 text-sm font-medium text-slate-600">
                                            <span class="block text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-500">Cuisine</span>
                                            <select
                                                v-model="activeCuisine"
                                                class="w-full rounded-[22px] border border-[#dde7e8] bg-white px-4 py-4 text-sm text-slate-700 outline-none transition focus:border-[#c4e0e4] focus:ring-2 focus:ring-[#d8edf0]"
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
                                                class="w-full rounded-[22px] border border-[#dde7e8] bg-white px-4 py-4 text-sm text-slate-700 outline-none transition focus:border-[#c4e0e4] focus:ring-2 focus:ring-[#d8edf0]"
                                            >
                                                <option v-for="category in categoryOptions" :key="category" :value="category">
                                                    {{ category }}
                                                </option>
                                            </select>
                                        </label>
                                    </div>

                                    <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap">
                                        <a
                                            href="#foods"
                                            class="inline-flex items-center justify-center rounded-[22px] bg-[var(--brand-teal)] px-5 py-4 text-sm font-semibold text-white shadow-[0_18px_42px_-24px_rgba(11,77,89,0.62)] transition duration-300 hover:-translate-y-0.5"
                                        >
                                            Browse foods
                                        </a>

                                        <a
                                            v-if="homepageRestaurantMenus.length"
                                            href="#restaurant-menus"
                                            class="inline-flex items-center justify-center rounded-[22px] border border-white/80 bg-white px-5 py-4 text-sm font-semibold text-slate-700 shadow-[0_16px_34px_-28px_rgba(11,77,89,0.18)] transition duration-300 hover:-translate-y-0.5 hover:text-[var(--brand-teal)]"
                                        >
                                            Browse by kitchen
                                        </a>

                                        <button
                                            v-if="hasActiveFilters"
                                            type="button"
                                            class="inline-flex items-center justify-center rounded-[22px] border border-white/80 bg-white px-5 py-4 text-sm font-semibold text-slate-700 shadow-[0_16px_34px_-28px_rgba(11,77,89,0.18)] transition duration-300 hover:-translate-y-0.5 hover:text-[var(--brand-teal)]"
                                            @click="resetFilters"
                                        >
                                            Reset filters
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="float-card relative overflow-hidden rounded-[34px] bg-slate-950 text-white shadow-[0_42px_120px_-64px_rgba(15,23,42,0.6)]">
                            <img
                                :src="heroImage"
                                alt="Featured BizLami dish"
                                class="absolute inset-0 h-full w-full object-cover"
                            >
                            <div class="absolute inset-0 bg-[linear-gradient(180deg,rgba(7,15,19,0.12)_0%,rgba(7,15,19,0.46)_45%,rgba(7,15,19,0.82)_100%)]"></div>
                            <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(255,255,255,0.18),transparent_35%)]"></div>

                            <div class="relative flex h-full min-h-[460px] flex-col justify-between p-5 sm:p-5">
                                <div class="flex flex-wrap gap-2">
                                    <span class="rounded-full border border-white/15 bg-white/10 px-4 py-2 text-[11px] font-semibold uppercase tracking-[0.22em] text-white/85 backdrop-blur">
                                        Featured now
                                    </span>
                                    <span class="rounded-full border border-white/15 bg-white/10 px-4 py-2 text-sm font-medium text-white/80 backdrop-blur">
                                        Guest checkout friendly
                                    </span>
                                </div>

                                <div v-if="heroHighlight" class="space-y-5">
                                    <div class="rounded-[30px] border border-white/12 bg-[rgba(10,18,24,0.48)] p-4 backdrop-blur-xl shadow-[0_26px_80px_-52px_rgba(0,0,0,0.72)]">
                                        <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-white/60">
                                            {{ heroHighlight.restaurantCategory }}
                                        </p>
                                        <h2 class="mt-3 text-[1.9rem] font-semibold leading-tight text-white">{{ heroHighlight.name }}</h2>
                                        <p class="mt-3 text-sm leading-6 text-white/80">
                                            {{ heroHighlight.description }}
                                        </p>

                                        <div class="mt-4 flex flex-wrap gap-2">
                                            <span class="rounded-full bg-white/12 px-3 py-1 text-xs font-semibold text-white">{{ heroHighlight.price }}</span>
                                            <span class="rounded-full bg-white/12 px-3 py-1 text-xs font-semibold text-white/90">{{ heroHighlight.restaurantName }}</span>
                                            <span class="rounded-full bg-white/12 px-3 py-1 text-xs font-semibold text-white/90">{{ heroHighlight.eta }}</span>
                                        </div>

                                        <AddToCartButton
                                            :menu-item-id="heroHighlight.id"
                                            :restaurant-id="heroHighlight.restaurantId"
                                            :restaurant-name="heroHighlight.restaurantName"
                                            :item-name="heroHighlight.name"
                                            route-name="cart.store"
                                            label="Add featured dish"
                                            button-class="mt-5 inline-flex items-center justify-center rounded-full bg-white px-5 py-3 text-sm font-semibold text-slate-950 shadow-[0_18px_42px_-28px_rgba(15,23,42,0.45)] transition duration-300 hover:-translate-y-0.5"
                                        />

                                        <p class="mt-3 text-xs leading-6 text-white/72">
                                            Build the cart first. Login or account creation only happens when you checkout.
                                        </p>
                                    </div>

                                    <div v-if="heroCompanionFoods.length" class="grid gap-3 sm:grid-cols-2">
                                        <Link
                                            v-for="food in heroCompanionFoods"
                                            :key="food.id"
                                            :href="route('foods.show', food.slug)"
                                            class="group rounded-[24px] border border-white/12 bg-white/10 p-4 backdrop-blur transition duration-300 hover:-translate-y-0.5 hover:bg-white/14"
                                        >
                                            <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-white/60">{{ food.restaurantName }}</p>
                                            <h3 class="mt-2 text-base font-semibold text-white">{{ food.name }}</h3>
                                            <div class="mt-3 flex items-center justify-between gap-3 text-sm text-white/75">
                                                <span>{{ food.price }}</span>
                                                <span class="transition duration-300 group-hover:translate-x-0.5">View dish</span>
                                            </div>
                                        </Link>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section id="categories" class="reveal-rise reveal-delay-2 scroll-mt-32 space-y-6">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-[var(--brand-teal)]">Categories</p>
                            <h2 class="mt-2 text-3xl font-semibold text-slate-900">Start with the food you want, not the guessing.</h2>
                            <p class="mt-2 max-w-2xl text-sm leading-7 text-slate-600">
                                Pick a category and go straight to the food you want.
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
                            class="hover-card group rounded-[26px] border border-white/85 bg-white/92 p-4 text-left shadow-[0_24px_60px_-44px_rgba(15,23,42,0.24)] transition duration-300 hover:-translate-y-1 hover:shadow-[0_28px_70px_-42px_rgba(15,23,42,0.3)]"
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

                <section v-if="homepageRestaurantMenus.length" id="restaurant-menus" class="reveal-rise reveal-delay-3 scroll-mt-32 space-y-6">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-[var(--brand-teal)]">Kitchen menus</p>
                            <h2 class="mt-2 text-3xl font-semibold text-slate-900">Browse restaurant dishes right on the homepage.</h2>
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
                            class="hover-card overflow-hidden rounded-[32px] border border-white/85 bg-white/92 shadow-[0_24px_60px_-44px_rgba(15,23,42,0.24)]"
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
                                        class="hover-card overflow-hidden rounded-[26px] border border-[#edf2f2] bg-[#fffcf8] shadow-[0_18px_45px_-36px_rgba(15,23,42,0.28)]"
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

                <section id="foods" class="reveal-rise reveal-delay-4 scroll-mt-32 space-y-6">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-[var(--brand-teal)]">Foods</p>
                            <h2 class="mt-2 text-3xl font-semibold text-slate-900">Browse with confidence.</h2>
                            <p class="mt-2 max-w-2xl text-sm leading-7 text-slate-600">
                                Cleaner cards, clearer pricing, and only the details that help you decide quickly.
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
                            class="hover-card overflow-hidden rounded-[28px] border border-white/85 bg-white/92 shadow-[0_24px_60px_-44px_rgba(15,23,42,0.24)] transition duration-300 hover:-translate-y-1 hover:shadow-[0_28px_70px_-42px_rgba(15,23,42,0.3)]"
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

<style scoped>
:global(html) {
    scroll-behavior: smooth;
}

.soft-grid {
    background-image:
        linear-gradient(to right, rgba(148, 163, 184, 0.08) 1px, transparent 1px),
        linear-gradient(to bottom, rgba(148, 163, 184, 0.08) 1px, transparent 1px);
    background-size: 72px 72px;
    mask-image: linear-gradient(180deg, rgba(0, 0, 0, 0.38), transparent 82%);
}

.nav-shell {
    transform-origin: top center;
}

.ambient-orb {
    animation: orb-drift 16s ease-in-out infinite;
}

.ambient-orb-delay {
    animation-delay: 2.8s;
}

.ambient-orb-slow {
    animation-duration: 20s;
    animation-delay: 1.4s;
}

.float-card {
    animation: float-card 10s ease-in-out infinite;
}

.reveal-rise {
    opacity: 0;
    transform: translateY(22px);
    animation: reveal-rise 0.72s cubic-bezier(0.22, 1, 0.36, 1) forwards;
}

.reveal-delay-2 {
    animation-delay: 0.08s;
}

.reveal-delay-3 {
    animation-delay: 0.16s;
}

.reveal-delay-4 {
    animation-delay: 0.24s;
}

.reveal-delay-5 {
    animation-delay: 0.32s;
}

.hover-card {
    transition:
        transform 280ms ease,
        box-shadow 280ms ease,
        border-color 280ms ease,
        background-color 280ms ease;
}

.hover-card:hover {
    transform: translateY(-4px);
}

@keyframes reveal-rise {
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes float-card {
    0%,
    100% {
        transform: translateY(0);
    }

    50% {
        transform: translateY(-8px);
    }
}

@keyframes orb-drift {
    0%,
    100% {
        transform: translate3d(0, 0, 0) scale(1);
    }

    50% {
        transform: translate3d(16px, -18px, 0) scale(1.06);
    }
}
</style>
