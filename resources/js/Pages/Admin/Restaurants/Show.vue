<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    restaurant: {
        type: Object,
        required: true,
    },
    merchant: {
        type: Object,
        default: null,
    },
    menuItems: {
        type: Array,
        default: () => [],
    },
    activeOrders: {
        type: Array,
        default: () => [],
    },
});
</script>

<template>
    <Head :title="`Admin Restaurant - ${restaurant.name}`" />

    <AdminLayout>
        <template #header>
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div class="space-y-3">
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-[var(--brand-orange-deep)]">
                        Restaurant profile
                    </p>
                    <div class="flex flex-wrap items-center gap-3">
                        <h2 class="text-3xl font-semibold leading-tight text-slate-900">
                            {{ restaurant.name }}
                        </h2>
                        <span
                            class="inline-flex rounded-full px-3 py-1 text-xs font-semibold ring-1"
                            :class="restaurant.statusAccent"
                        >
                            {{ restaurant.statusLabel }}
                        </span>
                        <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold ring-1" :class="restaurant.visibilityAccent">
                            {{ restaurant.visibilityLabel }}
                        </span>
                    </div>
                    <p class="max-w-2xl text-sm leading-6 text-slate-600">
                        {{ restaurant.category }} · {{ restaurant.cuisine }} · {{ restaurant.featured }}
                    </p>
                </div>

                <div class="flex flex-col gap-3 sm:flex-row">
                    <Link
                        :href="route('admin.restaurants.index')"
                        class="inline-flex items-center justify-center rounded-full border border-white/80 bg-white/80 px-5 py-3 text-sm font-semibold text-slate-700 transition duration-200 hover:text-[var(--brand-teal)]"
                    >
                        Back to restaurants
                    </Link>
                    <Link
                        v-if="restaurant.publicMenuUrl"
                        :href="restaurant.publicMenuUrl"
                        class="inline-flex items-center justify-center rounded-full bg-gradient-to-r from-[var(--brand-teal)] to-[var(--brand-orange)] px-5 py-3 text-sm font-semibold text-white shadow-[0_20px_45px_-24px_rgba(11,77,89,0.85)] transition duration-200 hover:-translate-y-0.5"
                    >
                        Open public menu
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                <section class="rounded-[32px] border border-white/80 bg-[linear-gradient(135deg,#ffffff_0%,#fff8f1_58%,#f3fbfb_100%)] p-6 shadow-[0_34px_85px_-56px_rgba(11,77,89,0.6)] sm:p-8">
                    <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-4">
                        <article class="rounded-[28px] border border-white/80 bg-white/88 p-6 shadow-[0_28px_70px_-48px_rgba(11,77,89,0.55)]">
                            <p class="text-sm uppercase tracking-[0.2em] text-[var(--brand-teal)]">Rating</p>
                            <p class="mt-3 text-2xl font-semibold text-slate-900">{{ restaurant.ratingLabel }}</p>
                            <p class="mt-3 text-sm leading-6 text-slate-500">Guest-facing score displayed on public discovery pages.</p>
                        </article>

                        <article class="rounded-[28px] border border-white/80 bg-white/88 p-6 shadow-[0_28px_70px_-48px_rgba(11,77,89,0.55)]">
                            <p class="text-sm uppercase tracking-[0.2em] text-[var(--brand-orange-deep)]">Menu items</p>
                            <p class="mt-3 text-2xl font-semibold text-slate-900">{{ restaurant.menuItemsCount }}</p>
                            <p class="mt-3 text-sm leading-6 text-slate-500">All dishes attached to this kitchen.</p>
                        </article>

                        <article class="rounded-[28px] border border-white/80 bg-white/88 p-6 shadow-[0_28px_70px_-48px_rgba(11,77,89,0.55)]">
                            <p class="text-sm uppercase tracking-[0.2em] text-emerald-700">Live dishes</p>
                            <p class="mt-3 text-2xl font-semibold text-slate-900">{{ restaurant.availableMenuItemsCount }}</p>
                            <p class="mt-3 text-sm leading-6 text-slate-500">Dishes currently visible in public discovery.</p>
                        </article>

                        <article class="rounded-[28px] bg-[var(--brand-teal)] p-6 text-white shadow-[0_28px_70px_-40px_rgba(11,77,89,0.68)]">
                            <p class="text-sm uppercase tracking-[0.2em] text-white/70">Active orders</p>
                            <p class="mt-3 text-2xl font-semibold">{{ restaurant.activeOrdersCount }}</p>
                            <p class="mt-3 text-sm leading-6 text-white/80">Preparing and on-the-way platform workload for this restaurant.</p>
                        </article>
                    </div>

                    <div class="mt-6 flex flex-wrap gap-3 border-t border-[#edf2f2] pt-6">
                        <span class="rounded-full border border-[#dceced] bg-[#f4fbfb] px-4 py-2 text-sm font-medium text-slate-700">
                            {{ restaurant.deliveryFee }}
                        </span>
                        <span class="rounded-full border border-[#ece7de] bg-white px-4 py-2 text-sm font-medium text-slate-700">
                            Updated {{ restaurant.updatedAt }}
                        </span>
                    </div>
                </section>

                <section class="grid gap-6 xl:grid-cols-[0.95fr_1.05fr]">
                    <section class="rounded-[32px] border border-white/80 bg-white/88 p-6 shadow-[0_34px_85px_-56px_rgba(11,77,89,0.6)] sm:p-8">
                        <div class="flex items-end justify-between gap-4">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-[var(--brand-teal)]">Merchant owner</p>
                                <h3 class="mt-2 text-2xl font-semibold text-slate-900">Account and ownership context</h3>
                            </div>
                        </div>

                        <div v-if="merchant" class="mt-6 rounded-[28px] border border-[#edf2f2] bg-[linear-gradient(135deg,#ffffff_0%,#fff8f1_62%,#f4fbfb_100%)] p-5">
                            <div class="space-y-4">
                                <div>
                                    <div class="flex flex-wrap items-center gap-3">
                                        <h4 class="text-lg font-semibold text-slate-900">{{ merchant.name }}</h4>
                                        <span class="rounded-full bg-sky-50 px-3 py-1 text-xs font-semibold uppercase tracking-[0.16em] text-sky-700 ring-1 ring-sky-200">
                                            {{ merchant.approvalLabel }}
                                        </span>
                                    </div>
                                    <p class="mt-1 text-sm text-slate-500">{{ merchant.email }}</p>
                                </div>

                                <div class="grid gap-3 sm:grid-cols-2">
                                    <div class="rounded-[22px] bg-[#f4fbfb] px-4 py-3 ring-1 ring-[#dceced]">
                                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[var(--brand-teal)]">Managed restaurants</p>
                                        <p class="mt-2 text-sm font-semibold text-slate-900">{{ merchant.managedRestaurantsCount }}</p>
                                    </div>

                                    <div class="rounded-[22px] bg-[#fff7ef] px-4 py-3 ring-1 ring-[#f6dcc5]">
                                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[var(--brand-orange-deep)]">Owner contact</p>
                                        <p class="mt-2 text-sm font-semibold text-slate-900">Ready to reach</p>
                                    </div>
                                </div>

                                <div class="flex flex-wrap gap-3 border-t border-[#edf2f2] pt-4">
                                    <Link
                                        :href="merchant.usersDetailUrl"
                                        class="inline-flex items-center justify-center rounded-full bg-gradient-to-r from-[var(--brand-teal)] to-[var(--brand-orange)] px-5 py-3 text-sm font-semibold text-white shadow-[0_20px_45px_-24px_rgba(11,77,89,0.85)] transition duration-200 hover:-translate-y-0.5"
                                    >
                                        Open merchant profile
                                    </Link>
                                    <a
                                        :href="merchant.emailUrl"
                                        class="inline-flex items-center justify-center rounded-full border border-white/80 bg-white px-5 py-3 text-sm font-semibold text-slate-700 shadow-[0_18px_42px_-32px_rgba(11,77,89,0.35)] transition duration-200 hover:text-[var(--brand-teal)]"
                                    >
                                        Email merchant
                                    </a>
                                </div>
                            </div>
                        </div>

                        <div v-else class="mt-6 rounded-[28px] border border-dashed border-[#d8e7e8] bg-[#fffcf8] p-8 text-sm leading-6 text-slate-500">
                            This restaurant is not currently assigned to a merchant account.
                        </div>

                        <div class="mt-6 rounded-[28px] border border-[#edf2f2] bg-white p-5">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[var(--brand-orange-deep)]">Guest-facing description</p>
                            <p class="mt-3 text-sm leading-7 text-slate-600">{{ restaurant.featured }}</p>
                        </div>

                        <div class="mt-6 rounded-[28px] border border-[#edf2f2] bg-[linear-gradient(135deg,#ffffff_0%,#eef8ff_62%,#f4fbfb_100%)] p-5">
                            <div class="flex flex-wrap items-start justify-between gap-4">
                                <div class="max-w-md">
                                    <div class="flex flex-wrap items-center gap-3">
                                        <h4 class="text-lg font-semibold text-slate-900">Discovery control</h4>
                                        <span :class="restaurant.visibilityAccent" class="rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-[0.16em] ring-1">
                                            {{ restaurant.visibilityLabel }}
                                        </span>
                                    </div>
                                    <p class="mt-3 text-sm leading-6 text-slate-600">
                                        When hidden, this restaurant disappears from guest browse, customer browse, food detail access, and the sitemap.
                                    </p>
                                </div>

                                <div class="flex flex-col gap-3 md:items-end">
                                    <Link
                                        v-if="restaurant.isVisible"
                                        as="button"
                                        method="post"
                                        :href="restaurant.hideUrl"
                                        preserve-scroll
                                        class="inline-flex items-center justify-center rounded-full bg-[#46566b] px-5 py-3 text-sm font-semibold text-white shadow-[0_20px_45px_-24px_rgba(70,86,107,0.45)] transition duration-200 hover:-translate-y-0.5"
                                    >
                                        Hide from discovery
                                    </Link>
                                    <Link
                                        v-else
                                        as="button"
                                        method="post"
                                        :href="restaurant.revealUrl"
                                        preserve-scroll
                                        class="inline-flex items-center justify-center rounded-full bg-gradient-to-r from-[var(--brand-teal)] to-[var(--brand-orange)] px-5 py-3 text-sm font-semibold text-white shadow-[0_20px_45px_-24px_rgba(11,77,89,0.85)] transition duration-200 hover:-translate-y-0.5"
                                    >
                                        Restore discovery
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="space-y-6">
                        <section class="rounded-[32px] border border-white/80 bg-white/88 p-6 shadow-[0_34px_85px_-56px_rgba(11,77,89,0.6)] sm:p-8">
                            <div class="flex items-end justify-between gap-4">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.22em] text-[var(--brand-orange-deep)]">Menu preview</p>
                                    <h3 class="mt-2 text-2xl font-semibold text-slate-900">Latest dishes attached to this kitchen</h3>
                                </div>

                                <span class="rounded-full border border-white/80 bg-[#fff7ef] px-4 py-2 text-sm font-medium text-slate-600 shadow-[0_18px_44px_-30px_rgba(11,77,89,0.4)]">
                                    {{ menuItems.length }} records
                                </span>
                            </div>

                            <div v-if="menuItems.length" class="mt-6 grid gap-4">
                                <article
                                    v-for="menuItem in menuItems"
                                    :key="menuItem.id"
                                    class="rounded-[26px] border border-[#edf2f2] bg-[linear-gradient(135deg,#ffffff_0%,#fff8f1_62%,#f4fbfb_100%)] p-5"
                                >
                                    <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                                        <div class="flex items-start gap-4">
                                            <div class="flex h-16 w-16 shrink-0 items-center justify-center overflow-hidden rounded-[20px] bg-white ring-1 ring-[#e4eded]">
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

                                            <div>
                                                <div class="flex flex-wrap items-center gap-3">
                                                    <h4 class="text-lg font-semibold text-slate-900">{{ menuItem.name }}</h4>
                                                    <span :class="menuItem.statusAccent" class="rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-[0.16em] ring-1">
                                                        {{ menuItem.statusLabel }}
                                                    </span>
                                                </div>
                                                <p class="mt-1 text-sm text-slate-500">{{ menuItem.category }} · {{ menuItem.updatedAt }}</p>
                                                <p class="mt-3 text-sm leading-6 text-slate-600">{{ menuItem.description }}</p>
                                            </div>
                                        </div>

                                        <div class="flex flex-col gap-3 md:items-end">
                                            <span class="rounded-full border border-[#f6dcc5] bg-[#fff8f1] px-3 py-1 text-sm font-semibold text-slate-700">
                                                {{ menuItem.price }}
                                            </span>

                                            <Link
                                                v-if="menuItem.publicDetailUrl"
                                                :href="menuItem.publicDetailUrl"
                                                class="inline-flex items-center justify-center rounded-full border border-white/80 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-[0_18px_42px_-32px_rgba(11,77,89,0.35)] transition duration-200 hover:text-[var(--brand-teal)]"
                                            >
                                                Open public dish
                                            </Link>
                                        </div>
                                    </div>
                                </article>
                            </div>

                            <div v-else class="mt-6 rounded-[28px] border border-dashed border-[#d8e7e8] bg-[#fffcf8] p-8 text-sm leading-6 text-slate-500">
                                No menu items are attached to this restaurant yet.
                            </div>
                        </section>

                        <section class="rounded-[32px] border border-white/80 bg-white/88 p-6 shadow-[0_34px_85px_-56px_rgba(11,77,89,0.6)] sm:p-8">
                            <div class="flex items-end justify-between gap-4">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.22em] text-sky-700">Active order stream</p>
                                    <h3 class="mt-2 text-2xl font-semibold text-slate-900">Current operational load</h3>
                                </div>

                                <span class="rounded-full border border-white/80 bg-[#eef7ff] px-4 py-2 text-sm font-medium text-slate-600 shadow-[0_18px_44px_-30px_rgba(11,77,89,0.4)]">
                                    {{ activeOrders.length }} orders
                                </span>
                            </div>

                            <div v-if="activeOrders.length" class="mt-6 grid gap-4">
                                <article
                                    v-for="order in activeOrders"
                                    :key="`restaurant-active-order-${order.id}`"
                                    class="rounded-[26px] border border-[#edf2f2] bg-[linear-gradient(135deg,#ffffff_0%,#eef8ff_62%,#f4fbfb_100%)] p-5"
                                >
                                    <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                                        <div>
                                            <div class="flex flex-wrap items-center gap-3">
                                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-sky-700">{{ order.orderNumber }}</p>
                                                <span :class="order.statusAccent" class="rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-[0.16em] ring-1">
                                                    {{ order.statusLabel }}
                                                </span>
                                            </div>
                                            <h4 class="mt-2 text-lg font-semibold text-slate-900">{{ order.customerName }}</h4>
                                            <p class="mt-2 text-sm leading-6 text-slate-600">{{ order.summary }}</p>
                                        </div>

                                        <div class="grid gap-2 text-sm text-slate-600 md:text-right">
                                            <p><span class="font-semibold text-slate-900">Placed:</span> {{ order.placedAt }}</p>
                                            <p><span class="font-semibold text-slate-900">Total:</span> {{ order.total }}</p>
                                            <p><span class="font-semibold text-slate-900">Courier:</span> {{ order.courierName ?? 'Unassigned' }}</p>
                                            <p><span class="font-semibold text-slate-900">Stop:</span> {{ order.destinationShortLabel }}</p>
                                        </div>
                                    </div>
                                </article>
                            </div>

                            <div v-else class="mt-6 rounded-[28px] border border-dashed border-[#d8e7e8] bg-[#fffcf8] p-8 text-sm leading-6 text-slate-500">
                                This restaurant has no active preparing or on-the-way orders right now.
                            </div>
                        </section>
                    </section>
                </section>
            </div>
        </div>
    </AdminLayout>
</template>