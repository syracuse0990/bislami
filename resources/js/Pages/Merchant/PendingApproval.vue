<script setup>
import MerchantLayout from '@/Layouts/MerchantLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    merchant: {
        type: Object,
        required: true,
    },
});

const checkpoints = [
    {
        title: 'Profile review',
        detail: 'BizLami operations checks business identity, account ownership, and merchant onboarding details.',
    },
    {
        title: 'Kitchen readiness',
        detail: 'Menus, delivery promises, and restaurant visibility are unlocked only after the workspace is approved.',
    },
    {
        title: 'Launch unlock',
        detail: 'Once approved, menu management and order operations become available immediately from this same workspace.',
    },
];
</script>

<template>
    <Head title="Merchant Approval Pending" />

    <MerchantLayout>
        <template #header>
            <div class="space-y-3">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-[var(--brand-orange-deep)]">
                    Merchant approval
                </p>
                <div class="flex flex-wrap items-center gap-3">
                    <h2 class="text-3xl font-semibold leading-tight text-slate-900">
                        Your merchant workspace is staged and waiting for approval.
                    </h2>
                    <span class="inline-flex rounded-full bg-orange-50 px-3 py-1 text-xs font-semibold uppercase tracking-[0.16em] text-orange-700 ring-1 ring-orange-200">
                        Approval pending
                    </span>
                </div>
                <p class="max-w-3xl text-sm leading-6 text-slate-600">
                    BizLami is holding menu and order tools until the merchant profile is reviewed. This keeps the operator workflow clean once your kitchen goes live.
                </p>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                <section class="rounded-[32px] border border-white/80 bg-[linear-gradient(135deg,#ffffff_0%,#fff8f1_48%,#eef9fb_100%)] p-6 shadow-[0_34px_85px_-56px_rgba(11,77,89,0.6)] sm:p-8">
                    <div class="grid gap-6 xl:grid-cols-[1.1fr_0.9fr] xl:items-start">
                        <div class="space-y-6">
                            <div class="rounded-[28px] border border-white/80 bg-white/88 p-6 shadow-[0_28px_70px_-48px_rgba(11,77,89,0.55)]">
                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[var(--brand-teal)]">Current status</p>
                                <h3 class="mt-3 text-2xl font-semibold text-slate-900">Review queue is active for {{ merchant.name }}.</h3>
                                <p class="mt-4 text-sm leading-7 text-slate-600">
                                    Signed in as {{ merchant.email }}. Your merchant account was created {{ merchant.joinedAt }} and currently has {{ merchant.managedRestaurantsCount }} restaurant records linked.
                                </p>

                                <div class="mt-5 flex flex-wrap gap-3">
                                    <span class="rounded-full border border-[#dceced] bg-[#f4fbfb] px-4 py-2 text-sm font-medium text-slate-700">
                                        {{ merchant.verificationLabel }}
                                    </span>
                                    <span class="rounded-full border border-[#f6dcc5] bg-[#fff8f1] px-4 py-2 text-sm font-medium text-slate-700">
                                        Merchant tools locked until approval
                                    </span>
                                </div>
                            </div>

                            <section class="grid gap-4 md:grid-cols-3">
                                <article
                                    v-for="checkpoint in checkpoints"
                                    :key="checkpoint.title"
                                    class="rounded-[28px] border border-white/80 bg-white/88 p-5 shadow-[0_28px_70px_-48px_rgba(11,77,89,0.55)]"
                                >
                                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[var(--brand-orange-deep)]">{{ checkpoint.title }}</p>
                                    <p class="mt-3 text-sm leading-7 text-slate-600">{{ checkpoint.detail }}</p>
                                </article>
                            </section>
                        </div>

                        <section class="rounded-[28px] border border-white/80 bg-white/88 p-6 shadow-[0_28px_70px_-48px_rgba(11,77,89,0.55)]">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[var(--brand-teal)]">What you can do now</p>
                            <h3 class="mt-3 text-2xl font-semibold text-slate-900">Keep the account ready for launch.</h3>
                            <div class="mt-6 space-y-4 text-sm leading-7 text-slate-600">
                                <p>Use account settings to keep contact details current so approval and rollout communication goes to the right operator.</p>
                                <p>Return here to refresh status. The workspace unlocks menu management and merchant order tools as soon as approval is granted.</p>
                            </div>

                            <div class="mt-6 flex flex-col gap-3 sm:flex-row">
                                <Link
                                    :href="route('merchant.dashboard')"
                                    class="inline-flex items-center justify-center rounded-full bg-gradient-to-r from-[var(--brand-teal)] to-[var(--brand-orange)] px-5 py-3 text-sm font-semibold text-white shadow-[0_20px_45px_-24px_rgba(11,77,89,0.85)] transition duration-200 hover:-translate-y-0.5"
                                >
                                    Refresh approval status
                                </Link>
                                <Link
                                    :href="route('customer.settings.edit')"
                                    class="inline-flex items-center justify-center rounded-full border border-white/80 bg-white px-5 py-3 text-sm font-semibold text-slate-700 shadow-[0_18px_42px_-32px_rgba(11,77,89,0.35)] transition duration-200 hover:text-[var(--brand-teal)]"
                                >
                                    Review account settings
                                </Link>
                            </div>
                        </section>
                    </div>
                </section>
            </div>
        </div>
    </MerchantLayout>
</template>