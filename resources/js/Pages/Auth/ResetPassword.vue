<script setup>
import TextField from '@/Components/Forms/Fields/TextField.vue';
import PrimaryButton from '@/Components/UI/Buttons/PrimaryButton.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

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

const submit = () => {
    form.post(route('password.store'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Reset Password" />

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
                    autocomplete="new-password"
                />
            </div>

            <div class="mt-4">
                <TextField
                    id="password_confirmation"
                    v-model="form.password_confirmation"
                    label="Confirm Password"
                    type="password"
                    :message="form.errors.password_confirmation"
                    required
                    autocomplete="new-password"
                />
            </div>

            <div class="mt-4 flex items-center justify-end">
                <PrimaryButton
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    Reset Password
                </PrimaryButton>
            </div>
        </form>
    </GuestLayout>
</template>
