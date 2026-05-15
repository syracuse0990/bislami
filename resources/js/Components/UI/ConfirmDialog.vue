<script setup>
import { useConfirm } from '@/Composables/useConfirm';
import { onMounted, onUnmounted } from 'vue';

const { confirmState, handleConfirm, handleCancel } = useConfirm();

function onKeydown(e) {
    if (!confirmState.isOpen) return;
    if (e.key === 'Escape') handleCancel();
    if (e.key === 'Enter') handleConfirm();
}

onMounted(() => document.addEventListener('keydown', onKeydown));
onUnmounted(() => document.removeEventListener('keydown', onKeydown));
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="confirmState.isOpen"
                class="fixed inset-0 z-[9999] flex items-center justify-center p-4"
                aria-modal="true"
                role="dialog"
                :aria-labelledby="'confirm-title'"
                :aria-describedby="confirmState.message ? 'confirm-message' : undefined"
                @click.self="handleCancel"
            >
                <!-- Backdrop -->
                <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" @click="handleCancel" />

                <!-- Dialog panel -->
                <Transition
                    enter-active-class="transition duration-200 ease-out"
                    enter-from-class="opacity-0 scale-95 translate-y-2"
                    enter-to-class="opacity-100 scale-100 translate-y-0"
                    leave-active-class="transition duration-150 ease-in"
                    leave-from-class="opacity-100 scale-100 translate-y-0"
                    leave-to-class="opacity-0 scale-95 translate-y-2"
                >
                    <div
                        v-if="confirmState.isOpen"
                        class="relative w-full max-w-md overflow-hidden rounded-[28px] border border-white/80 bg-[linear-gradient(145deg,#ffffff_0%,#fff8f1_58%,#f4fbfb_100%)] shadow-[0_40px_100px_-30px_rgba(11,77,89,0.45)] ring-1 ring-white/60"
                    >
                        <!-- Top accent line -->
                        <div
                            class="h-1 w-full"
                            :class="confirmState.intent === 'danger'
                                ? 'bg-gradient-to-r from-red-400 to-rose-500'
                                : 'bg-gradient-to-r from-[var(--brand-teal)] to-[var(--brand-orange)]'"
                        />

                        <div class="px-8 py-7">
                            <!-- Icon -->
                            <div
                                class="mb-5 inline-flex h-12 w-12 items-center justify-center rounded-2xl"
                                :class="confirmState.intent === 'danger'
                                    ? 'bg-red-50 text-red-500 ring-1 ring-red-100'
                                    : 'bg-[#f0f8f8] text-[var(--brand-teal)] ring-1 ring-[#dceced]'"
                            >
                                <!-- Danger icon -->
                                <svg
                                    v-if="confirmState.intent === 'danger'"
                                    class="h-6 w-6"
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                    aria-hidden="true"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>

                                <!-- Default (question / alert) icon -->
                                <svg
                                    v-else
                                    class="h-6 w-6"
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                    aria-hidden="true"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>

                            <!-- Title -->
                            <h2
                                id="confirm-title"
                                class="text-xl font-semibold text-slate-900"
                            >
                                {{ confirmState.title }}
                            </h2>

                            <!-- Message -->
                            <p
                                v-if="confirmState.message"
                                id="confirm-message"
                                class="mt-2.5 text-sm leading-6 text-slate-500"
                            >
                                {{ confirmState.message }}
                            </p>

                            <!-- Buttons -->
                            <div class="mt-8 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                                <!-- Cancel -->
                                <button
                                    type="button"
                                    class="inline-flex items-center justify-center rounded-full border border-[#d6e2e2] bg-white px-6 py-3 text-sm font-semibold text-slate-700 transition duration-200 hover:-translate-y-0.5 hover:border-[#c6d4d4] hover:bg-[#f8fbfb] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#dce8e8]"
                                    @click="handleCancel"
                                >
                                    {{ confirmState.cancelLabel }}
                                </button>

                                <!-- Confirm — danger variant -->
                                <button
                                    v-if="confirmState.intent === 'danger'"
                                    type="button"
                                    class="inline-flex items-center justify-center rounded-full bg-gradient-to-br from-red-500 to-rose-600 px-6 py-3 text-sm font-semibold text-white shadow-[0_18px_40px_-20px_rgba(239,68,68,0.65)] transition duration-200 hover:-translate-y-0.5 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-400"
                                    @click="handleConfirm"
                                >
                                    {{ confirmState.confirmLabel }}
                                </button>

                                <!-- Confirm — default variant -->
                                <button
                                    v-else
                                    type="button"
                                    class="inline-flex items-center justify-center rounded-full bg-gradient-to-r from-[var(--brand-teal)] to-[var(--brand-orange)] px-6 py-3 text-sm font-semibold text-white shadow-[0_18px_40px_-20px_rgba(11,77,89,0.6)] transition duration-200 hover:-translate-y-0.5 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#ffd6b6]"
                                    @click="handleConfirm"
                                >
                                    {{ confirmState.confirmLabel }}
                                </button>
                            </div>
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>
