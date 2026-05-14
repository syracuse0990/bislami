<script setup>
import AddToCartButton from '@/Components/Cart/AddToCartButton.vue';
import { Link } from '@inertiajs/vue3';

defineProps({
    id: {
        type: Number,
        required: true,
    },
    category: {
        type: String,
        default: '',
    },
    slug: {
        type: String,
        required: true,
    },
    menuItemId: {
        type: Number,
        default: null,
    },
    name: {
        type: String,
        required: true,
    },
    cuisine: {
        type: String,
        required: true,
    },
    eta: {
        type: String,
        required: true,
    },
    rating: {
        type: String,
        required: true,
    },
    deliveryFee: {
        type: String,
        required: true,
    },
    featured: {
        type: String,
        required: true,
    },
    featuredItem: {
        type: String,
        required: true,
    },
    featuredImageUrl: {
        type: String,
        default: null,
    },
    featuredPrice: {
        type: String,
        default: '',
    },
});
</script>

<template>
    <article class="group overflow-hidden rounded-[30px] border border-white/80 bg-white/88 shadow-[0_30px_75px_-52px_rgba(11,77,89,0.55)] transition duration-300 hover:-translate-y-1 hover:shadow-[0_38px_85px_-50px_rgba(11,77,89,0.7)]">
        <div class="bg-[linear-gradient(135deg,#f4fbfb_0%,#fff7ef_62%,#ffffff_100%)] p-5">
            <div class="flex items-start justify-between gap-4">
                <div class="flex items-start gap-4">
                    <div class="flex h-16 w-16 items-center justify-center overflow-hidden rounded-[20px] bg-white/90 p-2 shadow-[0_16px_35px_-25px_rgba(11,77,89,0.45)] ring-1 ring-white">
                        <img v-if="featuredImageUrl" :src="featuredImageUrl" :alt="`${featuredItem} image`" class="h-full w-full object-cover">
                        <img v-else src="/images/bizlami_icon.png" alt="BizLami icon" class="h-full w-full object-contain">
                    </div>

                    <div>
                        <h3 class="text-xl font-semibold text-slate-900">{{ name }}</h3>
                        <p class="mt-1 text-sm text-slate-500">{{ cuisine }}</p>
                    </div>
                </div>

                <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-medium text-emerald-700 shadow-sm">
                    {{ rating }}
                </span>
            </div>
        </div>

        <div class="space-y-5 p-5">
            <div class="grid gap-3 sm:grid-cols-2">
                <div class="rounded-[22px] bg-[#f4fbfb] px-4 py-3 ring-1 ring-[#dceced]">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[var(--brand-teal)]">Delivery</p>
                    <p class="mt-2 text-sm font-semibold text-slate-900">{{ eta }}</p>
                </div>
                <div class="rounded-[22px] bg-[#fff7ef] px-4 py-3 ring-1 ring-[#f6dcc5]">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[var(--brand-orange-deep)]">Fee</p>
                    <p class="mt-2 text-sm font-semibold text-slate-900">{{ deliveryFee }}</p>
                </div>
            </div>

            <div class="rounded-[26px] bg-[#fffaf3] p-4 ring-1 ring-[#f3dfcc]">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[var(--brand-orange-deep)]">
                    Featured item
                </p>
                <div class="mt-2 flex items-start justify-between gap-4">
                    <div>
                        <p class="text-sm font-semibold text-slate-900">{{ featuredItem }}</p>
                        <p class="mt-1 text-sm leading-6 text-slate-600">{{ featured }}</p>
                    </div>

                    <span v-if="featuredPrice" class="rounded-full bg-white px-3 py-1 text-xs font-semibold text-slate-700 ring-1 ring-[#e6eded] shadow-sm">
                        {{ featuredPrice }}
                    </span>
                </div>
            </div>

            <div class="flex flex-col gap-3 border-t border-[#edf2f2] pt-5 sm:flex-row sm:items-center sm:justify-between">
                <Link
                    :href="route('customer.restaurants.show', slug)"
                    class="inline-flex items-center justify-center rounded-full border border-white/80 bg-white px-5 py-3 text-sm font-semibold text-slate-700 shadow-[0_18px_42px_-32px_rgba(11,77,89,0.35)] transition duration-200 hover:text-[var(--brand-teal)]"
                >
                    Browse full menu
                </Link>

                <AddToCartButton
                    v-if="menuItemId"
                    :menu-item-id="menuItemId"
                    :restaurant-id="id"
                    :restaurant-name="name"
                    :item-name="featuredItem"
                    label="Add featured dish"
                />

                <span v-else class="inline-flex items-center justify-center rounded-full bg-[#eef4f4] px-5 py-3 text-sm font-semibold text-slate-400">
                    Menu soon
                </span>
            </div>
        </div>
    </article>
</template>