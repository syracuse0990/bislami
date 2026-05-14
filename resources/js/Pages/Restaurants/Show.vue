<script setup>
import ApplicationLogo from '@/Components/Branding/ApplicationLogo.vue';
import AddToCartButton from '@/Components/Cart/AddToCartButton.vue';
import PublicSeoHead from '@/Components/Seo/PublicSeoHead.vue';
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
    restaurant: {
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
const seoImageUrl = computed(() => props.restaurant.menuItems[0]?.imageUrl ?? '/images/bizlami_icon.png');
const seoDescription = computed(() => `${props.restaurant.name} on ${siteName.value}: ${props.restaurant.cuisine}. ${props.restaurant.featured}`);
const restaurantRatingValue = computed(() => Number.parseFloat(props.restaurant.rating) || null);
const orderingCopy = computed(() => (isGuestViewer.value
    ? 'Everything visible here is ready to add. BizLami only asks for an account when you continue to checkout.'
    : 'Customer accounts can place food orders. Browse the menu here, then continue with a customer login.'));
const menuActionCopy = computed(() => (isGuestViewer.value
    ? 'Add dishes now and sign in only when you reach checkout'
    : 'Ordering is limited to customer accounts'));
const seoStructuredData = computed(() => [
    {
        '@context': 'https://schema.org',
        '@type': 'Restaurant',
        name: props.restaurant.name,
        url: route('restaurants.show', props.restaurant.slug),
        image: toAbsoluteUrl(appUrl.value, seoImageUrl.value),
        description: seoDescription.value,
        servesCuisine: props.restaurant.cuisine,
        aggregateRating: restaurantRatingValue.value
            ? {
                '@type': 'AggregateRating',
                ratingValue: restaurantRatingValue.value,
                ratingCount: 1,
            }
            : undefined,
        hasMenu: {
            '@type': 'Menu',
            name: `${props.restaurant.name} menu`,
            hasMenuItem: props.restaurant.menuItems.slice(0, 8).map((menuItem) => ({
                '@type': 'MenuItem',
                name: menuItem.name,
                description: menuItem.description,
                image: toAbsoluteUrl(appUrl.value, menuItem.imageUrl),
                offers: {
                    '@type': 'Offer',
                    priceCurrency: 'PHP',
                    price: Number.parseInt(String(menuItem.price).replace(/[^\d]/g, ''), 10) || 0,
                    availability: 'https://schema.org/InStock',
                    url: route('foods.show', menuItem.slug),
                },
            })),
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
                name: props.restaurant.name,
                item: route('restaurants.show', props.restaurant.slug),
            },
        ],
    },
]);
</script>

