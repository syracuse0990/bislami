<script setup>
import TextField from '@/Components/Forms/Fields/TextField.vue';
import PrimaryButton from '@/Components/UI/Buttons/PrimaryButton.vue';
import AuthSplitLayout from '@/Layouts/AuthSplitLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    email: {
        type: String,
        required: true,
    },
    token: {
        type: String,
        required: true,
    },
});

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

const resetHighlights = [
    'Set a new password and return to your carts, orders, and saved details.',
    'Keep merchant menu workflows and delivery coordination protected.',
    'Use one secure account across the entire BizLami experience.',
];

const submit = () => {
    form.post(route('password.store'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <AuthSplitLayout
        hero-title="Set a new password and get back to a smoother delivery workflow."
        hero-description="Secure your account again and return to restaurant discovery, ordering, and operations without extra friction."
        :highlights="resetHighlights"
        panel-title="Reset your password"
        panel-description="Choose a new password for your BizLami account and sign back in with confidence."
        mobile-description="Choose a new BizLami password and return to ordering, tracking, and menu management."
        content-width-class="max-w-[520px] xl:max-w-[560px]"
        :panel-chrome="false"
    >
        <Head title="Reset Password" />

        <form class="space-y-4" @submit.prevent="submit">
            <TextField
                id="email"
                v-model="form.email"
                label="Email address"
                type="email"
                :message="form.errors.email"
                label-class="text-sm font-semibold text-slate-700"
                input-class="mt-2 w-full rounded-[18px] border border-[#dde5ed] bg-[#f7fafc] px-4 py-4 text-sm text-slate-900 shadow-none placeholder:text-slate-400 focus:border-[var(--brand-teal)] focus:bg-white focus:ring-[rgba(11,77,89,0.12)]"
                error-class="mt-2"
                required
                autofocus
                autocomplete="username"
                placeholder="you@example.com"
            />

            <TextField
                id="password"
                v-model="form.password"
                label="New password"
                type="password"
                :message="form.errors.password"
                label-class="text-sm font-semibold text-slate-700"
                input-class="mt-2 w-full rounded-[18px] border border-[#dde5ed] bg-[#f7fafc] px-4 py-4 text-sm text-slate-900 shadow-none placeholder:text-slate-400 focus:border-[var(--brand-teal)] focus:bg-white focus:ring-[rgba(11,77,89,0.12)]"
                error-class="mt-2"
                required
                autocomplete="new-password"
                placeholder="Create a secure password"
            />

            <TextField
                id="password_confirmation"
                v-model="form.password_confirmation"
                label="Confirm password"
                type="password"
                :message="form.errors.password_confirmation"
                label-class="text-sm font-semibold text-slate-700"
                input-class="mt-2 w-full rounded-[18px] border border-[#dde5ed] bg-[#f7fafc] px-4 py-4 text-sm text-slate-900 shadow-none placeholder:text-slate-400 focus:border-[var(--brand-teal)] focus:bg-white focus:ring-[rgba(11,77,89,0.12)]"
                error-class="mt-2"
                required
                autocomplete="new-password"
                placeholder="Repeat your new password"
            />

            <div class="flex flex-col gap-3 pt-1 sm:flex-row sm:items-center sm:justify-between">
                <Link
                    :href="route('login')"
                    class="text-sm font-semibold text-[var(--brand-teal)] transition hover:text-[var(--brand-orange-deep)]"
                >
                    Back to sign in
                </Link>

                <PrimaryButton
                    class="w-full sm:w-auto"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    Reset password
                </PrimaryButton>
            </div>
        </form>
    </AuthSplitLayout>
</template>
