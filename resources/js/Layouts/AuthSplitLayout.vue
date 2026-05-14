<script setup>
import ApplicationLogo from '@/Components/Branding/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';

defineProps({
    heroTitle: {
        type: String,
        required: true,
    },
    heroDescription: {
        type: String,
        required: true,
    },
    highlights: {
        type: Array,
        default: () => [],
    },
    panelTitle: {
        type: String,
        required: true,
    },
    panelDescription: {
        type: String,
        default: '',
    },
    badgeLabel: {
        type: String,
        default: 'Food delivery workspace',
    },
    mobileDescription: {
        type: String,
        default: '',
    },
    contentWidthClass: {
        type: String,
        default: 'max-w-[430px]',
    },
    panelChrome: {
        type: Boolean,
        default: true,
    },
});

const authBackdropUrl = 'https://images.unsplash.com/photo-1497366754035-f200968a6e72?auto=format&fit=crop&w=1600&q=80';
const audienceBadges = ['Customers', 'Merchant teams', 'Operations'];
</script>

<template>
    <div class="min-h-[100dvh] bg-[linear-gradient(180deg,#f4f7fb_0%,#ffffff_100%)] text-slate-900 lg:h-[100dvh] lg:overflow-hidden">
        <div class="grid min-h-[100dvh] lg:h-full lg:grid-cols-[1.08fr_0.92fr]">
            <section class="relative hidden overflow-hidden bg-[#0b1e29] text-white lg:flex">
                <img
                    :src="authBackdropUrl"
                    alt="BizLami workspace background"
                    class="absolute inset-0 h-full w-full object-cover"
                >
                <div class="absolute inset-0 bg-[linear-gradient(90deg,rgba(4,20,31,0.88)_0%,rgba(4,20,31,0.72)_38%,rgba(4,20,31,0.52)_100%)]"></div>
                <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,rgba(14,182,159,0.18),transparent_28%),radial-gradient(circle_at_bottom_left,rgba(228,138,36,0.16),transparent_30%)]"></div>
                <div class="absolute inset-x-0 bottom-0 h-40 bg-[linear-gradient(180deg,transparent,rgba(4,20,31,0.5))]"></div>

                <div class="relative flex h-full flex-col justify-between p-8 xl:p-10">
                    <div class="space-y-8">
                        <Link href="/" class="inline-flex items-center gap-4">
                            <div class="flex h-14 w-14 items-center justify-center overflow-hidden rounded-full border border-white/15 bg-white ring-1 ring-white/20 shadow-[0_20px_40px_-28px_rgba(0,0,0,0.45)]">
                                <img src="/images/bizlami_icon.png" alt="BizLami icon" class="h-full w-full scale-[1.2] object-cover">
                            </div>

                            <div class="flex h-14 w-[220px] items-center overflow-hidden rounded-[22px] border border-white/12 bg-white px-4 py-2 shadow-[0_20px_45px_-28px_rgba(0,0,0,0.35)]">
                                <ApplicationLogo fit="cover" class="h-full w-full scale-[1.08]" />
                            </div>
                        </Link>

                        <div class="max-w-xl space-y-5 pt-4">
                            <p class="inline-flex rounded-full border border-white/15 bg-white/10 px-4 py-2 text-xs font-semibold uppercase tracking-[0.2em] text-white/85 backdrop-blur">
                                {{ badgeLabel }}
                            </p>

                            <div class="space-y-4">
                                <h1 class="max-w-2xl text-[3rem] font-semibold leading-[0.98] text-white xl:text-[3.2rem]">
                                    {{ heroTitle }}
                                </h1>
                                <p class="max-w-lg text-base leading-7 text-white/80 xl:text-lg xl:leading-8">
                                    {{ heroDescription }}
                                </p>
                            </div>

                            <ul v-if="highlights.length" class="grid max-w-lg gap-3 pt-1 text-sm text-white/82">
                                <li
                                    v-for="item in highlights"
                                    :key="item"
                                    class="flex items-start gap-3 rounded-[20px] border border-white/10 bg-[rgba(8,20,32,0.26)] px-4 py-2.5 backdrop-blur-md"
                                >
                                    <span class="mt-1 inline-flex h-2.5 w-2.5 shrink-0 rounded-full bg-[var(--brand-orange)]"></span>
                                    <span class="leading-6">{{ item }}</span>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <div class="space-y-4 pt-8">
                        <div class="flex flex-wrap gap-3">
                            <span
                                v-for="label in audienceBadges"
                                :key="label"
                                class="inline-flex items-center rounded-full border border-white/12 bg-[rgba(10,24,36,0.34)] px-4 py-2 text-xs font-semibold uppercase tracking-[0.18em] text-white/70 backdrop-blur"
                            >
                                {{ label }}
                            </span>
                        </div>

                        <p class="text-xs text-white/42">
                            BizLami workspace access for customer, merchant, courier, and admin flows.
                        </p>
                    </div>
                </div>
            </section>

            <section class="relative flex min-h-[100dvh] items-center justify-center overflow-hidden bg-white px-5 py-8 sm:px-8 lg:h-full lg:min-h-0 lg:px-12 xl:px-16">
                <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(11,77,89,0.06),transparent_28%),radial-gradient(circle_at_bottom_left,rgba(237,144,45,0.08),transparent_24%)]"></div>

                <div
                    :class="[
                        'relative w-full',
                        contentWidthClass,
                        panelChrome
                            ? 'rounded-[30px] border border-white/80 bg-[var(--surface-strong)] p-7 shadow-[0_36px_80px_-56px_rgba(11,77,89,0.6)] backdrop-blur sm:p-8'
                            : 'border-0 bg-transparent p-0 shadow-none'
                    ]"
                >
                    <div class="mb-6 space-y-4 lg:hidden">
                        <Link href="/" class="inline-flex items-center gap-3">
                            <div class="flex h-12 w-12 items-center justify-center overflow-hidden rounded-full border border-[rgba(11,77,89,0.12)] bg-white ring-1 ring-white/80 shadow-[0_18px_36px_-28px_rgba(11,77,89,0.28)]">
                                <img src="/images/bizlami_icon.png" alt="BizLami icon" class="h-full w-full scale-[1.22] object-cover">
                            </div>

                            <div class="flex h-12 w-[188px] items-center overflow-hidden rounded-[20px] border border-[rgba(11,77,89,0.08)] bg-white px-4 py-2 ring-1 ring-white/80 shadow-[0_18px_36px_-28px_rgba(11,77,89,0.2)]">
                                <ApplicationLogo fit="cover" class="h-full w-full scale-[1.1]" />
                            </div>
                        </Link>

                        <p v-if="mobileDescription || panelDescription" class="text-sm leading-6 text-slate-500">
                            {{ mobileDescription || panelDescription }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <h1 class="text-4xl font-semibold tracking-tight text-slate-950">{{ panelTitle }}</h1>
                        <p v-if="panelDescription" class="text-base text-slate-500">{{ panelDescription }}</p>
                    </div>

                    <div class="mt-6 space-y-6">
                        <slot />
                    </div>
                </div>
            </section>
        </div>
    </div>
</template>