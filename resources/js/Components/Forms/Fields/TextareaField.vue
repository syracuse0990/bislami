<script setup>
import InputError from '@/Components/Forms/InputError.vue';
import InputLabel from '@/Components/Forms/InputLabel.vue';
import { useAttrs } from 'vue';

defineOptions({ inheritAttrs: false });

defineProps({
    id: {
        type: String,
        required: true,
    },
    label: {
        type: String,
        required: true,
    },
    message: {
        type: String,
        default: '',
    },
    labelClass: {
        type: String,
        default: '',
    },
    textareaClass: {
        type: String,
        default: 'mt-2 block min-h-[132px] w-full rounded-2xl border border-[#d7e7e8] bg-white/95 px-4 py-3 text-sm text-slate-900 shadow-[0_18px_40px_-28px_rgba(11,77,89,0.7)] focus:border-[var(--brand-orange)] focus:ring-[#ffd6b6]',
    },
    errorClass: {
        type: String,
        default: 'mt-2',
    },
});

const model = defineModel({
    type: String,
    required: true,
});

const attrs = useAttrs();
</script>

<template>
    <div>
        <InputLabel :for="id" :value="label" :class="labelClass" />

        <textarea
            :id="id"
            v-model="model"
            :class="textareaClass"
            v-bind="attrs"
        />

        <InputError :message="message" :class="errorClass" />
    </div>
</template>