<script setup>
import InputError from '@/Components/Forms/InputError.vue';
import InputLabel from '@/Components/Forms/InputLabel.vue';
import { buildTime12HourOptions } from '@/Support/time12Hour';
import { computed, useAttrs } from 'vue';

defineOptions({ inheritAttrs: false });

const props = defineProps({
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
    stepMinutes: {
        type: Number,
        default: 15,
    },
    labelClass: {
        type: String,
        default: '',
    },
    selectClass: {
        type: String,
        default: 'mt-1 block w-full rounded-2xl border border-[#d7e7e8] bg-white/95 px-4 py-3 text-sm text-slate-900 shadow-[0_18px_40px_-28px_rgba(11,77,89,0.7)] focus:border-[var(--brand-orange)] focus:ring-[#ffd6b6] disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-400',
    },
    errorClass: {
        type: String,
        default: 'mt-2',
    },
});

const model = defineModel({
    type: String,
    default: '',
});

const attrs = useAttrs();
const options = computed(() => buildTime12HourOptions(props.stepMinutes, [model.value]));
</script>

<template>
    <div>
        <InputLabel :for="id" :value="label" :class="labelClass" />

        <select
            :id="id"
            v-model="model"
            :class="selectClass"
            v-bind="attrs"
        >
            <option value="">Select a time</option>

            <option v-for="option in options" :key="option.value" :value="option.value">
                {{ option.label }}
            </option>
        </select>

        <InputError :message="message" :class="errorClass" />
    </div>
</template>
