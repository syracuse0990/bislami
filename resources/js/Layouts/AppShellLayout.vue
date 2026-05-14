<script setup>
import ApplicationLogo from '@/Components/Branding/ApplicationLogo.vue';
import Dropdown from '@/Components/Navigation/Dropdown.vue';
import DropdownLink from '@/Components/Navigation/DropdownLink.vue';
import NavLink from '@/Components/Navigation/NavLink.vue';
import ResponsiveNavLink from '@/Components/Navigation/ResponsiveNavLink.vue';
import { Link } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({
    home: {
        type: Object,
        required: true,
    },
    navigation: {
        type: Array,
        default: () => [],
    },
    settings: {
        type: Object,
        default: null,
    },
});

const showingNavigationDropdown = ref(false);
</script>

<template>
    <div class="relative min-h-screen overflow-hidden">
        <div class="pointer-events-none absolute inset-0">
            <div class="absolute left-[-5rem] top-8 h-72 w-72 rounded-full bg-[var(--brand-teal)]/8 blur-3xl"></div>
            <div class="absolute right-[-4rem] top-20 h-64 w-64 rounded-full bg-[var(--brand-orange)]/10 blur-3xl"></div>
        </div>

        <div class="relative min-h-screen">
            <nav class="sticky top-0 z-40 border-b border-white/70 bg-[rgba(252,247,239,0.78)] backdrop-blur-xl">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="flex min-h-[84px] items-center justify-between gap-5">
                        <div class="flex items-center gap-4">
                            <Link :href="route(home.name)" class="flex items-center gap-3">
                                <div class="hidden h-14 w-[210px] items-center rounded-[24px] bg-white/92 px-3 py-2 shadow-[0_20px_48px_-34px_rgba(11,77,89,0.55)] sm:flex">
                                    <ApplicationLogo class="h-full w-full" />
                                </div>

                                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-white/92 p-2 shadow-[0_20px_48px_-34px_rgba(11,77,89,0.55)] sm:hidden">
                                    <ApplicationLogo compact class="h-full w-full" />
                                </div>
                            </Link>

                            <div class="hidden items-center gap-2 rounded-full border border-white/80 bg-white/70 p-1 shadow-[0_18px_44px_-30px_rgba(11,77,89,0.4)] xl:flex">
                                <NavLink
                                    :href="route(home.name)"
                                    :active="route().current(home.activePattern ?? home.name)"
                                >
                                    {{ home.label }}
                                </NavLink>

                                <NavLink
                                    v-for="link in navigation"
                                    :key="link.name"
                                    :href="route(link.name)"
                                    :active="route().current(link.activePattern ?? link.name)"
                                >
                                    {{ link.label }}
                                </NavLink>
                            </div>
                        </div>

                        <div class="hidden items-center gap-3 sm:flex">
                            <div class="hidden text-right lg:block">
                                <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">
                                    Signed in
                                </p>
                                <p class="text-sm font-semibold text-slate-900">
                                    {{ $page.props.auth.user.name }}
                                </p>
                            </div>

                            <Dropdown align="right" width="48">
                                <template #trigger>
                                    <span class="inline-flex rounded-full">
                                        <button
                                            type="button"
                                            class="inline-flex items-center gap-2 rounded-full border border-white/80 bg-white/90 px-4 py-2 text-sm font-semibold text-slate-700 shadow-[0_18px_40px_-30px_rgba(11,77,89,0.45)] transition duration-150 ease-in-out hover:text-[var(--brand-teal)] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#ffd6b6]"
                                        >
                                            <span class="hidden md:inline">{{ $page.props.auth.user.email }}</span>
                                            <span class="md:hidden">Menu</span>

                                            <svg
                                                class="h-4 w-4"
                                                xmlns="http://www.w3.org/2000/svg"
                                                viewBox="0 0 20 20"
                                                fill="currentColor"
                                            >
                                                <path
                                                    fill-rule="evenodd"
                                                    d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                                    clip-rule="evenodd"
                                                />
                                            </svg>
                                        </button>
                                    </span>
                                </template>

                                <template #content>
                                    <DropdownLink
                                        v-if="settings"
                                        :href="route(settings.name)"
                                    >
                                        {{ settings.label }}
                                    </DropdownLink>

                                    <DropdownLink
                                        :href="route('logout')"
                                        method="post"
                                        as="button"
                                    >
                                        Log Out
                                    </DropdownLink>
                                </template>
                            </Dropdown>
                        </div>

                        <div class="-me-2 flex items-center sm:hidden">
                            <button
                                @click="showingNavigationDropdown = !showingNavigationDropdown"
                                class="inline-flex items-center justify-center rounded-2xl border border-white/80 bg-white/90 p-2 text-slate-700 shadow-[0_18px_40px_-30px_rgba(11,77,89,0.45)] transition duration-150 ease-in-out hover:text-[var(--brand-teal)] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#ffd6b6]"
                            >
                                <svg
                                    class="h-6 w-6"
                                    stroke="currentColor"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        :class="{
                                            hidden: showingNavigationDropdown,
                                            'inline-flex': !showingNavigationDropdown,
                                        }"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M4 6h16M4 12h16M4 18h16"
                                    />
                                    <path
                                        :class="{
                                            hidden: !showingNavigationDropdown,
                                            'inline-flex': showingNavigationDropdown,
                                        }"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12"
                                    />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <div
                    :class="{
                        block: showingNavigationDropdown,
                        hidden: !showingNavigationDropdown,
                    }"
                    class="border-t border-white/70 bg-white/70 px-4 pb-4 pt-3 backdrop-blur sm:hidden"
                >
                    <div class="space-y-2">
                        <ResponsiveNavLink
                            :href="route(home.name)"
                            :active="route().current(home.activePattern ?? home.name)"
                        >
                            {{ home.label }}
                        </ResponsiveNavLink>

                        <ResponsiveNavLink
                            v-for="link in navigation"
                            :key="link.name"
                            :href="route(link.name)"
                            :active="route().current(link.activePattern ?? link.name)"
                        >
                            {{ link.label }}
                        </ResponsiveNavLink>
                    </div>

                    <div class="mt-4 rounded-[28px] border border-[#e0ecec] bg-white/90 p-4 shadow-[0_20px_48px_-34px_rgba(11,77,89,0.45)]">
                        <div>
                            <div class="text-base font-semibold text-slate-900">
                                {{ $page.props.auth.user.name }}
                            </div>
                            <div class="text-sm text-slate-500">
                                {{ $page.props.auth.user.email }}
                            </div>
                        </div>

                        <div class="mt-3 space-y-2">
                            <ResponsiveNavLink
                                v-if="settings"
                                :href="route(settings.name)"
                            >
                                {{ settings.label }}
                            </ResponsiveNavLink>

                            <ResponsiveNavLink
                                :href="route('logout')"
                                method="post"
                                as="button"
                            >
                                Log Out
                            </ResponsiveNavLink>
                        </div>
                    </div>
                </div>
            </nav>

            <header v-if="$slots.header" class="relative">
                <div class="mx-auto max-w-7xl px-4 pt-8 sm:px-6 lg:px-8">
                    <div class="overflow-hidden rounded-[32px] border border-white/75 bg-[var(--surface)] p-6 shadow-[0_36px_90px_-58px_rgba(11,77,89,0.6)] backdrop-blur sm:p-8">
                        <div class="flex items-start justify-between gap-6">
                            <div class="min-w-0 flex-1">
                                <slot name="header" />
                            </div>

                            <div class="hidden h-20 w-20 shrink-0 items-center justify-center rounded-[28px] bg-[linear-gradient(145deg,#fffaf4,#ffffff)] p-3 shadow-[0_24px_60px_-40px_rgba(11,77,89,0.55)] ring-1 ring-[#f4dfce] sm:flex">
                                <img
                                    src="/images/bizlami_icon.png"
                                    alt="BizLami icon"
                                    class="h-full w-full object-contain"
                                >
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <main class="relative pb-16">
                <slot />
            </main>
        </div>
    </div>
</template>