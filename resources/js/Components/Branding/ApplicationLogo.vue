<script setup>
defineProps({
    compact: {
        type: Boolean,
        default: false,
    },
    fit: {
        type: String,
        default: 'contain',
    },
    alt: {
        type: String,
        default: 'BizLami Food Delivery App',
    },
});
</script>

<template>
    <img
        :src="compact ? '/images/bizlami_icon.png' : '/images/bizlame_logo.png'"
        :alt="alt"
        :class="[
            'block h-full w-full',
            fit === 'cover' ? 'object-cover' : 'object-contain',
        ]"
    />
</template>
