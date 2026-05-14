<script setup>
import AppShellLayout from '@/Layouts/AppShellLayout.vue';

const home = {
    name: 'admin.dashboard',
    label: 'Overview',
};

const navigation = [
    { name: 'admin.restaurants.index', label: 'Restaurants', activePattern: 'admin.restaurants.*' },
    { name: 'admin.users.index', label: 'Users', activePattern: 'admin.users.*' },
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