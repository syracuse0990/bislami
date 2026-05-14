<script setup>
import AppShellLayout from '@/Layouts/AppShellLayout.vue';

const home = {
    name: 'customer.dashboard',
    label: 'Home',
    activePattern: 'customer.dashboard',
};

const navigation = [
    { name: 'customer.restaurants.index', label: 'Browse', activePattern: 'customer.restaurants.*' },
    { name: 'customer.cart.index', label: 'Cart', activePattern: 'customer.cart.*' },
    { name: 'customer.checkout.index', label: 'Checkout', activePattern: 'customer.checkout.*' },
    { name: 'customer.orders.index', label: 'Orders', activePattern: 'customer.orders.*' },
];

const settings = {
    name: 'customer.settings.edit',
    label: 'Account Settings',
};
</script>

<template>
    <AppShellLayout :home="home" :navigation="navigation" :settings="settings" navigation-orientation="horizontal">
        <template v-if="$slots.header" #header>
            <slot name="header" />
        </template>

        <slot />
    </AppShellLayout>
</template>