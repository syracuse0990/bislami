<template>
    <button
        class="inline-flex items-center justify-center rounded-2xl border border-transparent bg-gradient-to-r from-[var(--brand-teal)] to-[var(--brand-orange)] px-5 py-3 text-xs font-bold uppercase tracking-[0.22em] text-white shadow-[0_20px_45px_-24px_rgba(11,77,89,0.85)] transition duration-200 ease-out hover:-translate-y-0.5 hover:shadow-[0_24px_50px_-24px_rgba(11,77,89,0.95)] focus:outline-none focus:ring-2 focus:ring-[#ffd6b6] focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60"
    >
        <slot />
    </button>
</template>
