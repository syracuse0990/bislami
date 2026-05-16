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
    () => [flash.value?.success, flash.value?.warning, flash.value?.error],
    (current, previous = []) => {
        const [success, warning, error] = current;
        const [previousSuccess, previousWarning, previousError] = previous;

        if (success && success !== previousSuccess) {
            showToast('success', 'Success', success, 4200);
        }

        if (warning && warning !== previousWarning) {
            showToast('warning', 'Warning', warning, 5200);
        }

        if (error && error !== previousError) {
            showToast('error', 'Error', error, 6200);
        }
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