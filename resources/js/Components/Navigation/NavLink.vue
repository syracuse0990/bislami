<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    href: {
        type: String,
        required: true,
    },
    active: {
        type: Boolean,
    },
});

const classes = computed(() =>
    props.active
    ? 'inline-flex items-center rounded-full bg-[var(--brand-teal)] px-4 py-2 text-sm font-semibold text-white shadow-[0_18px_38px_-22px_rgba(11,77,89,0.9)] transition duration-200 ease-out focus:outline-none focus-visible:ring-2 focus-visible:ring-[#ffd6b6] focus-visible:ring-offset-2'
    : 'inline-flex items-center rounded-full px-4 py-2 text-sm font-semibold text-slate-600 transition duration-200 ease-out hover:bg-white/85 hover:text-[var(--brand-teal)] focus:outline-none focus-visible:bg-white focus-visible:text-[var(--brand-teal)]',
);
</script>

<template>
    <Link :href="href" :class="classes">
        <slot />
    </Link>
</template>
