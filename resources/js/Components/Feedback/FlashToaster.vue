<script setup>
import { usePage } from '@inertiajs/vue3';
import { Toaster, sileo } from '@llayz46/sileo-vue';
import { computed, reactive, watch } from 'vue';

const page = usePage();
const activeToastIds = reactive({
    success: null,
    warning: null,
    error: null,
});

const flash = computed(() => page.props.flash ?? {
    success: null,
    warning: null,
    error: null,
});

function showToast(level, title, description, duration) {
    if (!description) {
        return;
    }

    if (activeToastIds[level]) {
        sileo.dismiss(activeToastIds[level]);
    }

    activeToastIds[level] = sileo[level]({
        title,
        description,
        duration,
    });
}

watch(
    () => flash.value,
    (current) => {
        showToast('success', 'Success', current?.success, 4200);
        showToast('warning', 'Warning', current?.warning, 5200);
        showToast('error', 'Error', current?.error, 6200);
    },
    { immediate: true },
);
</script>

<template>
    <Toaster
        position="top-right"
        theme="light"
        :offset="{ top: 24, right: 24 }"
        :options="{ roundness: 24 }"
    />
</template>