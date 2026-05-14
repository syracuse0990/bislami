<script setup>
import AppShellLayout from '@/Layouts/AppShellLayout.vue';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const home = {
    name: 'merchant.dashboard',
    label: 'Overview',
};

const navigation = [
    { name: 'merchant.profile.show', label: 'Profile', activePattern: 'merchant.profile.*' },
    { name: 'merchant.menu.index', label: 'Menu', activePattern: 'merchant.menu.*' },
    { name: 'merchant.orders.index', label: 'Orders', activePattern: 'merchant.orders.*' },
];

const settings = {
    name: 'customer.settings.edit',
    label: 'Account Settings',
};

const page = usePage();
const isMerchantApproved = computed(() => Boolean(page.props.auth.user?.merchantVerifiedAt));
const visibleNavigation = computed(() => (isMerchantApproved.value ? navigation : []));
</script>

<template>
    <AppShellLayout :home="home" :navigation="visibleNavigation" :settings="settings" navigation-orientation="vertical">
        <template v-if="$slots.header" #header>
            <slot name="header" />
        </template>

        <slot />
    </AppShellLayout>
</template>