<script setup>
import InputError from '@/Components/Forms/InputError.vue';
import InputLabel from '@/Components/Forms/InputLabel.vue';
import TextInput from '@/Components/Forms/Fields/TextInput.vue';
import { ref, useAttrs } from 'vue';

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
    type: {
        type: String,
        default: 'text',
    },
    message: {
        type: String,
        default: '',
    },
    labelClass: {
        type: String,
        default: '',
    },
    inputClass: {
        type: String,
        default: 'mt-1 block w-full',
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
const input = ref(null);

defineExpose({
    focus: () => input.value?.focus(),
});
</script>

<template>
    <div>
        <InputLabel :for="id" :value="label" :class="labelClass" />

        <TextInput
            :id="id"
            ref="input"
            v-model="model"
            :type="type"
            :class="inputClass"
            v-bind="attrs"
        />

        <InputError :message="message" :class="errorClass" />
    </div>
</template>