<script setup>
import AddToCartButton from '@/Components/Cart/AddToCartButton.vue';
import ApplicationLogo from '@/Components/Branding/ApplicationLogo.vue';
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

const { search, activeCuisine } = useDiscoveryFilterState(props.filters);
const page = usePage();

const cuisineOptions = computed(() => ['All', ...props.cuisines]);
const bestRestaurants = computed(() => props.restaurants.slice(0, 3));
const viewerRole = computed(() => page.props.auth?.user?.role ?? null);
const shoppingCart = computed(() => page.props.shoppingCart ?? {
    itemsCount: 0,
});
const cartItemsCount = computed(() => shoppingCart.value.itemsCount ?? 0);
const isGuestViewer = computed(() => viewerRole.value === null);
const siteName = computed(() => page.props.seo?.siteName ?? 'BizLami');
const appUrl = computed(() => page.props.seo?.appUrl ?? '');
const seoDescription = computed(() => `Browse ${props.restaurantsPagination.total} kitchens on ${siteName.value}. Compare cuisines, featured dishes, ratings, and delivery windows before placing an order.`);
const seoImageUrl = computed(() => bestRestaurants.value[0]?.featuredImageUrl ?? '/images/bizlami_icon.png');
const directoryCopy = computed(() => (isGuestViewer.value
    ? 'This page is for scanning the full lineup: compare kitchens, search cuisine styles, and add a featured dish straight into your cart. BizLami only asks for an account at checkout.'
    : 'This page is for comparing kitchens and previewing menus. Food ordering stays limited to customer accounts.'));
const seoStructuredData = computed(() => [
    {
        '@context': 'https://schema.org',
        '@type': 'CollectionPage',
        name: `${siteName.value} Kitchens`,
        url: route('restaurants.index'),
        description: seoDescription.value,
        mainEntity: {
            '@type': 'ItemList',
            itemListElement: props.restaurants.map((restaurant, index) => ({
                '@type': 'ListItem',
                position: index + 1,
                url: route('restaurants.show', restaurant.slug),
                name: restaurant.name,
                image: toAbsoluteUrl(appUrl.value, restaurant.featuredImageUrl),
            })),
        },
    },
]);
</script>

