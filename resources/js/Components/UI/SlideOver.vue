<script setup>
import { computed, onUnmounted, watch } from 'vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    closeable: {
        type: Boolean,
        default: true,
    },
    maxWidth: {
        type: String,
        default: 'xl',
    },
});

const emit = defineEmits(['close']);

watch(
    () => props.show,
    (showing) => {
        document.body.style.overflow = showing ? 'hidden' : '';
    },
    { immediate: true },
);

onUnmounted(() => {
    document.body.style.overflow = '';
});

const close = () => {
    if (props.closeable) {
        emit('close');
    }
};

const widthClass = computed(() => ({
    lg: 'max-w-lg',
    xl: 'max-w-xl',
    '2xl': 'max-w-2xl',
    '3xl': 'max-w-3xl',
})[props.maxWidth] ?? 'max-w-xl');
</script>

<template>
    <div v-if="show" class="fixed inset-0 z-[70] overflow-hidden">
        <Transition
            enter-active-class="transition ease-out duration-300"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition ease-in duration-200"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div class="absolute inset-0 bg-slate-950/35 backdrop-blur-[2px]" @click="close" />
        </Transition>

        <div class="absolute inset-y-0 right-0 flex max-w-full pl-6 sm:pl-10">
            <Transition
                enter-active-class="transform transition ease-out duration-300"
                enter-from-class="translate-x-full"
                enter-to-class="translate-x-0"
                leave-active-class="transform transition ease-in duration-200"
                leave-from-class="translate-x-0"
                leave-to-class="translate-x-full"
            >
                <div v-show="show" class="pointer-events-auto w-screen" :class="widthClass">
                    <div class="flex h-full flex-col overflow-y-auto bg-white shadow-[0_28px_80px_-30px_rgba(11,77,89,0.65)]">
                        <slot />
                    </div>
                </div>
            </Transition>
        </div>
    </div>
</template>