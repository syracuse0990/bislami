<script setup>
import CourierLayout from '@/Layouts/CourierLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    overview: {
        type: Object,
        default: () => ({
            activeRunsCount: 0,
            availableClaimsCount: 0,
            pickupReadyCount: 0,
            mappedStopsCount: 0,
            completedTodayCount: 0,
        }),
    },
    assignedDeliveries: {
        type: Array,
        default: () => [],
    },
    availableClaims: {
        type: Array,
        default: () => [],
    },
    pickupQueue: {
        type: Array,
        default: () => [],
    },
});

const metrics = computed(() => [
    {
        label: 'Active runs',
        value: props.overview.activeRunsCount,
        detail: 'Stops already assigned to this courier and still in motion.',
        tone: 'text-[var(--brand-teal)]',
        surface: 'bg-[#f4fbfb] ring-[#dceced]',
    },
    {
        label: 'Available claims',
        value: props.overview.availableClaimsCount,
        detail: 'Orders on the road that are free for this courier to claim.',
        tone: 'text-[var(--brand-orange-deep)]',
        surface: 'bg-[#fff7ef] ring-[#f6dcc5]',
    },
    {
        label: 'Pickup-ready kitchens',
        value: props.overview.pickupReadyCount,
        detail: 'Preparing orders that still need the next courier handoff step.',
        tone: 'text-sky-700',
        surface: 'bg-[#eef7ff] ring-[#d5e8fb]',
    },
    {
        label: 'Mapped stops',
        value: props.overview.mappedStopsCount,
        detail: 'Visible deliveries already carrying precise coordinate pins.',
        tone: 'text-emerald-700',
        surface: 'bg-[#eefcf5] ring-[#d3f2df]',
    },
    {
        label: 'Completed today',
        value: props.overview.completedTodayCount,
        detail: 'Delivered orders closed by this courier today.',
        tone: 'text-fuchsia-700',
        surface: 'bg-[#fff0fa] ring-[#f2d5e8]',
    },
]);

const summarySignals = computed(() => [
    {
        label: 'Assigned now',
        value: props.assignedDeliveries.length,
        detail: 'Deliveries already under this courier account.',
    },
    {
        label: 'Open opportunities',
        value: props.availableClaims.length,
        detail: 'Claimable runs that can be picked up immediately.',
    },
    {
        label: 'Awaiting pickup',
        value: props.pickupQueue.length,
        detail: 'Kitchen-side jobs still waiting for rider movement.',
    },
]);
</script>

