<script setup>
import InputError from '@/Components/Forms/InputError.vue';
import InputLabel from '@/Components/Forms/InputLabel.vue';

defineOptions({ inheritAttrs: false });

const emit = defineEmits(['change']);

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
    helper: {
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
        <InputLabel :for="id" :value="label" />

        <label
            :for="id"
            class="mt-2 flex min-h-[132px] cursor-pointer flex-col items-center justify-center rounded-[26px] border border-dashed border-[#d7e7e8] bg-[#f8fbfb] px-5 py-6 text-center transition duration-200 hover:border-[var(--brand-orange)] hover:bg-[#fff7ef]"
        >
            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-white shadow-sm ring-1 ring-[#e4eded]">
                <img src="/images/bizlami_icon.png" alt="BizLami icon" class="h-7 w-7 object-contain">
            </div>
            <p class="mt-4 text-sm font-semibold text-slate-900">Upload a dish image</p>
            <p class="mt-1 text-sm text-slate-500">PNG, JPG, WEBP, or GIF up to 5 MB</p>
            <p v-if="helper" class="mt-3 max-w-sm text-xs leading-5 text-slate-500">{{ helper }}</p>
        </label>

        <input
            :id="id"
            type="file"
            class="sr-only"
            @change="handleChange"
            v-bind="$attrs"
        >

        <InputError :message="message" class="mt-3" />
    </div>
</template>