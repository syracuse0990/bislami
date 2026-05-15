<script setup>
import MenuItemForm from '@/Features/merchant/menu/components/MenuItemForm.vue';
import MerchantLayout from '@/Layouts/MerchantLayout.vue';
import { Head } from '@inertiajs/vue3';
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
</script>

<template>
    <Head :title="pageTitle" />

    <MerchantLayout>
        <div class="py-8">
            <div class="px-4 sm:px-6 lg:px-0">
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
            </div>
        </div>
    </MerchantLayout>
</template>