<script setup>
import AddToCartButton from '@/Components/Cart/AddToCartButton.vue';
import CustomerLayout from '@/Layouts/CustomerLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    restaurant: {
        type: Object,
        required: true,
    },
});
</script>

<template>
    <Head :title="restaurant.name" />

    <CustomerLayout>
        <template #header>
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div class="space-y-3">
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-[var(--brand-orange-deep)]">
                        Restaurant menu
                    </p>
                    <h2 class="text-3xl font-semibold leading-tight text-slate-900">
                        {{ restaurant.name }} is ready for your next cart.
                    </h2>
                    <p class="max-w-2xl text-sm leading-6 text-slate-600">
                        Scan categories, compare dishes, and add items without losing the restaurant context.
                    </p>
                </div>

                <div class="flex flex-col gap-3 sm:flex-row">
                    <Link
                        :href="route('customer.restaurants.index')"
                        class="inline-flex items-center justify-center rounded-full border border-white/80 bg-white/80 px-5 py-3 text-sm font-semibold text-slate-700 transition duration-200 hover:text-[var(--brand-teal)]"
                    >
                        Back to restaurants
                    </Link>
                    <Link
                        :href="route('customer.cart.index')"
                        class="inline-flex items-center justify-center rounded-full bg-[var(--brand-teal)] px-5 py-3 text-sm font-semibold text-white shadow-[0_20px_45px_-24px_rgba(11,77,89,0.85)] transition duration-200 hover:-translate-y-0.5"
                    >
                        Open cart
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-6xl space-y-8 sm:px-6 lg:px-8">
                <section class="overflow-hidden rounded-[32px] border border-white/80 bg-[linear-gradient(135deg,#ffffff_0%,#fff8f1_58%,#f3fbfb_100%)] p-6 shadow-[0_34px_85px_-56px_rgba(11,77,89,0.6)] sm:p-8">
                    <div class="grid gap-6 lg:grid-cols-[1.15fr_0.85fr] lg:items-center">
                        <div class="max-w-3xl space-y-5">
                            <div class="inline-flex items-center gap-3 rounded-full border border-[#f5dcc7] bg-white/85 px-4 py-2 text-xs font-semibold uppercase tracking-[0.2em] text-[var(--brand-orange-deep)]">
                                <img src="/images/bizlami_icon.png" alt="BizLami icon" class="h-5 w-5 object-contain">
                                {{ restaurant.category }}
                            </div>

                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">
                                    {{ restaurant.category }}
                                </p>
                                <h3 class="mt-2 text-3xl font-semibold text-slate-900 sm:text-4xl">
                                    {{ restaurant.name }}
                                </h3>
                                <p class="mt-2 text-sm leading-7 text-slate-600">
                                    {{ restaurant.cuisine }}
                                </p>
                            </div>

                            <p class="text-base leading-7 text-slate-600">
                                {{ restaurant.featured }}
                            </p>

                            <div class="flex flex-wrap gap-3">
                                <span class="rounded-full bg-emerald-50 px-4 py-2 text-sm font-medium text-emerald-700 shadow-sm">
                                    {{ restaurant.rating }}
                                </span>
                                <span class="rounded-full border border-[#deecec] bg-white/85 px-4 py-2 text-sm font-medium text-slate-700">
                                    {{ restaurant.eta }}
                                </span>
                                <span class="rounded-full border border-[#f6dcc5] bg-[#fff8f1] px-4 py-2 text-sm font-medium text-slate-700">
                                    {{ restaurant.deliveryFee }}
                                </span>
                            </div>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-1">
                            <div class="rounded-[28px] bg-white/88 p-5 shadow-[0_24px_60px_-42px_rgba(11,77,89,0.45)] ring-1 ring-white">
                                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-[var(--brand-teal)]">Menu categories</p>
                                <p class="mt-2 text-3xl font-semibold text-slate-900">{{ restaurant.categories.length }}</p>
                                <p class="mt-2 text-sm leading-6 text-slate-600">Quickly scan the sections before adding dishes to your active cart.</p>
                            </div>

                            <div class="rounded-[28px] bg-[var(--brand-teal)] p-5 text-white shadow-[0_24px_60px_-36px_rgba(11,77,89,0.6)]">
                                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-white/70">Live dishes</p>
                                <p class="mt-2 text-3xl font-semibold">{{ restaurant.menuItems.length }}</p>
                                <p class="mt-2 text-sm leading-6 text-white/80">Every visible item can be added straight into the current customer cart.</p>
                            </div>
                        </div>
                    </div>

                    <div v-if="restaurant.categories.length" class="mt-6 flex flex-wrap gap-3 border-t border-[#edf2f2] pt-6">
                        <span
                            v-for="category in restaurant.categories"
                            :key="category"
                            class="rounded-full border border-[#f6dcc5] bg-[#fff8f1] px-4 py-2 text-sm font-medium text-[var(--brand-orange-deep)]"
                        >
                            {{ category }}
                        </span>
                    </div>
                </section>

                <section v-if="restaurant.menuItems.length" class="space-y-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-[var(--brand-teal)]">Dish lineup</p>
                        <h3 class="mt-2 text-2xl font-semibold text-slate-900">Choose dishes without leaving the menu context</h3>
                    </div>

                    <div class="grid gap-6 lg:grid-cols-2">
                    <article
                        v-for="menuItem in restaurant.menuItems"
                        :key="menuItem.id"
                        class="rounded-[30px] border border-white/80 bg-white/88 p-6 shadow-[0_28px_70px_-48px_rgba(11,77,89,0.55)]"
                    >
                        <div class="mb-5 flex h-52 items-center justify-center overflow-hidden rounded-[24px] bg-[#f4fbfb] ring-1 ring-[#dceced]">
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
                                class="h-20 w-20 object-contain"
                            >
                        </div>

                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[var(--brand-teal)]">
                                    {{ menuItem.category }}
                                </p>
                                <h3 class="mt-2 text-xl font-semibold text-slate-900">
                                    {{ menuItem.name }}
                                </h3>
                            </div>

                            <span class="rounded-full border border-[#f6dcc5] bg-[#fff8f1] px-3 py-1 text-sm font-semibold text-slate-700">
                                {{ menuItem.price }}
                            </span>
                        </div>

                        <p class="mt-4 text-sm leading-7 text-slate-600">
                            {{ menuItem.description }}
                        </p>

                        <div class="mt-6 flex flex-col gap-4 border-t border-[#edf2f2] pt-4 sm:flex-row sm:items-center sm:justify-between">
                            <p class="text-xs uppercase tracking-[0.18em] text-slate-400">
                                Add this dish to your active cart
                            </p>

                            <AddToCartButton
                                :menu-item-id="menuItem.id"
                                :restaurant-id="restaurant.id"
                                :restaurant-name="restaurant.name"
                                :item-name="menuItem.name"
                            />
                        </div>
                    </article>
                    </div>
                </section>

                <section v-else class="rounded-[32px] border border-white/80 bg-white/82 p-8 text-sm text-slate-500 shadow-[0_30px_80px_-52px_rgba(11,77,89,0.55)]">
                    This restaurant does not have any live dishes yet.
                </section>
            </div>
        </div>
    </CustomerLayout>
</template>