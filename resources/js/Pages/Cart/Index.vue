<script setup>
import ApplicationLogo from '@/Components/Branding/ApplicationLogo.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    canLogin: {
        type: Boolean,
        default: false,
    },
    canRegister: {
        type: Boolean,
        default: false,
    },
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

    <div class="relative min-h-screen overflow-hidden bg-[linear-gradient(180deg,#faf7f1_0%,#ffffff_50%,#f7fbfb_100%)] text-slate-900">
        <div class="pointer-events-none absolute inset-0">
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,rgba(11,77,89,0.08),transparent_28%),radial-gradient(circle_at_bottom_right,rgba(217,107,18,0.08),transparent_24%)]"></div>
        </div>

        <div class="relative mx-auto max-w-7xl px-6 py-8 lg:px-8">
            <header class="flex flex-col gap-4 py-4 lg:flex-row lg:items-center lg:justify-between">
                <Link :href="route('home')" class="flex items-center gap-3">
                    <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-white/92 p-2 shadow-[0_20px_48px_-32px_rgba(11,77,89,0.45)]">
                        <img src="/images/bizlami_icon.png" alt="BizLami icon" class="h-full w-full object-contain">
                    </div>

                    <div class="hidden h-14 w-[224px] items-center rounded-[24px] bg-white/92 px-3 py-2 shadow-[0_20px_48px_-32px_rgba(11,77,89,0.45)] sm:flex">
                        <ApplicationLogo class="h-full w-full" />
                    </div>
                </Link>

                <nav class="flex flex-wrap items-center gap-3">
                    <Link
                        :href="route('home')"
                        class="inline-flex items-center rounded-full border border-white/80 bg-white/80 px-5 py-3 text-sm font-semibold text-slate-700 transition duration-200 hover:text-[var(--brand-teal)]"
                    >
                        Browse foods
                    </Link>

                    <Link
                        :href="route('restaurants.index')"
                        class="inline-flex items-center rounded-full border border-white/80 bg-white/80 px-5 py-3 text-sm font-semibold text-slate-700 transition duration-200 hover:text-[var(--brand-teal)]"
                    >
                        Kitchens
                    </Link>

                    <Link
                        v-if="canLogin"
                        :href="route('login')"
                        class="inline-flex items-center rounded-full border border-white/80 bg-white/80 px-5 py-3 text-sm font-semibold text-slate-700 transition duration-200 hover:text-[var(--brand-teal)]"
                    >
                        Log in
                    </Link>

                    <Link
                        v-if="canRegister"
                        :href="route('register')"
                        class="inline-flex items-center rounded-full bg-gradient-to-r from-[var(--brand-teal)] to-[var(--brand-orange)] px-5 py-3 text-sm font-semibold text-white shadow-[0_20px_45px_-24px_rgba(11,77,89,0.85)] transition duration-200 hover:-translate-y-0.5"
                    >
                        Create account
                    </Link>
                </nav>
            </header>

            <main class="space-y-8 pb-16 pt-8">
                <section class="rounded-[32px] border border-white/80 bg-white/92 p-6 shadow-[0_34px_85px_-56px_rgba(11,77,89,0.38)] sm:p-8">
                    <div class="space-y-3">
                        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-[var(--brand-orange-deep)]">Cart review</p>
                        <h1 class="text-3xl font-semibold leading-tight text-slate-900 sm:text-4xl">
                            Review your dishes before checkout.
                        </h1>
                        <p class="max-w-2xl text-sm leading-7 text-slate-600">
                            You can build the cart as a guest. When you continue to checkout, BizLami will ask you to log in or create an account.
                        </p>
                    </div>
                </section>

                <div class="grid gap-8 lg:grid-cols-[1.12fr_0.88fr]">
                    <section class="rounded-[32px] border border-white/80 bg-white/92 p-6 shadow-[0_30px_80px_-52px_rgba(11,77,89,0.32)] sm:p-8">
                        <div v-if="cart.items.length" class="space-y-5">
                            <div
                                v-for="item in cart.items"
                                :key="item.menuItemId || item.name"
                                class="flex flex-col gap-5 rounded-[28px] border border-[#edf2f2] bg-[#fffcf8] p-5 sm:flex-row sm:items-start sm:justify-between"
                            >
                                <div class="flex items-start gap-4">
                                    <div class="flex h-14 w-14 shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-white shadow-[0_16px_35px_-25px_rgba(11,77,89,0.35)] ring-1 ring-[#edf2f2]">
                                        <img
                                            v-if="item.imageUrl"
                                            :src="item.imageUrl"
                                            :alt="item.name"
                                            class="h-full w-full object-cover"
                                        >
                                        <img
                                            v-else
                                            src="/images/bizlami_icon.png"
                                            alt="BizLami icon"
                                            class="h-full w-full object-contain p-2"
                                        >
                                    </div>

                                    <div>
                                        <h2 class="text-base font-semibold text-slate-900">{{ item.name }}</h2>
                                        <p class="mt-1 text-sm text-slate-500">{{ item.restaurant }}</p>

                                        <div v-if="item.menuItemId" class="mt-4 flex items-center gap-3">
                                            <Link
                                                :href="route('cart.items.decrement', item.menuItemId)"
                                                method="post"
                                                as="button"
                                                class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-[#d7e7e8] bg-white text-base font-semibold text-slate-700 shadow-sm transition duration-150 ease-in-out hover:text-[var(--brand-teal)]"
                                            >
                                                {{ item.quantityValue > 1 ? '−' : '×' }}
                                            </Link>

                                            <span class="min-w-10 text-center text-sm font-semibold text-slate-700">
                                                {{ item.quantityValue }}
                                            </span>

                                            <Link
                                                :href="route('cart.items.increment', item.menuItemId)"
                                                method="post"
                                                as="button"
                                                class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-r from-[var(--brand-teal)] to-[var(--brand-orange)] text-base font-semibold text-white shadow-[0_18px_38px_-24px_rgba(11,77,89,0.8)] transition duration-150 ease-in-out hover:-translate-y-0.5"
                                            >
                                                +
                                            </Link>
                                        </div>
                                    </div>
                                </div>

                                <div class="text-right text-sm text-slate-600">
                                    <p class="font-medium">{{ item.quantity }}</p>
                                    <p class="mt-1 text-lg font-semibold text-slate-900">{{ item.price }}</p>
                                </div>
                            </div>
                        </div>

                        <div v-else class="rounded-[28px] border border-dashed border-[#d8e7e8] bg-[#fffcf8] p-8 text-sm text-slate-500">
                            Your cart is empty. Add dishes from discovery or a kitchen menu to start checkout.
                        </div>
                    </section>

                    <aside class="rounded-[32px] border border-white/80 bg-white/92 p-6 shadow-[0_30px_80px_-52px_rgba(11,77,89,0.32)] sm:p-8 lg:sticky lg:top-28 lg:self-start">
                        <h2 class="text-lg font-semibold text-slate-900">Order summary</h2>

                        <p v-if="cart.restaurant" class="mt-2 text-sm text-slate-500">
                            From {{ cart.restaurant }}
                        </p>

                        <div class="mt-6 rounded-[26px] bg-[#f4fbfb] p-5 ring-1 ring-[#dceced]">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[var(--brand-teal)]">Checkout note</p>
                            <p class="mt-2 text-sm leading-6 text-slate-600">
                                Guest carts are allowed. You only need to log in or create an account when you continue to checkout.
                            </p>
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
                            :href="cart.items.length ? route('checkout.index') : route('home')"
                            class="mt-6 inline-flex w-full justify-center rounded-full bg-gradient-to-r from-[var(--brand-teal)] to-[var(--brand-orange)] px-5 py-3 text-sm font-semibold text-white shadow-[0_20px_45px_-24px_rgba(11,77,89,0.85)] transition duration-200 hover:-translate-y-0.5"
                        >
                            {{ cart.items.length ? 'Continue to checkout' : 'Browse foods' }}
                        </Link>

                        <div v-if="cart.items.length" class="mt-4 flex flex-wrap gap-3 text-sm">
                            <Link
                                v-if="canLogin"
                                :href="route('login')"
                                class="font-semibold text-[var(--brand-teal)] transition duration-200 hover:text-slate-900"
                            >
                                Already have an account?
                            </Link>

                            <Link
                                v-if="canRegister"
                                :href="route('register')"
                                class="font-semibold text-[var(--brand-orange-deep)] transition duration-200 hover:text-slate-900"
                            >
                                New here? Create one at checkout.
                            </Link>
                        </div>
                    </aside>
                </div>
            </main>
        </div>
    </div>
</template>