<template>
    <PublicSeoHead
        :title="restaurant.name"
        :description="seoDescription"
        :canonical-url="route('restaurants.show', restaurant.slug)"
        type="restaurant"
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
                    <div class="grid gap-6 lg:grid-cols-[1.15fr_0.85fr] lg:items-center">
                        <div class="max-w-3xl space-y-5">
                            <div class="inline-flex items-center gap-3 rounded-full border border-[#f5dcc7] bg-white/85 px-4 py-2 text-xs font-semibold uppercase tracking-[0.2em] text-[var(--brand-orange-deep)]">
                                <img src="/images/bizlami_icon.png" alt="BizLami icon" class="h-5 w-5 object-contain">
                                {{ restaurant.category }}
                            </div>

                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">
                                    {{ restaurant.category }}
                                </p>
                                <h1 class="mt-2 text-3xl font-semibold text-slate-900 sm:text-5xl">
                                    {{ restaurant.name }}
                                </h1>
                                <p class="mt-2 text-sm leading-7 text-slate-600">
                                    {{ restaurant.cuisine }}
                                </p>
                            </div>

                            <p class="text-base leading-7 text-slate-600">
                                {{ restaurant.featured }}
                            </p>

                            <div class="flex flex-wrap gap-3">
                                <span class="rounded-full bg-emerald-50 px-4 py-2 text-sm font-medium text-emerald-700 shadow-sm">
                                    {{ restaurant.rating }}
                                </span>
                                <span class="rounded-full border border-[#deecec] bg-white/85 px-4 py-2 text-sm font-medium text-slate-700">
                                    {{ restaurant.eta }}
                                </span>
                                <span class="rounded-full border border-[#f6dcc5] bg-[#fff8f1] px-4 py-2 text-sm font-medium text-slate-700">
                                    {{ restaurant.deliveryFee }}
                                </span>
                            </div>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-1">
                            <div class="rounded-[28px] bg-white/88 p-5 shadow-[0_24px_60px_-42px_rgba(11,77,89,0.45)] ring-1 ring-white">
                                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-[var(--brand-teal)]">Menu categories</p>
                                <p class="mt-2 text-3xl font-semibold text-slate-900">{{ restaurant.categories.length }}</p>
                                <p class="mt-2 text-sm leading-6 text-slate-600">Preview the full spread before committing to an account.</p>
                            </div>

                            <div class="rounded-[28px] bg-[var(--brand-teal)] p-5 text-white shadow-[0_24px_60px_-36px_rgba(11,77,89,0.6)]">
                                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-white/70">Live dishes</p>
                                <p class="mt-2 text-3xl font-semibold">{{ restaurant.menuItems.length }}</p>
                                <p class="mt-2 text-sm leading-6 text-white/80">{{ orderingCopy }}</p>
                            </div>
                        </div>
                    </div>

                    <div v-if="restaurant.categories.length" class="mt-6 flex flex-wrap gap-3 border-t border-[#edf2f2] pt-6">
                        <span
                            v-for="category in restaurant.categories"
                            :key="category"
                            class="rounded-full border border-[#f6dcc5] bg-[#fff8f1] px-4 py-2 text-sm font-medium text-[var(--brand-orange-deep)]"
                        >
                            {{ category }}
                        </span>
                    </div>
                </section>

                <section v-if="restaurant.menuItems.length" class="space-y-4">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-[var(--brand-teal)]">Menu preview</p>
                            <h2 class="mt-2 text-2xl font-semibold text-slate-900">
                                {{ isGuestViewer ? 'Add dishes without leaving the menu.' : 'Browse the full menu before switching accounts.' }}
                            </h2>
                        </div>

                        <div class="rounded-full border border-white/80 bg-white/82 px-4 py-2 text-sm font-medium text-slate-600 shadow-[0_18px_44px_-30px_rgba(11,77,89,0.4)]">
                            {{ restaurant.menuItems.length }} dishes live
                        </div>
                    </div>

                    <div class="grid gap-6 lg:grid-cols-2">
                        <article
                            v-for="menuItem in restaurant.menuItems"
                            :key="menuItem.id"
                            :id="`dish-${menuItem.id}`"
                            class="rounded-[30px] border border-white/80 bg-white/88 p-6 shadow-[0_28px_70px_-48px_rgba(11,77,89,0.55)]"
                        >
                            <div class="mb-5 flex h-52 items-center justify-center overflow-hidden rounded-[24px] bg-[#f4fbfb] ring-1 ring-[#dceced]">
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
                                    class="h-20 w-20 object-contain"
                                >
                            </div>

                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[var(--brand-teal)]">
                                        {{ menuItem.category }}
                                    </p>
                                    <h3 class="mt-2 text-xl font-semibold text-slate-900">
                                        {{ menuItem.name }}
                                    </h3>
                                </div>

                                <span class="rounded-full border border-[#f6dcc5] bg-[#fff8f1] px-3 py-1 text-sm font-semibold text-slate-700">
                                    {{ menuItem.price }}
                                </span>
                            </div>

                            <p class="mt-4 text-sm leading-7 text-slate-600">
                                {{ menuItem.description }}
                            </p>

                            <div class="mt-6 flex flex-col gap-3 border-t border-[#edf2f2] pt-4">
                                <p class="text-xs uppercase tracking-[0.18em] text-slate-400">
                                    {{ menuActionCopy }}
                                </p>

                                <div class="flex flex-wrap gap-3">
                                    <AddToCartButton
                                        v-if="isGuestViewer"
                                        :menu-item-id="menuItem.id"
                                        :restaurant-id="restaurant.id"
                                        :restaurant-name="restaurant.name"
                                        :item-name="menuItem.name"
                                        :redirect-to="`${page.url}#dish-${menuItem.id}`"
                                        route-name="cart.store"
                                        label="Add to cart"
                                    />

                                    <Link
                                        :href="route('foods.show', menuItem.slug)"
                                        class="inline-flex items-center justify-center rounded-full border border-white/80 bg-white px-5 py-3 text-sm font-semibold text-slate-700 shadow-[0_18px_42px_-32px_rgba(11,77,89,0.35)] transition duration-200 hover:text-[var(--brand-teal)]"
                                    >
                                        View dish details
                                    </Link>
                                </div>
                            </div>
                        </article>
                    </div>
                </section>

                <section v-else class="rounded-[32px] border border-white/80 bg-white/82 p-8 text-sm text-slate-500 shadow-[0_30px_80px_-52px_rgba(11,77,89,0.55)]">
                    This restaurant does not have any live dishes yet.
                </section>
            </main>
        </div>
    </div>
</template>