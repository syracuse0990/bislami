<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head } from '@inertiajs/vue3';

const props = defineProps({
    overview: {
        type: Object,
        default: () => ({
            activeOrders: 0,
            preparingOrders: 0,
            awaitingCourier: 0,
            claimedDeliveries: 0,
            pinnedDestinations: 0,
        }),
    },
    dispatchBoard: {
        type: Array,
        default: () => [],
    },
});

const metrics = [
    {
        label: 'Active orders',
        value: props.overview.activeOrders,
        detail: 'Preparing and on-the-way orders across the platform',
        tone: 'text-[var(--brand-teal)]',
    },
    {
        label: 'Preparing kitchens',
        value: props.overview.preparingOrders,
        detail: 'Orders still inside the merchant handoff stage',
        tone: 'text-[var(--brand-orange-deep)]',
    },
    {
        label: 'Awaiting courier',
        value: props.overview.awaitingCourier,
        detail: 'Orders on the road queue that still need a rider claim',
        tone: 'text-sky-700',
    },
    {
        label: 'Claimed deliveries',
        value: props.overview.claimedDeliveries,
        detail: 'Active routes already owned by a courier',
        tone: 'text-emerald-700',
    },
    {
        label: 'Pinned destinations',
        value: props.overview.pinnedDestinations,
        detail: 'Orders already carrying precise map coordinates',
        tone: 'text-fuchsia-700',
    },
];
</script>

<template>
    <Head title="Admin Overview" />

    <AdminLayout>
        <template #header>
            <div class="space-y-3">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-[var(--brand-orange-deep)]">
                    Dispatch overview
                </p>
                <h2 class="text-3xl font-semibold leading-tight text-slate-900">
                    See kitchen pressure, courier claims, and destination quality in one board.
                </h2>
                <p class="max-w-2xl text-sm leading-6 text-slate-600">
                    The admin workspace now reflects the live order stream, using the same normalized payload that can later back mobile ops tooling.
                </p>
            </div>
        </template>

        <div class="py-8">
            <div class="space-y-6 px-4 sm:px-6 lg:px-0">
                <section class="rounded-[32px] border border-white/80 bg-[linear-gradient(135deg,#ffffff_0%,#fff8f1_58%,#f3fbfb_100%)] p-6 shadow-[0_34px_85px_-56px_rgba(11,77,89,0.6)] sm:p-8">
                    <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-5">
                        <article
                            v-for="metric in metrics"
                            :key="metric.label"
                            class="rounded-[28px] border border-white/80 bg-white/88 p-6 shadow-[0_28px_70px_-48px_rgba(11,77,89,0.55)]"
                        >
                            <p class="text-sm uppercase tracking-[0.2em]" :class="metric.tone">
                                {{ metric.label }}
                            </p>
                            <p class="mt-3 text-3xl font-semibold text-slate-900">
                                {{ metric.value }}
                            </p>
                            <p class="mt-3 text-sm leading-6 text-slate-500">
                                {{ metric.detail }}
                            </p>
                        </article>
                    </div>
                </section>

                <section class="rounded-[32px] border border-white/80 bg-white/88 p-6 shadow-[0_34px_85px_-56px_rgba(11,77,89,0.6)] sm:p-8">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-[var(--brand-teal)]">Dispatch board</p>
                            <h3 class="mt-2 text-2xl font-semibold text-slate-900">{{ dispatchBoard.length }} active order{{ dispatchBoard.length === 1 ? '' : 's' }} in the shared operational stream</h3>
                        </div>

                        <div class="rounded-full border border-white/80 bg-[#f4fbfb] px-4 py-2 text-sm font-medium text-slate-600 shadow-[0_20px_48px_-36px_rgba(11,77,89,0.35)]">
                            Same payload shape, ready to reuse in later admin or mobile clients.
                        </div>
                    </div>

                    <div v-if="dispatchBoard.length" class="mt-6 grid gap-4">
                        <article
                            v-for="order in dispatchBoard"
                            :key="order.id"
                            class="rounded-[28px] border border-[#edf2f2] bg-[linear-gradient(135deg,#ffffff_0%,#fff8f1_62%,#f4fbfb_100%)] p-5"
                        >
                            <div class="flex flex-col gap-5 xl:flex-row xl:items-start xl:justify-between">
                                <div class="flex items-start gap-4">
                                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-white p-2 ring-1 ring-[#e4eded]">
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
                                            <h3 class="text-lg font-semibold text-slate-900">
                                                {{ order.restaurantName }} -> {{ order.destination.shortLabel }}
                                            </h3>
                                            <p class="mt-1 text-sm text-slate-600">{{ order.summary }}</p>
                                        </div>

                                        <div class="grid gap-3 text-sm text-slate-600 sm:grid-cols-4">
                                            <div>
                                                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-[var(--brand-teal)]">Customer</p>
                                                <p class="mt-1 text-slate-900">{{ order.customerName }}</p>
                                            </div>
                                            <div>
                                                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-[var(--brand-teal)]">Assigned courier</p>
                                                <p class="mt-1 text-slate-900">{{ order.assignment.label }}</p>
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

                                <div class="rounded-[24px] border border-[#edf2f2] bg-white/92 p-5 xl:w-[320px]">
                                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[var(--brand-orange-deep)]">Destination quality</p>
                                    <p class="mt-3 text-sm leading-6 text-slate-700">{{ order.destination.address }}</p>

                                    <p v-if="order.driverNotes" class="mt-3 text-sm leading-6 text-slate-500">
                                        Note: {{ order.driverNotes }}
                                    </p>

                                    <div class="mt-4 flex flex-wrap gap-3">
                                        <span
                                            class="rounded-full px-3 py-2 text-xs font-semibold uppercase tracking-[0.16em] ring-1"
                                            :class="order.assignment.claimed
                                                ? 'bg-[#e9fbf8] text-[var(--brand-teal)] ring-[#bfe3da]'
                                                : 'bg-white text-slate-600 ring-[#e4eded]'"
                                        >
                                            {{ order.assignment.claimed ? 'Courier claimed' : 'Unclaimed route' }}
                                        </span>

                                        <span
                                            class="rounded-full px-3 py-2 text-xs font-semibold uppercase tracking-[0.16em] ring-1"
                                            :class="order.destination.hasCoordinates
                                                ? 'bg-[#fff0fa] text-fuchsia-700 ring-fuchsia-200'
                                                : 'bg-white text-slate-600 ring-[#e4eded]'"
                                        >
                                            {{ order.destination.hasCoordinates ? 'Map ready' : 'Address only' }}
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

                    <div v-else class="mt-6 rounded-[28px] border border-dashed border-[#d8e7e8] bg-[#fffcf8] p-8 text-sm leading-6 text-slate-500">
                        No active dispatch activity right now. When merchants and couriers start moving orders again, the normalized live board will appear here.
                    </div>
                </section>
            </div>
        </div>
    </AdminLayout>
</template>