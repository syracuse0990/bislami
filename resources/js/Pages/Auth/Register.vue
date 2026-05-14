<script setup>
import GoogleAuthButton from '@/Components/Auth/GoogleAuthButton.vue';
import TextField from '@/Components/Forms/Fields/TextField.vue';
import PrimaryButton from '@/Components/UI/Buttons/PrimaryButton.vue';
import AuthSplitLayout from '@/Layouts/AuthSplitLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const form = useForm({
    account_type: 'customer',
    name: '',
    store_name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const accountTypes = [
    {
        key: 'customer',
        title: 'Customer',
        eyebrow: 'Order and track',
        description: 'Browse restaurants, save delivery details, and move through checkout with less friction.',
    },
    {
        key: 'merchant',
        title: 'Merchant',
        eyebrow: 'Sell and manage',
        description: 'Request a merchant workspace, submit your store identity, and wait for admin approval before tools unlock.',
    },
];

const registerHighlights = [
    'Save delivery details and speed up future checkouts.',
    'Keep ordering, menu updates, and account access in one premium workspace.',
    'Add your store details now and stay ready for merchant onboarding later.',
];

const fieldLabelClass = 'text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500';
const fieldInputClass = 'mt-2 w-full rounded-[18px] border border-[#dde5ed] bg-[#f7fafc] px-4 py-4 text-sm text-slate-900 shadow-none placeholder:text-slate-400 focus:border-[var(--brand-teal)] focus:bg-white focus:ring-[rgba(11,77,89,0.12)]';
const fieldErrorClass = 'mt-2';
const page = usePage();
const googleAuthEnabled = computed(() => Boolean(page.props.services?.socialAuth?.google?.enabled));
const googleAuthUrl = route('auth.social.redirect', { provider: 'google' });
const isMerchantAccount = computed(() => form.account_type === 'merchant');
const submitLabel = computed(() => isMerchantAccount.value ? 'Create merchant account' : 'Create customer account');

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <AuthSplitLayout
        hero-title="Create a BizLami account that feels ready for both ordering and merchant growth."
        hero-description="Start with one premium account for discovery, checkout, order updates, and future store operations when you are ready to expand."
        :highlights="registerHighlights"
        panel-title="Create your account"
        panel-description="Set up your BizLami access in a minute, with room for both personal ordering and future store setup."
        mobile-description="Create your account to browse restaurants, save details, and optionally attach your store name for future merchant setup."
        content-width-class="max-w-[560px] xl:max-w-[620px]"
        :panel-chrome="false"
    >
        <Head title="Register" />

        <form class="space-y-4" @submit.prevent="submit">
            <div class="space-y-3 rounded-[26px] border border-[rgba(11,77,89,0.08)] bg-white/75 p-4 shadow-[0_24px_50px_-40px_rgba(11,77,89,0.22)] backdrop-blur">
                <div class="space-y-1">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-[var(--brand-teal)]/70">Account type</p>
                    <p class="text-sm leading-6 text-slate-500">
                        Customers can order right away. Merchants can sign up for review. Drivers stay admin-only.
                    </p>
                </div>

                <div class="grid gap-3 sm:grid-cols-2">
                    <label
                        v-for="account in accountTypes"
                        :key="account.key"
                        class="group cursor-pointer"
                    >
                        <input v-model="form.account_type" type="radio" name="account_type" :value="account.key" class="sr-only">
                        <div
                            :class="[
                                'h-full rounded-[22px] border px-4 py-4 transition duration-200',
                                form.account_type === account.key
                                    ? 'border-[rgba(11,77,89,0.22)] bg-[linear-gradient(135deg,rgba(244,250,252,0.96),rgba(255,247,239,0.92))] shadow-[0_24px_50px_-38px_rgba(11,77,89,0.3)]'
                                    : 'border-[#e4ebf2] bg-white hover:border-[rgba(11,77,89,0.16)] hover:bg-[#fbfdff]'
                            ]"
                        >
                            <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-[var(--brand-teal)]/70">{{ account.eyebrow }}</p>
                            <h3 class="mt-2 text-lg font-semibold text-slate-900">{{ account.title }}</h3>
                            <p class="mt-2 text-sm leading-6 text-slate-500">{{ account.description }}</p>
                        </div>
                    </label>
                </div>
                <p v-if="form.errors.account_type" class="text-sm text-rose-600">{{ form.errors.account_type }}</p>
            </div>

            <div v-if="googleAuthEnabled && !isMerchantAccount" class="space-y-4">
                <GoogleAuthButton :href="googleAuthUrl" label="Continue with Google" />

                <div class="relative text-center text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-400">
                    <div class="absolute inset-x-0 top-1/2 h-px -translate-y-1/2 bg-[#e4ebf2]"></div>
                    <span class="relative bg-white px-4">Or continue with email</span>
                </div>
            </div>

            <div v-else-if="googleAuthEnabled && isMerchantAccount" class="rounded-[18px] border border-[#e7edf3] bg-white/80 px-4 py-3 text-xs leading-5 text-slate-500">
                Merchant signup stays on email for now so BizLami can capture your store details before the approval review starts.
            </div>

            <div :class="isMerchantAccount ? 'grid gap-4 sm:grid-cols-2' : 'grid gap-4'">
                <TextField
                    id="name"
                    v-model="form.name"
                    label="Full name"
                    type="text"
                    :message="form.errors.name"
                    :label-class="fieldLabelClass"
                    :input-class="fieldInputClass"
                    :error-class="fieldErrorClass"
                    required
                    autofocus
                    autocomplete="name"
                    placeholder="Your full name"
                />

                <div v-if="isMerchantAccount">
                    <TextField
                        id="store_name"
                        v-model="form.store_name"
                        label="Store name"
                        type="text"
                        :message="form.errors.store_name"
                        :label-class="fieldLabelClass"
                        :input-class="fieldInputClass"
                        :error-class="fieldErrorClass"
                        autocomplete="organization"
                        placeholder="Your restaurant or store name"
                    />
                 
                </div>
            </div>

            <TextField
                id="email"
                v-model="form.email"
                label="Email address"
                type="email"
                :message="form.errors.email"
                :label-class="fieldLabelClass"
                :input-class="fieldInputClass"
                :error-class="fieldErrorClass"
                required
                autocomplete="username"
                placeholder="you@example.com"
            />

            <div class="grid gap-4 sm:grid-cols-2">
                <TextField
                    id="password"
                    v-model="form.password"
                    label="Password"
                    type="password"
                    :message="form.errors.password"
                    :label-class="fieldLabelClass"
                    :input-class="fieldInputClass"
                    :error-class="fieldErrorClass"
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
                    :label-class="fieldLabelClass"
                    :input-class="fieldInputClass"
                    :error-class="fieldErrorClass"
                    required
                    autocomplete="new-password"
                    placeholder="Repeat your password"
                />
            </div>

            <div class="rounded-[20px] border border-[#e7edf3] bg-white/80 px-4 py-3 text-xs leading-5 text-slate-500">
                By continuing, you create a secure BizLami {{ isMerchantAccount ? 'merchant' : 'customer' }} account. Merchants enter an approval queue before workspace tools unlock.
            </div>

            <div class="flex flex-col gap-3 border-t border-[#e8eef3] pt-4 sm:flex-row sm:items-center sm:justify-between">
                <Link
                    :href="route('login')"
                    class="text-sm font-semibold text-[var(--brand-teal)] transition hover:text-[var(--brand-orange-deep)]"
                >
                    Already registered?
                </Link>

                <PrimaryButton
                    class="w-full sm:w-auto"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    {{ submitLabel }}
                </PrimaryButton>
            </div>
        </form>
    </AuthSplitLayout>
</template>
