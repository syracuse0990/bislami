<script setup>
import AuthSplitLayout from '@/Layouts/AuthSplitLayout.vue';
import PrimaryButton from '@/Components/UI/Buttons/PrimaryButton.vue';
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    status: {
        type: String,
    },
});

const form = useForm({});

const submit = () => {
    form.post(route('verification.send'));
};

const verificationLinkSent = computed(
    () => props.status === 'verification-link-sent',
);

const verificationHighlights = [
    'Confirm your email before protected account actions continue.',
    'Keep customer orders, merchant tools, and operations access secure.',
    'Use the same verified identity across every BizLami workspace.',
];
</script>

<template>
    <AuthSplitLayout
        hero-title="Verify your email and keep your BizLami account fully unlocked."
        hero-description="Email verification keeps ordering, merchant access, and account recovery tied to the right person before sensitive actions continue."
        :highlights="verificationHighlights"
        panel-title="Verify your email"
        panel-description="Check your inbox for the verification link, or resend one if you need a fresh email."
        mobile-description="Verify your email before continuing into protected BizLami account actions."
        content-width-class="max-w-[520px] xl:max-w-[560px]"
        :panel-chrome="false"
    >
        <Head title="Email Verification" />

        <div class="rounded-[20px] border border-[#e7edf3] bg-white/80 px-4 py-3 text-sm leading-7 text-slate-500">
            Thanks for signing up. Before getting started, verify your email using the link we just sent. If it has not arrived yet, you can request another one below.
        </div>

        <div
            v-if="verificationLinkSent"
            class="rounded-[18px] border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700"
        >
            A new verification link has been sent to the email address you provided during registration.
        </div>

        <form class="space-y-4" @submit.prevent="submit">
            <div class="flex flex-col gap-3 pt-1 sm:flex-row sm:items-center sm:justify-between">
                <PrimaryButton
                    class="w-full sm:w-auto"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    Resend verification email
                </PrimaryButton>

                <Link
                    :href="route('logout')"
                    method="post"
                    as="button"
                    class="text-sm font-semibold text-[var(--brand-teal)] transition hover:text-[var(--brand-orange-deep)]"
                >
                    Log out
                </Link>
                >
            </div>
        </form>
    </AuthSplitLayout>
</template>
