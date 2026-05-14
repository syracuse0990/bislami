<script setup>
import ReorderToCartButton from '@/Components/Cart/ReorderToCartButton.vue';
import { Link } from '@inertiajs/vue3';

defineProps({
    id: {
        type: Number,
        required: true,
    },
    orderNumber: {
        type: String,
        required: true,
    },
    restaurantId: {
        type: Number,
        required: true,
    },
    restaurant: {
        type: String,
        required: true,
    },
    restaurantSlug: {
        type: String,
        required: true,
    },
    status: {
        type: String,
        required: true,
    },
    statusKey: {
        type: String,
        default: '',
    },
    summary: {
        type: String,
        required: true,
    },
    placedAt: {
        type: String,
        required: true,
    },
    total: {
        type: String,
        required: true,
    },
    canTrack: {
        type: Boolean,
        default: false,
    },
    canReorder: {
        type: Boolean,
        default: false,
    },
    reorder: {
        type: Object,
        default: () => ({
            visible: false,
            available: false,
            label: null,
            description: null,
            items: [],
        }),
    },
    accent: {
        type: String,
        default: 'bg-orange-50 text-orange-700',
    },
});
</script>

<template>
    <article class="rounded-[30px] border border-white/80 bg-white/88 p-6 shadow-[0_28px_70px_-48px_rgba(11,77,89,0.55)]">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div class="flex items-start gap-4">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-[#f4fbfb] p-2 ring-1 ring-[#dceced]">
                    <img src="/images/bizlami_icon.png" alt="BizLami icon" class="h-full w-full object-contain">
                </div>

                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[var(--brand-teal)]">{{ orderNumber }}</p>
                    <h3 class="mt-2 text-lg font-semibold text-slate-900">{{ restaurant }}</h3>
                    <p class="mt-2 text-sm leading-6 text-slate-600">{{ summary }}</p>
                </div>
            </div>

            <span class="rounded-full px-3 py-1 text-xs font-medium shadow-sm" :class="accent">
                {{ status }}
            </span>
        </div>

        <div class="mt-5 flex items-center justify-between border-t border-[#edf2f2] pt-4 text-sm text-slate-500">
            <span>Placed {{ placedAt }}</span>
            <span class="font-semibold text-slate-700">{{ total }}</span>
        </div>

        <div class="mt-4 flex flex-wrap gap-3">
            <Link
                :href="route('customer.orders.show', id)"
                class="inline-flex items-center justify-center rounded-full bg-[var(--brand-teal)] px-4 py-2 text-sm font-semibold text-white shadow-[0_20px_45px_-24px_rgba(11,77,89,0.85)] transition duration-200 hover:-translate-y-0.5"
            >
                {{ canTrack ? 'Track order' : 'View details' }}
            </Link>

            <ReorderToCartButton
                v-if="canReorder"
                :order-id="id"
                :restaurant-id="restaurantId"
                :restaurant-name="restaurant"
                :order-number="orderNumber"
                button-class="inline-flex items-center justify-center rounded-full border border-[var(--brand-orange-deep)]/15 bg-[#fff6ed] px-4 py-2 text-sm font-semibold text-[var(--brand-orange-deep)] shadow-[0_18px_42px_-32px_rgba(197,92,24,0.38)] transition duration-200 hover:-translate-y-0.5 hover:bg-[#ffefdf]"
            />

            <Link
                :href="route('customer.restaurants.show', restaurantSlug)"
                class="inline-flex items-center justify-center rounded-full border border-white/80 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-[0_18px_42px_-32px_rgba(11,77,89,0.35)] transition duration-200 hover:text-[var(--brand-teal)]"
            >
                Kitchen menu
            </Link>

            <span class="rounded-full bg-[#fff7ef] px-3 py-1 text-xs font-semibold text-[var(--brand-orange-deep)] ring-1 ring-[#f6dcc5]">
                {{ canTrack ? 'Live status' : (reorder.label ?? (canReorder ? 'Ready to reorder' : 'History saved')) }}
            </span>
        </div>

        <div
            v-if="reorder.visible && !canReorder && reorder.description"
            class="mt-4 rounded-[22px] border border-[#f0dfcf] bg-[#fffaf3] px-4 py-3"
        >
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[var(--brand-orange-deep)]">{{ reorder.label }}</p>
            <p class="mt-2 text-sm leading-6 text-slate-600">{{ reorder.description }}</p>

            <div v-if="reorder.items?.length" class="mt-3 flex flex-wrap gap-2">
                <span
                    v-for="item in reorder.items.slice(0, 2)"
                    :key="`${item.name}-${item.reason}`"
                    class="rounded-full bg-white px-3 py-1 text-xs font-medium text-slate-600 ring-1 ring-[#e4eded]"
                >
                    {{ item.name }}
                </span>

                <span
                    v-if="reorder.items.length > 2"
                    class="rounded-full bg-white px-3 py-1 text-xs font-medium text-slate-500 ring-1 ring-[#e4eded]"
                >
                    +{{ reorder.items.length - 2 }} more
                </span>
            </div>
        </div>
    </article>
</template>