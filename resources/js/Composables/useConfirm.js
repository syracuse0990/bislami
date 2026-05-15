import { reactive } from 'vue';

const state = reactive({
    isOpen: false,
    title: 'Are you sure?',
    message: '',
    confirmLabel: 'Confirm',
    cancelLabel: 'Cancel',
    intent: 'default', // 'default' | 'danger'
    resolve: null,
});

export function useConfirm() {
    function confirm({
        title = 'Are you sure?',
        message = '',
        confirmLabel = 'Confirm',
        cancelLabel = 'Cancel',
        intent = 'default',
    } = {}) {
        return new Promise((resolve) => {
            Object.assign(state, {
                isOpen: true,
                title,
                message,
                confirmLabel,
                cancelLabel,
                intent,
                resolve,
            });
        });
    }

    function handleConfirm() {
        state.isOpen = false;
        state.resolve?.(true);
        state.resolve = null;
    }

    function handleCancel() {
        state.isOpen = false;
        state.resolve?.(false);
        state.resolve = null;
    }

    return { confirmState: state, confirm, handleConfirm, handleCancel };
}
