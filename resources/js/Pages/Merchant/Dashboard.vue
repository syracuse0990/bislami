<script setup>
import MerchantLayout from '@/Layouts/MerchantLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    overview: {
        type: Object,
        default: () => ({
            activeOrdersCount: 0,
            preparingOrdersCount: 0,
            ordersTodayCount: 0,
            liveMenuItemsCount: 0,
            pausedMenuItemsCount: 0,
            restaurantsCount: 0,
            visibleRestaurantsCount: 0,
            hiddenRestaurantsCount: 0,
            pinnedDestinationsCount: 0,
        }),
    },
    recentOrders: {
        type: Array,
        default: () => [],
    },
    recentMenuItems: {
        type: Array,
        default: () => [],
    },
    restaurants: {
        type: Array,
        default: () => [],
    },
});

const primaryMetrics = computed(() => [
    {
        label: 'Active orders',
        value: props.overview.activeOrdersCount,
        detail: 'Preparing and on-the-way orders across every managed kitchen.',
        tone: 'text-[var(--brand-orange-deep)]',
        surface: 'bg-[#fff7ef] ring-[#f6dcc5]',
    },
    {
        label: 'Ready for handoff',
        value: props.overview.preparingOrdersCount,
        detail: 'Tickets still inside the kitchen stage and waiting for a rider handoff.',
        tone: 'text-[var(--brand-teal)]',
        surface: 'bg-[#f4fbfb] ring-[#dceced]',
    },
    {
        label: 'Orders today',
        value: props.overview.ordersTodayCount,
        detail: 'Orders placed today across your restaurant footprint.',
        tone: 'text-sky-700',
        surface: 'bg-[#eef7ff] ring-[#d5e8fb]',
    },
    {
        label: 'Live dishes',
        value: props.overview.liveMenuItemsCount,
        detail: 'Menu items currently ready to surface in discovery and checkout.',
        tone: 'text-emerald-700',
        surface: 'bg-[#eefcf5] ring-[#d3f2df]',
    },
]);

const workspaceSignals = computed(() => [
    {
        label: 'Restaurants',
        value: props.overview.restaurantsCount,
        detail: `${props.overview.visibleRestaurantsCount} visible · ${props.overview.hiddenRestaurantsCount} hidden`,
    },
    {
        label: 'Pinned destinations',
        value: props.overview.pinnedDestinationsCount,
        detail: 'Active orders already carrying exact map coordinates.',
    },
    {
        label: 'Paused dishes',
        value: props.overview.pausedMenuItemsCount,
        detail: 'Menu items currently held back from ordering.',
    },
]);
</script>

