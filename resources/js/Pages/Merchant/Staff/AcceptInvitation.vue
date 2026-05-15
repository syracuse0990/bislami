<script setup>
import ApplicationLogo from '@/Components/Branding/ApplicationLogo.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    invitation: { type: Object, required: true },
});

const form = useForm({});

function accept() {
    form.post(route('invitations.accept', props.invitation.token));
}
</script>

<template>
    <Head title="Accept Invitation" />

    <div class="relative flex min-h-screen items-center justify-center bg-[linear-gradient(145deg,#f9f7f4_0%,#f4fbfb_50%,#fff8f1_100%)] p-4">
        <!-- Ambient blobs -->
        <div class="pointer-events-none absolute inset-0 overflow-hidden">
            <div class="absolute left-[-6rem] top-[-4rem] h-80 w-80 rounded-full bg-[var(--brand-teal)]/10 blur-3xl" />
            <div class="absolute bottom-[-4rem] right-[-4rem] h-64 w-64 rounded-full bg-[var(--brand-orange)]/12 blur-3xl" />
        </div>

        <div class="relative w-full max-w-md">
            <!-- Logo -->
            <div class="mb-8 flex justify-center">
                <div class="flex h-16 w-[200px] items-center rounded-[24px] bg-white/92 px-4 py-3 shadow-[0_20px_48px_-34px_rgba(11,77,89,0.55)]">
                    <ApplicationLogo class="h-full w-full" />
                </div>
            </div>

            <!-- Card -->
            <div class="overflow-hidden rounded-[32px] border border-white/80 bg-[linear-gradient(145deg,#ffffff_0%,#fff8f1_58%,#f4fbfb_100%)] shadow-[0_40px_100px_-30px_rgba(11,77,89,0.45)]">
                <!-- Accent top bar -->
                <div class="h-1 w-full bg-gradient-to-r from-[var(--brand-teal)] to-[var(--brand-orange)]" />

                <div class="px-8 py-8">
                    <!-- Icon -->
                    <div class="mb-5 inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-[#f0f8f8] ring-1 ring-[#dceced]">
                        <svg class="h-7 w-7 text-[var(--brand-teal)]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2" />
                            <circle cx="9" cy="7" r="4" />
                            <path d="M23 21v-2a4 4 0 00-3-3.87" />
                            <path d="M16 3.13a4 4 0 010 7.75" />
                        </svg>
                    </div>

                    <p class="text-xs font-semibold uppercase tracking-[0.22em] text-[var(--brand-teal)]">You've been invited</p>
                    <h1 class="mt-2 text-2xl font-semibold text-slate-900">Join {{ invitation.restaurantName }}</h1>
                    <p class="mt-3 text-sm leading-6 text-slate-500">
                        <span class="font-medium text-slate-700">{{ invitation.invitedBy }}</span> has invited you to join
                        <span class="font-medium text-slate-700">{{ invitation.restaurantName }}</span> as a team member.
                    </p>

                    <!-- Details -->
                    <div class="mt-6 rounded-[20px] border border-[#e0ecec] bg-[#f8fbfb] p-4">
                        <dl class="space-y-3">
                            <div class="flex items-center justify-between">
                                <dt class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">Role</dt>
                                <dd class="rounded-full bg-[#f0f8f8] px-3 py-1 text-xs font-semibold uppercase tracking-[0.16em] text-[var(--brand-teal)] ring-1 ring-[#d4e8e9]">
                                    {{ invitation.roleLabel }}
                                </dd>
                            </div>
                            <div class="flex items-center justify-between">
                                <dt class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">Invited email</dt>
                                <dd class="text-sm text-slate-700">{{ invitation.invitedEmail }}</dd>
                            </div>
                            <div class="flex items-center justify-between">
                                <dt class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">Sent</dt>
                                <dd class="text-sm text-slate-500">{{ invitation.invitedAt }}</dd>
                            </div>
                        </dl>
                    </div>

                    <p class="mt-4 text-xs leading-5 text-slate-400">
                        By accepting, you confirm that your logged-in account belongs to the person who was invited. Your role and permissions can be changed by the restaurant owner at any time.
                    </p>

                    <!-- Actions -->
                    <div class="mt-8 flex flex-col gap-3">
                        <button
                            type="button"
                            class="inline-flex w-full items-center justify-center rounded-full bg-gradient-to-r from-[var(--brand-teal)] to-[var(--brand-orange)] px-6 py-4 text-sm font-semibold text-white shadow-[0_20px_45px_-24px_rgba(11,77,89,0.7)] transition duration-200 hover:-translate-y-0.5 disabled:opacity-60"
                            :disabled="form.processing"
                            @click="accept"
                        >
                            {{ form.processing ? 'Accepting…' : 'Accept invitation' }}
                        </button>
                        <a
                            :href="route('home')"
                            class="inline-flex w-full items-center justify-center rounded-full border border-[#d6e2e2] bg-white px-6 py-3 text-sm font-semibold text-slate-700 transition duration-200 hover:-translate-y-0.5"
                        >
                            Not now
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
