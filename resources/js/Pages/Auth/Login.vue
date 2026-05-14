<script setup>
import GoogleAuthButton from '@/Components/Auth/GoogleAuthButton.vue';
import ApplicationLogo from '@/Components/Branding/ApplicationLogo.vue';
import InputError from '@/Components/Forms/InputError.vue';
import TextInput from '@/Components/Forms/Fields/TextInput.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const workspaceHighlights = [
    'Guest-first ordering and smoother checkout recovery',
    'Live merchant menu updates with reusable form flows',
    'Courier and operations tracking in one workspace',
    'Clearer discovery, carts, and order progress for every team',
];

const trustNotes = [
    {
        title: 'Merchant teams',
        quote: 'Menu updates, availability changes, and pricing reviews stay faster when everything lives in one polished workflow.',
    },
    {
        title: 'Operations',
        quote: 'Dispatch visibility and delivery follow-through are easier to manage when the whole order journey is in one place.',
    },
    {
        title: 'Customers',
        quote: 'Discovery, cart recovery, and checkout handoff feel consistent from the homepage all the way to order tracking.',
    },
];

const loginBackdropUrl = 'https://images.unsplash.com/photo-1497366754035-f200968a6e72?auto=format&fit=crop&w=1600&q=80';
const page = usePage();
const googleAuthEnabled = computed(() => Boolean(page.props.services?.socialAuth?.google?.enabled));
const googleAuthUrl = route('auth.social.redirect', { provider: 'google' });

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="Log in" />

    <div class="min-h-[100dvh] bg-[linear-gradient(180deg,#f4f7fb_0%,#ffffff_100%)] text-slate-900 lg:h-[100dvh] lg:overflow-hidden">
        <div class="grid min-h-[100dvh] lg:h-full lg:grid-cols-[1.08fr_0.92fr]">
            <section class="relative hidden overflow-hidden bg-[#0b1e29] text-white lg:flex">
                <img
                    :src="loginBackdropUrl"
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
                                Food delivery workspace
                            </p>

                            <div class="space-y-4">
                                <h1 class="max-w-2xl text-[3rem] font-semibold leading-[0.98] text-white xl:text-[3.2rem]">
                                    Manage orders smarter, ship faster, and keep every team aligned.
                                </h1>
                                <p class="max-w-lg text-base leading-7 text-white/80 xl:text-lg xl:leading-8">
                                    BizLami brings customer discovery, merchant updates, and delivery coordination into one focused workspace built for daily operations.
                                </p>
                            </div>

                            <ul class="grid max-w-lg gap-3 pt-1 text-sm text-white/82">
                                <li v-for="item in workspaceHighlights" :key="item" class="flex items-start gap-3 rounded-[20px] border border-white/10 bg-[rgba(8,20,32,0.26)] px-4 py-2.5 backdrop-blur-md">
                                    <span class="mt-1 inline-flex h-2.5 w-2.5 shrink-0 rounded-full bg-[var(--brand-orange)]"></span>
                                    <span class="leading-6">{{ item }}</span>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <div class="space-y-4 pt-8">
                        <div class="grid gap-3 xl:grid-cols-3">
                            <article
                                v-for="note in trustNotes"
                                :key="note.title"
                                class="rounded-[24px] border border-white/10 bg-[linear-gradient(180deg,rgba(10,24,36,0.34),rgba(10,24,36,0.18))] p-4 backdrop-blur-md"
                            >
                                <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-white/55">{{ note.title }}</p>
                                <p class="mt-2 text-sm leading-6 text-white/80">{{ note.quote }}</p>
                            </article>
                        </div>

                        <p class="text-xs text-white/42">
                            BizLami workspace access for customer, merchant, courier, and admin flows.
                        </p>
                    </div>
                </div>
            </section>

            <section class="relative flex min-h-[100dvh] items-center justify-center overflow-hidden bg-white px-5 py-8 sm:px-8 lg:h-full lg:min-h-0 lg:px-12 xl:px-16">
                <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(11,77,89,0.06),transparent_28%),radial-gradient(circle_at_bottom_left,rgba(237,144,45,0.08),transparent_24%)]"></div>

                <div class="relative w-full max-w-[430px] space-y-6">
                    <div class="space-y-4 lg:hidden">
                        <Link href="/" class="inline-flex items-center gap-3">
                            <div class="flex h-12 w-12 items-center justify-center overflow-hidden rounded-full border border-[rgba(11,77,89,0.12)] bg-white ring-1 ring-white/80 shadow-[0_18px_36px_-28px_rgba(11,77,89,0.28)]">
                                <img src="/images/bizlami_icon.png" alt="BizLami icon" class="h-full w-full scale-[1.22] object-cover">
                            </div>

                            <div class="flex h-12 w-[188px] items-center overflow-hidden rounded-[20px] border border-[rgba(11,77,89,0.08)] bg-white px-4 py-2 ring-1 ring-white/80 shadow-[0_18px_36px_-28px_rgba(11,77,89,0.2)]">
                                <ApplicationLogo fit="cover" class="h-full w-full scale-[1.1]" />
                            </div>
                        </Link>

                        <p class="text-sm leading-6 text-slate-500">
                            Sign in to keep orders, menus, and delivery activity moving in one place.
                        </p>
                    </div>

                    <div class="space-y-2">
                        <h1 class="text-4xl font-semibold tracking-tight text-slate-950">Welcome back</h1>
                        <p class="text-base text-slate-500">Sign in to your BizLami workspace</p>
                    </div>

                    <div v-if="status" class="rounded-[18px] border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
                        {{ status }}
                    </div>

                    <div v-if="googleAuthEnabled" class="space-y-4">
                        <GoogleAuthButton :href="googleAuthUrl" label="Continue with Google" />

                        <div class="relative text-center text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-400">
                            <div class="absolute inset-x-0 top-1/2 h-px -translate-y-1/2 bg-[#e4ebf2]"></div>
                            <span class="relative bg-white px-4">Or continue with email</span>
                        </div>
                    </div>

                    <form class="space-y-4" @submit.prevent="submit">
                        <div class="space-y-2">
                            <label for="email" class="text-sm font-semibold text-slate-700">Email address</label>
                            <div class="relative">
                                <span class="pointer-events-none absolute inset-y-0 left-4 flex items-center text-slate-400">
                                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M3.33337 5.83331L9.28951 9.71556C9.72904 10.002 10.2704 10.002 10.71 9.71556L16.6667 5.83331" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" />
                                        <rect x="3.33337" y="4.16669" width="13.3333" height="11.6667" rx="2.5" stroke="currentColor" stroke-width="1.6" />
                                    </svg>
                                </span>

                                <TextInput
                                    id="email"
                                    v-model="form.email"
                                    type="email"
                                    class="w-full rounded-[18px] border border-[#dde5ed] bg-[#f7fafc] py-4 pl-12 pr-4 text-sm text-slate-900 shadow-none placeholder:text-slate-400 focus:border-[var(--brand-teal)] focus:bg-white focus:ring-[rgba(11,77,89,0.12)]"
                                    required
                                    autofocus
                                    autocomplete="username"
                                    placeholder="you@example.com"
                                />
                            </div>
                            <InputError :message="form.errors.email" />
                        </div>

                        <div class="space-y-2">
                            <div class="flex items-center justify-between gap-4">
                                <label for="password" class="text-sm font-semibold text-slate-700">Password</label>
                                <Link
                                    v-if="canResetPassword"
                                    :href="route('password.request')"
                                    class="text-sm font-semibold text-[var(--brand-teal)] transition hover:text-[var(--brand-orange-deep)]"
                                >
                                    Forgot password?
                                </Link>
                            </div>

                            <div class="relative">
                                <span class="pointer-events-none absolute inset-y-0 left-4 flex items-center text-slate-400">
                                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M6.66663 8.33335V6.66669C6.66663 4.82574 8.15899 3.33335 9.99996 3.33335C11.8409 3.33335 13.3333 4.82574 13.3333 6.66669V8.33335" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
                                        <rect x="4.16663" y="8.33335" width="11.6667" height="8.33333" rx="2.5" stroke="currentColor" stroke-width="1.6" />
                                    </svg>
                                </span>

                                <TextInput
                                    id="password"
                                    v-model="form.password"
                                    type="password"
                                    class="w-full rounded-[18px] border border-[#dde5ed] bg-[#f7fafc] py-4 pl-12 pr-12 text-sm text-slate-900 shadow-none placeholder:text-slate-400 focus:border-[var(--brand-teal)] focus:bg-white focus:ring-[rgba(11,77,89,0.12)]"
                                    required
                                    autocomplete="current-password"
                                    placeholder="Enter your password"
                                />

                                <span class="pointer-events-none absolute inset-y-0 right-4 flex items-center text-slate-300">
                                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M2.5 10C3.86667 7.20833 6.63333 5.41667 10 5.41667C13.3667 5.41667 16.1333 7.20833 17.5 10C16.1333 12.7917 13.3667 14.5833 10 14.5833C6.63333 14.5833 3.86667 12.7917 2.5 10Z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" />
                                        <circle cx="10" cy="10" r="2.08333" stroke="currentColor" stroke-width="1.6" />
                                    </svg>
                                </span>
                            </div>
                            <InputError :message="form.errors.password" />
                        </div>

                        <label class="flex items-center gap-3 text-sm font-medium text-slate-500">
                            <input v-model="form.remember" type="checkbox" class="peer sr-only">
                            <span class="relative h-6 w-10 rounded-full bg-[#d8e3eb] transition duration-200 peer-checked:bg-[var(--brand-teal)]">
                                <span class="absolute left-1 top-1 h-4 w-4 rounded-full bg-white shadow-sm transition duration-200 peer-checked:translate-x-4"></span>
                            </span>
                            <span>Remember me for 30 days</span>
                        </label>

                        <button
                            type="submit"
                            class="inline-flex w-full items-center justify-center gap-2 rounded-[18px] bg-gradient-to-r from-[var(--brand-teal)] via-[var(--brand-teal-deep)] to-[var(--brand-orange)] px-5 py-4 text-base font-semibold text-white shadow-[0_24px_55px_-28px_rgba(11,77,89,0.72)] transition duration-200 hover:-translate-y-0.5 hover:shadow-[0_28px_62px_-28px_rgba(11,77,89,0.8)] disabled:cursor-not-allowed disabled:opacity-70"
                            :disabled="form.processing"
                        >
                            <span>{{ form.processing ? 'Signing in...' : 'Sign in' }}</span>
                            <span aria-hidden="true">-></span>
                        </button>
                    </form>

                    <div class="space-y-3 pt-1">
                        <div class="relative text-center text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-400">
                            <div class="absolute inset-x-0 top-1/2 h-px -translate-y-1/2 bg-[#e4ebf2]"></div>
                            <span class="relative bg-white px-4">New to BizLami?</span>
                        </div>

                        <Link
                            :href="route('register')"
                            class="inline-flex w-full items-center justify-center rounded-[18px] border border-[#dbe4ed] bg-[#f7fafc] px-5 py-4 text-sm font-semibold text-slate-700 transition duration-200 hover:border-[var(--brand-teal)] hover:bg-white hover:text-[var(--brand-teal)]"
                        >
                            Create a free account
                        </Link>
                    </div>
                </div>
            </section>
        </div>
    </div>
</template>
