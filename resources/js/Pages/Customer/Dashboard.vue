<script setup>
import AddToCartButton from '@/Components/Cart/AddToCartButton.vue';
import ReorderToCartButton from '@/Components/Cart/ReorderToCartButton.vue';
import FoodSpotlightCard from '@/Features/customer/restaurants/components/FoodSpotlightCard.vue';
import RestaurantCard from '@/Features/customer/restaurants/components/RestaurantCard.vue';
import CustomerLayout from '@/Layouts/CustomerLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    overview: {
        type: Object,
        default: () => ({
            restaurantsCount: 0,
            foodsCount: 0,
            activeOrdersCount: 0,
            recentOrdersCount: 0,
            cartItemsCount: 0,
            cartTotalValue: 0,
            averageDeliveryMinutes: 0,
            highestRatedRestaurant: null,
        }),
    },
    spotlightRestaurants: {
        type: Array,
        default: () => [],
    },
    spotlightFoods: {
        type: Array,
        default: () => [],
    },
    cart: {
        type: Object,
        default: () => ({
            restaurant: null,
            idempotencyKey: null,
            items: [],
            totals: [],
            totalValue: 0,
            totalFormatted: '₱0',
        }),
    },
    recentOrders: {
        type: Array,
        default: () => [],
    },
});

const heroMetrics = computed(() => [
    {
        label: 'Active orders',
        value: props.overview.activeOrdersCount,
        detail: 'Orders still moving through prep or delivery right now.',
        tone: 'text-[var(--brand-orange-deep)]',
        surface: 'bg-[#fff7ef] ring-[#f6dcc5]',
    },
    {
        label: 'Cart items',
        value: props.overview.cartItemsCount,
        detail: 'Items waiting in your bag so you can continue without rebuilding it.',
        tone: 'text-[var(--brand-teal)]',
        surface: 'bg-[#f4fbfb] ring-[#dceced]',
    },
    {
        label: 'Kitchens live',
        value: props.overview.restaurantsCount,
        detail: 'Restaurants currently ready to browse and order from.',
        tone: 'text-sky-700',
        surface: 'bg-[#eef7ff] ring-[#d5e8fb]',
    },
    {
        label: 'Live dishes',
        value: props.overview.foodsCount,
        detail: 'Available dishes across the current discovery feed.',
        tone: 'text-emerald-700',
        surface: 'bg-[#eefcf5] ring-[#d3f2df]',
    },
]);

const activeOrders = computed(() => props.recentOrders.filter((order) => order.canTrack));
const completedOrders = computed(() => props.recentOrders.filter((order) => !order.canTrack));
const reorderableCompletedOrders = computed(() => completedOrders.value.filter((order) => order.canReorder));
const featuredFoods = computed(() => props.spotlightFoods.slice(0, 3));
const featuredRestaurants = computed(() => props.spotlightRestaurants.slice(0, 3));
const hasCartItems = computed(() => props.cart.items.length > 0);
</script>

