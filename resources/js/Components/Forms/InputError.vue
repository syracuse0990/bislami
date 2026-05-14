<script setup>
defineProps({
    message: {
        type: String,
    },
});
</script>

<template>
    <div v-show="message">
        <p class="rounded-2xl border border-[#ffd8bf] bg-[#fff4e9] px-3 py-2 text-sm font-medium text-[var(--brand-orange-deep)]">
            {{ message }}
        </p>
    </div>
</template>
