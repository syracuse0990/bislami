<script setup>
import InputError from '@/Components/Forms/InputError.vue';
import InputLabel from '@/Components/Forms/InputLabel.vue';

defineOptions({ inheritAttrs: false });

const emit = defineEmits(['change']);

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
    helper: {
        type: String,
        default: '',
    },
    accept: {
        type: String,
        default: '',
    },
    title: {
        type: String,
        default: 'Upload a dish image',
    },
    description: {
        type: String,
        default: 'PNG, JPG, WEBP, or GIF up to 5 MB',
    },
    previewSrc: {
        type: String,
        default: '',
    },
    previewAlt: {
        type: String,
        default: '',
    },
});

const handleChange = (event) => {
    emit('change', event.target.files?.[0] ?? null);
};
</script>

<template>
    <div>
        <InputLabel :for="props.id" :value="props.label" />

        <label
            :for="props.id"
            class="mt-2 flex min-h-[132px] cursor-pointer flex-col items-center justify-center rounded-[26px] border border-dashed border-[#d7e7e8] bg-[#f8fbfb] px-5 py-6 text-center transition duration-200 hover:border-[var(--brand-orange)] hover:bg-[#fff7ef]"
        >
            <div
                v-if="props.previewSrc"
                class="overflow-hidden rounded-[22px] bg-white shadow-sm ring-1 ring-[#e4eded]"
            >
                <img
                    :src="props.previewSrc"
                    :alt="props.previewAlt || props.label"
                    class="h-24 w-24 object-cover"
                >
            </div>

            <div v-else class="flex h-12 w-12 items-center justify-center rounded-2xl bg-white shadow-sm ring-1 ring-[#e4eded]">
                <img src="/images/bizlami_icon.png" alt="BizLami icon" class="h-7 w-7 object-contain">
            </div>

            <p class="mt-4 text-sm font-semibold text-slate-900">{{ props.previewSrc ? `Change ${props.label.toLowerCase()}` : props.title }}</p>
            <p class="mt-1 text-sm text-slate-500">{{ props.description }}</p>
            <p v-if="props.helper" class="mt-3 max-w-sm text-xs leading-5 text-slate-500">{{ props.helper }}</p>
        </label>

        <input
            :id="props.id"
            type="file"
            class="sr-only"
            :accept="props.accept"
            @change="handleChange"
            v-bind="$attrs"
        >

        <InputError :message="props.message" class="mt-3" />
    </div>
</template>