<template>
    <Head title="Merchant Overview" />

    <MerchantLayout>
        <template #header>
            <div class="space-y-3">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-[var(--brand-orange-deep)]">
                    Merchant overview
                </p>
                <h2 class="text-3xl font-semibold leading-tight text-slate-900">
                    Watch the kitchen pace, order volume, and prep health in one place.
                </h2>
                <p class="max-w-2xl text-sm leading-6 text-slate-600">
                    The merchant workspace now matches the storefront polish while keeping operational signals readable.
                </p>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                <section class="rounded-[32px] border border-white/80 bg-[linear-gradient(135deg,#ffffff_0%,#fff8f1_58%,#f3fbfb_100%)] p-6 shadow-[0_34px_85px_-56px_rgba(11,77,89,0.6)] sm:p-8">
                    <div class="grid gap-6 xl:grid-cols-[1.15fr_0.85fr] xl:items-start">
                        <div class="space-y-6">
                            <div class="flex flex-wrap items-center gap-3">
                                <span class="rounded-full border border-[#f5dcc7] bg-white/85 px-4 py-2 text-xs font-semibold uppercase tracking-[0.22em] text-[var(--brand-orange-deep)]">
                                    Merchant control room
                                </span>
                                <span class="rounded-full border border-[#dceced] bg-[#f4fbfb] px-4 py-2 text-xs font-semibold uppercase tracking-[0.18em] text-[var(--brand-teal)]">
                                    {{ overview.restaurantsCount }} restaurant{{ overview.restaurantsCount === 1 ? '' : 's' }} managed
                                </span>
                            </div>

                            <div class="space-y-4">
                                <h3 class="max-w-3xl text-3xl font-semibold leading-tight text-slate-900 sm:text-[2.5rem]">
                                    Everything the kitchen team needs to triage service without leaving the landing screen.
                                </h3>
                                <p class="max-w-2xl text-sm leading-7 text-slate-600">
                                    Start with order pressure, spot any menu gaps, and jump straight into the right workflow before service slows down.
                                </p>
                            </div>

                            <div class="flex flex-wrap gap-3">
                                <Link
                                    :href="route('merchant.orders.index')"
                                    class="inline-flex items-center justify-center rounded-full bg-gradient-to-r from-[var(--brand-teal)] to-[var(--brand-orange)] px-5 py-3 text-sm font-semibold text-white shadow-[0_20px_45px_-24px_rgba(11,77,89,0.85)] transition duration-200 hover:-translate-y-0.5"
                                >
                                    Open orders board
                                </Link>
                                <Link
                                    :href="route('merchant.menu.index')"
                                    class="inline-flex items-center justify-center rounded-full border border-white/80 bg-white px-5 py-3 text-sm font-semibold text-slate-700 shadow-[0_18px_42px_-32px_rgba(11,77,89,0.35)] transition duration-200 hover:text-[var(--brand-teal)]"
                                >
                                    Open menu studio
                                </Link>
                            </div>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-3 xl:grid-cols-1">
                            <article
                                v-for="signal in workspaceSignals"
                                :key="signal.label"
                                class="rounded-[28px] border border-white/80 bg-white/88 p-5 shadow-[0_28px_70px_-48px_rgba(11,77,89,0.55)]"
                            >
                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[var(--brand-teal)]">
                                    {{ signal.label }}
                                </p>
                                <p class="mt-3 text-3xl font-semibold text-slate-900">
                                    {{ signal.value }}
                                </p>
                                <p class="mt-3 text-sm leading-6 text-slate-500">
                                    {{ signal.detail }}
                                </p>
                            </article>
                        </div>
                    </div>
                </section>

                <section class="grid gap-6 md:grid-cols-2 xl:grid-cols-4">
                    <article
                        v-for="metric in primaryMetrics"
                        :key="metric.label"
                        class="rounded-[28px] border border-white/80 bg-white/88 p-6 shadow-[0_28px_70px_-48px_rgba(11,77,89,0.55)]"
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
                </section>

                <section class="grid gap-6 xl:grid-cols-[1.05fr_0.95fr]">
                    <section class="rounded-[32px] border border-white/80 bg-white/88 p-6 shadow-[0_34px_85px_-56px_rgba(11,77,89,0.6)] sm:p-8">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-[var(--brand-orange-deep)]">Priority queue</p>
                                <h3 class="mt-2 text-2xl font-semibold text-slate-900">The latest tickets that still need merchant attention.</h3>
                            </div>

                            <Link
                                :href="route('merchant.orders.index')"
                                class="inline-flex items-center justify-center rounded-full border border-white/80 bg-[#fff7ef] px-4 py-2 text-sm font-semibold text-slate-700 shadow-[0_18px_42px_-32px_rgba(11,77,89,0.35)] transition duration-200 hover:text-[var(--brand-teal)]"
                            >
                                Full queue
                            </Link>
                        </div>

                        <div v-if="recentOrders.length" class="mt-6 grid gap-4">
                            <article
                                v-for="order in recentOrders"
                                :key="order.id"
                                class="rounded-[26px] border border-[#edf2f2] bg-[linear-gradient(135deg,#ffffff_0%,#fff8f1_62%,#f4fbfb_100%)] p-5"
                            >
                                <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                                    <div>
                                        <div class="flex flex-wrap items-center gap-3">
                                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[var(--brand-orange-deep)]">
                                                {{ order.orderNumber }}
                                            </p>
                                            <span :class="order.statusAccent" class="rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-[0.16em] ring-1">
                                                {{ order.statusLabel }}
                                            </span>
                                        </div>

                                        <h4 class="mt-3 text-lg font-semibold text-slate-900">
                                            {{ order.restaurantName }}
                                        </h4>
                                        <p class="mt-1 text-sm leading-6 text-slate-600">{{ order.summary }}</p>

                                        <div class="mt-4 flex flex-wrap gap-2">
                                            <span class="rounded-full border border-[#dceced] bg-[#f4fbfb] px-3 py-1 text-xs font-medium text-slate-700">
                                                {{ order.customerName }}
                                            </span>
                                            <span class="rounded-full border border-[#ece7de] bg-white px-3 py-1 text-xs font-medium text-slate-700">
                                                {{ order.destinationShortLabel }}
                                            </span>
                                            <span
                                                class="rounded-full px-3 py-1 text-xs font-medium ring-1"
                                                :class="order.destinationHasCoordinates
                                                    ? 'bg-[#e9fbf8] text-[var(--brand-teal)] ring-[#bfe3da]'
                                                    : 'bg-white text-slate-600 ring-[#e4eded]'"
                                            >
                                                {{ order.destinationHasCoordinates ? 'Pinned map' : 'Address only' }}
                                            </span>
                                        </div>
                                    </div>

                                    <div class="grid gap-2 text-sm text-slate-600 md:text-right">
                                        <p><span class="font-semibold text-slate-900">Placed:</span> {{ order.placedAt }}</p>
                                        <p><span class="font-semibold text-slate-900">Total:</span> {{ order.total }}</p>
                                        <p><span class="font-semibold text-slate-900">Courier:</span> {{ order.assignmentLabel }}</p>
                                    </div>
                                </div>
                            </article>
                        </div>

                        <div v-else class="mt-6 rounded-[28px] border border-dashed border-[#d8e7e8] bg-[#fffcf8] p-8 text-sm leading-6 text-slate-500">
                            No active merchant orders right now. New customer tickets will appear here first so the kitchen can move quickly.
                        </div>
                    </section>

                    <div class="space-y-6">
                        <section class="rounded-[32px] border border-white/80 bg-white/88 p-6 shadow-[0_34px_85px_-56px_rgba(11,77,89,0.6)] sm:p-8">
                            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.22em] text-[var(--brand-teal)]">Menu health</p>
                                    <h3 class="mt-2 text-2xl font-semibold text-slate-900">Recently touched dishes and current availability.</h3>
                                </div>

                                <Link
                                    :href="route('merchant.menu.index')"
                                    class="inline-flex items-center justify-center rounded-full border border-white/80 bg-[#f4fbfb] px-4 py-2 text-sm font-semibold text-slate-700 shadow-[0_18px_42px_-32px_rgba(11,77,89,0.35)] transition duration-200 hover:text-[var(--brand-teal)]"
                                >
                                    Manage menu
                                </Link>
                            </div>

                            <div v-if="recentMenuItems.length" class="mt-6 grid gap-4">
                                <article
                                    v-for="menuItem in recentMenuItems"
                                    :key="menuItem.id"
                                    class="rounded-[24px] border border-[#edf2f2] bg-[#fffcf8] p-5"
                                >
                                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                        <div>
                                            <div class="flex flex-wrap items-center gap-3">
                                                <h4 class="text-base font-semibold text-slate-900">{{ menuItem.name }}</h4>
                                                <span :class="menuItem.availabilityAccent" class="rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-[0.16em] ring-1">
                                                    {{ menuItem.availabilityLabel }}
                                                </span>
                                            </div>
                                            <p class="mt-2 text-sm text-slate-500">{{ menuItem.restaurantName }} · {{ menuItem.category }}</p>
                                        </div>

                                        <div class="grid gap-2 text-sm text-slate-600 sm:text-right">
                                            <p><span class="font-semibold text-slate-900">Price:</span> {{ menuItem.price }}</p>
                                            <p><span class="font-semibold text-slate-900">Updated:</span> {{ menuItem.updatedAt }}</p>
                                        </div>
                                    </div>
                                </article>
                            </div>

                            <div v-else class="mt-6 rounded-[28px] border border-dashed border-[#d8e7e8] bg-[#fffcf8] p-8 text-sm leading-6 text-slate-500">
                                No menu items yet. As dishes are created or adjusted, the latest changes will stay visible here.
                            </div>
                        </section>
                    </div>
                </section>

                <section class="rounded-[32px] border border-white/80 bg-white/88 p-6 shadow-[0_34px_85px_-56px_rgba(11,77,89,0.6)] sm:p-8">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-[var(--brand-orange-deep)]">Restaurant portfolio</p>
                            <h3 class="mt-2 text-2xl font-semibold text-slate-900">See menu readiness and live pressure by kitchen.</h3>
                        </div>

                        <div class="rounded-full border border-white/80 bg-white/85 px-4 py-2 text-sm font-medium text-slate-600 shadow-[0_20px_48px_-36px_rgba(11,77,89,0.35)]">
                            {{ restaurants.length }} kitchen{{ restaurants.length === 1 ? '' : 's' }} in view
                        </div>
                    </div>

                    <div v-if="restaurants.length" class="mt-6 grid gap-4 xl:grid-cols-2">
                        <article
                            v-for="restaurant in restaurants"
                            :key="restaurant.id"
                            class="rounded-[28px] border border-[#edf2f2] bg-[linear-gradient(135deg,#ffffff_0%,#fff8f1_62%,#f4fbfb_100%)] p-5"
                        >
                            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                <div>
                                    <div class="flex flex-wrap items-center gap-3">
                                        <h4 class="text-lg font-semibold text-slate-900">{{ restaurant.name }}</h4>
                                        <span :class="restaurant.visibilityAccent" class="rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-[0.16em] ring-1">
                                            {{ restaurant.visibilityLabel }}
                                        </span>
                                    </div>
                                    <p class="mt-2 text-sm text-slate-500">{{ restaurant.category }} · {{ restaurant.cuisine }}</p>
                                    <p class="mt-3 text-sm leading-6 text-slate-600">{{ restaurant.featuredText }}</p>
                                </div>

                                <div class="grid gap-2 text-sm text-slate-600 sm:text-right">
                                    <p><span class="font-semibold text-slate-900">Rating:</span> {{ restaurant.ratingLabel }}</p>
                                    <p><span class="font-semibold text-slate-900">Delivery:</span> {{ restaurant.deliveryWindow }}</p>
                                    <p><span class="font-semibold text-slate-900">Fee:</span> {{ restaurant.deliveryFee }}</p>
                                </div>
                            </div>

                            <div class="mt-5 grid gap-3 sm:grid-cols-3">
                                <div class="rounded-[22px] bg-[#f4fbfb] px-4 py-3 ring-1 ring-[#dceced]">
                                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-[var(--brand-teal)]">Live dishes</p>
                                    <p class="mt-2 text-lg font-semibold text-slate-900">{{ restaurant.liveMenuItemsCount }}</p>
                                </div>

                                <div class="rounded-[22px] bg-[#fff7ef] px-4 py-3 ring-1 ring-[#f6dcc5]">
                                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-[var(--brand-orange-deep)]">Paused dishes</p>
                                    <p class="mt-2 text-lg font-semibold text-slate-900">{{ restaurant.pausedMenuItemsCount }}</p>
                                </div>

                                <div class="rounded-[22px] bg-white px-4 py-3 ring-1 ring-[#e5eded]">
                                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Active orders</p>
                                    <p class="mt-2 text-lg font-semibold text-slate-900">{{ restaurant.activeOrdersCount }}</p>
                                </div>
                            </div>

                            <div class="mt-5 flex flex-wrap gap-3 border-t border-[#edf2f2] pt-4">
                                <Link
                                    :href="route('merchant.menu.index')"
                                    class="inline-flex items-center justify-center rounded-full bg-gradient-to-r from-[var(--brand-teal)] to-[var(--brand-orange)] px-5 py-3 text-sm font-semibold text-white shadow-[0_20px_45px_-24px_rgba(11,77,89,0.85)] transition duration-200 hover:-translate-y-0.5"
                                >
                                    Manage dishes
                                </Link>
                                <Link
                                    :href="route('merchant.orders.index')"
                                    class="inline-flex items-center justify-center rounded-full border border-white/80 bg-white px-5 py-3 text-sm font-semibold text-slate-700 shadow-[0_18px_42px_-32px_rgba(11,77,89,0.35)] transition duration-200 hover:text-[var(--brand-teal)]"
                                >
                                    Open order queue
                                </Link>
                            </div>
                        </article>
                    </div>

                    <div v-else class="mt-6 rounded-[28px] border border-dashed border-[#d8e7e8] bg-[#fffcf8] p-8 text-sm leading-6 text-slate-500">
                        No restaurant records are assigned to this merchant account yet. Once kitchens are linked, this dashboard will summarize menu health and active order pressure here.
                    </div>
                </section>
            </div>
        </div>
    </MerchantLayout>
</template>