<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    href: {
        type: String,
        required: true,
    },
});
</script>

<template>
    <Link
        :href="href"
        class="block w-full rounded-2xl px-4 py-3 text-start text-sm font-semibold leading-5 text-slate-700 transition duration-150 ease-in-out hover:bg-[#fff8f1] hover:text-[var(--brand-teal)] focus:bg-[#fff8f1] focus:text-[var(--brand-teal)] focus:outline-none"
    >
        <slot />
    </Link>
</template>
