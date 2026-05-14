<script setup>
import AddToCartButton from '@/Components/Cart/AddToCartButton.vue';
import ReorderToCartButton from '@/Components/Cart/ReorderToCartButton.vue';
import CustomerLayout from '@/Layouts/CustomerLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    order: {
        type: Object,
        default: () => ({
            id: null,
            orderNumber: '',
            restaurant: {
                id: null,
                name: '',
                slug: '',
                category: '',
                cuisine: '',
                featured: '',
                eta: '',
                deliveryFee: '',
            },
            status: {
                key: '',
                label: '',
                accent: 'bg-gray-100 text-gray-700',
            },
            guidance: {
                title: '',
                description: '',
            },
            timeline: [],
            summary: '',
            placedAt: '',
            placedAtDate: '',
            paymentMethod: '',
            driverNotes: '',
            canTrack: false,
            canReorder: false,
            reorder: {
                visible: false,
                available: false,
                label: null,
                description: null,
                items: [],
            },
            items: [],
            totals: {
                subtotal: { formatted: '₱0' },
                delivery: { formatted: '₱0' },
                serviceFee: { formatted: '₱0' },
                total: { formatted: '₱0' },
            },
            destination: {
                address: '',
                shortLabel: '',
                hasCoordinates: false,
                mapsUrl: null,
            },
        }),
    },
});

const orderSignals = computed(() => [
    {
        label: 'Placed',
        value: props.order.placedAtDate,
        detail: props.order.placedAt,
        tone: 'text-[var(--brand-orange-deep)]',
        surface: 'bg-[#fff7ef] ring-[#f6dcc5]',
    },
    {
        label: 'Delivery flow',
        value: props.order.status.label,
        detail: props.order.canTrack ? 'This order is still active and trackable.' : 'This order has finished its delivery flow.',
        tone: 'text-[var(--brand-teal)]',
        surface: 'bg-[#f4fbfb] ring-[#dceced]',
    },
    {
        label: 'Payment',
        value: props.order.paymentMethod,
        detail: 'Stored payment method used during checkout.',
        tone: 'text-sky-700',
        surface: 'bg-[#eef7ff] ring-[#d5e8fb]',
    },
    {
        label: 'Order total',
        value: props.order.totals.total.formatted,
        detail: 'Final charged total including delivery and service fee.',
        tone: 'text-emerald-700',
        surface: 'bg-[#eefcf5] ring-[#d3f2df]',
    },
]);

const hasDriverNotes = computed(() => Boolean(props.order.driverNotes));

const timelineTone = (step) => {
    if (step.state === 'complete') {
        return 'bg-[var(--brand-teal)] text-white ring-[var(--brand-teal)] shadow-[0_16px_36px_-26px_rgba(11,77,89,0.8)]';
    }

    if (step.state === 'current') {
        return 'bg-[#fff7ef] text-[var(--brand-orange-deep)] ring-[#f6dcc5]';
    }

    return 'bg-white text-slate-400 ring-[#e4eded]';
};

const timelineCopyTone = (step) => {
    if (step.state === 'current') {
        return 'text-slate-700';
    }

    if (step.state === 'complete') {
        return 'text-slate-600';
    }

    return 'text-slate-400';
};
</script>

