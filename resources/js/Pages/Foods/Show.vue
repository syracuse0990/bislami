<script setup>
import ApplicationLogo from '@/Components/Branding/ApplicationLogo.vue';
import AddToCartButton from '@/Components/Cart/AddToCartButton.vue';
import PublicSeoHead from '@/Components/Seo/PublicSeoHead.vue';
import FoodSpotlightCard from '@/Features/customer/restaurants/components/FoodSpotlightCard.vue';
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
    food: {
        type: Object,
        required: true,
    },
});

const page = usePage();
const viewerRole = computed(() => page.props.auth?.user?.role ?? null);
const shoppingCart = computed(() => page.props.shoppingCart ?? {
    itemsCount: 0,
});

const siteName = computed(() => page.props.seo?.siteName ?? 'BizLami');
const appUrl = computed(() => page.props.seo?.appUrl ?? '');
const cartItemsCount = computed(() => shoppingCart.value.itemsCount ?? 0);
const isGuestViewer = computed(() => viewerRole.value === null);
const seoDescription = computed(() => `${props.food.name} from ${props.food.restaurant.name} on ${siteName.value}. ${props.food.description}`);
const restaurantRatingValue = computed(() => Number.parseFloat(props.food.restaurant.rating) || null);
const checkoutCopy = computed(() => (isGuestViewer.value
    ? 'Add this dish now. BizLami only asks you to log in or create an account when you continue to checkout.'
    : 'Customer accounts can place food orders. Use a customer login to continue to checkout.'));
const seoStructuredData = computed(() => [
    {
        '@context': 'https://schema.org',
        '@type': 'Product',
        name: props.food.name,
        url: route('foods.show', props.food.slug),
        image: toAbsoluteUrl(appUrl.value, props.food.imageUrl),
        description: props.food.description,
        category: props.food.category,
        brand: {
            '@type': 'Brand',
            name: siteName.value,
        },
        seller: {
            '@type': 'Restaurant',
            name: props.food.restaurant.name,
        },
        aggregateRating: restaurantRatingValue.value
            ? {
                '@type': 'AggregateRating',
                ratingValue: restaurantRatingValue.value,
                ratingCount: 1,
            }
            : undefined,
        offers: {
            '@type': 'Offer',
            priceCurrency: 'PHP',
            price: props.food.priceValue,
            availability: 'https://schema.org/InStock',
            url: route('foods.show', props.food.slug),
        },
    },
    {
        '@context': 'https://schema.org',
        '@type': 'BreadcrumbList',
        itemListElement: [
            {
                '@type': 'ListItem',
                position: 1,
                name: 'Food Discovery',
                item: route('home'),
            },
            {
                '@type': 'ListItem',
                position: 2,
                name: 'Kitchens',
                item: route('restaurants.index'),
            },
            {
                '@type': 'ListItem',
                position: 3,
                name: props.food.restaurant.name,
                item: route('restaurants.show', props.food.restaurant.slug),
            },
            {
                '@type': 'ListItem',
                position: 4,
                name: props.food.name,
                item: route('foods.show', props.food.slug),
            },
        ],
    },
]);
</script>

