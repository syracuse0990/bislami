<script setup>
import Checkbox from '@/Components/Forms/Fields/Checkbox.vue';

defineOptions({ inheritAttrs: false });

defineProps({
    label: {
        type: String,
        required: true,
    },
});

const checked = defineModel('checked', {
    type: [Array, Boolean],
    default: false,
});
</script>

<template>
    <label class="flex items-center gap-3 rounded-2xl bg-[#f8fbfb] px-4 py-3 ring-1 ring-[#e1ecec]">
        <Checkbox v-model:checked="checked" v-bind="$attrs" />
        <span class="text-sm font-medium text-slate-700">{{ label }}</span>
    </label>
</template>