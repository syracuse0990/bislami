<script setup>
import { onMounted, ref } from 'vue';

const model = defineModel({
    type: String,
    required: true,
});

const input = ref(null);

onMounted(() => {
    if (input.value.hasAttribute('autofocus')) {
        input.value.focus();
    }
});

defineExpose({ focus: () => input.value.focus() });
</script>

<template>
    <input
        class="rounded-2xl border border-[#d7e7e8] bg-white/95 px-4 py-3 text-sm text-slate-900 shadow-[0_18px_40px_-28px_rgba(11,77,89,0.7)] placeholder:text-slate-400 focus:border-[var(--brand-orange)] focus:ring-[#ffd6b6]"
        v-model="model"
        ref="input"
    />
</template>
