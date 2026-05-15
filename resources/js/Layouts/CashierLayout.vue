<script setup>
import ApplicationLogo from '@/Components/Branding/ApplicationLogo.vue';
import ConfirmDialog from '@/Components/UI/ConfirmDialog.vue';
import { Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref } from 'vue';

const page = usePage();
const user = computed(() => page.props.auth?.user ?? null);

const currentTime = ref('');
const updateTime = () => {
    currentTime.value = new Date().toLocaleTimeString('en-PH', {
        hour: '2-digit',
        minute: '2-digit',
        hour12: true,
    });
};
let timer = null;
onMounted(() => {
    updateTime();
    timer = setInterval(updateTime, 10000);
});
onUnmounted(() => {
    if (timer) clearInterval(timer);
});

// User dropdown
const userMenuOpen = ref(false);
const userMenuEl = ref(null);
const toggleUserMenu = () => { userMenuOpen.value = !userMenuOpen.value; };
const closeUserMenu = (e) => {
    if (userMenuEl.value && !userMenuEl.value.contains(e.target)) {
        userMenuOpen.value = false;
    }
};
onMounted(() => document.addEventListener('click', closeUserMenu));
onUnmounted(() => document.removeEventListener('click', closeUserMenu));

const logout = () => {
    userMenuOpen.value = false;
    router.post(route('logout'));
};

// Change password modal
const pwModalOpen = ref(false);
const pwForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});
const openPwModal = () => {
    userMenuOpen.value = false;
    pwForm.reset();
    pwForm.clearErrors();
    pwModalOpen.value = true;
};
const closePwModal = () => {
    pwModalOpen.value = false;
    pwForm.reset();
    pwForm.clearErrors();
};
const submitPw = () => {
    pwForm.put(route('password.update'), {
        preserveScroll: true,
        onSuccess: () => closePwModal(),
    });
};
</script>

