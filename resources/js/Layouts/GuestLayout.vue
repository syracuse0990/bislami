<script setup>
import ApplicationLogo from '@/Components/Branding/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="relative overflow-hidden px-4 py-8 sm:px-6 lg:px-8">
        <div class="pointer-events-none absolute inset-0">
            <div class="absolute left-[-6rem] top-6 h-72 w-72 rounded-full bg-[var(--brand-teal)]/10 blur-3xl"></div>
            <div class="absolute right-[-5rem] top-20 h-64 w-64 rounded-full bg-[var(--brand-orange)]/10 blur-3xl"></div>
            <div class="absolute bottom-[-8rem] left-1/3 h-80 w-80 rounded-full bg-[var(--brand-teal)]/8 blur-3xl"></div>
        </div>

        <div class="relative mx-auto grid min-h-[calc(100vh-4rem)] max-w-6xl overflow-hidden rounded-[36px] border border-white/70 bg-white/70 shadow-[0_42px_120px_-64px_rgba(11,77,89,0.55)] backdrop-blur xl:grid-cols-[1.08fr_0.92fr]">
            <section class="hidden flex-col justify-between bg-[linear-gradient(145deg,var(--brand-teal-deep),var(--brand-teal)_56%,var(--brand-orange))] p-10 text-white xl:flex">
                <div class="space-y-6">
                    <Link
                        href="/"
                        class="inline-flex h-24 w-[290px] items-center rounded-[28px] bg-white/94 px-5 py-4 shadow-[0_24px_60px_-36px_rgba(0,0,0,0.55)]"
                    >
                        <ApplicationLogo class="h-full w-full" />
                    </Link>

                    <div class="space-y-4">
                        <p class="inline-flex rounded-full border border-white/15 bg-white/10 px-4 py-2 text-xs font-semibold uppercase tracking-[0.24em] text-white/80">
                            Food delivery workspace
                        </p>
                        <h1 class="text-4xl font-semibold leading-tight text-white">
                            Fast browsing for diners, cleaner operations for every team.
                        </h1>
                        <p class="max-w-lg text-base/7 text-white/80">
                            BizLami brings discovery, checkout, order tracking, and menu operations into one polished experience.
                        </p>
                    </div>
                </div>

                <div class="grid gap-4 text-sm text-white/80">
                    <div class="rounded-[26px] border border-white/15 bg-white/10 p-5 backdrop-blur">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-white/60">Customer journey</p>
                        <p class="mt-2 text-lg font-semibold text-white">Browse, add to cart, check out, and track with less friction.</p>
                    </div>
                    <div class="rounded-[26px] border border-white/15 bg-white/10 p-5 backdrop-blur">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-white/60">Merchant workflow</p>
                        <p class="mt-2 text-lg font-semibold text-white">Reusable forms and clearer menu management keep updates quick.</p>
                    </div>
                </div>
            </section>

            <section class="flex items-center justify-center p-5 sm:p-10">
                <div class="w-full max-w-md rounded-[30px] border border-white/80 bg-[var(--surface-strong)] p-7 shadow-[0_36px_80px_-56px_rgba(11,77,89,0.6)] backdrop-blur sm:p-8">
                    <div class="mb-8 space-y-4 xl:hidden">
                        <Link
                            href="/"
                            class="inline-flex h-16 w-[224px] items-center rounded-2xl bg-white px-4 py-3 shadow-[0_20px_48px_-32px_rgba(11,77,89,0.45)]"
                        >
                            <ApplicationLogo class="h-full w-full" />
                        </Link>
                        <p class="text-sm leading-6 text-slate-600">
                            Sign in to browse restaurants, manage menus, and keep orders moving.
                        </p>
                    </div>

                    <slot />
                </div>
            </section>
        </div>
    </div>
</template>
