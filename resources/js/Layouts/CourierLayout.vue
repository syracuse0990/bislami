<script setup>
import AppShellLayout from '@/Layouts/AppShellLayout.vue';

const home = {
    name: 'courier.dashboard',
    label: 'Overview',
};

const navigation = [
    { name: 'courier.deliveries.index', label: 'Deliveries', activePattern: 'courier.deliveries.*' },
];

const settings = {
    name: 'customer.settings.edit',
    label: 'Account Settings',
};
</script>

<template>
    <AppShellLayout :home="home" :navigation="navigation" :settings="settings">
        <template v-if="$slots.header" #header>
            <slot name="header" />
        </template>

        <slot />
    </AppShellLayout>
</template>