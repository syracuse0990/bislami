<script setup>
import OrderCard from '@/Features/customer/orders/components/OrderCard.vue';
import CustomerLayout from '@/Layouts/CustomerLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    orders: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({
            status: 'all',
        }),
    },
    overview: {
        type: Object,
        default: () => ({
            activeOrdersCount: 0,
            completedOrdersCount: 0,
            totalOrdersCount: 0,
        }),
    },
});

const tabs = [
    { key: 'all', label: 'Everything', description: 'Active and completed orders in one timeline.' },
    { key: 'active', label: 'Live now', description: 'Preparing and on-the-way orders that need attention.' },
    { key: 'history', label: 'Delivered', description: 'Completed meals ready for review or re-order context.' },
];

const activeTab = computed(() => tabs.find((tab) => tab.key === props.filters.status) ?? tabs[0]);

const emptyState = computed(() => {
    if (props.filters.status === 'active') {
        return {
            title: 'No live deliveries right now',
            copy: 'As soon as a kitchen starts preparing your meal, it will appear here with tracking and destination detail.',
        };
    }

    if (props.filters.status === 'history') {
        return {
            title: 'No completed orders yet',
            copy: 'Delivered meals will collect here so you can revisit payment, items, and your favorite kitchens.',
        };
    }

    return {
        title: 'Your delivery timeline starts here',
        copy: 'Once you place an order, BizLami will keep the live steps, charges, and restaurant context in one clean history.',
    };
});

function tabCount(key) {
    if (key === 'active') {
        return props.overview.activeOrdersCount;
    }

    if (key === 'history') {
        return props.overview.completedOrdersCount;
    }

    return props.overview.totalOrdersCount;
}

function tabHref(key) {
    return key === 'all'
        ? route('customer.orders.index')
        : route('customer.orders.index', { status: key });
}
</script>

