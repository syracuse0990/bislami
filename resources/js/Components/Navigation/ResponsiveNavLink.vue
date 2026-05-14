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
    ? 'block w-full rounded-2xl bg-[var(--brand-teal)] px-4 py-3 text-start text-sm font-semibold text-white shadow-[0_18px_38px_-22px_rgba(11,77,89,0.9)] transition duration-200 ease-out'
    : 'block w-full rounded-2xl px-4 py-3 text-start text-sm font-semibold text-slate-700 transition duration-200 ease-out hover:bg-[#fff8f1] hover:text-[var(--brand-teal)] focus:outline-none focus-visible:bg-[#fff8f1] focus-visible:text-[var(--brand-teal)]',
);
</script>

<template>
    <Link :href="href" :class="classes">
        <slot />
    </Link>
</template>