<template>
    <div class="flex h-screen flex-col overflow-hidden bg-[#0b4d59]">
        <!-- Top header bar -->
        <header class="flex h-14 shrink-0 items-center justify-between px-5">
            <div class="flex items-center gap-3">
                <Link :href="route('merchant.cashier.dashboard')" class="flex items-center gap-2.5">
                    <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-white/15 p-1.5">
                        <ApplicationLogo compact class="h-full w-full brightness-0 invert" />
                    </div>
                    <div class="hidden sm:block">
                        <p class="text-[9px] font-bold uppercase tracking-[0.2em] text-white/40">POS Terminal</p>
                        <slot name="restaurant-name">
                            <p class="text-sm font-semibold text-white">Cashier</p>
                        </slot>
                    </div>
                </Link>
            </div>

            <div class="flex items-center gap-3 text-sm">
                <span class="hidden font-mono text-sm font-medium text-white/60 sm:inline">{{ currentTime }}</span>
                <span class="hidden h-4 w-px bg-white/20 sm:block" />

                <!-- User dropdown -->
                <div ref="userMenuEl" class="relative hidden sm:block">
                    <button
                        type="button"
                        class="flex items-center gap-1.5 rounded-full px-2.5 py-1 text-sm font-medium text-white/80 transition hover:bg-white/10"
                        @click.stop="toggleUserMenu"
                    >
                        <span class="flex h-6 w-6 items-center justify-center rounded-full bg-white/20 text-[10px] font-bold text-white">
                            {{ user?.name?.charAt(0).toUpperCase() }}
                        </span>
                        {{ user?.name }}
                        <svg class="h-3.5 w-3.5 text-white/50 transition-transform" :class="{ 'rotate-180': userMenuOpen }" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    <!-- Dropdown panel -->
                    <Transition
                        enter-active-class="transition duration-100 ease-out"
                        enter-from-class="opacity-0 scale-95 -translate-y-1"
                        enter-to-class="opacity-100 scale-100 translate-y-0"
                        leave-active-class="transition duration-75 ease-in"
                        leave-from-class="opacity-100 scale-100 translate-y-0"
                        leave-to-class="opacity-0 scale-95 -translate-y-1"
                    >
                        <div
                            v-if="userMenuOpen"
                            class="absolute right-0 top-full z-50 mt-2 w-52 origin-top-right overflow-hidden rounded-2xl border border-[#e8e3da] bg-white shadow-xl"
                        >
                            <!-- User info header -->
                            <div class="border-b border-[#f3efe9] px-4 py-3">
                                <p class="truncate text-sm font-semibold text-[#0b4d59]">{{ user?.name }}</p>
                                <p class="truncate text-xs text-gray-400">{{ user?.email }}</p>
                            </div>

                            <!-- Actions -->
                            <div class="py-1.5">
                                <button
                                    type="button"
                                    class="flex w-full items-center gap-3 px-4 py-2.5 text-sm text-gray-700 transition hover:bg-[#faf7f3]"
                                    @click="openPwModal"
                                >
                                    <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z" />
                                    </svg>
                                    Change Password
                                </button>
                            </div>

                            <div class="border-t border-[#f3efe9] py-1.5">
                                <button
                                    type="button"
                                    class="flex w-full items-center gap-3 px-4 py-2.5 text-sm text-red-600 transition hover:bg-red-50"
                                    @click="logout"
                                >
                                    <svg class="h-4 w-4 text-red-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75" />
                                    </svg>
                                    Sign Out
                                </button>
                            </div>
                        </div>
                    </Transition>
                </div>

                <span class="h-4 w-px bg-white/20" />
                <slot name="header-action">
                    <Link
                        :href="route('merchant.cashier.dashboard')"
                        class="rounded-full border border-white/20 bg-white/10 px-3 py-1.5 text-xs font-semibold text-white/80 transition hover:bg-white/20"
                    >
                        Exit POS
                    </Link>
                </slot>
            </div>
        </header>

        <!-- Page content fills remaining height -->
        <div class="min-h-0 flex-1 overflow-hidden rounded-t-[20px] bg-[#f3efe9]">
            <slot />
        </div>

        <ConfirmDialog />

        <!-- Change Password Modal -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition duration-150 ease-out"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition duration-100 ease-in"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="pwModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true">
                    <!-- Backdrop -->
                    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="closePwModal" />

                    <!-- Panel -->
                    <Transition
                        enter-active-class="transition duration-150 ease-out"
                        enter-from-class="opacity-0 scale-95"
                        enter-to-class="opacity-100 scale-100"
                        leave-active-class="transition duration-100 ease-in"
                        leave-from-class="opacity-100 scale-100"
                        leave-to-class="opacity-0 scale-95"
                    >
                        <div v-if="pwModalOpen" class="relative w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl">
                            <!-- Header -->
                            <div class="border-b border-[#e8e3da] px-6 py-4">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <h2 class="text-base font-semibold text-[#0b4d59]">Change Password</h2>
                                        <p class="text-xs text-gray-400">Update your account password</p>
                                    </div>
                                    <button type="button" class="rounded-lg p-1 text-gray-400 transition hover:bg-[#f3efe9] hover:text-gray-600" @click="closePwModal" aria-label="Close">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                                    </button>
                                </div>
                            </div>

                            <!-- Form -->
                            <form @submit.prevent="submitPw" class="px-6 py-5 space-y-4">
                                <!-- Current password -->
                                <div>
                                    <label class="mb-1.5 block text-xs font-semibold text-gray-600">Current Password</label>
                                    <input
                                        v-model="pwForm.current_password"
                                        type="password"
                                        autocomplete="current-password"
                                        class="w-full rounded-xl border px-3.5 py-2.5 text-sm text-gray-800 outline-none transition focus:ring-2 focus:ring-[#0b4d59]/20"
                                        :class="pwForm.errors.current_password ? 'border-red-400 bg-red-50' : 'border-[#e8e3da] bg-[#faf7f3] focus:border-[#0b4d59]'"
                                    />
                                    <p v-if="pwForm.errors.current_password" class="mt-1 text-xs text-red-500">{{ pwForm.errors.current_password }}</p>
                                </div>

                                <!-- New password -->
                                <div>
                                    <label class="mb-1.5 block text-xs font-semibold text-gray-600">New Password</label>
                                    <input
                                        v-model="pwForm.password"
                                        type="password"
                                        autocomplete="new-password"
                                        class="w-full rounded-xl border px-3.5 py-2.5 text-sm text-gray-800 outline-none transition focus:ring-2 focus:ring-[#0b4d59]/20"
                                        :class="pwForm.errors.password ? 'border-red-400 bg-red-50' : 'border-[#e8e3da] bg-[#faf7f3] focus:border-[#0b4d59]'"
                                    />
                                    <p v-if="pwForm.errors.password" class="mt-1 text-xs text-red-500">{{ pwForm.errors.password }}</p>
                                </div>

                                <!-- Confirm password -->
                                <div>
                                    <label class="mb-1.5 block text-xs font-semibold text-gray-600">Confirm New Password</label>
                                    <input
                                        v-model="pwForm.password_confirmation"
                                        type="password"
                                        autocomplete="new-password"
                                        class="w-full rounded-xl border px-3.5 py-2.5 text-sm text-gray-800 outline-none transition focus:ring-2 focus:ring-[#0b4d59]/20"
                                        :class="pwForm.errors.password_confirmation ? 'border-red-400 bg-red-50' : 'border-[#e8e3da] bg-[#faf7f3] focus:border-[#0b4d59]'"
                                    />
                                    <p v-if="pwForm.errors.password_confirmation" class="mt-1 text-xs text-red-500">{{ pwForm.errors.password_confirmation }}</p>
                                </div>

                                <!-- Actions -->
                                <div class="flex items-center justify-end gap-3 pt-1">
                                    <button
                                        type="button"
                                        class="rounded-xl border border-[#e8e3da] bg-white px-4 py-2 text-sm font-medium text-gray-600 transition hover:bg-[#f3efe9]"
                                        @click="closePwModal"
                                    >
                                        Cancel
                                    </button>
                                    <button
                                        type="submit"
                                        :disabled="pwForm.processing"
                                        class="rounded-xl bg-[#0b4d59] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#0a3f49] disabled:opacity-60"
                                    >
                                        {{ pwForm.processing ? 'Saving...' : 'Update Password' }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </Transition>
                </div>
            </Transition>
        </Teleport>
    </div>
</template>
