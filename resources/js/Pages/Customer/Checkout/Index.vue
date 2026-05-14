<script setup>
import GoogleAddressField from '@/Components/Maps/GoogleAddressField.vue';
import SelectField from '@/Components/Forms/Fields/SelectField.vue';
import TextField from '@/Components/Forms/Fields/TextField.vue';
import TextareaField from '@/Components/Forms/Fields/TextareaField.vue';
import PrimaryButton from '@/Components/UI/Buttons/PrimaryButton.vue';
import CustomerLayout from '@/Layouts/CustomerLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    checkout: {
        type: Object,
        default: () => ({
            restaurant: null,
            summary: null,
            deliveryAddress: '',
            deliveryLatitude: null,
            deliveryLongitude: null,
            idempotencyKey: null,
            paymentMethod: '',
            driverNotes: '',
            total: '₱0',
        }),
    },
});

const form = useForm({
    delivery_address: props.checkout.deliveryAddress,
    delivery_latitude: props.checkout.deliveryLatitude,
    delivery_longitude: props.checkout.deliveryLongitude,
    idempotency_key: props.checkout.idempotencyKey,
    payment_method: props.checkout.paymentMethod || 'Cash on delivery',
    driver_notes: props.checkout.driverNotes,
});

const paymentOptions = [
    { label: 'Cash on delivery', value: 'Cash on delivery' },
    { label: 'bKash', value: 'bKash' },
    { label: 'Card on delivery', value: 'Card on delivery' },
];

const submit = () => {
    form.post(route('customer.checkout.place'));
};
</script>

<template>
    <Head title="Checkout" />

    <CustomerLayout>
        <template #header>
            <div class="space-y-3">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-[var(--brand-orange-deep)]">
                    Checkout
                </p>
                <h2 class="text-3xl font-semibold leading-tight text-slate-900">
                    Confirm delivery details without losing the order picture.
                </h2>
                <p class="max-w-2xl text-sm leading-6 text-slate-600">
                    Payment, address, and driver notes stay beside the order summary so customers can finish faster.
                </p>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-6xl space-y-8 sm:px-6 lg:px-8">
                <section class="rounded-[32px] border border-white/80 bg-white/88 p-6 shadow-[0_34px_85px_-56px_rgba(11,77,89,0.6)] sm:p-8">
                    <div v-if="checkout.restaurant" class="space-y-6">
                        <div class="grid gap-6 lg:grid-cols-[1.12fr_0.88fr]">
                            <form @submit.prevent="submit" class="space-y-6 rounded-[30px] border border-[#edf2f2] bg-[#fffcf8] p-6">
                                <div class="space-y-2">
                                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[var(--brand-teal)]">
                                        Delivery details
                                    </p>
                                    <h3 class="text-2xl font-semibold text-slate-900">Finish your order with clean, readable inputs.</h3>
                                </div>

                                <GoogleAddressField
                                    v-if="$page.props.services.googleMaps.enabled"
                                    id="delivery_address"
                                    v-model="form.delivery_address"
                                    v-model:latitude="form.delivery_latitude"
                                    v-model:longitude="form.delivery_longitude"
                                    label="Delivery address"
                                    :message="form.errors.delivery_address"
                                    required
                                    autocomplete="street-address"
                                    placeholder="House, road, area, and city"
                                />

                                <TextField
                                    v-else
                                    id="delivery_address"
                                    v-model="form.delivery_address"
                                    label="Delivery address"
                                    :message="form.errors.delivery_address"
                                    required
                                    autocomplete="street-address"
                                    placeholder="House, road, area, and city"
                                />

                                <SelectField
                                    id="payment_method"
                                    v-model="form.payment_method"
                                    label="Payment method"
                                    :message="form.errors.payment_method"
                                    :options="paymentOptions"
                                    required
                                />

                                <TextareaField
                                    id="driver_notes"
                                    v-model="form.driver_notes"
                                    label="Driver notes"
                                    :message="form.errors.driver_notes"
                                    placeholder="Gate code, landmark, or delivery instructions"
                                />

                                <div class="flex flex-col gap-3 sm:flex-row">
                                    <p v-if="form.errors.checkout" class="text-sm font-medium text-[var(--brand-orange-deep)] sm:self-center">
                                        {{ form.errors.checkout }}
                                    </p>

                                    <Link
                                        :href="route('customer.cart.index')"
                                        class="inline-flex items-center justify-center rounded-full border border-white/80 bg-white px-5 py-3 text-sm font-semibold text-slate-700 shadow-[0_18px_42px_-32px_rgba(11,77,89,0.35)] transition duration-200 hover:text-[var(--brand-teal)]"
                                    >
                                        Back to cart
                                    </Link>

                                    <PrimaryButton
                                        :class="{ 'opacity-25': form.processing }"
                                        :disabled="form.processing"
                                    >
                                        Place order
                                    </PrimaryButton>
                                </div>
                            </form>

                            <aside class="space-y-4 rounded-[30px] border border-[#edf2f2] bg-white p-6">
                                <div class="rounded-[26px] bg-[#fff7ef] p-5 ring-1 ring-[#f6dcc5]">
                                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[var(--brand-orange-deep)]">
                                        Dispatch summary
                                    </p>
                                    <p class="mt-2 text-lg font-semibold text-slate-900">
                                        {{ checkout.restaurant }}
                                    </p>
                                    <p class="mt-2 text-sm leading-6 text-slate-600">
                                        {{ checkout.summary }}
                                    </p>
                                    <p class="mt-3 font-semibold text-slate-900">
                                        Current total: {{ checkout.total }}
                                    </p>
                                </div>

                                <div class="rounded-[26px] bg-[#f4fbfb] p-5 ring-1 ring-[#dceced]">
                                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[var(--brand-teal)]">
                                        Final checklist
                                    </p>
                                    <ul class="mt-3 space-y-3 text-sm leading-6 text-slate-600">
                                        <li>Confirm the drop-off address before placing the order.</li>
                                        <li>Choose a payment method that matches how you want to settle the delivery.</li>
                                        <li>Add driver notes only when they help complete the drop faster.</li>
                                    </ul>
                                </div>
                            </aside>
                        </div>
                    </div>

                    <div v-else class="rounded-[28px] border border-dashed border-[#d8e7e8] bg-[#fffcf8] p-8 text-sm text-slate-500">
                        There is no active checkout yet. Start from your cart to confirm delivery details.
                    </div>
                </section>
            </div>
        </div>
    </CustomerLayout>
</template>