<script setup>
import TextField from '@/Components/Forms/Fields/TextField.vue';
import PrimaryButton from '@/Components/UI/Buttons/PrimaryButton.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Register" />

        <form @submit.prevent="submit">
            <div>
                <TextField
                    id="name"
                    v-model="form.name"
                    label="Name"
                    type="text"
                    :message="form.errors.name"
                    required
                    autofocus
                    autocomplete="name"
                />
            </div>

            <div class="mt-4">
                <TextField
                    id="email"
                    v-model="form.email"
                    label="Email"
                    type="email"
                    :message="form.errors.email"
                    required
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
                <Link
                    :href="route('login')"
                    class="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                >
                    Already registered?
                </Link>

                <PrimaryButton
                    class="ms-4"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    Register
                </PrimaryButton>
            </div>
        </form>
    </GuestLayout>
</template>
