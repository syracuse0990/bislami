<script setup>
import CourierLayout from '@/Layouts/CourierLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    deliveries: {
        type: Array,
        default: () => [],
    },
});
</script>

<template>
    <Head title="Deliveries" />

    <CourierLayout>
        <template #header>
            <div class="space-y-3">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-[var(--brand-orange-deep)]">
                    Delivery queue
                </p>
                <h2 class="text-3xl font-semibold leading-tight text-slate-900">
                    Prioritize the next stops with a cleaner live queue.
                </h2>
                <p class="max-w-2xl text-sm leading-6 text-slate-600">
                    Pickup and drop-off status stay readable so riders can move through the route with less friction.
                </p>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-6xl space-y-6 sm:px-6 lg:px-8">
                <section class="rounded-[32px] border border-white/80 bg-[linear-gradient(135deg,#ffffff_0%,#fff8f1_58%,#f3fbfb_100%)] p-6 shadow-[0_34px_85px_-56px_rgba(11,77,89,0.6)] sm:p-8">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-[var(--brand-teal)]">Route board</p>
                            <h3 class="mt-2 text-2xl font-semibold text-slate-900">{{ deliveries.length }} active delivery{{ deliveries.length === 1 ? '' : 'ies' }} ready for routing</h3>
                        </div>

                        <div class="rounded-full border border-white/80 bg-white/85 px-4 py-2 text-sm font-medium text-slate-600 shadow-[0_20px_48px_-36px_rgba(11,77,89,0.35)]">
                            Structured destination data now mirrors what a later mobile courier app will need.
                        </div>
                    </div>

                    <div v-if="deliveries.length" class="mt-6 grid gap-4">
                        <article
                            v-for="delivery in deliveries"
                            :key="delivery.id"
                            class="rounded-[28px] border border-white/80 bg-white/88 p-5 shadow-[0_28px_70px_-48px_rgba(11,77,89,0.55)]"
                        >
                            <div class="flex flex-col gap-5 xl:flex-row xl:items-start xl:justify-between">
                                <div class="flex items-start gap-4">
                                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-[#f4fbfb] p-2 ring-1 ring-[#dceced]">
                                        <img src="/images/bizlami_icon.png" alt="BizLami icon" class="h-full w-full object-contain">
                                    </div>

                                    <div class="space-y-4">
                                        <div class="flex flex-wrap items-center gap-3">
                                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[var(--brand-orange-deep)]">
                                                {{ delivery.orderNumber }}
                                            </p>
                                            <span :class="delivery.statusAccent" class="rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-[0.16em] ring-1">
                                                {{ delivery.statusLabel }}
                                            </span>
                                        </div>

                                        <div>
                                            <h3 class="text-lg font-semibold text-slate-900">
                                                {{ delivery.restaurantName }} -> {{ delivery.destination.shortLabel }}
                                            </h3>
                                            <p class="mt-1 text-sm text-slate-600">{{ delivery.summary }}</p>
                                        </div>

                                        <div class="grid gap-3 text-sm text-slate-600 sm:grid-cols-3">
                                            <div>
                                                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-[var(--brand-teal)]">Customer</p>
                                                <p class="mt-1 text-slate-900">{{ delivery.customerName }}</p>
                                            </div>
                                            <div>
                                                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-[var(--brand-teal)]">Queued</p>
                                                <p class="mt-1 text-slate-900">{{ delivery.placedAt }}</p>
                                            </div>
                                            <div>
                                                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-[var(--brand-teal)]">Total</p>
                                                <p class="mt-1 text-slate-900">{{ delivery.total }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="rounded-[24px] border border-[#edf2f2] bg-[#fff9f1] p-5 xl:w-[320px]">
                                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[var(--brand-orange-deep)]">Drop-off details</p>
                                    <p class="mt-3 text-sm leading-6 text-slate-700">{{ delivery.destination.address }}</p>

                                    <div class="mt-4 rounded-[20px] bg-white px-4 py-3 ring-1 ring-[#e4eded]">
                                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-[var(--brand-teal)]">Assignment</p>
                                        <p class="mt-2 text-sm font-medium text-slate-900">{{ delivery.assignment.label }}</p>
                                    </div>

                                    <p v-if="delivery.driverNotes" class="mt-3 text-sm leading-6 text-slate-500">
                                        Note: {{ delivery.driverNotes }}
                                    </p>

                                    <div class="mt-4 flex flex-wrap gap-3">
                                        <span
                                            class="rounded-full px-3 py-2 text-xs font-semibold uppercase tracking-[0.16em] ring-1"
                                            :class="delivery.destination.hasCoordinates
                                                ? 'bg-[#e9fbf8] text-[var(--brand-teal)] ring-[#bfe3da]'
                                                : 'bg-white text-slate-600 ring-[#e4eded]'"
                                        >
                                            {{ delivery.destination.hasCoordinates ? 'Precise pin' : 'Address lookup' }}
                                        </span>

                                        <Link
                                            v-if="delivery.assignment.canClaim"
                                            :href="route('courier.deliveries.claim', delivery.id)"
                                            method="post"
                                            as="button"
                                            class="inline-flex items-center justify-center rounded-full bg-[var(--brand-orange)] px-4 py-2 text-xs font-semibold uppercase tracking-[0.16em] text-slate-950 shadow-[0_18px_40px_-26px_rgba(242,126,33,0.75)] transition duration-200 hover:-translate-y-0.5"
                                        >
                                            Claim Delivery
                                        </Link>

                                        <Link
                                            v-else-if="delivery.assignment.canComplete"
                                            :href="route('courier.deliveries.complete', delivery.id)"
                                            method="post"
                                            as="button"
                                            class="inline-flex items-center justify-center rounded-full bg-[var(--brand-teal)] px-4 py-2 text-xs font-semibold uppercase tracking-[0.16em] text-white shadow-[0_18px_40px_-26px_rgba(11,77,89,0.7)] transition duration-200 hover:-translate-y-0.5"
                                        >
                                            Mark Delivered
                                        </Link>

                                        <span
                                            v-else-if="delivery.statusKey === 'preparing'"
                                            class="inline-flex items-center justify-center rounded-full bg-white px-4 py-2 text-xs font-semibold uppercase tracking-[0.16em] text-slate-600 ring-1 ring-[#e4eded]"
                                        >
                                            Awaiting Pickup
                                        </span>

                                        <span
                                            v-else
                                            class="inline-flex items-center justify-center rounded-full bg-white px-4 py-2 text-xs font-semibold uppercase tracking-[0.16em] text-slate-600 ring-1 ring-[#e4eded]"
                                        >
                                            Assigned Elsewhere
                                        </span>

                                        <a
                                            v-if="delivery.destination.mapsUrl"
                                            :href="delivery.destination.mapsUrl"
                                            target="_blank"
                                            rel="noreferrer"
                                            class="inline-flex items-center justify-center rounded-full bg-[var(--brand-orange)] px-4 py-2 text-xs font-semibold uppercase tracking-[0.16em] text-slate-950 shadow-[0_18px_40px_-26px_rgba(242,126,33,0.75)] transition duration-200 hover:-translate-y-0.5"
                                        >
                                            Navigate
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </article>
                    </div>

                    <div v-else class="mt-6 rounded-[28px] border border-dashed border-[#d8e7e8] bg-white/80 p-8 text-sm leading-6 text-slate-500">
                        No active deliveries yet. New customer orders marked for dispatch will appear here with the same destination fields a mobile rider app can reuse later.
                    </div>
                </section>
            </div>
        </div>
    </CourierLayout>
</template>