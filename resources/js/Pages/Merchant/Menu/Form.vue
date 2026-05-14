<script setup>
import MenuItemForm from '@/Features/merchant/menu/components/MenuItemForm.vue';
import MerchantLayout from '@/Layouts/MerchantLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    mode: {
        type: String,
        default: 'create',
    },
    menuItem: {
        type: Object,
        default: null,
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

const isEditing = computed(() => props.mode === 'edit');

const pageTitle = computed(() => isEditing.value
    ? `Edit ${props.menuItem?.name ?? 'Menu Item'}`
    : 'Create Menu Item');

const pageHeading = computed(() => isEditing.value
    ? `Update ${props.menuItem?.name ?? 'this menu item'} with less friction.`
    : 'Create a menu item on its own focused page.');

const pageDescription = computed(() => isEditing.value
    ? 'Adjust price, availability, and option sets without scanning a long listing page first.'
    : 'Fill out the basics first, then add the optional pricing rules, artwork, and customizations in the lower sections.');

const summaryCards = computed(() => [
    {
        key: 'restaurants',
        label: 'Restaurants',
        value: props.restaurantOptions.length,
        helper: 'Choose the restaurant before you price the dish.',
    },
    {
        key: 'categories',
        label: 'Categories',
        value: props.categoryOptions.length,
        helper: 'Reuse categories to keep customer search cleaner.',
    },
    {
        key: 'workflow',
        label: 'Workflow',
        value: isEditing.value ? 'Edit existing' : 'Create new',
        helper: isEditing.value ? 'Save returns to the menu table.' : 'After save, the new dish returns to the table view.',
    },
]);
</script>

<template>
    <Head :title="pageTitle" />

    <MerchantLayout>
        <template #header>
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div class="space-y-3">
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-[var(--brand-orange-deep)]">
                        {{ isEditing ? 'Edit menu item' : 'Create menu item' }}
                    </p>
                    <h2 class="text-3xl font-semibold leading-tight text-slate-900">
                        {{ pageHeading }}
                    </h2>
                    <p class="max-w-2xl text-sm leading-6 text-slate-600">
                        {{ pageDescription }}
                    </p>
                </div>

                <Link
                    :href="route('merchant.menu.index')"
                    class="inline-flex items-center justify-center rounded-full border border-[#d0e2e3] bg-[#f7fbfb] px-5 py-3 text-xs font-semibold uppercase tracking-[0.18em] text-[var(--brand-teal)] transition duration-200 hover:-translate-y-0.5"
                >
                    Back to menu table
                </Link>
            </div>
        </template>

        <div class="py-8">
            <div class="grid gap-6 px-4 sm:px-6 lg:grid-cols-[minmax(0,1fr)_320px] lg:px-0">
                <section>
                    <MenuItemForm
                        :restaurants="restaurantOptions"
                        :category-options="categoryOptions"
                        :menu-item="menuItem"
                        :title="isEditing ? 'Edit menu item' : 'Create menu item'"
                        :description="isEditing
                            ? 'Update the dish details, pricing, and optional customizations from one organized form.'
                            : 'Start with the essentials, then add promos, images, and optional configuration blocks below.'"
                        :submit-label="isEditing ? 'Save changes' : 'Create item'"
                        :cancel-href="route('merchant.menu.index')"
                    />
                </section>

                <aside class="space-y-4">
                    <article class="rounded-[28px] border border-white/80 bg-white/90 p-5 shadow-[0_24px_64px_-48px_rgba(11,77,89,0.45)]">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[var(--brand-teal)]">Publishing guide</p>

                        <div class="mt-4 space-y-3">
                            <div class="rounded-[22px] bg-[#f8fbfb] px-4 py-4 ring-1 ring-[#e1ecec]">
                                <p class="text-sm font-semibold text-slate-900">1. Basics first</p>
                                <p class="mt-1 text-sm leading-6 text-slate-600">Pick the restaurant, category, dish name, and description before touching anything optional.</p>
                            </div>

                            <div class="rounded-[22px] bg-[#f8fbfb] px-4 py-4 ring-1 ring-[#e1ecec]">
                                <p class="text-sm font-semibold text-slate-900">2. Selling rules next</p>
                                <p class="mt-1 text-sm leading-6 text-slate-600">Add promo pricing, time windows, and availability only if the dish actually needs them.</p>
                            </div>

                            <div class="rounded-[22px] bg-[#f8fbfb] px-4 py-4 ring-1 ring-[#e1ecec]">
                                <p class="text-sm font-semibold text-slate-900">3. Options last</p>
                                <p class="mt-1 text-sm leading-6 text-slate-600">Variants, add-ons, modifiers, and bundles live lower on the page so the core details stay easy to finish.</p>
                            </div>
                        </div>
                    </article>

                    <article class="rounded-[28px] border border-white/80 bg-white/90 p-5 shadow-[0_24px_64px_-48px_rgba(11,77,89,0.45)]">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[var(--brand-teal)]">Page summary</p>

                        <div class="mt-4 grid gap-3">
                            <div v-for="card in summaryCards" :key="card.key" class="rounded-[22px] bg-[#fff8f1] px-4 py-4 ring-1 ring-[#f4dfbf]">
                                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-[var(--brand-orange-deep)]">{{ card.label }}</p>
                                <p class="mt-2 text-sm font-semibold text-slate-900">{{ card.value }}</p>
                                <p class="mt-1 text-xs leading-5 text-slate-500">{{ card.helper }}</p>
                            </div>
                        </div>
                    </article>

                    <article v-if="menuItem" class="rounded-[28px] border border-white/80 bg-white/90 p-5 shadow-[0_24px_64px_-48px_rgba(11,77,89,0.45)]">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[var(--brand-teal)]">Current item</p>
                        <p class="mt-2 text-lg font-semibold text-slate-900">{{ menuItem.name }}</p>

                        <div class="mt-4 flex flex-wrap gap-2">
                            <span class="rounded-full bg-[#fff8f1] px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.16em] text-[var(--brand-orange-deep)] ring-1 ring-[#f6dcc5]">
                                {{ menuItem.category }}
                            </span>
                            <span class="rounded-full bg-[#f8fbfb] px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.16em] text-[var(--brand-teal)] ring-1 ring-[#dceced]">
                                {{ menuItem.availability }}
                            </span>
                            <span v-if="menuItem.promoPrice" class="rounded-full bg-[#fff4dc] px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.16em] text-[#9a5d03] ring-1 ring-[#f2d59f]">
                                Promo {{ menuItem.promoPrice }}
                            </span>
                        </div>

                        <p class="mt-4 text-sm leading-6 text-slate-600">{{ menuItem.description }}</p>
                    </article>
                </aside>
            </div>
        </div>
    </MerchantLayout>
</template>