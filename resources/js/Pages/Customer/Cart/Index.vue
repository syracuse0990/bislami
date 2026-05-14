<script setup>
import CustomerLayout from '@/Layouts/CustomerLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    cart: {
        type: Object,
        default: () => ({
            restaurant: null,
            items: [],
            totals: [],
            total: '₱0',
        }),
    },
});
</script>

<template>
    <Head title="Cart" />

    <CustomerLayout>
        <template #header>
            <div class="space-y-3">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-[var(--brand-orange-deep)]">
                    Cart review
                </p>
                <h2 class="text-3xl font-semibold leading-tight text-slate-900">
                    Review quantities, totals, and the next step before checkout.
                </h2>
                <p class="max-w-2xl text-sm leading-6 text-slate-600">
                    Everything important stays visible here, from item adjustments to the final summary.
                </p>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto grid max-w-7xl gap-8 sm:px-6 lg:grid-cols-[1.15fr_0.85fr] lg:px-8">
                <section class="rounded-[32px] border border-white/80 bg-white/85 p-6 shadow-[0_34px_85px_-56px_rgba(11,77,89,0.6)] sm:p-8">
                    <div v-if="cart.items.length" class="space-y-5">
                        <div class="grid gap-3 sm:grid-cols-3">
                            <div class="rounded-[24px] bg-[#f3fbfb] px-4 py-3 ring-1 ring-[#dceced]">
                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[var(--brand-teal)]">Step 1</p>
                                <p class="mt-2 text-sm font-semibold text-slate-900">Review items</p>
                            </div>
                            <div class="rounded-[24px] bg-[#fff7ef] px-4 py-3 ring-1 ring-[#f6dcc5]">
                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[var(--brand-orange-deep)]">Step 2</p>
                                <p class="mt-2 text-sm font-semibold text-slate-900">Confirm totals</p>
                            </div>
                            <div class="rounded-[24px] bg-white px-4 py-3 ring-1 ring-[#deecec]">
                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Step 3</p>
                                <p class="mt-2 text-sm font-semibold text-slate-900">Continue to checkout</p>
                            </div>
                        </div>

                        <div
                            v-for="item in cart.items"
                            :key="item.menuItemId || item.name"
                            class="flex flex-col gap-5 rounded-[28px] border border-[#edf2f2] bg-[#fffcf8] p-5 sm:flex-row sm:items-start sm:justify-between"
                        >
                            <div class="flex items-start gap-4">
                                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-white p-2 shadow-[0_16px_35px_-25px_rgba(11,77,89,0.35)] ring-1 ring-[#edf2f2]">
                                    <img src="/images/bizlami_icon.png" alt="BizLami icon" class="h-full w-full object-contain">
                                </div>

                                <div>
                                <h3 class="text-base font-semibold text-slate-900">
                                    {{ item.name }}
                                </h3>
                                <p class="mt-1 text-sm text-slate-500">
                                    {{ item.restaurant }}
                                </p>

                                <div v-if="item.menuItemId" class="mt-4 flex items-center gap-3">
                                    <Link
                                        :href="route('customer.cart.items.decrement', item.menuItemId)"
                                        method="post"
                                        as="button"
                                        class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-[#d7e7e8] bg-white text-base font-semibold text-slate-700 shadow-sm transition duration-150 ease-in-out hover:text-[var(--brand-teal)] focus:outline-none focus:ring-2 focus:ring-[#ffd6b6]"
                                    >
                                        {{ item.quantityValue > 1 ? '−' : '×' }}
                                    </Link>

                                    <span class="min-w-10 text-center text-sm font-semibold text-slate-700">
                                        {{ item.quantityValue }}
                                    </span>

                                    <Link
                                        :href="route('customer.cart.items.increment', item.menuItemId)"
                                        method="post"
                                        as="button"
                                        class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-r from-[var(--brand-teal)] to-[var(--brand-orange)] text-base font-semibold text-white shadow-[0_18px_38px_-24px_rgba(11,77,89,0.8)] transition duration-150 ease-in-out hover:-translate-y-0.5 focus:outline-none focus:ring-2 focus:ring-[#ffd6b6]"
                                    >
                                        +
                                    </Link>
                                </div>
                                </div>
                            </div>

                            <div class="text-right text-sm text-slate-600">
                                <p class="font-medium">{{ item.quantity }}</p>
                                <p class="mt-1 text-lg font-semibold text-slate-900">
                                    {{ item.price }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div v-else class="rounded-[28px] border border-dashed border-[#d8e7e8] bg-[#fffcf8] p-8 text-sm text-slate-500">
                        Your cart is empty. Add dishes from a restaurant to start checkout.
                    </div>
                </section>

                <aside class="rounded-[32px] border border-white/80 bg-white/88 p-6 shadow-[0_34px_85px_-56px_rgba(11,77,89,0.6)] sm:p-8 lg:sticky lg:top-28 lg:self-start">
                    <h3 class="text-lg font-semibold text-slate-900">Order summary</h3>

                    <p v-if="cart.restaurant" class="mt-2 text-sm text-slate-500">
                        From {{ cart.restaurant }}
                    </p>

                    <div class="mt-6 rounded-[26px] bg-[#f4fbfb] p-5 ring-1 ring-[#dceced]">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[var(--brand-teal)]">Ready when you are</p>
                        <p class="mt-2 text-sm leading-6 text-slate-600">Confirm totals here, then continue into payment and delivery details without losing the active order summary.</p>
                    </div>

                    <div class="mt-6 space-y-3 text-sm text-slate-600">
                        <div
                            v-for="line in cart.totals"
                            :key="line.label"
                            class="flex items-center justify-between"
                        >
                            <span>{{ line.label }}</span>
                            <span>{{ line.value }}</span>
                        </div>
                    </div>

                    <div class="mt-6 flex items-center justify-between border-t border-[#edf2f2] pt-4">
                        <span class="font-medium text-slate-900">Total</span>
                        <span class="text-lg font-semibold text-slate-900">{{ cart.total }}</span>
                    </div>

                    <Link
                        :href="cart.items.length ? route('customer.checkout.index') : route('customer.restaurants.index')"
                        class="mt-6 inline-flex w-full justify-center rounded-full bg-gradient-to-r from-[var(--brand-teal)] to-[var(--brand-orange)] px-5 py-3 text-sm font-semibold text-white shadow-[0_20px_45px_-24px_rgba(11,77,89,0.85)] transition duration-200 hover:-translate-y-0.5"
                    >
                        {{ cart.items.length ? 'Continue to checkout' : 'Browse restaurants' }}
                    </Link>
                </aside>
            </div>
        </div>
    </CustomerLayout>
</template>