<template>
    <Head title="Customer Home" />

    <CustomerLayout>
        <template #header>
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div class="space-y-3">
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-[var(--brand-orange-deep)]">
                        Customer home
                    </p>
                    <h2 class="text-3xl font-semibold leading-tight text-slate-900 sm:text-4xl">
                        Open BizLami and know what to reorder, what is still on the way, and what looks good next.
                    </h2>
                    <p class="max-w-2xl text-sm leading-6 text-slate-600">
                        The landing screen now balances momentum and clarity: live order state, cart recovery, and curated browse shortcuts without dropping you into a wall of listings.
                    </p>
                </div>

                <div class="flex flex-col gap-3 sm:flex-row">
                    <Link
                        :href="route('customer.restaurants.index')"
                        class="inline-flex items-center justify-center rounded-full bg-[var(--brand-teal)] px-5 py-3 text-sm font-semibold text-white shadow-[0_20px_45px_-24px_rgba(11,77,89,0.85)] transition duration-200 hover:-translate-y-0.5"
                    >
                        Browse kitchens
                    </Link>
                    <Link
                        :href="route('customer.orders.index')"
                        class="inline-flex items-center justify-center rounded-full border border-white/80 bg-white/80 px-5 py-3 text-sm font-semibold text-slate-700 transition duration-200 hover:text-[var(--brand-teal)]"
                    >
                        Order timeline
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl space-y-8 sm:px-6 lg:px-8">
                <section class="rounded-[32px] border border-white/80 bg-[linear-gradient(135deg,#ffffff_0%,#fff8f1_56%,#f3fbfb_100%)] p-6 shadow-[0_34px_85px_-56px_rgba(11,77,89,0.6)] sm:p-8">
                    <div class="grid gap-6 xl:grid-cols-[1.15fr_0.85fr] xl:items-start">
                        <div class="space-y-6">
                            <div class="flex flex-wrap items-center gap-3">
                                <span class="rounded-full border border-[#f5dcc7] bg-white/85 px-4 py-2 text-xs font-semibold uppercase tracking-[0.22em] text-[var(--brand-orange-deep)]">
                                    Delivery command view
                                </span>
                                <span class="rounded-full border border-[#dceced] bg-[#f4fbfb] px-4 py-2 text-xs font-semibold uppercase tracking-[0.18em] text-[var(--brand-teal)]">
                                    {{ overview.averageDeliveryMinutes }} min average delivery
                                </span>
                            </div>

                            <div class="space-y-4">
                                <h3 class="max-w-3xl text-3xl font-semibold leading-tight text-slate-900 sm:text-[2.5rem]">
                                    Dinner decisions should feel fast, not cluttered.
                                </h3>
                                <p class="max-w-2xl text-sm leading-7 text-slate-600">
                                    Resume your cart, check whether a rider is still en route, or jump into kitchens that match the strongest ratings in the live feed.
                                </p>
                            </div>

                            <div class="grid gap-4 sm:grid-cols-2">
                                <article
                                    v-for="metric in heroMetrics"
                                    :key="metric.label"
                                    class="rounded-[28px] border border-white/80 bg-white/88 p-5 shadow-[0_28px_70px_-48px_rgba(11,77,89,0.55)]"
                                >
                                    <div class="inline-flex rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] ring-1" :class="[metric.surface, metric.tone]">
                                        {{ metric.label }}
                                    </div>
                                    <p class="mt-4 text-4xl font-semibold text-slate-900">
                                        {{ metric.value }}
                                    </p>
                                    <p class="mt-3 text-sm leading-6 text-slate-500">
                                        {{ metric.detail }}
                                    </p>
                                </article>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <section class="rounded-[30px] border border-white/80 bg-white/90 p-6 shadow-[0_28px_70px_-48px_rgba(11,77,89,0.55)]">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-[var(--brand-teal)]">Cart pulse</p>
                                        <h3 class="mt-2 text-2xl font-semibold text-slate-900">
                                            {{ hasCartItems ? 'Your next order is still in progress.' : 'No open cart yet.' }}
                                        </h3>
                                    </div>

                                    <span class="rounded-full bg-[#fff7ef] px-3 py-1 text-xs font-semibold uppercase tracking-[0.16em] text-[var(--brand-orange-deep)] ring-1 ring-[#f6dcc5]">
                                        {{ cart.totalFormatted }}
                                    </span>
                                </div>

                                <p class="mt-3 text-sm leading-6 text-slate-600">
                                    <template v-if="hasCartItems">
                                        {{ cart.items.length }} line item{{ cart.items.length === 1 ? '' : 's' }} from {{ cart.restaurant }} are ready to resume.
                                    </template>
                                    <template v-else>
                                        Browse the live kitchens to build a fresh cart without losing the new home overview.
                                    </template>
                                </p>

                                <div v-if="hasCartItems" class="mt-5 space-y-3">
                                    <article
                                        v-for="item in cart.items.slice(0, 3)"
                                        :key="`${item.menuItemId}-${item.name}`"
                                        class="flex items-center justify-between gap-4 rounded-[22px] bg-[#f7fbfb] px-4 py-3 ring-1 ring-[#dceced]"
                                    >
                                        <div>
                                            <p class="text-sm font-semibold text-slate-900">{{ item.name }}</p>
                                            <p class="mt-1 text-xs uppercase tracking-[0.16em] text-slate-500">{{ item.quantityLabel }}</p>
                                        </div>
                                        <span class="text-sm font-semibold text-slate-700">{{ item.lineTotal.formatted }}</span>
                                    </article>
                                </div>

                                <div class="mt-5 flex flex-wrap gap-3">
                                    <Link
                                        :href="hasCartItems ? route('customer.checkout.index') : route('customer.restaurants.index')"
                                        class="inline-flex items-center justify-center rounded-full bg-gradient-to-r from-[var(--brand-teal)] to-[var(--brand-orange)] px-5 py-3 text-sm font-semibold text-white shadow-[0_20px_45px_-24px_rgba(11,77,89,0.85)] transition duration-200 hover:-translate-y-0.5"
                                    >
                                        {{ hasCartItems ? 'Resume checkout' : 'Start browsing' }}
                                    </Link>
                                    <Link
                                        :href="route('customer.cart.index')"
                                        class="inline-flex items-center justify-center rounded-full border border-white/80 bg-white px-5 py-3 text-sm font-semibold text-slate-700 shadow-[0_18px_42px_-32px_rgba(11,77,89,0.35)] transition duration-200 hover:text-[var(--brand-teal)]"
                                    >
                                        Open cart
                                    </Link>
                                </div>
                            </section>

                            <section class="rounded-[30px] border border-white/80 bg-[linear-gradient(135deg,#fffaf3_0%,#ffffff_100%)] p-6 shadow-[0_28px_70px_-48px_rgba(11,77,89,0.42)]">
                                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-[var(--brand-orange-deep)]">Best rated right now</p>
                                <h3 class="mt-2 text-2xl font-semibold text-slate-900">
                                    {{ overview.highestRatedRestaurant ?? 'Live ratings update with active kitchens.' }}
                                </h3>
                                <p class="mt-3 text-sm leading-6 text-slate-600">
                                    Curated from the same live discovery feed that powers public browse and mobile home.
                                </p>
                            </section>
                        </div>
                    </div>
                </section>

                <section class="grid gap-6 xl:grid-cols-[1.05fr_0.95fr]">
                    <section class="rounded-[32px] border border-white/80 bg-white/88 p-6 shadow-[0_34px_85px_-56px_rgba(11,77,89,0.6)] sm:p-8">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-[var(--brand-orange-deep)]">Live orders</p>
                                <h3 class="mt-2 text-2xl font-semibold text-slate-900">See what still needs your attention before you browse again.</h3>
                            </div>

                            <Link
                                :href="route('customer.orders.index')"
                                class="inline-flex items-center justify-center rounded-full border border-white/80 bg-[#fff7ef] px-4 py-2 text-sm font-semibold text-slate-700 shadow-[0_18px_42px_-32px_rgba(11,77,89,0.35)] transition duration-200 hover:text-[var(--brand-teal)]"
                            >
                                Full order timeline
                            </Link>
                        </div>

                        <div v-if="activeOrders.length" class="mt-6 grid gap-4">
                            <article
                                v-for="order in activeOrders"
                                :key="order.id"
                                class="rounded-[26px] border border-[#edf2f2] bg-[linear-gradient(135deg,#ffffff_0%,#fff8f1_62%,#f4fbfb_100%)] p-5"
                            >
                                <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                                    <div>
                                        <div class="flex flex-wrap items-center gap-3">
                                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[var(--brand-orange-deep)]">{{ order.orderNumber }}</p>
                                            <span class="rounded-full bg-[#f4fbfb] px-3 py-1 text-xs font-semibold uppercase tracking-[0.16em] text-[var(--brand-teal)] ring-1 ring-[#dceced]">
                                                {{ order.status.label }}
                                            </span>
                                        </div>

                                        <h4 class="mt-3 text-lg font-semibold text-slate-900">{{ order.restaurant.name }}</h4>
                                        <p class="mt-1 text-sm leading-6 text-slate-600">{{ order.summary }}</p>
                                    </div>

                                    <div class="grid gap-2 text-sm text-slate-600 md:text-right">
                                        <p><span class="font-semibold text-slate-900">Updated:</span> {{ order.placedAgo ?? 'Just now' }}</p>
                                        <p><span class="font-semibold text-slate-900">Total:</span> {{ order.total.formatted }}</p>
                                    </div>
                                </div>

                                <div class="mt-4 flex flex-wrap gap-3 border-t border-[#edf2f2] pt-4">
                                    <Link
                                        :href="route('customer.orders.show', order.id)"
                                        class="inline-flex items-center justify-center rounded-full bg-[var(--brand-teal)] px-4 py-2 text-sm font-semibold text-white shadow-[0_20px_45px_-24px_rgba(11,77,89,0.85)] transition duration-200 hover:-translate-y-0.5"
                                    >
                                        Track this order
                                    </Link>
                                    <Link
                                        :href="route('customer.restaurants.show', order.restaurant.slug)"
                                        class="inline-flex items-center justify-center rounded-full border border-white/80 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-[0_18px_42px_-32px_rgba(11,77,89,0.35)] transition duration-200 hover:text-[var(--brand-teal)]"
                                    >
                                        Browse this kitchen
                                    </Link>
                                </div>
                            </article>
                        </div>

                        <div v-else class="mt-6 rounded-[28px] border border-dashed border-[#d8e7e8] bg-[#fffcf8] p-8 text-sm leading-6 text-slate-500">
                            No active deliveries are moving right now. This space keeps the urgent state visible so browsing never hides a live order.
                        </div>
                    </section>

                    <section class="rounded-[32px] border border-white/80 bg-white/88 p-6 shadow-[0_34px_85px_-56px_rgba(11,77,89,0.6)] sm:p-8">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-[var(--brand-teal)]">Recent history</p>
                                <h3 class="mt-2 text-2xl font-semibold text-slate-900">Keep completed orders nearby for quick repeat decisions.</h3>
                            </div>

                            <span class="rounded-full border border-white/80 bg-[#f4fbfb] px-4 py-2 text-sm font-medium text-slate-600 shadow-[0_20px_48px_-36px_rgba(11,77,89,0.35)]">
                                {{ reorderableCompletedOrders.length }} ready to reorder
                            </span>
                        </div>

                        <div v-if="completedOrders.length" class="mt-6 grid gap-4">
                            <article
                                v-for="order in completedOrders"
                                :key="order.id"
                                class="rounded-[24px] bg-[#f8fbfb] p-5 ring-1 ring-[#e2eded]"
                            >
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">{{ order.orderNumber }}</p>
                                        <h4 class="mt-2 text-lg font-semibold text-slate-900">{{ order.restaurant.name }}</h4>
                                        <p class="mt-1 text-sm leading-6 text-slate-600">{{ order.summary }}</p>
                                    </div>
                                    <span class="rounded-full bg-white px-3 py-1 text-xs font-semibold uppercase tracking-[0.16em] text-slate-600 ring-1 ring-[#e4eded]">
                                        {{ order.status.label }}
                                    </span>
                                </div>
                                <div class="mt-4 flex items-center justify-between gap-4 text-sm text-slate-500">
                                    <p>{{ order.placedAgo ?? 'Recently placed' }}</p>
                                    <div class="flex flex-wrap items-center gap-3">
                                        <p class="font-semibold text-slate-700">{{ order.total.formatted }}</p>
                                        <ReorderToCartButton
                                            v-if="order.canReorder"
                                            :order-id="order.id"
                                            :restaurant-id="order.restaurant.id"
                                            :restaurant-name="order.restaurant.name"
                                            :order-number="order.orderNumber"
                                            label="Reorder"
                                            button-class="inline-flex items-center justify-center rounded-full bg-[var(--brand-orange-deep)] px-3 py-1.5 text-xs font-semibold text-white shadow-[0_18px_42px_-32px_rgba(197,92,24,0.5)] transition duration-200 hover:-translate-y-0.5"
                                        />
                                        <Link
                                            :href="route('customer.orders.show', order.id)"
                                            class="inline-flex items-center justify-center rounded-full border border-white/80 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-[0_18px_42px_-32px_rgba(11,77,89,0.35)] transition duration-200 hover:text-[var(--brand-teal)]"
                                        >
                                            View detail
                                        </Link>
                                    </div>
                                </div>

                                <div
                                    v-if="order.reorder?.visible && !order.canReorder && order.reorder.description"
                                    class="mt-4 rounded-[20px] bg-white px-4 py-3 ring-1 ring-[#e4eded]"
                                >
                                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">{{ order.reorder.label }}</p>
                                    <p class="mt-2 text-sm leading-6 text-slate-600">{{ order.reorder.description }}</p>
                                </div>
                            </article>
                        </div>

                        <div v-else class="mt-6 rounded-[28px] border border-dashed border-[#d8e7e8] bg-[#fffcf8] p-8 text-sm leading-6 text-slate-500">
                            Finished orders will appear here once you complete your first delivery cycle.
                        </div>
                    </section>
                </section>

                <section class="space-y-4">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-[var(--brand-teal)]">Spotlight dishes</p>
                            <h3 class="mt-2 text-2xl font-semibold text-slate-900">High-signal dishes so customers can act before they overthink.</h3>
                        </div>

                        <Link
                            :href="route('customer.restaurants.index')"
                            class="inline-flex items-center justify-center rounded-full border border-white/80 bg-white/80 px-4 py-2 text-sm font-semibold text-slate-700 shadow-[0_18px_42px_-32px_rgba(11,77,89,0.35)] transition duration-200 hover:text-[var(--brand-teal)]"
                        >
                            Open full browse
                        </Link>
                    </div>

                    <div v-if="featuredFoods.length" class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
                        <FoodSpotlightCard
                            v-for="food in featuredFoods"
                            :key="food.id"
                            :food="food"
                        >
                            <template #actions="{ food }">
                                <Link
                                    :href="route('customer.restaurants.show', food.restaurantSlug)"
                                    class="inline-flex items-center justify-center rounded-full border border-white/80 bg-white px-5 py-3 text-sm font-semibold text-slate-700 shadow-[0_18px_42px_-32px_rgba(11,77,89,0.35)] transition duration-200 hover:text-[var(--brand-teal)]"
                                >
                                    View menu
                                </Link>

                                <AddToCartButton
                                    :menu-item-id="food.id"
                                    :restaurant-id="food.restaurantId"
                                    :restaurant-name="food.restaurantName"
                                    :item-name="food.name"
                                />
                            </template>
                        </FoodSpotlightCard>
                    </div>
                </section>

                <section v-if="featuredRestaurants.length" class="space-y-4">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-[var(--brand-orange-deep)]">Kitchen shortcuts</p>
                            <h3 class="mt-2 text-2xl font-semibold text-slate-900">Jump straight into the restaurants most likely to convert a fast decision.</h3>
                        </div>
                    </div>

                    <div class="grid gap-6 lg:grid-cols-3">
                        <RestaurantCard
                            v-for="restaurant in featuredRestaurants"
                            :key="restaurant.slug"
                            v-bind="restaurant"
                        />
                    </div>
                </section>
            </div>
        </div>
    </CustomerLayout>
</template>