<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import CourierLayout from '@/Layouts/CourierLayout.vue';
import CustomerLayout from '@/Layouts/CustomerLayout.vue';
import MerchantLayout from '@/Layouts/MerchantLayout.vue';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();

const layouts = {
    admin: AdminLayout,
    courier: CourierLayout,
    customer: CustomerLayout,
    merchant: MerchantLayout,
};

const currentLayout = computed(() => layouts[page.props.auth.user?.role] ?? CustomerLayout);
</script>

<template>
    <component :is="currentLayout">
        <template v-if="$slots.header" #header>
            <slot name="header" />
        </template>

        <slot />
    </component>
</template>
