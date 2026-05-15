<script setup>
import AppShellLayout from '@/Layouts/AppShellLayout.vue';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const home = {
    name: 'merchant.dashboard',
    label: 'Overview',
    icon: 'overview',
};

const navigation = [
    { name: 'merchant.profile.show', label: 'Profile', activePattern: 'merchant.profile.*', icon: 'profile' },
    {
        name: 'merchant.menu.index',
        label: 'Menu',
        activePattern: 'merchant.menu.*',
        icon: 'menu',
        children: [
            { name: 'merchant.menu.index', label: 'All Items', activePattern: 'merchant.menu.index' },
            { name: 'merchant.menu.daily.index', label: "Today's Service", activePattern: 'merchant.menu.daily.*' },
        ],
    },
    { name: 'merchant.orders.index', label: 'Orders', activePattern: 'merchant.orders.*', icon: 'orders' },
    { name: 'merchant.cashier.dashboard', label: 'POS Cashier', activePattern: 'merchant.cashier.*', icon: 'cashier' },
    {
        name: 'merchant.staff.index',
        label: 'Staff',
        activePattern: 'merchant.staff.*',
        icon: 'staff',
        children: [
            { name: 'merchant.staff.index', label: 'Team & Roles', activePattern: 'merchant.staff.index' },
            { name: 'merchant.staff.activity', label: 'Activity Log', activePattern: 'merchant.staff.activity' },
        ],
    },
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