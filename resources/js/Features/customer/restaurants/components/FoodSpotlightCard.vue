<script setup>
defineProps({
    food: {
        type: Object,
        required: true,
    },
});
</script>

<template>
    <article class="group flex h-full flex-col overflow-hidden rounded-[32px] border border-white/80 bg-white/90 shadow-[0_34px_85px_-56px_rgba(11,77,89,0.58)] transition duration-300 hover:-translate-y-1.5 hover:shadow-[0_42px_95px_-52px_rgba(11,77,89,0.72)]">
        <div class="relative overflow-hidden bg-[linear-gradient(135deg,#f4fbfb_0%,#fff7ef_58%,#ffffff_100%)] p-4 sm:p-5">
            <div class="pointer-events-none absolute -right-12 -top-10 h-32 w-32 rounded-full bg-[var(--brand-orange)]/12 blur-3xl"></div>

            <div class="relative aspect-[4/3] overflow-hidden rounded-[26px] bg-white/92 shadow-[0_22px_52px_-34px_rgba(11,77,89,0.45)] ring-1 ring-white">
                <img
                    v-if="food.imageUrl"
                    :src="food.imageUrl"
                    :alt="food.name"
                    class="h-full w-full object-cover transition duration-500 group-hover:scale-105"
                >
                <img
                    v-else
                    src="/images/bizlami_icon.png"
                    alt="BizLami icon"
                    class="h-full w-full object-contain p-8"
                >
            </div>

            <div class="mt-4 flex items-start justify-between gap-4">
                <div class="space-y-2">
                    <span class="inline-flex rounded-full border border-[#f5dcc7] bg-white/85 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.22em] text-[var(--brand-orange-deep)]">
                        {{ food.category }}
                    </span>
                    <div>
                        <h3 class="text-2xl font-semibold text-slate-900">{{ food.name }}</h3>
                        <p class="mt-1 text-sm font-medium text-slate-500">{{ food.restaurantName }}</p>
                    </div>
                </div>

                <span class="rounded-full bg-[var(--brand-teal)] px-3 py-1.5 text-sm font-semibold text-white shadow-[0_16px_36px_-26px_rgba(11,77,89,0.8)]">
                    {{ food.price }}
                </span>
            </div>
        </div>

        <div class="flex flex-1 flex-col gap-5 p-5">
            <p class="text-sm leading-6 text-slate-600">
                {{ food.description }}
            </p>

            <div class="grid gap-3 sm:grid-cols-3">
                <div class="rounded-[22px] bg-[#f4fbfb] px-4 py-3 ring-1 ring-[#dceced]">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-[var(--brand-teal)]">Delivery</p>
                    <p class="mt-2 text-sm font-semibold text-slate-900">{{ food.eta }}</p>
                </div>

                <div class="rounded-[22px] bg-[#fff7ef] px-4 py-3 ring-1 ring-[#f6dcc5]">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-[var(--brand-orange-deep)]">Kitchen</p>
                    <p class="mt-2 text-sm font-semibold text-slate-900">{{ food.restaurantCategory }}</p>
                </div>

                <div class="rounded-[22px] bg-white px-4 py-3 ring-1 ring-[#e5eded]">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">Rating</p>
                    <p class="mt-2 text-sm font-semibold text-slate-900">{{ food.rating }}</p>
                </div>
            </div>

            <div class="rounded-[24px] bg-[#fffaf3] p-4 ring-1 ring-[#f0dfcf]">
                <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-[var(--brand-orange-deep)]">Good to know</p>
                <div class="mt-3 flex flex-wrap gap-2">
                    <span class="rounded-full bg-white px-3 py-1 text-xs font-medium text-slate-600 ring-1 ring-[#ece7de]">
                        {{ food.deliveryFee }}
                    </span>
                    <span class="rounded-full bg-white px-3 py-1 text-xs font-medium text-slate-600 ring-1 ring-[#ece7de]">
                        {{ food.restaurantCuisine }}
                    </span>
                </div>
            </div>

            <div class="mt-auto flex flex-wrap gap-3 border-t border-[#edf2f2] pt-5">
                <slot name="actions" :food="food" />
            </div>
        </div>
    </article>
</template>