<template>
    <Head title="Orders" />

    <CustomerLayout>
        <template #header>
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div class="space-y-3">
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-[var(--brand-orange-deep)]">
                        Order history
                    </p>
                    <h2 class="text-3xl font-semibold leading-tight text-slate-900">
                        Track what you have ordered without losing the big picture.
                    </h2>
                    <p class="max-w-2xl text-sm leading-6 text-slate-600">
                        Completed and active orders stay organized in one clear list, and every card now opens into a detail view with delivery, payment, and item context.
                    </p>
                </div>

                <Link
                    :href="route('customer.restaurants.index')"
                    class="inline-flex items-center justify-center rounded-full bg-[var(--brand-teal)] px-5 py-3 text-sm font-semibold text-white shadow-[0_20px_45px_-24px_rgba(11,77,89,0.85)] transition duration-200 hover:-translate-y-0.5"
                >
                    Browse restaurants
                </Link>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-6xl space-y-6 sm:px-6 lg:px-8">
                <section class="grid gap-4 xl:grid-cols-[minmax(0,1.55fr)_repeat(3,minmax(0,1fr))]">
                    <article class="rounded-[34px] border border-white/80 bg-[linear-gradient(135deg,#fff8ef_0%,#ffffff_46%,#eef8f8_100%)] p-6 shadow-[0_34px_88px_-56px_rgba(11,77,89,0.58)] sm:p-8">
                        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-[var(--brand-orange-deep)]">Order command center</p>
                        <h3 class="mt-3 text-2xl font-semibold text-slate-900 sm:text-[2rem]">{{ activeTab.label }} orders, arranged for fast scanning</h3>
                        <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600">
                            {{ activeTab.description }} Open any card to inspect delivery steps, payment details, destination context, and item breakdown without losing your place.
                        </p>

                        <div class="mt-6 flex flex-wrap gap-3">
                            <div class="rounded-full border border-white/80 bg-white/85 px-4 py-2 text-sm font-medium text-slate-700 shadow-[0_18px_42px_-32px_rgba(11,77,89,0.32)]">
                                {{ orders.length }} visible now
                            </div>
                            <div class="rounded-full border border-[var(--brand-teal)]/15 bg-[rgba(11,77,89,0.08)] px-4 py-2 text-sm font-medium text-[var(--brand-teal)]">
                                One tap to tracking, totals, and address details
                            </div>
                        </div>
                    </article>

                    <article class="rounded-[30px] border border-white/80 bg-white/88 p-6 shadow-[0_28px_70px_-48px_rgba(11,77,89,0.45)]">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-[var(--brand-teal)]">Active now</p>
                        <p class="mt-4 text-3xl font-semibold text-slate-900">{{ overview.activeOrdersCount }}</p>
                        <p class="mt-2 text-sm leading-6 text-slate-500">Orders currently preparing or already on the road.</p>
                    </article>

                    <article class="rounded-[30px] border border-white/80 bg-white/88 p-6 shadow-[0_28px_70px_-48px_rgba(11,77,89,0.45)]">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-[var(--brand-orange-deep)]">Delivered</p>
                        <p class="mt-4 text-3xl font-semibold text-slate-900">{{ overview.completedOrdersCount }}</p>
                        <p class="mt-2 text-sm leading-6 text-slate-500">Completed meals saved for review and easy repeat decisions.</p>
                    </article>

                    <article class="rounded-[30px] border border-white/80 bg-[linear-gradient(180deg,#ffffff_0%,#f7fbfb_100%)] p-6 shadow-[0_28px_70px_-48px_rgba(11,77,89,0.45)]">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Lifetime total</p>
                        <p class="mt-4 text-3xl font-semibold text-slate-900">{{ overview.totalOrdersCount }}</p>
                        <p class="mt-2 text-sm leading-6 text-slate-500">Your full customer order history across live and completed meals.</p>
                    </article>
                </section>

                <section class="rounded-[32px] border border-white/80 bg-white/86 p-5 shadow-[0_28px_70px_-48px_rgba(11,77,89,0.42)] sm:p-6">
                    <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-[var(--brand-teal)]">History view</p>
                            <h3 class="mt-2 text-2xl font-semibold text-slate-900">Choose the signal you want to focus on</h3>
                            <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">
                                Switch between live deliveries and completed history without losing the same polished card layout.
                            </p>
                        </div>

                        <div class="flex flex-wrap gap-3">
                            <Link
                                v-for="tab in tabs"
                                :key="tab.key"
                                :href="tabHref(tab.key)"
                                prefetch
                                preserve-scroll
                                class="inline-flex items-center gap-3 rounded-full border px-4 py-2 text-sm font-semibold transition duration-200"
                                :class="props.filters.status === tab.key
                                    ? 'border-[var(--brand-teal)] bg-[var(--brand-teal)] text-white shadow-[0_20px_45px_-24px_rgba(11,77,89,0.75)]'
                                    : 'border-white/80 bg-white text-slate-700 shadow-[0_18px_42px_-32px_rgba(11,77,89,0.3)] hover:border-[var(--brand-teal)]/20 hover:text-[var(--brand-teal)]'"
                            >
                                <span>{{ tab.label }}</span>
                                <span
                                    class="rounded-full px-2.5 py-1 text-xs"
                                    :class="props.filters.status === tab.key ? 'bg-white/18 text-white' : 'bg-[#f4fbfb] text-[var(--brand-teal)]'"
                                >
                                    {{ tabCount(tab.key) }}
                                </span>
                            </Link>
                        </div>
                    </div>
                </section>

                <div v-if="orders.length" class="space-y-6">
                    <OrderCard
                        v-for="order in orders"
                        :key="order.id"
                        v-bind="order"
                    />
                </div>

                <section v-else class="rounded-[32px] border border-dashed border-[var(--brand-teal)]/18 bg-[linear-gradient(135deg,rgba(255,255,255,0.92)_0%,rgba(243,251,251,0.95)_100%)] p-8 shadow-[0_30px_80px_-52px_rgba(11,77,89,0.4)]">
                    <p class="text-xs font-semibold uppercase tracking-[0.22em] text-[var(--brand-orange-deep)]">Nothing in this view yet</p>
                    <h3 class="mt-3 text-2xl font-semibold text-slate-900">{{ emptyState.title }}</h3>
                    <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600">{{ emptyState.copy }}</p>

                    <div class="mt-6 flex flex-wrap gap-3">
                        <Link
                            :href="route('customer.restaurants.index')"
                            class="inline-flex items-center justify-center rounded-full bg-[var(--brand-teal)] px-5 py-3 text-sm font-semibold text-white shadow-[0_20px_45px_-24px_rgba(11,77,89,0.85)] transition duration-200 hover:-translate-y-0.5"
                        >
                            Browse restaurants
                        </Link>

                        <Link
                            v-if="props.filters.status !== 'all'"
                            :href="route('customer.orders.index')"
                            class="inline-flex items-center justify-center rounded-full border border-white/80 bg-white px-5 py-3 text-sm font-semibold text-slate-700 shadow-[0_18px_42px_-32px_rgba(11,77,89,0.35)] transition duration-200 hover:text-[var(--brand-teal)]"
                        >
                            View all orders
                        </Link>
                    </div>
                </section>
            </div>
        </div>
    </CustomerLayout>
</template>