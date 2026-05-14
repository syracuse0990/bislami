<script setup>
import AuthSplitLayout from '@/Layouts/AuthSplitLayout.vue';
import TextField from '@/Components/Forms/Fields/TextField.vue';
import PrimaryButton from '@/Components/UI/Buttons/PrimaryButton.vue';
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({
    password: '',
});

const confirmationHighlights = [
    'Confirm your password before BizLami allows access to secure account actions.',
    'Protect customer orders, merchant controls, and sensitive profile settings.',
    'Use one final check before entering protected flows.',
];

const submit = () => {
    form.post(route('password.confirm'), {
        onFinish: () => form.reset(),
    });
};
</script>

<template>
    <AuthSplitLayout
        hero-title="Confirm your password before entering this protected BizLami area."
        hero-description="This extra confirmation step protects sensitive account actions and keeps customer and merchant operations secure."
        :highlights="confirmationHighlights"
        panel-title="Confirm your password"
        panel-description="Enter your current password to continue into this protected section."
        mobile-description="Confirm your password before continuing into this protected BizLami area."
        content-width-class="max-w-[520px] xl:max-w-[560px]"
        :panel-chrome="false"
    >
        <Head title="Confirm Password" />

        <div class="rounded-[20px] border border-[#e7edf3] bg-white/80 px-4 py-3 text-sm leading-7 text-slate-500">
            This is a secure area of the application. Please confirm your password before continuing.
        </div>

        <form class="space-y-4" @submit.prevent="submit">
            <TextField
                id="password"
                v-model="form.password"
                label="Password"
                type="password"
                :message="form.errors.password"
                label-class="text-sm font-semibold text-slate-700"
                input-class="mt-2 w-full rounded-[18px] border border-[#dde5ed] bg-[#f7fafc] px-4 py-4 text-sm text-slate-900 shadow-none placeholder:text-slate-400 focus:border-[var(--brand-teal)] focus:bg-white focus:ring-[rgba(11,77,89,0.12)]"
                error-class="mt-2"
                required
                autocomplete="current-password"
                autofocus
                placeholder="Enter your current password"
            />

            <div class="flex justify-end pt-1">
                <PrimaryButton
                    class="w-full sm:w-auto"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    Confirm password
                </PrimaryButton>
            </div>
        </form>
    </AuthSplitLayout>
</template>
