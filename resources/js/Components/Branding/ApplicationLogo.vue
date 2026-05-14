<script setup>
defineProps({
    compact: {
        type: Boolean,
        default: false,
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
        class="block h-full w-full object-contain"
    />
</template>
