<script setup>
import CheckboxField from '@/Components/Forms/Fields/CheckboxField.vue';
import TextField from '@/Components/Forms/Fields/TextField.vue';
import PrimaryButton from '@/Components/UI/Buttons/PrimaryButton.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

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

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Log in" />

        <div v-if="status" class="mb-4 text-sm font-medium text-green-600">
            {{ status }}
        </div>

        <form @submit.prevent="submit">
            <div>
                <TextField
                    id="email"
                    v-model="form.email"
                    label="Email"
                    type="email"
                    :message="form.errors.email"
                    required
                    autofocus
                    autocomplete="username"
                />
            </div>

            <div class="mt-4">
                <TextField
                    id="password"
                    v-model="form.password"
                    label="Password"
                    type="password"
                    :message="form.errors.password"
                    required
                    autocomplete="current-password"
                />
            </div>

            <div class="mt-4 block">
                <CheckboxField
                    name="remember"
                    label="Remember me"
                    v-model:checked="form.remember"
                />
            </div>

            <div class="mt-4 flex items-center justify-end">
                <Link
                    v-if="canResetPassword"
                    :href="route('password.request')"
                    class="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                >
                    Forgot your password?
                </Link>

                <PrimaryButton
                    class="ms-4"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    Log in
                </PrimaryButton>
            </div>
        </form>
    </GuestLayout>
</template>
