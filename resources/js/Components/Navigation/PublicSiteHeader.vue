<script setup>
import ApplicationLogo from '@/Components/Branding/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

const props = defineProps({
    links: {
        type: Array,
        default: () => [],
    },
    showCart: {
        type: Boolean,
        default: false,
    },
    cartHref: {
        type: String,
        default: '',
    },
    cartItemsCount: {
        type: Number,
        default: 0,
    },
    loginHref: {
        type: String,
        default: '',
    },
    registerHref: {
        type: String,
        default: '',
    },
    helperText: {
        type: String,
        default: 'Search food first. Checkout only when you are ready.',
    },
});

const isScrolled = ref(false);
const visibleLinks = computed(() => props.links.filter((link) => link?.label && link?.href));

function updateScrollState() {
    if (typeof window === 'undefined') {
        return;
    }

    isScrolled.value = window.scrollY > 18;
}

function isAnchorLink(href) {
    return typeof href === 'string' && href.startsWith('#');
}

onMounted(() => {
    updateScrollState();

    if (typeof window !== 'undefined') {
        window.addEventListener('scroll', updateScrollState, { passive: true });
    }
});

onBeforeUnmount(() => {
    if (typeof window !== 'undefined') {
        window.removeEventListener('scroll', updateScrollState);
    }
});
</script>

<template>
    <div class="sticky top-4 z-50 pb-5">
        <header
            :class="[
                'relative overflow-hidden rounded-[30px] border px-4 py-4 backdrop-blur-xl transition-all duration-500 sm:px-6',
                isScrolled
                    ? 'border-white/85 bg-[rgba(255,255,255,0.84)] shadow-[0_30px_90px_-54px_rgba(15,23,42,0.38)]'
                    : 'border-white/70 bg-[rgba(255,255,255,0.68)] shadow-[0_22px_70px_-52px_rgba(15,23,42,0.26)]',
            ]"
        >
            <div class="absolute inset-x-10 top-0 h-px bg-[linear-gradient(90deg,transparent,rgba(11,77,89,0.32),transparent)]"></div>

            <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                <Link :href="route('home')" class="flex min-w-0 items-center gap-3">
                    <div class="flex h-[4.5rem] w-[4.5rem] items-center justify-center overflow-hidden rounded-full border border-[rgba(11,77,89,0.12)] bg-[linear-gradient(145deg,rgba(255,255,255,0.98),rgba(241,250,251,0.94))] ring-1 ring-white/80 shadow-[0_22px_56px_-34px_rgba(11,77,89,0.28)] transition duration-300">
                        <img
                            src="/images/bizlami_icon.png"
                            alt="BizLami icon"
                            class="h-full w-full max-w-none scale-[1.34] object-cover mix-blend-multiply"
                        >
                    </div>

                    <div class="min-w-0 space-y-1">
                        <div class="hidden h-16 w-[252px] items-center overflow-hidden rounded-[28px] border border-[rgba(11,77,89,0.08)] bg-[linear-gradient(135deg,rgba(241,250,251,0.98),rgba(255,247,239,0.96))] ring-1 ring-white/80 shadow-[0_22px_56px_-38px_rgba(11,77,89,0.24)] sm:flex">
                            <ApplicationLogo fit="cover" class="h-full w-full max-w-none scale-[1.14] mix-blend-multiply" />
                        </div>
                        <p class="hidden text-xs font-medium text-slate-500 lg:block">
                            {{ helperText }}
                        </p>
                    </div>
                </Link>

                <div class="flex flex-col gap-3 xl:min-w-0 xl:flex-1 xl:items-end">
                    <nav v-if="visibleLinks.length" class="flex flex-wrap items-center gap-2">
                        <template v-for="item in visibleLinks" :key="`${item.label}-${item.href}`">
                            <a
                                v-if="isAnchorLink(item.href)"
                                :href="item.href"
                                class="inline-flex items-center rounded-full border border-white/80 bg-white/75 px-4 py-2 text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 transition duration-300 hover:-translate-y-0.5 hover:border-[#d6e7e9] hover:bg-white hover:text-[var(--brand-teal)]"
                            >
                                {{ item.label }}
                            </a>

                            <Link
                                v-else
                                :href="item.href"
                                class="inline-flex items-center rounded-full border border-white/80 bg-white/75 px-4 py-2 text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 transition duration-300 hover:-translate-y-0.5 hover:border-[#d6e7e9] hover:bg-white hover:text-[var(--brand-teal)]"
                            >
                                {{ item.label }}
                            </Link>
                        </template>
                    </nav>

                    <div class="flex flex-wrap items-center gap-2 sm:gap-3">
                        <Link
                            v-if="showCart && cartHref"
                            :href="cartHref"
                            class="inline-flex items-center gap-2 rounded-full border border-white/85 bg-white px-5 py-3 text-sm font-semibold text-slate-700 shadow-[0_18px_42px_-30px_rgba(11,77,89,0.24)] transition duration-300 hover:-translate-y-0.5 hover:text-[var(--brand-teal)]"
                        >
                            Cart

                            <span class="inline-flex min-w-6 items-center justify-center rounded-full bg-[var(--brand-teal)] px-2 py-0.5 text-xs font-semibold text-white">
                                {{ cartItemsCount }}
                            </span>
                        </Link>

                        <Link
                            v-if="loginHref"
                            :href="loginHref"
                            class="inline-flex items-center rounded-full border border-white/80 bg-white/88 px-5 py-3 text-sm font-semibold text-slate-700 transition duration-300 hover:-translate-y-0.5 hover:text-[var(--brand-teal)]"
                        >
                            Log in
                        </Link>

                        <Link
                            v-if="registerHref"
                            :href="registerHref"
                            class="inline-flex items-center rounded-full bg-gradient-to-r from-[var(--brand-teal)] via-[var(--brand-teal-deep)] to-[var(--brand-orange)] px-5 py-3 text-sm font-semibold text-white shadow-[0_24px_50px_-24px_rgba(11,77,89,0.72)] transition duration-300 hover:-translate-y-0.5 hover:shadow-[0_28px_60px_-24px_rgba(11,77,89,0.82)]"
                        >
                            Create account
                        </Link>
                    </div>
                </div>
            </div>
        </header>
    </div>
</template>