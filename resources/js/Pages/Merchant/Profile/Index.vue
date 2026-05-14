<script setup>
import RestaurantProfileForm from '@/Features/merchant/profile/components/RestaurantProfileForm.vue';
import MerchantLayout from '@/Layouts/MerchantLayout.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    profile: {
        type: Object,
        required: true,
    },
    hasProfile: {
        type: Boolean,
        default: false,
    },
});
</script>

<template>
    <Head title="Restaurant Profile" />

    <MerchantLayout>
        <div class="py-8">
            <div class="space-y-6 px-4 sm:px-6 lg:px-0">
                <section class="rounded-[32px] border border-white/80 bg-[linear-gradient(145deg,#ffffff_0%,#fff8f1_58%,#f4fbfb_100%)] p-6 shadow-[0_34px_85px_-56px_rgba(11,77,89,0.6)] sm:p-8">
                    <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-[var(--brand-teal)]">Restaurant profile management</p>
                            <h1 class="mt-2 text-2xl font-semibold text-slate-900">One compact profile for your restaurant business details and operating rules.</h1>
                            <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">
                                Manage the restaurant name, address, contact, map pin, weekly hours, closures, delivery radius, minimum order value, and preparation-time settings from one screen.
                            </p>
                        </div>

                        <span class="inline-flex items-center justify-center rounded-full bg-white px-4 py-2 text-xs font-semibold uppercase tracking-[0.18em] text-slate-700 ring-1 ring-[#e0ebeb]">
                            {{ hasProfile ? 'Profile active' : 'Profile not saved yet' }}
                        </span>
                    </div>
                </section>

                <RestaurantProfileForm :profile="profile" :has-profile="hasProfile" />
            </div>
        </div>
    </MerchantLayout>
</template>