<template>
    <PublicSeoHead
        :title="food.name"
        :description="seoDescription"
        :canonical-url="route('foods.show', food.slug)"
        type="product"
        :image-url="food.imageUrl"
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
                        :href="route('restaurants.show', food.restaurant.slug)"
                        class="inline-flex items-center rounded-full border border-white/80 bg-white/80 px-5 py-3 text-sm font-semibold text-slate-700 transition duration-200 hover:text-[var(--brand-teal)]"
                    >
                        Restaurant menu
                    </Link>

                    <Link
                        :href="route('restaurants.index')"
                        class="inline-flex items-center rounded-full border border-white/80 bg-white/80 px-5 py-3 text-sm font-semibold text-slate-700 transition duration-200 hover:text-[var(--brand-teal)]"
                    >
                        All kitchens
                    </Link>

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

            <main class="space-y-8 pb-12 pt-6">
                <section class="overflow-hidden rounded-[32px] border border-white/80 bg-[linear-gradient(135deg,#ffffff_0%,#fff8f1_58%,#f3fbfb_100%)] p-6 shadow-[0_34px_85px_-56px_rgba(11,77,89,0.6)] sm:p-8">
                    <div class="grid gap-8 lg:grid-cols-[0.96fr_1.04fr] lg:items-center">
                        <div class="overflow-hidden rounded-[30px] bg-white/92 shadow-[0_24px_60px_-42px_rgba(11,77,89,0.45)] ring-1 ring-white">
                            <div class="aspect-[4/3] bg-[linear-gradient(135deg,#f4fbfb_0%,#fff7ef_62%,#ffffff_100%)]">
                                <img
                                    v-if="food.imageUrl"
                                    :src="food.imageUrl"
                                    :alt="food.name"
                                    class="h-full w-full object-cover"
                                >
                                <img
                                    v-else
                                    src="/images/bizlami_icon.png"
                                    alt="BizLami icon"
                                    class="h-full w-full object-contain p-10"
                                >
                            </div>
                        </div>

                        <div class="space-y-6">
                            <div class="inline-flex items-center gap-3 rounded-full border border-[#f5dcc7] bg-white/85 px-4 py-2 text-xs font-semibold uppercase tracking-[0.2em] text-[var(--brand-orange-deep)]">
                                <img src="/images/bizlami_icon.png" alt="BizLami icon" class="h-5 w-5 object-contain">
                                {{ food.category }}
                            </div>

                            <div class="space-y-3">
                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">
                                    {{ food.restaurant.category }}
                                </p>
                                <h1 class="text-4xl font-semibold text-slate-900 sm:text-5xl">
                                    {{ food.name }}
                                </h1>
                                <p class="max-w-2xl text-base leading-8 text-slate-600">
                                    {{ food.description }}
                                </p>
                            </div>

                            <div class="flex flex-wrap gap-3">
                                <span class="rounded-full bg-[var(--brand-teal)] px-4 py-2 text-sm font-semibold text-white shadow-[0_18px_40px_-30px_rgba(11,77,89,0.6)]">
                                    {{ food.price }}
                                </span>
                                <span class="rounded-full bg-emerald-50 px-4 py-2 text-sm font-medium text-emerald-700 shadow-sm">
                                    {{ food.restaurant.rating }}
                                </span>
                                <span class="rounded-full border border-[#deecec] bg-white/85 px-4 py-2 text-sm font-medium text-slate-700">
                                    {{ food.restaurant.eta }}
                                </span>
                                <span class="rounded-full border border-[#f6dcc5] bg-[#fff8f1] px-4 py-2 text-sm font-medium text-slate-700">
                                    {{ food.restaurant.deliveryFee }}
                                </span>
                            </div>

                            <div class="rounded-[28px] bg-white/90 p-5 shadow-[0_24px_60px_-42px_rgba(11,77,89,0.35)] ring-1 ring-white">
                                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-[var(--brand-teal)]">From the kitchen</p>
                                <div class="mt-3 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                                    <div>
                                        <h2 class="text-2xl font-semibold text-slate-900">{{ food.restaurant.name }}</h2>
                                        <p class="mt-2 max-w-2xl text-sm leading-7 text-slate-600">{{ food.restaurant.cuisine }}</p>
                                        <p class="mt-2 text-sm leading-7 text-slate-600">{{ food.restaurant.featured }}</p>
                                    </div>

                                    <Link
                                        :href="route('restaurants.show', food.restaurant.slug)"
                                        class="inline-flex items-center justify-center rounded-full border border-white/80 bg-white px-5 py-3 text-sm font-semibold text-slate-700 shadow-[0_18px_42px_-32px_rgba(11,77,89,0.35)] transition duration-200 hover:text-[var(--brand-teal)]"
                                    >
                                        View full menu
                                    </Link>
                                </div>
                            </div>

                            <div class="flex flex-wrap gap-3 border-t border-[#edf2f2] pt-4">
                                <AddToCartButton
                                    v-if="isGuestViewer"
                                    :menu-item-id="food.id"
                                    :restaurant-id="food.restaurant.id"
                                    :restaurant-name="food.restaurant.name"
                                    :item-name="food.name"
                                    route-name="cart.store"
                                    label="Add to cart"
                                />

                                <Link
                                    :href="route('restaurants.show', food.restaurant.slug)"
                                    class="inline-flex items-center justify-center rounded-full border border-white/80 bg-white px-5 py-3 text-sm font-semibold text-slate-700 shadow-[0_18px_42px_-32px_rgba(11,77,89,0.35)] transition duration-200 hover:text-[var(--brand-teal)]"
                                >
                                    Explore the kitchen
                                </Link>
                            </div>

                            <p class="text-sm leading-7 text-slate-600">
                                {{ checkoutCopy }}
                            </p>
                        </div>
                    </div>
                </section>

                <section v-if="food.relatedItems.length" class="space-y-4">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-[var(--brand-teal)]">More from this kitchen</p>
                            <h2 class="mt-2 text-2xl font-semibold text-slate-900">Keep browsing dishes without losing context.</h2>
                        </div>

                        <div class="rounded-full border border-white/80 bg-white/82 px-4 py-2 text-sm font-medium text-slate-600 shadow-[0_18px_44px_-30px_rgba(11,77,89,0.4)]">
                            {{ food.relatedItems.length }} more dishes nearby
                        </div>
                    </div>

                    <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
                        <FoodSpotlightCard
                            v-for="relatedItem in food.relatedItems"
                            :key="relatedItem.slug"
                            :food="relatedItem"
                        >
                            <template #actions="{ food: relatedFood }">
                                <AddToCartButton
                                    v-if="isGuestViewer"
                                    :menu-item-id="relatedFood.id"
                                    :restaurant-id="relatedFood.restaurantId"
                                    :restaurant-name="relatedFood.restaurantName"
                                    :item-name="relatedFood.name"
                                    route-name="cart.store"
                                    label="Add to cart"
                                />

                                <Link
                                    :href="route('foods.show', relatedFood.slug)"
                                    class="inline-flex items-center justify-center rounded-full border border-white/80 bg-white px-5 py-3 text-sm font-semibold text-slate-700 shadow-[0_18px_42px_-32px_rgba(11,77,89,0.35)] transition duration-200 hover:text-[var(--brand-teal)]"
                                >
                                    View dish
                                </Link>

                                <Link
                                    v-if="!isGuestViewer"
                                    :href="route('restaurants.show', relatedFood.restaurantSlug)"
                                    class="inline-flex items-center justify-center rounded-full border border-white/80 bg-white px-5 py-3 text-sm font-semibold text-slate-700 shadow-[0_18px_42px_-32px_rgba(11,77,89,0.35)] transition duration-200 hover:text-[var(--brand-teal)]"
                                >
                                    View menu
                                </Link>
                            </template>
                        </FoodSpotlightCard>
                    </div>
                </section>
            </main>
        </div>
    </div>
</template>