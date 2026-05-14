<script setup>
defineProps({
    href: {
        type: String,
        required: true,
    },
    label: {
        type: String,
        default: 'Continue with Google',
    },
});
</script>

<template>
    <a
        :href="href"
        class="inline-flex w-full items-center justify-center gap-3 rounded-[18px] border border-[#dbe4ed] bg-white px-5 py-4 text-sm font-semibold text-slate-700 shadow-[0_20px_44px_-34px_rgba(15,23,42,0.22)] transition duration-200 hover:-translate-y-0.5 hover:border-[rgba(11,77,89,0.22)] hover:shadow-[0_24px_48px_-32px_rgba(11,77,89,0.28)]"
    >
        <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" aria-hidden="true" xmlns="http://www.w3.org/2000/svg">
            <path fill="#EA4335" d="M12 10.2v3.9h5.4c-.24 1.26-.96 2.33-2.04 3.05l3.3 2.56c1.92-1.77 3.03-4.38 3.03-7.49 0-.72-.06-1.41-.19-2.01H12Z" />
            <path fill="#34A853" d="M12 21.75c2.7 0 4.97-.9 6.63-2.44l-3.3-2.56c-.92.62-2.09.99-3.33.99-2.57 0-4.76-1.73-5.54-4.05l-3.41 2.63c1.65 3.28 5.04 5.43 8.95 5.43Z" />
            <path fill="#4A90E2" d="M6.46 13.69A5.94 5.94 0 0 1 6.15 12c0-.59.11-1.16.31-1.69L3.05 7.68A9.75 9.75 0 0 0 2.25 12c0 1.56.37 3.04 1.01 4.32l3.2-2.63Z" />
            <path fill="#FBBC05" d="M12 6.26c1.47 0 2.79.51 3.83 1.52l2.87-2.87C16.96 3.32 14.7 2.25 12 2.25c-3.91 0-7.3 2.15-8.95 5.43l3.41 2.63c.78-2.32 2.97-4.05 5.54-4.05Z" />
        </svg>

        <span>{{ label }}</span>
    </a>
</template>