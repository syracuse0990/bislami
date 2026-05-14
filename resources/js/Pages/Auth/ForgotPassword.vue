<script setup>
import TextField from '@/Components/Forms/Fields/TextField.vue';
import PrimaryButton from '@/Components/UI/Buttons/PrimaryButton.vue';
import AuthSplitLayout from '@/Layouts/AuthSplitLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
});

const recoveryHighlights = [
    'Recover account access without losing your saved delivery flow.',
    'Get back to current carts, orders, and merchant updates faster.',
    'Use the same account across customer, merchant, and operations journeys.',
];

const submit = () => {
    form.post(route('password.email'));
};
</script>

<template>
    <AuthSplitLayout
        hero-title="Recover access quickly and pick up where the order flow left off."
        hero-description="Reset access without losing momentum across browsing, checkout, merchant updates, and delivery operations."
        :highlights="recoveryHighlights"
        panel-title="Forgot your password?"
        panel-description="Enter your email and we will send a secure reset link so you can choose a new password."
        mobile-description="Recover your BizLami account and get back to orders, carts, and menu workflows faster."
        content-width-class="max-w-[520px] xl:max-w-[560px]"
        :panel-chrome="false"
    >
        <Head title="Forgot Password" />

        <div v-if="status" class="rounded-[18px] border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
            {{ status }}
        </div>

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
                    Send reset link
                </PrimaryButton>
            </div>
        </form>
    </AuthSplitLayout>
</template>
