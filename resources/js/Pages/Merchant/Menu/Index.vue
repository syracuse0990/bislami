<script setup>
import MenuItemForm from '@/Features/merchant/menu/components/MenuItemForm.vue';
import MerchantLayout from '@/Layouts/MerchantLayout.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    restaurants: {
        type: Array,
        default: () => [],
    },
    restaurantOptions: {
        type: Array,
        default: () => [],
    },
    categoryOptions: {
        type: Array,
        default: () => [],
    },
});
</script>

<template>
    <Head title="Menu" />

    <MerchantLayout>
        <template #header>
            <div class="space-y-3">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-[var(--brand-orange-deep)]">
                    Merchant studio
                </p>
                <h2 class="text-3xl font-semibold leading-tight text-slate-900">
                    Keep your delivery menu polished, readable, and ready to go live.
                </h2>
                <p class="max-w-2xl text-sm leading-6 text-slate-600">
                    Add new dishes, refine pricing, and update availability using the same reusable forms that shape the storefront experience.
                </p>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-5xl space-y-6 sm:px-6 lg:px-8">
                <section class="rounded-[32px] border border-white/80 bg-[linear-gradient(135deg,#ffffff_0%,#fff8f1_58%,#f3fbfb_100%)] p-6 shadow-[0_34px_85px_-56px_rgba(11,77,89,0.6)] sm:p-8">
                    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-[var(--brand-teal)]">Create and publish</p>
                            <h3 class="mt-2 text-2xl font-semibold text-slate-900">Add menu items with a cleaner operational flow</h3>
                        </div>

                        <div class="rounded-full border border-white/80 bg-white/85 px-4 py-2 text-sm font-medium text-slate-600 shadow-[0_20px_48px_-36px_rgba(11,77,89,0.35)]">
                            {{ restaurants.length }} restaurant{{ restaurants.length === 1 ? '' : 's' }} managed
                        </div>
                    </div>

                    <MenuItemForm
                        :restaurants="restaurantOptions"
                        :category-options="categoryOptions"
                        title="Add menu item"
                        description="Create a new dish and assign it to one of your restaurants."
                        submit-label="Create item"
                    />
                </section>

                <section
                    v-if="restaurants.length"
                    v-for="restaurant in restaurants"
                    :key="restaurant.name"
                    class="rounded-[32px] border border-white/80 bg-white/88 p-6 shadow-[0_30px_75px_-50px_rgba(11,77,89,0.55)]"
                >
                    <div class="flex flex-col gap-4 border-b border-[#edf2f2] pb-5 sm:flex-row sm:items-end sm:justify-between">
                        <div class="flex items-start gap-4">
                            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-[#f4fbfb] p-2 ring-1 ring-[#dceced]">
                                <img src="/images/bizlami_icon.png" alt="BizLami icon" class="h-full w-full object-contain">
                            </div>

                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[var(--brand-teal)]">Restaurant</p>
                                <h3 class="mt-2 text-xl font-semibold text-slate-900">{{ restaurant.name }}</h3>
                                <p class="mt-1 text-sm text-slate-500">{{ restaurant.cuisine }}</p>
                            </div>
                        </div>

                        <div class="rounded-full border border-[#f6dcc5] bg-[#fff8f1] px-4 py-2 text-sm font-medium text-[var(--brand-orange-deep)] shadow-sm">
                            {{ restaurant.totalItems }} live items
                        </div>
                    </div>

                    <div class="mt-5 grid gap-4">
                        <article
                            v-for="menuItem in restaurant.menuItems"
                            :key="`${restaurant.name}-${menuItem.name}`"
                            class="rounded-[28px] border border-[#edf2f2] bg-[#fffcf8] p-5"
                        >
                            <div class="mb-5 flex h-44 items-center justify-center overflow-hidden rounded-[24px] bg-[#f4fbfb] ring-1 ring-[#dceced]">
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
                                    class="h-16 w-16 object-contain"
                                >
                            </div>

                            <div class="mb-5 flex flex-col gap-4 border-b border-[#edf2f2] pb-4 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[var(--brand-teal)]">
                                        {{ menuItem.category }}
                                    </p>
                                    <h4 class="mt-1 text-base font-semibold text-slate-900">
                                        {{ menuItem.name }}
                                    </h4>
                                </div>

                                <div class="flex items-center gap-3">
                                    <span class="rounded-full border border-[#f6dcc5] bg-[#fff8f1] px-3 py-1 text-xs font-semibold text-slate-700">
                                        {{ menuItem.price }}
                                    </span>
                                    <span
                                        class="rounded-full px-3 py-1 text-xs font-medium"
                                        :class="menuItem.availability === 'Live'
                                            ? 'bg-emerald-50 text-emerald-700'
                                            : 'bg-gray-100 text-gray-600'"
                                    >
                                        {{ menuItem.availability }}
                                    </span>
                                </div>
                            </div>

                            <MenuItemForm
                                :restaurants="restaurantOptions"
                                :category-options="categoryOptions"
                                :menu-item="menuItem"
                                title="Edit menu item"
                                description="Update the dish details, pricing, and live status."
                                submit-label="Save changes"
                            />
                        </article>
                    </div>
                </section>

                <section v-else class="rounded-[32px] border border-white/80 bg-white/82 p-8 text-sm text-slate-500 shadow-[0_30px_80px_-52px_rgba(11,77,89,0.55)]">
                    Your restaurant menus will appear here once dishes are assigned to this merchant account.
                </section>
            </div>
        </div>
    </MerchantLayout>
</template>