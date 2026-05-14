<script setup>
import MerchantLayout from '@/Layouts/MerchantLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    queue: {
        type: Array,
        default: () => [],
    },
});
</script>

<template>
    <Head title="Merchant Orders" />

    <MerchantLayout>
        <template #header>
            <div class="space-y-3">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-[var(--brand-orange-deep)]">
                    Live order queue
                </p>
                <h2 class="text-3xl font-semibold leading-tight text-slate-900">
                    Keep the kitchen line focused with a clearer live queue.
                </h2>
                <p class="max-w-2xl text-sm leading-6 text-slate-600">
                    Open tickets remain readable and on-brand so merchants can move from prep to rider handoff faster.
                </p>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-6xl space-y-6 sm:px-6 lg:px-8">
                <section class="rounded-[32px] border border-white/80 bg-[linear-gradient(135deg,#ffffff_0%,#fff8f1_58%,#f3fbfb_100%)] p-6 shadow-[0_34px_85px_-56px_rgba(11,77,89,0.6)] sm:p-8">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-[var(--brand-teal)]">Kitchen dispatch board</p>
                            <h3 class="mt-2 text-2xl font-semibold text-slate-900">{{ queue.length }} active order{{ queue.length === 1 ? '' : 's' }} ready for kitchen follow-through</h3>
                        </div>

                        <div class="rounded-full border border-white/80 bg-white/85 px-4 py-2 text-sm font-medium text-slate-600 shadow-[0_20px_48px_-36px_rgba(11,77,89,0.35)]">
                            Live destination and handoff data for future app clients.
                        </div>
                    </div>

                    <div v-if="queue.length" class="mt-6 grid gap-4">
                        <article
                            v-for="order in queue"
                            :key="order.id"
                            class="rounded-[28px] border border-white/80 bg-white/88 p-5 shadow-[0_28px_70px_-48px_rgba(11,77,89,0.55)]"
                        >
                            <div class="flex flex-col gap-5 xl:flex-row xl:items-start xl:justify-between">
                                <div class="flex items-start gap-4">
                                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-[#fff7ef] p-2 ring-1 ring-[#f6dcc5]">
                                        <img src="/images/bizlami_icon.png" alt="BizLami icon" class="h-full w-full object-contain">
                                    </div>

                                    <div class="space-y-4">
                                        <div class="flex flex-wrap items-center gap-3">
                                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[var(--brand-orange-deep)]">
                                                {{ order.orderNumber }}
                                            </p>
                                            <span :class="order.statusAccent" class="rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-[0.16em] ring-1">
                                                {{ order.statusLabel }}
                                            </span>
                                        </div>

                                        <div>
                                            <h3 class="text-lg font-semibold text-slate-900">{{ order.restaurantName }}</h3>
                                            <p class="mt-1 text-sm text-slate-600">{{ order.summary }}</p>
                                        </div>

                                        <div class="grid gap-3 text-sm text-slate-600 sm:grid-cols-3">
                                            <div>
                                                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-[var(--brand-teal)]">Customer</p>
                                                <p class="mt-1 text-slate-900">{{ order.customerName }}</p>
                                            </div>
                                            <div>
                                                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-[var(--brand-teal)]">Placed</p>
                                                <p class="mt-1 text-slate-900">{{ order.placedAt }}</p>
                                            </div>
                                            <div>
                                                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-[var(--brand-teal)]">Total</p>
                                                <p class="mt-1 text-slate-900">{{ order.total }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="rounded-[24px] border border-[#edf2f2] bg-[#f8fbfb] p-5 xl:w-[320px]">
                                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[var(--brand-teal)]">Delivery destination</p>
                                    <p class="mt-3 text-sm leading-6 text-slate-700">{{ order.destination.address }}</p>

                                    <div class="mt-4 rounded-[20px] bg-white px-4 py-3 ring-1 ring-[#e4eded]">
                                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-[var(--brand-orange-deep)]">Courier assignment</p>
                                        <p class="mt-2 text-sm font-medium text-slate-900">{{ order.assignment.label }}</p>
                                    </div>

                                    <p v-if="order.driverNotes" class="mt-3 text-sm leading-6 text-slate-500">
                                        Note: {{ order.driverNotes }}
                                    </p>

                                    <div class="mt-4 flex flex-wrap gap-3">
                                        <span
                                            class="rounded-full px-3 py-2 text-xs font-semibold uppercase tracking-[0.16em] ring-1"
                                            :class="order.destination.hasCoordinates
                                                ? 'bg-[#e9fbf8] text-[var(--brand-teal)] ring-[#bfe3da]'
                                                : 'bg-white text-slate-600 ring-[#e4eded]'"
                                        >
                                            {{ order.destination.hasCoordinates ? 'Pinned on map' : 'Address only' }}
                                        </span>

                                        <Link
                                            v-if="order.statusKey === 'preparing'"
                                            :href="route('merchant.orders.dispatch', order.id)"
                                            method="post"
                                            as="button"
                                            class="inline-flex items-center justify-center rounded-full bg-[var(--brand-orange)] px-4 py-2 text-xs font-semibold uppercase tracking-[0.16em] text-slate-950 shadow-[0_18px_40px_-26px_rgba(242,126,33,0.75)] transition duration-200 hover:-translate-y-0.5"
                                        >
                                            Hand Off To Rider
                                        </Link>

                                        <span
                                            v-else-if="order.statusKey === 'on_the_way'"
                                            class="inline-flex items-center justify-center rounded-full bg-white px-4 py-2 text-xs font-semibold uppercase tracking-[0.16em] text-slate-600 ring-1 ring-[#e4eded]"
                                        >
                                            Rider In Motion
                                        </span>

                                        <a
                                            v-if="order.destination.mapsUrl"
                                            :href="order.destination.mapsUrl"
                                            target="_blank"
                                            rel="noreferrer"
                                            class="inline-flex items-center justify-center rounded-full bg-[var(--brand-teal)] px-4 py-2 text-xs font-semibold uppercase tracking-[0.16em] text-white shadow-[0_18px_40px_-26px_rgba(11,77,89,0.7)] transition duration-200 hover:-translate-y-0.5"
                                        >
                                            Open map
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </article>
                    </div>

                    <div v-else class="mt-6 rounded-[28px] border border-dashed border-[#d8e7e8] bg-white/80 p-8 text-sm leading-6 text-slate-500">
                        No active merchant orders yet. As soon as a customer places an order, the kitchen queue will show the full destination payload here.
                    </div>
                </section>
            </div>
        </div>
    </MerchantLayout>
</template>