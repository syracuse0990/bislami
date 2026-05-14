<script setup>
import { router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    menuItemId: {
        type: Number,
        required: true,
    },
    restaurantId: {
        type: Number,
        required: true,
    },
    restaurantName: {
        type: String,
        required: true,
    },
    itemName: {
        type: String,
        required: true,
    },
    label: {
        type: String,
        default: 'Add to cart',
    },
    routeName: {
        type: String,
        default: 'customer.cart.store',
    },
    buttonClass: {
        type: String,
        default: 'inline-flex items-center justify-center rounded-full bg-gradient-to-r from-[var(--brand-teal)] to-[var(--brand-orange)] px-5 py-3 text-sm font-semibold text-white shadow-[0_20px_45px_-24px_rgba(11,77,89,0.85)] transition duration-200 hover:-translate-y-0.5',
    },
    redirectTo: {
        type: String,
        default: '',
    },
});

const page = usePage();
const showingConfirmation = ref(false);

const activeCart = computed(() => page.props.shoppingCart ?? page.props.customerCart ?? {
    restaurantId: null,
    restaurantName: null,
    itemsCount: 0,
});

const redirectTarget = computed(() => props.redirectTo || page.url);

const hasCartConflict = computed(() => Boolean(
    activeCart.value.restaurantId
    && activeCart.value.restaurantId !== props.restaurantId
    && activeCart.value.itemsCount > 0,
));

function submit(replaceCart = false) {
    router.post(route(props.routeName, props.menuItemId), {
        replace_cart: replaceCart,
        redirect_to: redirectTarget.value,
    }, {
        preserveScroll: true,
        onFinish: () => {
            if (replaceCart) {
                showingConfirmation.value = false;
            }
        },
    });
}

function handleClick() {
    if (hasCartConflict.value) {
        showingConfirmation.value = true;

        return;
    }

    submit(false);
}
</script>

<template>
    <button type="button" :class="buttonClass" @click="handleClick">
        {{ label }}
    </button>

    <Teleport to="body">
        <div v-if="showingConfirmation" class="fixed inset-0 z-[90] flex items-center justify-center p-4">
            <button
                type="button"
                class="absolute inset-0 bg-slate-900/45"
                @click="showingConfirmation = false"
            ></button>

            <div class="relative w-full max-w-lg rounded-[32px] border border-white/75 bg-white p-6 shadow-[0_36px_90px_-42px_rgba(11,77,89,0.58)] sm:p-7">
                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-[var(--brand-orange-deep)]">
                    Replace current cart?
                </p>
                <h3 class="mt-3 text-2xl font-semibold text-slate-900">
                    Adding {{ itemName }} will start a new kitchen cart.
                </h3>
                <p class="mt-3 text-sm leading-7 text-slate-600">
                    Your current cart contains {{ activeCart.restaurantName }}. If you continue with {{ restaurantName }}, BizLami will clear those items and start a fresh cart for this kitchen.
                </p>

                <div class="mt-5 rounded-[24px] bg-[#fffaf3] p-4 ring-1 ring-[#f0dfcf]">
                    <div class="flex items-center justify-between gap-3 text-sm text-slate-600">
                        <span class="font-medium text-slate-900">Current cart</span>
                        <span>{{ activeCart.restaurantName }}</span>
                    </div>
                    <div class="mt-3 flex items-center justify-between gap-3 text-sm text-slate-600">
                        <span class="font-medium text-slate-900">New kitchen</span>
                        <span>{{ restaurantName }}</span>
                    </div>
                </div>

                <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:justify-end">
                    <button
                        type="button"
                        class="inline-flex items-center justify-center rounded-full border border-white/80 bg-white px-5 py-3 text-sm font-semibold text-slate-700 shadow-[0_18px_42px_-32px_rgba(11,77,89,0.35)] transition duration-200 hover:text-[var(--brand-teal)]"
                        @click="showingConfirmation = false"
                    >
                        Keep current cart
                    </button>

                    <button
                        type="button"
                        class="inline-flex items-center justify-center rounded-full bg-[var(--brand-orange-deep)] px-5 py-3 text-sm font-semibold text-white shadow-[0_20px_45px_-24px_rgba(197,92,24,0.82)] transition duration-200 hover:-translate-y-0.5"
                        @click="submit(true)"
                    >
                        Replace cart and add
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>