<template>
    <Head :title="`Order ${order.orderNumber}`" />

    <CustomerLayout>
        <template #header>
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div class="space-y-3">
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-[var(--brand-orange-deep)]">
                        Order detail
                    </p>
                    <h2 class="text-3xl font-semibold leading-tight text-slate-900 sm:text-4xl">
                        {{ order.orderNumber }} keeps delivery, payment, and item context in one view.
                    </h2>
                    <p class="max-w-2xl text-sm leading-6 text-slate-600">
                        This detail screen is designed for clarity under motion: you can see status, delivery context, totals, and kitchen links without digging through the full order list.
                    </p>
                </div>

                <div class="flex flex-col gap-3 sm:flex-row">
                    <Link
                        :href="route('customer.orders.index')"
                        class="inline-flex items-center justify-center rounded-full border border-white/80 bg-white/80 px-5 py-3 text-sm font-semibold text-slate-700 transition duration-200 hover:text-[var(--brand-teal)]"
                    >
                        Back to orders
                    </Link>
                    <ReorderToCartButton
                        v-if="order.canReorder"
                        :order-id="order.id"
                        :restaurant-id="order.restaurant.id"
                        :restaurant-name="order.restaurant.name"
                        :order-number="order.orderNumber"
                        button-class="inline-flex items-center justify-center rounded-full bg-[var(--brand-orange-deep)] px-5 py-3 text-sm font-semibold text-white shadow-[0_20px_45px_-24px_rgba(197,92,24,0.82)] transition duration-200 hover:-translate-y-0.5"
                    />
                    <Link
                        :href="route('customer.restaurants.show', order.restaurant.slug)"
                        class="inline-flex items-center justify-center rounded-full bg-[var(--brand-teal)] px-5 py-3 text-sm font-semibold text-white shadow-[0_20px_45px_-24px_rgba(11,77,89,0.85)] transition duration-200 hover:-translate-y-0.5"
                    >
                        Open kitchen menu
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
                                    {{ order.restaurant.name }}
                                </span>
                                <span class="rounded-full px-4 py-2 text-xs font-semibold uppercase tracking-[0.18em] ring-1" :class="order.status.accent">
                                    {{ order.status.label }}
                                </span>
                            </div>

                            <div class="space-y-4">
                                <h3 class="max-w-3xl text-3xl font-semibold leading-tight text-slate-900 sm:text-[2.5rem]">
                                    {{ order.summary }}
                                </h3>
                                <p class="max-w-2xl text-sm leading-7 text-slate-600">
                                    {{ order.restaurant.category }} kitchen · {{ order.restaurant.cuisine }} · {{ order.restaurant.eta }} delivery window
                                </p>
                            </div>

                            <div class="grid gap-4 sm:grid-cols-2">
                                <article
                                    v-for="signal in orderSignals"
                                    :key="signal.label"
                                    class="rounded-[28px] border border-white/80 bg-white/88 p-5 shadow-[0_28px_70px_-48px_rgba(11,77,89,0.55)]"
                                >
                                    <div class="inline-flex rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] ring-1" :class="[signal.surface, signal.tone]">
                                        {{ signal.label }}
                                    </div>
                                    <p class="mt-4 text-xl font-semibold text-slate-900">
                                        {{ signal.value }}
                                    </p>
                                    <p class="mt-3 text-sm leading-6 text-slate-500">
                                        {{ signal.detail }}
                                    </p>
                                </article>
                            </div>

                            <section class="rounded-[30px] border border-white/80 bg-white/90 p-6 shadow-[0_28px_70px_-48px_rgba(11,77,89,0.55)]">
                                <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-[var(--brand-teal)]">Tracking timeline</p>
                                        <h3 class="mt-2 text-2xl font-semibold text-slate-900">See exactly where the order is in the delivery flow.</h3>
                                    </div>

                                    <span class="rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-[0.16em] ring-1" :class="order.status.accent">
                                        {{ order.status.label }}
                                    </span>
                                </div>

                                <div class="mt-6 grid gap-4 lg:grid-cols-4">
                                    <article
                                        v-for="(step, index) in order.timeline"
                                        :key="step.key"
                                        class="relative rounded-[24px] border border-[#edf2f2] bg-[linear-gradient(135deg,#ffffff_0%,#fff8f1_64%,#f4fbfb_100%)] p-5"
                                    >
                                        <div class="flex items-center gap-3">
                                            <span
                                                class="inline-flex h-10 w-10 items-center justify-center rounded-full text-sm font-semibold ring-1 transition"
                                                :class="timelineTone(step)"
                                            >
                                                {{ index + 1 }}
                                            </span>
                                            <span class="text-[11px] font-semibold uppercase tracking-[0.18em]" :class="timelineCopyTone(step)">
                                                {{ step.state === 'complete' ? 'Complete' : step.state === 'current' ? 'Current' : 'Upcoming' }}
                                            </span>
                                        </div>

                                        <h4 class="mt-4 text-lg font-semibold text-slate-900">{{ step.label }}</h4>
                                        <p class="mt-2 text-sm leading-6" :class="timelineCopyTone(step)">{{ step.description }}</p>
                                    </article>
                                </div>
                            </section>
                        </div>

                        <div class="space-y-4">
                            <section class="rounded-[30px] border border-white/80 bg-white/90 p-6 shadow-[0_28px_70px_-48px_rgba(11,77,89,0.55)]">
                                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-[var(--brand-teal)]">Delivery destination</p>
                                <h3 class="mt-2 text-2xl font-semibold text-slate-900">{{ order.destination.shortLabel }}</h3>
                                <p class="mt-3 text-sm leading-6 text-slate-600">{{ order.destination.address || 'Delivery address will appear here once set.' }}</p>

                                <div class="mt-4 flex flex-wrap gap-3">
                                    <span
                                        class="rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-[0.16em] ring-1"
                                        :class="order.destination.hasCoordinates
                                            ? 'bg-[#e9fbf8] text-[var(--brand-teal)] ring-[#bfe3da]'
                                            : 'bg-white text-slate-600 ring-[#e4eded]'"
                                    >
                                        {{ order.destination.hasCoordinates ? 'Map pinned' : 'Address only' }}
                                    </span>
                                    <a
                                        v-if="order.destination.mapsUrl"
                                        :href="order.destination.mapsUrl"
                                        target="_blank"
                                        rel="noreferrer"
                                        class="inline-flex items-center justify-center rounded-full border border-white/80 bg-[#fff7ef] px-4 py-2 text-sm font-semibold text-slate-700 shadow-[0_18px_42px_-32px_rgba(11,77,89,0.35)] transition duration-200 hover:text-[var(--brand-teal)]"
                                    >
                                        Open in Maps
                                    </a>
                                </div>
                            </section>

                            <section class="rounded-[30px] border border-white/80 bg-[linear-gradient(135deg,#f4fbfb_0%,#ffffff_100%)] p-6 shadow-[0_28px_70px_-48px_rgba(11,77,89,0.42)]">
                                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-[var(--brand-teal)]">What happens next</p>
                                <h3 class="mt-2 text-2xl font-semibold text-slate-900">{{ order.guidance.title }}</h3>
                                <p class="mt-3 text-sm leading-7 text-slate-600">
                                    {{ order.guidance.description }}
                                </p>

                                <div
                                    v-if="order.reorder.visible && order.reorder.description"
                                    class="mt-5 rounded-[24px] border p-4"
                                    :class="order.canReorder ? 'border-[var(--brand-orange-deep)]/12 bg-[#fff7ef]' : 'border-[#e4eded] bg-white'"
                                >
                                    <p
                                        class="text-xs font-semibold uppercase tracking-[0.18em]"
                                        :class="order.canReorder ? 'text-[var(--brand-orange-deep)]' : 'text-slate-500'"
                                    >
                                        {{ order.reorder.label }}
                                    </p>
                                    <p class="mt-2 text-sm leading-6 text-slate-700">
                                        {{ order.reorder.description }}
                                    </p>

                                    <div v-if="order.reorder.items?.length" class="mt-4 space-y-3">
                                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Affected items</p>

                                        <article
                                            v-for="item in order.reorder.items"
                                            :key="`${item.name}-${item.reason}`"
                                            class="rounded-[18px] border border-[#e4eded] bg-[#f8fbfb] px-4 py-3"
                                        >
                                            <p class="text-sm font-semibold text-slate-900">{{ item.name }}</p>
                                            <p class="mt-1 text-sm leading-6 text-slate-600">{{ item.reason }}</p>

                                            <div v-if="item.suggestions?.length" class="mt-4 space-y-3">
                                                <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-[var(--brand-teal)]">
                                                    Try these live alternatives
                                                </p>

                                                <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                                                    <article
                                                        v-for="suggestion in item.suggestions"
                                                        :key="suggestion.id"
                                                        class="rounded-[16px] border border-[#dceced] bg-white px-4 py-3 shadow-[0_18px_40px_-34px_rgba(11,77,89,0.28)]"
                                                    >
                                                        <div class="flex items-start justify-between gap-3">
                                                            <div>
                                                                <p class="text-sm font-semibold text-slate-900">{{ suggestion.name }}</p>
                                                                <p v-if="suggestion.category" class="mt-1 text-xs font-medium uppercase tracking-[0.14em] text-slate-500">
                                                                    {{ suggestion.category }}
                                                                </p>
                                                            </div>
                                                            <span class="rounded-full bg-[#f4fbfb] px-3 py-1 text-xs font-semibold text-[var(--brand-teal)] ring-1 ring-[#dceced]">
                                                                {{ suggestion.price.formatted }}
                                                            </span>
                                                        </div>

                                                        <div class="mt-4 flex flex-wrap gap-3 border-t border-[#edf2f2] pt-3">
                                                            <AddToCartButton
                                                                :menu-item-id="suggestion.id"
                                                                :restaurant-id="order.restaurant.id"
                                                                :restaurant-name="order.restaurant.name"
                                                                :item-name="suggestion.name"
                                                                :redirect-to="route('customer.orders.show', order.id)"
                                                                label="Add to cart"
                                                                button-class="inline-flex items-center justify-center rounded-full bg-gradient-to-r from-[var(--brand-teal)] to-[var(--brand-orange)] px-4 py-2 text-sm font-semibold text-white shadow-[0_18px_42px_-28px_rgba(11,77,89,0.82)] transition duration-200 hover:-translate-y-0.5"
                                                            />

                                                            <Link
                                                                :href="route('customer.restaurants.show', order.restaurant.slug)"
                                                                class="inline-flex items-center justify-center rounded-full border border-white/80 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-[0_18px_42px_-32px_rgba(11,77,89,0.35)] transition duration-200 hover:text-[var(--brand-teal)]"
                                                            >
                                                                Open kitchen
                                                            </Link>
                                                        </div>
                                                    </article>
                                                </div>
                                            </div>
                                        </article>
                                    </div>
                                </div>
                            </section>

                            <section class="rounded-[30px] border border-white/80 bg-[linear-gradient(135deg,#fffaf3_0%,#ffffff_100%)] p-6 shadow-[0_28px_70px_-48px_rgba(11,77,89,0.42)]">
                                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-[var(--brand-orange-deep)]">Driver notes</p>
                                <p class="mt-3 text-sm leading-7 text-slate-600">
                                    {{ hasDriverNotes ? order.driverNotes : 'No special drop-off or driver note was saved for this order.' }}
                                </p>
                            </section>
                        </div>
                    </div>
                </section>

                <section class="grid gap-6 xl:grid-cols-[1.05fr_0.95fr]">
                    <section class="rounded-[32px] border border-white/80 bg-white/88 p-6 shadow-[0_34px_85px_-56px_rgba(11,77,89,0.6)] sm:p-8">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-[var(--brand-orange-deep)]">Items in this order</p>
                            <h3 class="mt-2 text-2xl font-semibold text-slate-900">Everything placed in a clean itemized view.</h3>
                        </div>

                        <div class="mt-6 grid gap-4">
                            <article
                                v-for="item in order.items"
                                :key="`${item.name}-${item.quantityValue}`"
                                class="rounded-[26px] border border-[#edf2f2] bg-[linear-gradient(135deg,#ffffff_0%,#fff8f1_62%,#f4fbfb_100%)] p-5"
                            >
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[var(--brand-teal)]">{{ item.quantityLabel }}</p>
                                        <h4 class="mt-2 text-lg font-semibold text-slate-900">{{ item.name }}</h4>
                                        <p class="mt-2 text-sm text-slate-500">{{ item.unitPrice }} each</p>
                                    </div>
                                    <span class="rounded-full bg-white px-3 py-1 text-sm font-semibold text-slate-700 ring-1 ring-[#e4eded]">
                                        {{ item.lineTotal }}
                                    </span>
                                </div>
                            </article>
                        </div>
                    </section>

                    <section class="rounded-[32px] border border-white/80 bg-white/88 p-6 shadow-[0_34px_85px_-56px_rgba(11,77,89,0.6)] sm:p-8">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-[var(--brand-teal)]">Charges and payment</p>
                            <h3 class="mt-2 text-2xl font-semibold text-slate-900">Final totals stay transparent after checkout.</h3>
                        </div>

                        <div class="mt-6 space-y-4 rounded-[28px] bg-[#f8fbfb] p-5 ring-1 ring-[#e2eded]">
                            <div class="flex items-center justify-between gap-4 text-sm text-slate-600">
                                <span>Subtotal</span>
                                <span class="font-semibold text-slate-900">{{ order.totals.subtotal.formatted }}</span>
                            </div>
                            <div class="flex items-center justify-between gap-4 text-sm text-slate-600">
                                <span>Delivery</span>
                                <span class="font-semibold text-slate-900">{{ order.totals.delivery.formatted }}</span>
                            </div>
                            <div class="flex items-center justify-between gap-4 text-sm text-slate-600">
                                <span>Service fee</span>
                                <span class="font-semibold text-slate-900">{{ order.totals.serviceFee.formatted }}</span>
                            </div>
                            <div class="flex items-center justify-between gap-4 border-t border-[#dfeaea] pt-4 text-base font-semibold text-slate-900">
                                <span>Total</span>
                                <span>{{ order.totals.total.formatted }}</span>
                            </div>
                        </div>

                        <div class="mt-6 rounded-[28px] bg-[#fffaf3] p-5 ring-1 ring-[#f0dfcf]">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[var(--brand-orange-deep)]">Payment method</p>
                            <p class="mt-2 text-lg font-semibold text-slate-900">{{ order.paymentMethod }}</p>
                            <p class="mt-2 text-sm leading-6 text-slate-600">Stored directly from the checkout step for easier support and customer clarity later.</p>
                        </div>
                    </section>
                </section>
            </div>
        </div>
    </CustomerLayout>
</template>