<template>
    <PublicSeoHead
        title="Kitchens"
        :description="seoDescription"
        :canonical-url="route('restaurants.index')"
        :image-url="seoImageUrl"
        :structured-data="seoStructuredData"
    />

    <div class="relative overflow-hidden text-slate-900">
        <div class="pointer-events-none absolute inset-0">
            <div class="absolute left-[-6rem] top-12 h-72 w-72 rounded-full bg-[var(--brand-teal)]/10 blur-3xl"></div>
            <div class="absolute right-[-4rem] top-8 h-64 w-64 rounded-full bg-[var(--brand-orange)]/12 blur-3xl"></div>
            <div class="absolute bottom-[-8rem] right-1/4 h-80 w-80 rounded-full bg-[var(--brand-teal)]/8 blur-3xl"></div>
        </div>

        <div class="relative mx-auto min-h-screen max-w-7xl px-6 py-8 lg:px-8">
            <header class="flex flex-col gap-4 py-4 lg:flex-row lg:items-center lg:justify-between">
                <Link :href="route('home')" class="flex items-center gap-3">
                    <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-white/92 p-2 shadow-[0_20px_48px_-32px_rgba(11,77,89,0.45)]">
                        <img src="/images/bizlami_icon.png" alt="BizLami icon" class="h-full w-full object-contain">
                    </div>

                    <div class="hidden h-14 w-[224px] items-center rounded-[24px] bg-white/92 px-3 py-2 shadow-[0_20px_48px_-32px_rgba(11,77,89,0.45)] sm:flex">
                        <ApplicationLogo class="h-full w-full" />
                    </div>
                </Link>

                <nav class="flex flex-wrap items-center gap-3">
                    <Link
                        :href="route('home')"
                        class="inline-flex items-center rounded-full border border-white/80 bg-white/80 px-5 py-3 text-sm font-semibold text-slate-700 transition duration-200 hover:text-[var(--brand-teal)]"
                    >
                        Back to discovery
                    </Link>

                    <Link
                        v-if="isGuestViewer"
                        :href="route('cart.index')"
                        class="inline-flex items-center gap-2 rounded-full border border-white/80 bg-white/90 px-5 py-3 text-sm font-semibold text-slate-700 shadow-[0_18px_42px_-30px_rgba(11,77,89,0.22)] transition duration-200 hover:text-[var(--brand-teal)]"
                    >
                        Cart

                        <span class="inline-flex min-w-6 items-center justify-center rounded-full bg-[var(--brand-teal)] px-2 py-0.5 text-xs font-semibold text-white">
                            {{ cartItemsCount }}
                        </span>
                    </Link>

                    <Link
                        v-if="canLogin && isGuestViewer"
                        :href="route('login')"
                        class="inline-flex items-center rounded-full border border-white/80 bg-white/80 px-5 py-3 text-sm font-semibold text-slate-700 transition duration-200 hover:text-[var(--brand-teal)]"
                    >
                        Log in
                    </Link>

                    <Link
                        v-if="canRegister && isGuestViewer"
                        :href="route('register')"
                        class="inline-flex items-center rounded-full bg-gradient-to-r from-[var(--brand-teal)] to-[var(--brand-orange)] px-5 py-3 text-sm font-semibold text-white shadow-[0_20px_45px_-24px_rgba(11,77,89,0.85)] transition duration-200 hover:-translate-y-0.5"
                    >
                        Create account
                    </Link>
                </nav>
            </header>

            <main class="space-y-10 pb-12 pt-6">
                <section class="grid gap-8 lg:grid-cols-[1.05fr_0.95fr] lg:items-start">
                    <div class="space-y-7">
                        <div class="space-y-5">
                            <p class="inline-flex rounded-full border border-[#f5dcc7] bg-white/80 px-4 py-2 text-xs font-semibold uppercase tracking-[0.24em] text-[var(--brand-orange-deep)]">
                                Public kitchen directory
                            </p>
                            <h1 class="max-w-4xl text-5xl font-semibold leading-[0.92] text-slate-900 sm:text-6xl">
                                Browse every kitchen before you commit to an order.
                            </h1>
                            <p class="max-w-2xl text-lg leading-8 text-slate-600">
                                {{ directoryCopy }}
                            </p>
                        </div>

                        <div class="overflow-hidden rounded-[34px] border border-white/80 bg-white/82 p-5 shadow-[0_34px_90px_-58px_rgba(11,77,89,0.58)] backdrop-blur sm:p-6">
                            <label for="restaurant-search" class="text-xs font-semibold uppercase tracking-[0.22em] text-[var(--brand-teal)]">
                                Search kitchens, featured dishes, and cuisine styles
                            </label>

                            <div class="mt-3 flex flex-col gap-3 lg:flex-row">
                                <div class="relative flex-1">
                                    <span class="pointer-events-none absolute inset-y-0 left-4 flex items-center text-slate-400">
                                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M14.1667 14.1667L17.5 17.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                                            <circle cx="8.75" cy="8.75" r="5.75" stroke="currentColor" stroke-width="1.8" />
                                        </svg>
                                    </span>

                                    <input
                                        id="restaurant-search"
                                        v-model="search"
                                        type="search"
                                        placeholder="Try Green Bowl, Bengali, wrap, grill..."
                                        class="w-full rounded-full border border-[#dde7e8] bg-white px-12 py-4 text-sm text-slate-700 shadow-[0_18px_45px_-34px_rgba(11,77,89,0.35)] outline-none transition placeholder:text-slate-400 focus:border-[#c4e0e4] focus:ring-2 focus:ring-[#d8edf0]"
                                    >
                                </div>

                                <Link
                                    :href="route('home')"
                                    class="inline-flex items-center justify-center rounded-full bg-[var(--brand-teal)] px-6 py-4 text-sm font-semibold text-white shadow-[0_24px_55px_-28px_rgba(11,77,89,0.9)] transition duration-200 hover:-translate-y-0.5"
                                >
                                    Browse foods instead
                                </Link>
                            </div>

                            <div class="mt-4 flex flex-wrap gap-2">
                                <button
                                    v-for="cuisine in cuisineOptions"
                                    :key="cuisine"
                                    type="button"
                                    @click="activeCuisine = cuisine"
                                    :class="activeCuisine === cuisine
                                        ? 'bg-[var(--brand-teal)] text-white shadow-[0_16px_36px_-28px_rgba(11,77,89,0.7)]'
                                        : 'border border-[#e0ecec] bg-white text-slate-600'"
                                    class="rounded-full px-4 py-2 text-sm font-medium transition duration-200"
                                >
                                    {{ cuisine }}
                                </button>
                            </div>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-3">
                            <article class="rounded-[28px] border border-white/80 bg-white/80 p-5 shadow-[0_26px_70px_-46px_rgba(11,77,89,0.5)]">
                                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-[var(--brand-teal)]">Live kitchens</p>
                                <p class="mt-2 text-3xl font-semibold text-slate-900">{{ stats.restaurantsCount }}</p>
                                <p class="mt-2 text-sm leading-6 text-slate-600">Every public restaurant menu preview starts here.</p>
                            </article>

                            <article class="rounded-[28px] border border-white/80 bg-white/80 p-5 shadow-[0_26px_70px_-46px_rgba(11,77,89,0.5)]">
                                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-[var(--brand-orange-deep)]">Visible now</p>
                                <p class="mt-2 text-3xl font-semibold text-slate-900">{{ restaurantsPagination.total }}</p>
                                <p class="mt-2 text-sm leading-6 text-slate-600">Search narrows this list without losing the full catalog behind it.</p>
                            </article>

                            <article class="rounded-[28px] bg-[var(--brand-teal)] p-5 text-white shadow-[0_26px_70px_-40px_rgba(11,77,89,0.68)]">
                                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-white/70">Average delivery</p>
                                <p class="mt-2 text-3xl font-semibold">{{ stats.averageDeliveryMinutes }} min</p>
                                <p class="mt-2 text-sm leading-6 text-white/80">Easy to compare before signing in or choosing a menu.</p>
                            </article>
                        </div>
                    </div>

                    <section class="space-y-4">
                        <div class="overflow-hidden rounded-[36px] border border-white/80 bg-white/82 p-6 shadow-[0_36px_90px_-54px_rgba(11,77,89,0.6)] backdrop-blur sm:p-8">
                            <div class="flex items-center justify-between gap-4">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.22em] text-[var(--brand-orange-deep)]">Best starting points</p>
                                    <h2 class="mt-2 text-3xl font-semibold text-slate-900">Top kitchens at a glance.</h2>
                                </div>

                                <div class="hidden rounded-full bg-[#f3fbfb] px-4 py-2 text-sm font-medium text-[var(--brand-teal)] ring-1 ring-[#dceced] sm:inline-flex">
                                    {{ bestRestaurants.length }} highlighted
                                </div>
                            </div>

                            <div v-if="bestRestaurants.length" class="mt-6 space-y-4">
                                <Link
                                    v-for="restaurant in bestRestaurants"
                                    :key="`best-restaurant-${restaurant.slug}`"
                                    :href="route('restaurants.show', restaurant.slug)"
                                    class="block rounded-[28px] bg-[linear-gradient(135deg,#fffdf8_0%,#f3fbfb_100%)] p-5 ring-1 ring-white shadow-[0_20px_48px_-36px_rgba(11,77,89,0.42)]"
                                >
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-[var(--brand-teal)]">{{ restaurant.category }}</p>
                                            <h3 class="mt-2 text-xl font-semibold text-slate-900">{{ restaurant.name }}</h3>
                                            <p class="mt-2 text-sm text-slate-500">{{ restaurant.cuisine }}</p>
                                        </div>
                                        <span class="rounded-full bg-white px-3 py-1 text-xs font-semibold text-slate-700 ring-1 ring-[#e6eded]">{{ restaurant.rating }}</span>
                                    </div>

                                    <div class="mt-4 flex flex-wrap gap-2">
                                        <span class="rounded-full bg-white px-3 py-1 text-xs font-medium text-slate-600 ring-1 ring-[#ece7de]">{{ restaurant.featuredItem }}</span>
                                        <span class="rounded-full bg-white px-3 py-1 text-xs font-medium text-slate-600 ring-1 ring-[#ece7de]">{{ restaurant.eta }}</span>
                                        <span class="rounded-full bg-white px-3 py-1 text-xs font-medium text-slate-600 ring-1 ring-[#ece7de]">{{ restaurant.deliveryFee }}</span>
                                    </div>
                                </Link>
                            </div>

                            <div v-else class="mt-6 rounded-[28px] bg-[#fffaf3] p-5 text-sm leading-6 text-slate-600 ring-1 ring-[#f0dfcf]">
                                No kitchens match the current search yet. Try a broader keyword or switch cuisine.
                            </div>
                        </div>
                    </section>
                </section>

                <section class="space-y-6">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-[var(--brand-teal)]">All kitchens</p>
                            <h2 class="mt-2 text-3xl font-semibold text-slate-900">Jump straight into any menu preview.</h2>
                            <p class="mt-2 max-w-2xl text-sm leading-7 text-slate-600">
                                Every card opens a public menu page where guests can review dishes first and sign in only when they are ready to order.
                            </p>
                        </div>

                        <div class="rounded-full border border-white/80 bg-white/82 px-4 py-2 text-sm font-medium text-slate-600 shadow-[0_18px_44px_-30px_rgba(11,77,89,0.4)]">
                            {{ restaurantsPagination.total }} kitchens found
                        </div>
                    </div>

                    <div v-if="restaurants.length" class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
                        <article
                            v-for="restaurant in restaurants"
                            :key="restaurant.slug"
                            class="group overflow-hidden rounded-[30px] border border-white/80 bg-white/88 shadow-[0_30px_75px_-52px_rgba(11,77,89,0.55)] transition duration-300 hover:-translate-y-1 hover:shadow-[0_38px_85px_-50px_rgba(11,77,89,0.7)]"
                        >
                            <Link :href="route('restaurants.show', restaurant.slug)" class="block">
                                <div class="bg-[linear-gradient(135deg,#f4fbfb_0%,#fff7ef_62%,#ffffff_100%)] p-5">
                                    <div class="flex items-start justify-between gap-4">
                                        <div class="flex items-start gap-4">
                                            <div class="flex h-16 w-16 items-center justify-center overflow-hidden rounded-[20px] bg-white/90 p-2 shadow-[0_16px_35px_-25px_rgba(11,77,89,0.45)] ring-1 ring-white">
                                                <img v-if="restaurant.featuredImageUrl" :src="restaurant.featuredImageUrl" :alt="`${restaurant.featuredItem} image`" class="h-full w-full object-cover">
                                                <img v-else src="/images/bizlami_icon.png" alt="BizLami icon" class="h-full w-full object-contain">
                                            </div>

                                            <div>
                                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[var(--brand-orange-deep)]">{{ restaurant.category }}</p>
                                                <h3 class="mt-1 text-xl font-semibold text-slate-900">{{ restaurant.name }}</h3>
                                                <p class="mt-1 text-sm text-slate-500">{{ restaurant.cuisine }}</p>
                                            </div>
                                        </div>

                                        <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-medium text-emerald-700 shadow-sm">
                                            {{ restaurant.rating }}
                                        </span>
                                    </div>
                                </div>

                                <div class="space-y-5 p-5">
                                    <div class="grid gap-3 sm:grid-cols-2">
                                        <div class="rounded-[22px] bg-[#f4fbfb] px-4 py-3 ring-1 ring-[#dceced]">
                                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[var(--brand-teal)]">Delivery</p>
                                            <p class="mt-2 text-sm font-semibold text-slate-900">{{ restaurant.eta }}</p>
                                        </div>
                                        <div class="rounded-[22px] bg-[#fff7ef] px-4 py-3 ring-1 ring-[#f6dcc5]">
                                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[var(--brand-orange-deep)]">Fee</p>
                                            <p class="mt-2 text-sm font-semibold text-slate-900">{{ restaurant.deliveryFee }}</p>
                                        </div>
                                    </div>

                                    <div class="rounded-[26px] bg-[#fffaf3] p-4 ring-1 ring-[#f3dfcc]">
                                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[var(--brand-orange-deep)]">
                                            Featured item
                                        </p>
                                        <div class="mt-2 flex items-start justify-between gap-4">
                                            <div>
                                                <p class="text-sm font-semibold text-slate-900">{{ restaurant.featuredItem }}</p>
                                                <p class="mt-1 text-sm leading-6 text-slate-600">{{ restaurant.featured }}</p>
                                            </div>

                                            <span v-if="restaurant.featuredPrice" class="rounded-full bg-white px-3 py-1 text-xs font-semibold text-slate-700 ring-1 ring-[#e6eded] shadow-sm">
                                                {{ restaurant.featuredPrice }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </Link>

                            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-[#edf2f2] px-5 py-5 text-sm font-semibold text-slate-700">
                                <Link
                                    :href="route('restaurants.show', restaurant.slug)"
                                    class="inline-flex items-center justify-center rounded-full border border-white/80 bg-white px-5 py-3 text-sm font-semibold text-slate-700 shadow-[0_18px_42px_-32px_rgba(11,77,89,0.35)] transition duration-200 hover:text-[var(--brand-teal)]"
                                >
                                    View public menu
                                </Link>

                                <AddToCartButton
                                    v-if="isGuestViewer && restaurant.menuItemId"
                                    :menu-item-id="restaurant.menuItemId"
                                    :restaurant-id="restaurant.id"
                                    :restaurant-name="restaurant.name"
                                    :item-name="restaurant.featuredItem"
                                    :redirect-to="page.url"
                                    route-name="cart.store"
                                    label="Add featured dish"
                                />

                                <span
                                    v-else-if="!restaurant.menuItemId"
                                    class="inline-flex items-center justify-center rounded-full bg-[#eef4f4] px-5 py-3 text-sm font-semibold text-slate-400"
                                >
                                    Menu soon
                                </span>

                                <span
                                    v-else
                                    class="inline-flex items-center justify-center rounded-full bg-[#eef4f4] px-5 py-3 text-sm font-semibold text-slate-500"
                                >
                                    Customer ordering only
                                </span>
                            </div>
                        </article>
                    </div>

                    <PaginationLinks
                        v-if="restaurants.length"
                        route-name="restaurants.index"
                        :filters="filters"
                        :pagination="restaurantsPagination"
                        item-label="kitchens"
                    />

                    <section v-else class="rounded-[32px] border border-white/80 bg-white/82 p-8 shadow-[0_30px_80px_-52px_rgba(11,77,89,0.55)]">
                        <h3 class="text-2xl font-semibold text-slate-900">No kitchens match the current filters.</h3>
                        <p class="mt-3 max-w-2xl text-sm leading-7 text-slate-600">
                            Clear the search or choose a broader cuisine to bring more kitchens back into view.
                        </p>
                    </section>
                </section>
            </main>
        </div>
    </div>
</template>