<template>
    <Head title="Courier Overview" />

    <CourierLayout>
        <template #header>
            <div class="space-y-3">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-[var(--brand-orange-deep)]">
                    Courier hub
                </p>
                <h2 class="text-3xl font-semibold leading-tight text-slate-900">
                    Keep live deliveries moving with the right signals up front.
                </h2>
                <p class="max-w-2xl text-sm leading-6 text-slate-600">
                    Active runs, earnings, and on-time performance stay visible in the same branded shell as the rest of the platform.
                </p>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                <section class="rounded-[32px] border border-white/80 bg-[linear-gradient(135deg,#ffffff_0%,#fff8f1_58%,#f3fbfb_100%)] p-6 shadow-[0_34px_85px_-56px_rgba(11,77,89,0.6)] sm:p-8">
                    <div class="grid gap-6 xl:grid-cols-[1.08fr_0.92fr] xl:items-start">
                        <div class="space-y-6">
                            <div class="flex flex-wrap items-center gap-3">
                                <div class="inline-flex items-center gap-3 rounded-full border border-[#f5dcc7] bg-white/85 px-4 py-2 text-xs font-semibold uppercase tracking-[0.2em] text-[var(--brand-orange-deep)]">
                                    <img src="/images/bizlami_icon.png" alt="BizLami icon" class="h-5 w-5 object-contain">
                                    Daily courier snapshot
                                </div>
                                <span class="rounded-full border border-[#dceced] bg-[#f4fbfb] px-4 py-2 text-xs font-semibold uppercase tracking-[0.18em] text-[var(--brand-teal)]">
                                    {{ overview.completedTodayCount }} completed today
                                </span>
                            </div>

                            <div class="space-y-4">
                                <h3 class="max-w-3xl text-3xl font-semibold leading-tight text-slate-900 sm:text-[2.5rem]">
                                    Start every shift with the route pressure, claimable runs, and map quality already visible.
                                </h3>
                                <p class="max-w-2xl text-sm leading-7 text-slate-600">
                                    The courier workspace now uses the same live delivery data as the queue, so the first screen helps riders decide what to claim, what to finish, and which stops are map-ready.
                                </p>
                            </div>

                            <div class="flex flex-wrap gap-3">
                                <Link
                                    :href="route('courier.deliveries.index')"
                                    class="inline-flex items-center justify-center rounded-full bg-gradient-to-r from-[var(--brand-teal)] to-[var(--brand-orange)] px-5 py-3 text-sm font-semibold text-white shadow-[0_20px_45px_-24px_rgba(11,77,89,0.85)] transition duration-200 hover:-translate-y-0.5"
                                >
                                    Open live delivery queue
                                </Link>
                            </div>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-3 xl:grid-cols-1">
                            <article
                                v-for="signal in summarySignals"
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

                <section class="grid gap-6 md:grid-cols-2 xl:grid-cols-5">
                    <article
                        v-for="metric in metrics"
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

                <section class="grid gap-6 xl:grid-cols-[1.02fr_0.98fr]">
                    <section class="rounded-[32px] border border-white/80 bg-white/88 p-6 shadow-[0_34px_85px_-56px_rgba(11,77,89,0.6)] sm:p-8">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-[var(--brand-teal)]">Your live route</p>
                                <h3 class="mt-2 text-2xl font-semibold text-slate-900">Assigned deliveries that are already under this courier account.</h3>
                            </div>

                            <Link
                                :href="route('courier.deliveries.index')"
                                class="inline-flex items-center justify-center rounded-full border border-white/80 bg-[#f4fbfb] px-4 py-2 text-sm font-semibold text-slate-700 shadow-[0_18px_42px_-32px_rgba(11,77,89,0.35)] transition duration-200 hover:text-[var(--brand-teal)]"
                            >
                                Full queue
                            </Link>
                        </div>

                        <div v-if="assignedDeliveries.length" class="mt-6 grid gap-4">
                            <article
                                v-for="delivery in assignedDeliveries"
                                :key="delivery.id"
                                class="rounded-[26px] border border-[#edf2f2] bg-[linear-gradient(135deg,#ffffff_0%,#eef8ff_62%,#f4fbfb_100%)] p-5"
                            >
                                <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                                    <div>
                                        <div class="flex flex-wrap items-center gap-3">
                                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-sky-700">{{ delivery.orderNumber }}</p>
                                            <span :class="delivery.statusAccent" class="rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-[0.16em] ring-1">
                                                {{ delivery.statusLabel }}
                                            </span>
                                        </div>
                                        <h4 class="mt-3 text-lg font-semibold text-slate-900">{{ delivery.restaurantName }}</h4>
                                        <p class="mt-1 text-sm text-slate-600">{{ delivery.summary }}</p>

                                        <div class="mt-4 flex flex-wrap gap-2">
                                            <span class="rounded-full border border-[#dceced] bg-[#f4fbfb] px-3 py-1 text-xs font-medium text-slate-700">
                                                {{ delivery.customerName }}
                                            </span>
                                            <span class="rounded-full border border-[#ece7de] bg-white px-3 py-1 text-xs font-medium text-slate-700">
                                                {{ delivery.destination.shortLabel }}
                                            </span>
                                            <span
                                                class="rounded-full px-3 py-1 text-xs font-medium ring-1"
                                                :class="delivery.destination.hasCoordinates
                                                    ? 'bg-[#e9fbf8] text-[var(--brand-teal)] ring-[#bfe3da]'
                                                    : 'bg-white text-slate-600 ring-[#e4eded]'"
                                            >
                                                {{ delivery.destination.hasCoordinates ? 'Map ready' : 'Address lookup' }}
                                            </span>
                                        </div>
                                    </div>

                                    <div class="grid gap-3 text-sm text-slate-600 md:text-right">
                                        <p><span class="font-semibold text-slate-900">Placed:</span> {{ delivery.placedAt }}</p>
                                        <p><span class="font-semibold text-slate-900">Total:</span> {{ delivery.total }}</p>
                                        <div class="flex flex-wrap gap-3 md:justify-end">
                                            <Link
                                                v-if="delivery.assignment.canComplete"
                                                :href="route('courier.deliveries.complete', delivery.id)"
                                                method="post"
                                                as="button"
                                                class="inline-flex items-center justify-center rounded-full bg-[var(--brand-teal)] px-4 py-2 text-xs font-semibold uppercase tracking-[0.16em] text-white shadow-[0_18px_40px_-26px_rgba(11,77,89,0.7)] transition duration-200 hover:-translate-y-0.5"
                                            >
                                                Mark delivered
                                            </Link>
                                            <a
                                                v-if="delivery.destination.mapsUrl"
                                                :href="delivery.destination.mapsUrl"
                                                target="_blank"
                                                rel="noreferrer"
                                                class="inline-flex items-center justify-center rounded-full border border-white/80 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-[0.16em] text-slate-700 shadow-[0_18px_42px_-32px_rgba(11,77,89,0.35)] transition duration-200 hover:text-[var(--brand-teal)]"
                                            >
                                                Open map
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </article>
                        </div>

                        <div v-else class="mt-6 rounded-[28px] border border-dashed border-[#d8e7e8] bg-[#fffcf8] p-8 text-sm leading-6 text-slate-500">
                            No deliveries are currently assigned to this courier. Claimable runs will appear in the next panel.
                        </div>
                    </section>

                    <section class="rounded-[32px] border border-white/80 bg-white/88 p-6 shadow-[0_34px_85px_-56px_rgba(11,77,89,0.6)] sm:p-8">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-[var(--brand-orange-deep)]">Available claims</p>
                                <h3 class="mt-2 text-2xl font-semibold text-slate-900">Runs on the road that this courier can claim immediately.</h3>
                            </div>
                        </div>

                        <div v-if="availableClaims.length" class="mt-6 grid gap-4">
                            <article
                                v-for="delivery in availableClaims"
                                :key="delivery.id"
                                class="rounded-[26px] border border-[#edf2f2] bg-[linear-gradient(135deg,#ffffff_0%,#fff8f1_62%,#f4fbfb_100%)] p-5"
                            >
                                <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                                    <div>
                                        <div class="flex flex-wrap items-center gap-3">
                                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[var(--brand-orange-deep)]">{{ delivery.orderNumber }}</p>
                                            <span :class="delivery.statusAccent" class="rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-[0.16em] ring-1">
                                                {{ delivery.statusLabel }}
                                            </span>
                                        </div>
                                        <h4 class="mt-3 text-lg font-semibold text-slate-900">{{ delivery.restaurantName }}</h4>
                                        <p class="mt-1 text-sm text-slate-600">{{ delivery.summary }}</p>

                                        <div class="mt-4 flex flex-wrap gap-2">
                                            <span class="rounded-full border border-[#dceced] bg-[#f4fbfb] px-3 py-1 text-xs font-medium text-slate-700">
                                                {{ delivery.customerName }}
                                            </span>
                                            <span class="rounded-full border border-[#ece7de] bg-white px-3 py-1 text-xs font-medium text-slate-700">
                                                {{ delivery.destination.shortLabel }}
                                            </span>
                                        </div>
                                    </div>

                                    <div class="grid gap-3 text-sm text-slate-600 md:text-right">
                                        <p><span class="font-semibold text-slate-900">Placed:</span> {{ delivery.placedAt }}</p>
                                        <p><span class="font-semibold text-slate-900">Total:</span> {{ delivery.total }}</p>
                                        <div class="flex flex-wrap gap-3 md:justify-end">
                                            <Link
                                                v-if="delivery.assignment.canClaim"
                                                :href="route('courier.deliveries.claim', delivery.id)"
                                                method="post"
                                                as="button"
                                                class="inline-flex items-center justify-center rounded-full bg-[var(--brand-orange)] px-4 py-2 text-xs font-semibold uppercase tracking-[0.16em] text-slate-950 shadow-[0_18px_40px_-26px_rgba(242,126,33,0.75)] transition duration-200 hover:-translate-y-0.5"
                                            >
                                                Claim delivery
                                            </Link>
                                            <a
                                                v-if="delivery.destination.mapsUrl"
                                                :href="delivery.destination.mapsUrl"
                                                target="_blank"
                                                rel="noreferrer"
                                                class="inline-flex items-center justify-center rounded-full border border-white/80 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-[0.16em] text-slate-700 shadow-[0_18px_42px_-32px_rgba(11,77,89,0.35)] transition duration-200 hover:text-[var(--brand-teal)]"
                                            >
                                                Open map
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </article>
                        </div>

                        <div v-else class="mt-6 rounded-[28px] border border-dashed border-[#d8e7e8] bg-[#fffcf8] p-8 text-sm leading-6 text-slate-500">
                            No claimable runs right now. New on-the-way orders without a courier will surface here.
                        </div>
                    </section>
                </section>

                <section class="rounded-[32px] border border-white/80 bg-white/88 p-6 shadow-[0_34px_85px_-56px_rgba(11,77,89,0.6)] sm:p-8">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-sky-700">Pickup-ready kitchens</p>
                            <h3 class="mt-2 text-2xl font-semibold text-slate-900">Orders still at the kitchen stage before rider handoff.</h3>
                        </div>

                        <div class="rounded-full border border-white/80 bg-[#eef7ff] px-4 py-2 text-sm font-medium text-slate-600 shadow-[0_20px_48px_-36px_rgba(11,77,89,0.35)]">
                            {{ pickupQueue.length }} waiting for pickup coordination
                        </div>
                    </div>

                    <div v-if="pickupQueue.length" class="mt-6 grid gap-4 xl:grid-cols-2">
                        <article
                            v-for="delivery in pickupQueue"
                            :key="delivery.id"
                            class="rounded-[26px] border border-[#edf2f2] bg-[linear-gradient(135deg,#ffffff_0%,#eef8ff_62%,#f4fbfb_100%)] p-5"
                        >
                            <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                                <div>
                                    <div class="flex flex-wrap items-center gap-3">
                                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-sky-700">{{ delivery.orderNumber }}</p>
                                        <span :class="delivery.statusAccent" class="rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-[0.16em] ring-1">
                                            {{ delivery.statusLabel }}
                                        </span>
                                    </div>
                                    <h4 class="mt-3 text-lg font-semibold text-slate-900">{{ delivery.restaurantName }}</h4>
                                    <p class="mt-1 text-sm text-slate-600">{{ delivery.summary }}</p>
                                </div>

                                <div class="grid gap-2 text-sm text-slate-600 md:text-right">
                                    <p><span class="font-semibold text-slate-900">Customer:</span> {{ delivery.customerName }}</p>
                                    <p><span class="font-semibold text-slate-900">Placed:</span> {{ delivery.placedAt }}</p>
                                    <p><span class="font-semibold text-slate-900">Assignment:</span> {{ delivery.assignment.label }}</p>
                                </div>
                            </div>

                            <div class="mt-4 flex flex-wrap gap-3 border-t border-[#edf2f2] pt-4">
                                <span class="inline-flex items-center justify-center rounded-full bg-white px-4 py-2 text-xs font-semibold uppercase tracking-[0.16em] text-slate-600 ring-1 ring-[#e4eded]">
                                    Awaiting merchant handoff
                                </span>
                                <a
                                    v-if="delivery.destination.mapsUrl"
                                    :href="delivery.destination.mapsUrl"
                                    target="_blank"
                                    rel="noreferrer"
                                    class="inline-flex items-center justify-center rounded-full border border-white/80 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-[0.16em] text-slate-700 shadow-[0_18px_42px_-32px_rgba(11,77,89,0.35)] transition duration-200 hover:text-[var(--brand-teal)]"
                                >
                                    Preview route
                                </a>
                            </div>
                        </article>
                    </div>

                    <div v-else class="mt-6 rounded-[28px] border border-dashed border-[#d8e7e8] bg-[#fffcf8] p-8 text-sm leading-6 text-slate-500">
                        No kitchens are waiting for courier pickup right now.
                    </div>
                </section>
            </div>
        </div>
    </CourierLayout>
</template>