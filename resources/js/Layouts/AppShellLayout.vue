<script setup>
import ApplicationLogo from '@/Components/Branding/ApplicationLogo.vue';
import ConfirmDialog from '@/Components/UI/ConfirmDialog.vue';
import Dropdown from '@/Components/Navigation/Dropdown.vue';
import NavLink from '@/Components/Navigation/NavLink.vue';
import ResponsiveNavLink from '@/Components/Navigation/ResponsiveNavLink.vue';
import { Link, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
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
    navigationOrientation: {
        type: String,
        default: 'horizontal',
    },
});

const showingNavigationDropdown = ref(false);
const isVerticalNavigation = computed(() => props.navigationOrientation === 'vertical');
const sidebarNavigation = computed(() => [props.home, ...props.navigation]);

const expandedGroups = ref([]);
const toggleGroup = (name) => {
    if (expandedGroups.value.includes(name)) {
        expandedGroups.value = expandedGroups.value.filter((n) => n !== name);
    } else {
        expandedGroups.value = [...expandedGroups.value, name];
    }
};
const isGroupExpanded = (name) => expandedGroups.value.includes(name);

const navIcons = {
    overview: ['M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
    profile: ['M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
    menu: ['M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01'],
    orders: ['M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z'],
    staff: ['M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2', 'M9 7a4 4 0 100 8 4 4 0 000-8z', 'M23 21v-2a4 4 0 00-3-3.87', 'M16 3.13a4 4 0 010 7.75'],
    cashier: ['M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z'],
    maintenance: [
        'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z',
        'M15 12a3 3 0 11-6 0 3 3 0 016 0z',
    ],
};

const page = usePage();
const currentUser = computed(() => page.props.auth.user ?? {});
const restaurantLogoUrl = computed(() => page.props.auth?.restaurantLogoUrl ?? null);
const userInitials = computed(() => {
    const name = String(currentUser.value?.name ?? '').trim();

    if (name === '') {
        return 'U';
    }

    return name
        .split(/\s+/)
        .slice(0, 2)
        .map((part) => part.charAt(0).toUpperCase())
        .join('');
});
</script>

<template>
    <div class="relative min-h-screen">
        <div class="pointer-events-none absolute inset-0 overflow-hidden">
            <div class="absolute left-[-5rem] top-8 h-72 w-72 rounded-full bg-[var(--brand-teal)]/8 blur-3xl"></div>
            <div class="absolute right-[-4rem] top-20 h-64 w-64 rounded-full bg-[var(--brand-orange)]/10 blur-3xl"></div>
        </div>

        <div class="relative min-h-screen">
            <nav class="sticky top-0 z-40 border-b border-white/70 bg-[rgba(252,247,239,0.78)] backdrop-blur-xl">
                <div :class="isVerticalNavigation ? 'px-4 sm:px-6 lg:px-8' : 'mx-auto max-w-7xl px-4 sm:px-6 lg:px-8'">
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

                            <div
                                v-if="!isVerticalNavigation"
                                class="hidden items-center gap-2 rounded-full border border-white/80 bg-white/70 p-1 shadow-[0_18px_44px_-30px_rgba(11,77,89,0.4)] xl:flex"
                            >
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
                            <button
                                type="button"
                                class="relative inline-flex h-11 w-11 items-center justify-center rounded-full border border-white/80 bg-white/90 text-slate-700 shadow-[0_18px_40px_-30px_rgba(11,77,89,0.45)] transition duration-150 ease-in-out hover:text-[var(--brand-teal)] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#ffd6b6]"
                                aria-label="Notifications coming soon"
                                title="Notifications coming soon"
                            >
                                <svg
                                    class="h-5 w-5"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    xmlns="http://www.w3.org/2000/svg"
                                    aria-hidden="true"
                                >
                                    <path d="M14.857 17H5.143A1.143 1.143 0 0 1 4 15.857c0-.303.12-.594.335-.808L5.5 13.885V10a4.5 4.5 0 1 1 9 0v3.885l1.165 1.164c.214.214.335.505.335.808A1.143 1.143 0 0 1 14.857 17Z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M8.5 19a1.5 1.5 0 0 0 3 0" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                <span class="absolute right-2 top-2 h-2 w-2 rounded-full bg-[var(--brand-orange)] ring-2 ring-white"></span>
                            </button>

                            <Dropdown align="right" width="64">
                                <template #trigger>
                                    <span class="inline-flex rounded-full">
                                        <button
                                            type="button"
                                            class="inline-flex items-center gap-3 rounded-full border border-white/80 bg-white/90 px-2 py-2 text-sm font-semibold text-slate-700 shadow-[0_18px_40px_-30px_rgba(11,77,89,0.45)] transition duration-150 ease-in-out hover:text-[var(--brand-teal)] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#ffd6b6]"
                                        >
                                            <span class="flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-br from-[var(--brand-teal)] to-[var(--brand-orange)] text-sm font-semibold text-white shadow-[0_14px_36px_-22px_rgba(11,77,89,0.7)]">
                                                {{ userInitials }}
                                            </span>

                                            <span class="hidden text-left lg:block">
                                                <span class="block text-sm font-semibold text-slate-900">{{ currentUser.name }}</span>
                                                <span class="block max-w-[10rem] truncate text-[11px] font-medium text-slate-500">{{ currentUser.email }}</span>
                                            </span>

                                            <svg
                                                class="hidden h-4 w-4 lg:block"
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
                                    <div class="space-y-2 p-2">
                                        <div class="rounded-[22px] border border-[#e6efef] bg-[#fcfefe] px-4 py-4">
                                            <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">
                                                Signed in
                                            </p>
                                            <p class="mt-2 text-sm font-semibold text-slate-900">
                                                {{ currentUser.name }}
                                            </p>
                                            <p class="mt-1 break-all text-xs text-slate-500">
                                                {{ currentUser.email }}
                                            </p>
                                        </div>

                                        <Link
                                            v-if="settings"
                                            :href="route(settings.name)"
                                            class="inline-flex w-full items-center justify-center rounded-full border border-[#e0ecec] bg-white px-4 py-3 text-sm font-semibold text-slate-700 transition duration-200 hover:bg-[#fff8f1] hover:text-[var(--brand-teal)]"
                                        >
                                            {{ settings.label }}
                                        </Link>

                                        <Link
                                            :href="route('logout')"
                                            method="post"
                                            as="button"
                                            class="inline-flex w-full items-center justify-center rounded-full bg-gradient-to-r from-[var(--brand-teal)] to-[var(--brand-orange)] px-4 py-3 text-sm font-semibold text-white shadow-[0_20px_45px_-24px_rgba(11,77,89,0.85)] transition duration-200 hover:-translate-y-0.5"
                                        >
                                            Log Out
                                        </Link>
                                    </div>
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

            <div :class="isVerticalNavigation ? 'lg:grid lg:grid-cols-[260px_minmax(0,1fr)] lg:gap-8 lg:px-8' : ''">
                <aside v-if="isVerticalNavigation" class="hidden lg:block lg:py-8">
                    <div class="sticky top-[108px] overflow-hidden rounded-[32px] border border-white/75 bg-[rgba(252,247,239,0.82)] p-4 shadow-[0_36px_90px_-58px_rgba(11,77,89,0.5)] backdrop-blur-xl">
                        <div class="space-y-1 px-2 pb-4">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-[var(--brand-teal)]/70">
                                Workspace navigation
                            </p>
                            <p class="text-sm leading-6 text-slate-500">
                                Move between dashboard tools from one vertical menu.
                            </p>
                        </div>

                        <div class="space-y-1 border-t border-[#e7edf3] pt-4">
                            <template v-for="link in sidebarNavigation" :key="link.name">
                                <!-- Collapsible group -->
                                <div v-if="link.children?.length">
                                    <button
                                        type="button"
                                        @click="toggleGroup(link.name)"
                                        :class="[
                                            'inline-flex w-full items-center gap-3 rounded-[20px] px-4 py-3 text-sm font-semibold transition duration-200 ease-out focus:outline-none focus-visible:ring-2 focus-visible:ring-[#ffd6b6]',
                                            route().current(link.activePattern ?? link.name)
                                                ? 'text-[var(--brand-teal)]'
                                                : 'text-slate-600 hover:bg-white/85 hover:text-[var(--brand-teal)]',
                                        ]"
                                    >
                                        <svg v-if="link.icon && navIcons[link.icon]" viewBox="0 0 24 24" fill="none" class="h-5 w-5 shrink-0" aria-hidden="true">
                                            <path v-for="(d, i) in navIcons[link.icon]" :key="i" :d="d" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                        <span class="flex-1 text-left">{{ link.label }}</span>
                                        <svg
                                            :class="['h-4 w-4 shrink-0 transition-transform duration-200', (isGroupExpanded(link.name) || route().current(link.activePattern ?? link.name)) ? 'rotate-180' : '']"
                                            viewBox="0 0 20 20"
                                            fill="currentColor"
                                            aria-hidden="true"
                                        >
                                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                    <div v-if="isGroupExpanded(link.name) || route().current(link.activePattern ?? link.name)" class="ml-4 mt-1 space-y-1 border-l-2 border-[#e7edf3] pl-3">
                                        <NavLink
                                            v-for="child in link.children"
                                            :key="child.name"
                                            :href="route(child.name)"
                                            :active="route().current(child.activePattern ?? child.name)"
                                            class="w-full justify-start rounded-[16px] px-3 py-2"
                                        >
                                            {{ child.label }}
                                        </NavLink>
                                    </div>
                                </div>
                                <!-- Regular nav item -->
                                <NavLink
                                    v-else
                                    :href="route(link.name)"
                                    :active="route().current(link.activePattern ?? link.name)"
                                    class="w-full justify-start gap-3 rounded-[20px] px-4 py-3"
                                >
                                    <svg v-if="link.icon && navIcons[link.icon]" viewBox="0 0 24 24" fill="none" class="h-5 w-5 shrink-0" aria-hidden="true">
                                        <path v-for="(d, i) in navIcons[link.icon]" :key="i" :d="d" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                    {{ link.label }}
                                </NavLink>
                            </template>
                        </div>

                    </div>
                </aside>

                <div :class="isVerticalNavigation ? 'min-w-0' : ''">
                    <header v-if="$slots.header" class="relative">
                        <div :class="isVerticalNavigation ? 'px-4 pt-8 sm:px-6 lg:px-0' : 'mx-auto max-w-7xl px-4 pt-8 sm:px-6 lg:px-8'">
                            <div class="overflow-hidden rounded-[32px] border border-white/75 bg-[var(--surface)] p-6 shadow-[0_36px_90px_-58px_rgba(11,77,89,0.6)] backdrop-blur sm:p-8">
                                <div class="flex items-start justify-between gap-6">
                                    <div class="min-w-0 flex-1">
                                        <slot name="header" />
                                    </div>

                                    <div class="hidden h-20 w-20 shrink-0 overflow-hidden rounded-[28px] bg-[linear-gradient(145deg,#fffaf4,#ffffff)] shadow-[0_24px_60px_-40px_rgba(11,77,89,0.55)] ring-1 ring-[#f4dfce] sm:flex">
                                        <img
                                            :src="restaurantLogoUrl ?? '/images/bizlami_icon.png'"
                                            :alt="restaurantLogoUrl ? 'Restaurant logo' : 'BizLami icon'"
                                            class="h-full w-full object-cover"
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
        </div>

        <ConfirmDialog />
    </div>
</template>