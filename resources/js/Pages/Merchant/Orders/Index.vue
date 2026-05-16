<script setup>
import MerchantRestaurantLogoCard from '@/Components/Branding/MerchantRestaurantLogoCard.vue';
import CheckboxField from '@/Components/Forms/Fields/CheckboxField.vue';
import SelectField from '@/Components/Forms/Fields/SelectField.vue';
import TextField from '@/Components/Forms/Fields/TextField.vue';
import TextareaField from '@/Components/Forms/Fields/TextareaField.vue';
import Modal from '@/Components/UI/Modal.vue';
import SlideOver from '@/Components/UI/SlideOver.vue';
import MerchantLayout from '@/Layouts/MerchantLayout.vue';
import { bootEcho } from '@/lib/echo';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { sileo } from '@llayz46/sileo-vue';
import { computed, onBeforeUnmount, reactive, ref, watch } from 'vue';

const props = defineProps({
    queue: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({
            restaurantId: 0,
            status: 'all',
            fulfillmentType: 'all',
            search: '',
        }),
    },
    stats: {
        type: Object,
        default: () => ({
            all: 0,
            pending: 0,
            accepted: 0,
            preparing: 0,
            ready: 0,
            pickedUp: 0,
            scheduled: 0,
        }),
    },
    restaurants: {
        type: Array,
        default: () => [],
    },
    rejectionReasons: {
        type: Array,
        default: () => [],
    },
});

const page = usePage();
const activeOrderActionId = ref(null);
const editingOrder = ref(null);
const focusedOrder = ref(null);
const rejectingOrder = ref(null);
const settingsForms = reactive({});
const subscribedChannels = ref([]);
const lastRealtimeEvent = ref('Waiting for the next merchant order event.');
const realtimeStatusLabel = ref('Realtime offline');

const filterForm = useForm({
    restaurant_id: props.filters.restaurantId ? String(props.filters.restaurantId) : '',
    status: props.filters.status ?? 'all',
    fulfillment_type: props.filters.fulfillmentType ?? 'all',
    search: props.filters.search ?? '',
});

const rejectForm = useForm({
    rejection_reason_code: props.rejectionReasons[0]?.value ?? 'busy_capacity',
    rejection_reason_note: '',
});

const editForm = useForm({
    fulfillment_type: 'delivery',
    delivery_address: '',
    delivery_latitude: '',
    delivery_longitude: '',
    payment_method: '',
    driver_notes: '',
    customer_notes: '',
    merchant_notes: '',
    scheduled_for: '',
});

const realtimeConfig = computed(() => page.props.services?.realtime ?? null);

const restaurantOptions = computed(() => ([
    { value: '', label: 'All restaurants' },
    ...props.restaurants.map((restaurant) => ({
        value: String(restaurant.id),
        label: restaurant.name,
    })),
]));

const statusOptions = [
    { value: 'all', label: 'All stages' },
    { value: 'pending', label: 'Pending review' },
    { value: 'accepted', label: 'Accepted' },
    { value: 'preparing', label: 'Preparing' },
    { value: 'ready', label: 'Ready for pickup' },
    { value: 'picked_up', label: 'Picked up' },
];

const fulfillmentOptions = [
    { value: 'all', label: 'Delivery and pickup' },
    { value: 'delivery', label: 'Delivery only' },
    { value: 'pickup', label: 'Pickup only' },
];

const statCards = computed(() => ([
    {
        key: 'pending',
        label: 'Pending review',
        value: props.stats.pending ?? 0,
        accent: 'from-[#fff1e3] to-[#fff8f1] text-[var(--brand-orange-deep)] ring-[#f6d7ba]',
    },
    {
        key: 'accepted',
        label: 'Accepted',
        value: props.stats.accepted ?? 0,
        accent: 'from-[#ecfbf7] to-[#f4fbfb] text-[var(--brand-teal)] ring-[#cde7e1]',
    },
    {
        key: 'preparing',
        label: 'Preparing',
        value: props.stats.preparing ?? 0,
        accent: 'from-[#fff4dc] to-[#fff9ee] text-[#9a5d03] ring-[#f2d59f]',
    },
    {
        key: 'ready',
        label: 'Ready now',
        value: props.stats.ready ?? 0,
        accent: 'from-[#ebf6ff] to-[#f5fbff] text-[#115e9a] ring-[#cfe3f5]',
    },
    {
        key: 'picked-up',
        label: 'Picked up',
        value: props.stats.pickedUp ?? 0,
        accent: 'from-[#f4f4ff] to-[#fafaff] text-[#5856b3] ring-[#d9d7f5]',
    },
    {
        key: 'scheduled',
        label: 'Scheduled',
        value: props.stats.scheduled ?? 0,
        accent: 'from-[#f8f4ff] to-[#fcfbff] text-[#7d4aa5] ring-[#e3d4f2]',
    },
]));

watch(
    () => props.filters,
    (filters) => {
        filterForm.restaurant_id = filters?.restaurantId ? String(filters.restaurantId) : '';
        filterForm.status = filters?.status ?? 'all';
        filterForm.fulfillment_type = filters?.fulfillmentType ?? 'all';
        filterForm.search = filters?.search ?? '';
    },
    { deep: true },
);

watch(
    () => props.queue,
    (queue) => {
        if (!focusedOrder.value) {
            return;
        }

        const refreshedOrder = queue.find((order) => order.id === focusedOrder.value.id);

        if (!refreshedOrder) {
            focusedOrder.value = null;

            return;
        }

        focusedOrder.value = refreshedOrder;
    },
    { deep: true },
);

watch(
    () => props.restaurants.map((restaurant) => `${restaurant.id}:${JSON.stringify(restaurant.orderSettings)}`).join('|'),
    () => {
        props.restaurants.forEach((restaurant) => {
            const form = restaurantSettingsForm(restaurant);

            form.auto_accept_enabled = Boolean(restaurant.orderSettings?.auto_accept_enabled);
            form.auto_reject_unavailable_items = Boolean(restaurant.orderSettings?.auto_reject_unavailable_items);
            form.sound_alerts_enabled = Boolean(restaurant.orderSettings?.sound_alerts_enabled);
        });

        connectRealtime();
    },
    { immediate: true },
);

onBeforeUnmount(() => {
    disconnectRealtime();
});

function restaurantSettingsForm(restaurant) {
    if (!settingsForms[restaurant.id]) {
        settingsForms[restaurant.id] = useForm({
            auto_accept_enabled: Boolean(restaurant.orderSettings?.auto_accept_enabled),
            auto_reject_unavailable_items: Boolean(restaurant.orderSettings?.auto_reject_unavailable_items),
            sound_alerts_enabled: Boolean(restaurant.orderSettings?.sound_alerts_enabled),
        });
    }

    return settingsForms[restaurant.id];
}

function applyFilters() {
    filterForm.get(route('merchant.orders.index'), {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

function resetFilters() {
    filterForm.restaurant_id = '';
    filterForm.status = 'all';
    filterForm.fulfillment_type = 'all';
    filterForm.search = '';

    applyFilters();
}

function performOrderAction(order, routeName) {
    activeOrderActionId.value = order.id;

    router.post(route(routeName, order.id), {}, {
        preserveScroll: true,
        onFinish: () => {
            activeOrderActionId.value = null;
        },
    });
}

function openRejectModal(order) {
    rejectingOrder.value = order;
    rejectForm.rejection_reason_code = props.rejectionReasons[0]?.value ?? 'busy_capacity';
    rejectForm.rejection_reason_note = '';
    rejectForm.clearErrors();
}

function openOrderDrawer(order) {
    focusedOrder.value = order;
}

function closeOrderDrawer() {
    focusedOrder.value = null;
}

function closeRejectModal() {
    rejectingOrder.value = null;
    rejectForm.clearErrors();
}

function submitReject() {
    if (!rejectingOrder.value) {
        return;
    }

    rejectForm.post(route('merchant.orders.reject', rejectingOrder.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            closeRejectModal();
        },
    });
}

function openEditModal(order) {
    editingOrder.value = order;
    editForm.fulfillment_type = order.fulfillment.key;
    editForm.delivery_address = order.destination.deliveryAddress ?? '';
    editForm.delivery_latitude = order.destination.latitude !== null ? String(order.destination.latitude) : '';
    editForm.delivery_longitude = order.destination.longitude !== null ? String(order.destination.longitude) : '';
    editForm.payment_method = order.paymentMethod ?? '';
    editForm.driver_notes = order.notes.delivery ?? '';
    editForm.customer_notes = order.notes.customer ?? '';
    editForm.merchant_notes = order.notes.merchant ?? '';
    editForm.scheduled_for = order.scheduledForValue ?? '';
    editForm.clearErrors();
}

function closeEditModal() {
    editingOrder.value = null;
    editForm.clearErrors();
}

function submitEdit() {
    if (!editingOrder.value) {
        return;
    }

    editForm.patch(route('merchant.orders.update', editingOrder.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            closeEditModal();
        },
    });
}

function saveRestaurantSettings(restaurant) {
    restaurantSettingsForm(restaurant).patch(route('merchant.restaurants.order-settings.update', restaurant.id), {
        preserveScroll: true,
    });
}

function actionIsBusy(orderId) {
    return activeOrderActionId.value === orderId || rejectForm.processing || editForm.processing;
}

function soundAlertsEnabledForRestaurant(restaurantId) {
    return Boolean(props.restaurants.find((restaurant) => restaurant.id === restaurantId)?.orderSettings?.sound_alerts_enabled);
}

function playAlertTone() {
    if (typeof window === 'undefined') {
        return;
    }

    const AudioContextClass = window.AudioContext || window.webkitAudioContext;

    if (!AudioContextClass) {
        return;
    }

    const audioContext = new AudioContextClass();
    const now = audioContext.currentTime;
    const oscillator = audioContext.createOscillator();
    const gainNode = audioContext.createGain();

    oscillator.type = 'triangle';
    oscillator.frequency.setValueAtTime(880, now);
    oscillator.frequency.exponentialRampToValueAtTime(660, now + 0.18);

    gainNode.gain.setValueAtTime(0.0001, now);
    gainNode.gain.exponentialRampToValueAtTime(0.12, now + 0.02);
    gainNode.gain.exponentialRampToValueAtTime(0.0001, now + 0.28);

    oscillator.connect(gainNode);
    gainNode.connect(audioContext.destination);
    oscillator.start(now);
    oscillator.stop(now + 0.3);
}

function handleRealtimeEvent(payload) {
    lastRealtimeEvent.value = `${payload.orderNumber} ${payload.status.label.toLowerCase()} for ${payload.restaurantName}`;

    if (payload.eventType === 'created') {
        sileo.warning({
            title: 'New incoming order',
            description: `${payload.orderNumber} · ${payload.customerName} · ${payload.total.formatted}`,
            duration: 5200,
        });

        if (soundAlertsEnabledForRestaurant(payload.restaurantId)) {
            playAlertTone();
        }
    } else {
        sileo.success({
            title: 'Order updated',
            description: `${payload.orderNumber} · ${payload.status.label}`,
            duration: 3600,
        });
    }

    router.reload({
        only: ['queue', 'stats', 'restaurants', 'rejectionReasons'],
        preserveScroll: true,
        preserveState: true,
    });
}

function connectRealtime() {
    disconnectRealtime();

    const echo = bootEcho(realtimeConfig.value);

    if (!echo || !props.restaurants.length) {
        realtimeStatusLabel.value = realtimeConfig.value?.enabled
            ? 'No private merchant channels subscribed yet.'
            : 'Realtime is waiting for Pusher credentials.';

        return;
    }

    subscribedChannels.value = props.restaurants.map((restaurant) => `merchant.orders.${restaurant.id}`);

    subscribedChannels.value.forEach((channelName) => {
        echo.private(channelName).listen('.merchant.order.changed', handleRealtimeEvent);
    });

    realtimeStatusLabel.value = `Listening on ${subscribedChannels.value.length} merchant channel${subscribedChannels.value.length === 1 ? '' : 's'}.`;
}

function disconnectRealtime() {
    if (!window.Echo) {
        return;
    }

    subscribedChannels.value.forEach((channelName) => {
        window.Echo.leave(channelName);
    });

    subscribedChannels.value = [];
}
</script>

<template>
    <Head title="Merchant Orders" />

    <MerchantLayout>
        <template #header>
            <div class="flex flex-col gap-5 xl:flex-row xl:items-end xl:justify-between">
                <div class="space-y-3">
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-[var(--brand-orange-deep)]">
                        Merchant operations board
                    </p>
                    <h2 class="max-w-3xl text-3xl font-semibold leading-tight text-slate-900 sm:text-[2.15rem]">
                        Review, accept, prep, and hand off orders from one live control room.
                    </h2>
                    <p class="max-w-3xl text-sm leading-6 text-slate-600 sm:text-base">
                        The board now keeps pending approvals, kitchen flow, pickup readiness, scheduled orders, and courier handoff in one merchant-first view.
                    </p>
                </div>

                <div class="rounded-[28px] border border-white/80 bg-white/90 px-5 py-4 shadow-[0_24px_60px_-40px_rgba(11,77,89,0.5)]">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-[var(--brand-teal)]">Realtime channel</p>
                    <p class="mt-2 text-sm font-medium text-slate-900">{{ realtimeStatusLabel }}</p>
                    <p class="mt-1 max-w-xs text-sm text-slate-500">{{ lastRealtimeEvent }}</p>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="space-y-6 px-4 sm:px-6 lg:px-0">
                <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-6">
                    <article
                        v-for="card in statCards"
                        :key="card.key"
                        class="rounded-[28px] border border-white/80 bg-white/88 p-5 shadow-[0_28px_70px_-48px_rgba(11,77,89,0.45)]"
                    >
                        <div class="inline-flex rounded-full bg-gradient-to-r px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] ring-1" :class="card.accent">
                            {{ card.label }}
                        </div>
                        <p class="mt-5 text-3xl font-semibold text-slate-900">{{ card.value }}</p>
                        <p class="mt-2 text-sm text-slate-500">{{ card.key === 'scheduled' ? 'Future handoffs and pickup windows' : 'Live merchant order pipeline' }}</p>
                    </article>
                </section>

                <section class="grid gap-4 xl:grid-cols-[1.35fr_0.85fr]">
                    <form
                        class="rounded-[32px] border border-white/80 bg-[linear-gradient(145deg,#ffffff_0%,#fff8f1_56%,#f3fbfb_100%)] p-6 shadow-[0_34px_85px_-56px_rgba(11,77,89,0.6)] sm:p-8"
                        @submit.prevent="applyFilters"
                    >
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-[var(--brand-teal)]">Bulk order filters</p>

                        <div class="px-4 py-8 sm:px-6 lg:px-0">
                            <MerchantRestaurantLogoCard
                                class="max-w-3xl"
                                note="Use the current restaurant logo as a quick visual anchor while you triage live merchant orders."
                            />
                        </div>
                                <h3 class="mt-2 text-2xl font-semibold text-slate-900">Trim the queue by stage, restaurant, or fulfillment mode.</h3>
                            </div>

                            <div class="rounded-full border border-white/80 bg-white/90 px-4 py-2 text-sm font-medium text-slate-600 shadow-[0_20px_48px_-36px_rgba(11,77,89,0.35)]">
                                {{ stats.all ?? queue.length }} active merchant orders
                            </div>
                        </div>

                        <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                            <SelectField
                                id="restaurant_id"
                                v-model="filterForm.restaurant_id"
                                label="Restaurant"
                                :options="restaurantOptions"
                                :message="filterForm.errors.restaurant_id"
                            />

                            <SelectField
                                id="status"
                                v-model="filterForm.status"
                                label="Stage"
                                :options="statusOptions"
                                :message="filterForm.errors.status"
                            />

                            <SelectField
                                id="fulfillment_type"
                                v-model="filterForm.fulfillment_type"
                                label="Fulfillment"
                                :options="fulfillmentOptions"
                                :message="filterForm.errors.fulfillment_type"
                            />

                            <TextField
                                id="search"
                                v-model="filterForm.search"
                                label="Search"
                                placeholder="Order, customer, food, address"
                                :message="filterForm.errors.search"
                            />
                        </div>

                        <div class="mt-5 flex flex-wrap gap-3">
                            <button
                                type="submit"
                                class="inline-flex items-center justify-center rounded-full bg-[var(--brand-orange)] px-5 py-3 text-xs font-semibold uppercase tracking-[0.2em] text-slate-950 shadow-[0_22px_46px_-26px_rgba(242,126,33,0.8)] transition duration-200 hover:-translate-y-0.5 disabled:opacity-60"
                                :disabled="filterForm.processing"
                            >
                                {{ filterForm.processing ? 'Filtering…' : 'Apply filters' }}
                            </button>

                            <button
                                type="button"
                                class="inline-flex items-center justify-center rounded-full border border-[#d6e7e7] bg-white px-5 py-3 text-xs font-semibold uppercase tracking-[0.18em] text-slate-700 transition duration-200 hover:-translate-y-0.5"
                                @click="resetFilters"
                            >
                                Reset board
                            </button>
                        </div>
                    </form>

                    <section class="rounded-[32px] border border-white/80 bg-[#f7fbfb] p-6 shadow-[0_30px_75px_-58px_rgba(11,77,89,0.5)] sm:p-8">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-[var(--brand-orange-deep)]">Realtime intake</p>
                                <h3 class="mt-2 text-xl font-semibold text-slate-900">Push updates and sound alerts stay tied to each restaurant.</h3>
                            </div>
                            <span class="rounded-full bg-[#e9fbf8] px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.16em] text-[var(--brand-teal)] ring-1 ring-[#c6e8df]">
                                {{ realtimeConfig?.enabled ? 'Connected setup' : 'Needs env setup' }}
                            </span>
                        </div>

                        <div class="mt-6 space-y-4">
                            <div class="rounded-[24px] border border-[#dceaea] bg-white px-5 py-4">
                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[var(--brand-teal)]">Live note</p>
                                <p class="mt-2 text-sm leading-6 text-slate-600">{{ lastRealtimeEvent }}</p>
                            </div>

                            <div class="grid gap-3 sm:grid-cols-2">
                                <div class="rounded-[24px] border border-[#dceaea] bg-white px-5 py-4">
                                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[var(--brand-teal)]">Alerts armed</p>
                                    <p class="mt-2 text-2xl font-semibold text-slate-900">
                                        {{ restaurants.filter((restaurant) => restaurant.orderSettings?.sound_alerts_enabled).length }}
                                    </p>
                                    <p class="mt-1 text-sm text-slate-500">Restaurants with sound enabled</p>
                                </div>

                                <div class="rounded-[24px] border border-[#dceaea] bg-white px-5 py-4">
                                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[var(--brand-teal)]">Auto accept</p>
                                    <p class="mt-2 text-2xl font-semibold text-slate-900">
                                        {{ restaurants.filter((restaurant) => restaurant.orderSettings?.auto_accept_enabled).length }}
                                    </p>
                                    <p class="mt-1 text-sm text-slate-500">Restaurants using instant acceptance</p>
                                </div>
                            </div>

                            <p class="text-sm leading-6 text-slate-500">
                                Websocket host: <span class="font-medium text-slate-700">websocket.digisaka.app</span>. The board listens on private merchant channels and refreshes only the queue slice when a new order lands.
                            </p>
                        </div>
                    </section>
                </section>

                <section v-if="restaurants.length" class="grid gap-4 xl:grid-cols-2">
                    <article
                        v-for="restaurant in restaurants"
                        :key="restaurant.id"
                        class="rounded-[30px] border border-white/80 bg-white/88 p-6 shadow-[0_28px_70px_-50px_rgba(11,77,89,0.5)]"
                    >
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-[var(--brand-orange-deep)]">Restaurant automation</p>
                                <h3 class="mt-2 text-xl font-semibold text-slate-900">{{ restaurant.name }}</h3>
                                <p class="mt-2 text-sm leading-6 text-slate-500">
                                    Tune acceptance and alert behavior for this storefront without leaving the operations board.
                                </p>
                            </div>

                            <button
                                type="button"
                                class="inline-flex items-center justify-center rounded-full border border-[#d6e7e7] bg-[#f8fbfb] px-4 py-2 text-xs font-semibold uppercase tracking-[0.18em] text-[var(--brand-teal)]"
                            >
                                Restaurant {{ restaurant.id }}
                            </button>
                        </div>

                        <div class="mt-5 grid gap-3">
                            <CheckboxField
                                :id="`restaurant-${restaurant.id}-auto-accept`"
                                v-model:checked="restaurantSettingsForm(restaurant).auto_accept_enabled"
                                label="Auto-accept orders that do not need manual review"
                            />

                            <CheckboxField
                                :id="`restaurant-${restaurant.id}-auto-reject`"
                                v-model:checked="restaurantSettingsForm(restaurant).auto_reject_unavailable_items"
                                label="Auto-reject carts with unavailable items"
                            />

                            <CheckboxField
                                :id="`restaurant-${restaurant.id}-sound-alerts`"
                                v-model:checked="restaurantSettingsForm(restaurant).sound_alerts_enabled"
                                label="Play a sound when a new order arrives"
                            />
                        </div>

                        <div class="mt-5 flex items-center justify-between gap-3 rounded-[24px] bg-[#f8fbfb] px-4 py-4 ring-1 ring-[#e1ecec]">
                            <p class="text-sm text-slate-600">Apply these rules to future incoming orders for this restaurant.</p>

                            <button
                                type="button"
                                class="inline-flex items-center justify-center rounded-full bg-[var(--brand-teal)] px-4 py-2 text-xs font-semibold uppercase tracking-[0.18em] text-white shadow-[0_18px_40px_-26px_rgba(11,77,89,0.72)] transition duration-200 hover:-translate-y-0.5 disabled:opacity-60"
                                :disabled="restaurantSettingsForm(restaurant).processing"
                                @click="saveRestaurantSettings(restaurant)"
                            >
                                {{ restaurantSettingsForm(restaurant).processing ? 'Saving…' : 'Save rules' }}
                            </button>
                        </div>
                    </article>
                </section>

                <section class="rounded-[32px] border border-white/80 bg-[linear-gradient(145deg,#ffffff_0%,#fdf7ef_54%,#f4fbfb_100%)] p-6 shadow-[0_34px_85px_-56px_rgba(11,77,89,0.6)] sm:p-8">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-[var(--brand-teal)]">Active order board</p>
                            <h3 class="mt-2 text-2xl font-semibold text-slate-900">{{ queue.length }} order{{ queue.length === 1 ? '' : 's' }} currently inside the merchant workflow.</h3>
                            <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">
                                Orders stay grouped with the information a kitchen lead actually needs: schedule, customer notes, destination, courier state, and the next valid action.
                            </p>
                        </div>

                        <div class="rounded-full border border-white/80 bg-white/90 px-4 py-2 text-sm font-medium text-slate-600 shadow-[0_20px_48px_-36px_rgba(11,77,89,0.35)]">
                            Pending through picked-up lifecycle coverage
                        </div>
                    </div>

                    <div v-if="queue.length" class="mt-6 grid gap-4">
                        <article
                            v-for="order in queue"
                            :key="order.id"
                            class="rounded-[28px] border border-white/80 bg-white/92 p-5 shadow-[0_28px_70px_-48px_rgba(11,77,89,0.55)] sm:p-6"
                        >
                            <div class="flex flex-col gap-6 xl:flex-row xl:items-start xl:justify-between">
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-3">
                                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[var(--brand-orange-deep)]">
                                            {{ order.orderNumber }}
                                        </p>
                                        <span :class="order.statusAccent" class="rounded-full px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] ring-1">
                                            {{ order.statusLabel }}
                                        </span>
                                        <span class="rounded-full bg-[#f6fbfb] px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-[var(--brand-teal)] ring-1 ring-[#d8eaea]">
                                            {{ order.fulfillment.label }}
                                        </span>
                                        <span v-if="order.isScheduled" class="rounded-full bg-[#f7f1ff] px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-[#7d4aa5] ring-1 ring-[#e6d7f4]">
                                            Scheduled {{ order.scheduledFor }}
                                        </span>
                                    </div>

                                    <div class="mt-4 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                        <div>
                                            <h3 class="text-xl font-semibold text-slate-900">{{ order.restaurantName }}</h3>
                                            <p class="mt-1 text-sm leading-6 text-slate-600">{{ order.summary }}</p>
                                        </div>

                                        <div class="rounded-[22px] bg-[#f8fbfb] px-4 py-3 ring-1 ring-[#e1ecec]">
                                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-[var(--brand-teal)]">Assignment</p>
                                            <p class="mt-2 text-sm font-medium text-slate-900">{{ order.assignment.label }}</p>
                                        </div>
                                    </div>

                                    <div class="mt-5 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                                        <div class="rounded-[22px] bg-[#f8fbfb] px-4 py-4 ring-1 ring-[#e1ecec]">
                                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-[var(--brand-teal)]">Customer</p>
                                            <p class="mt-2 text-sm font-medium text-slate-900">{{ order.customerName }}</p>
                                        </div>

                                        <div class="rounded-[22px] bg-[#f8fbfb] px-4 py-4 ring-1 ring-[#e1ecec]">
                                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-[var(--brand-teal)]">Placed</p>
                                            <p class="mt-2 text-sm font-medium text-slate-900">{{ order.placedAt }}</p>
                                            <p class="mt-1 text-xs text-slate-500">{{ order.placedAtDate }}</p>
                                        </div>

                                        <div class="rounded-[22px] bg-[#f8fbfb] px-4 py-4 ring-1 ring-[#e1ecec]">
                                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-[var(--brand-teal)]">Total</p>
                                            <p class="mt-2 text-sm font-medium text-slate-900">{{ order.total }}</p>
                                            <p class="mt-1 text-xs text-slate-500">{{ order.paymentMethod }}</p>
                                        </div>

                                        <div class="rounded-[22px] bg-[#f8fbfb] px-4 py-4 ring-1 ring-[#e1ecec]">
                                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-[var(--brand-teal)]">Destination</p>
                                            <p class="mt-2 text-sm font-medium text-slate-900">{{ order.destination.shortLabel }}</p>
                                            <p class="mt-1 text-xs text-slate-500">{{ order.destination.hasCoordinates ? 'Pinned on map' : 'Address only' }}</p>
                                        </div>
                                    </div>

                                    <div class="mt-5 grid gap-4 lg:grid-cols-[1.1fr_0.9fr]">
                                        <div class="rounded-[24px] border border-[#e6efef] bg-[#f8fbfb] p-5">
                                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[var(--brand-orange-deep)]">Customer and merchant notes</p>
                                            <div class="mt-4 grid gap-3">
                                                <div class="rounded-[18px] bg-white px-4 py-3 ring-1 ring-[#e5eded]">
                                                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-[var(--brand-teal)]">Customer note</p>
                                                    <p class="mt-2 text-sm leading-6 text-slate-600">{{ order.notes.customer || 'No allergy or delivery instructions attached.' }}</p>
                                                </div>

                                                <div class="rounded-[18px] bg-white px-4 py-3 ring-1 ring-[#e5eded]">
                                                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-[var(--brand-teal)]">Driver handoff note</p>
                                                    <p class="mt-2 text-sm leading-6 text-slate-600">{{ order.notes.delivery || 'No rider note yet.' }}</p>
                                                </div>

                                                <div class="rounded-[18px] bg-white px-4 py-3 ring-1 ring-[#e5eded]">
                                                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-[var(--brand-teal)]">Merchant note</p>
                                                    <p class="mt-2 text-sm leading-6 text-slate-600">{{ order.notes.merchant || 'No internal merchant note yet.' }}</p>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="rounded-[24px] border border-[#e6efef] bg-[#fbfdfd] p-5">
                                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[var(--brand-orange-deep)]">Next actions</p>
                                            <div class="mt-4 flex flex-wrap gap-3">
                                                <button
                                                    type="button"
                                                    class="inline-flex items-center justify-center rounded-full border border-[#d6e7e7] bg-white px-4 py-2 text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-700 transition duration-200 hover:-translate-y-0.5"
                                                    @click="openOrderDrawer(order)"
                                                >
                                                    View details
                                                </button>

                                                <button
                                                    v-if="order.actions.canAccept"
                                                    type="button"
                                                    class="inline-flex items-center justify-center rounded-full bg-[var(--brand-teal)] px-4 py-2 text-[11px] font-semibold uppercase tracking-[0.18em] text-white shadow-[0_18px_40px_-26px_rgba(11,77,89,0.72)] transition duration-200 hover:-translate-y-0.5 disabled:opacity-60"
                                                    :disabled="actionIsBusy(order.id)"
                                                    @click="performOrderAction(order, 'merchant.orders.accept')"
                                                >
                                                    Accept order
                                                </button>

                                                <button
                                                    v-if="order.actions.canReject"
                                                    type="button"
                                                    class="inline-flex items-center justify-center rounded-full bg-[#fff2ef] px-4 py-2 text-[11px] font-semibold uppercase tracking-[0.18em] text-[#b44d2d] ring-1 ring-[#f2cabd] transition duration-200 hover:-translate-y-0.5"
                                                    :disabled="actionIsBusy(order.id)"
                                                    @click="openRejectModal(order)"
                                                >
                                                    Reject order
                                                </button>

                                                <button
                                                    v-if="order.actions.canEdit"
                                                    type="button"
                                                    class="inline-flex items-center justify-center rounded-full border border-[#d6e7e7] bg-white px-4 py-2 text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-700 transition duration-200 hover:-translate-y-0.5"
                                                    :disabled="actionIsBusy(order.id)"
                                                    @click="openEditModal(order)"
                                                >
                                                    Edit before prep
                                                </button>

                                                <button
                                                    v-if="order.actions.canStartPreparing"
                                                    type="button"
                                                    class="inline-flex items-center justify-center rounded-full bg-[#fff4dc] px-4 py-2 text-[11px] font-semibold uppercase tracking-[0.18em] text-[#8f5604] ring-1 ring-[#f0d6a8] transition duration-200 hover:-translate-y-0.5 disabled:opacity-60"
                                                    :disabled="actionIsBusy(order.id)"
                                                    @click="performOrderAction(order, 'merchant.orders.start-preparing')"
                                                >
                                                    Start preparing
                                                </button>

                                                <button
                                                    v-if="order.actions.canMarkReady"
                                                    type="button"
                                                    class="inline-flex items-center justify-center rounded-full bg-[var(--brand-orange)] px-4 py-2 text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-950 shadow-[0_18px_40px_-26px_rgba(242,126,33,0.75)] transition duration-200 hover:-translate-y-0.5 disabled:opacity-60"
                                                    :disabled="actionIsBusy(order.id)"
                                                    @click="performOrderAction(order, 'merchant.orders.dispatch')"
                                                >
                                                    Mark ready
                                                </button>

                                                <button
                                                    v-if="order.actions.canCompletePickup"
                                                    type="button"
                                                    class="inline-flex items-center justify-center rounded-full bg-[#e8fbf3] px-4 py-2 text-[11px] font-semibold uppercase tracking-[0.18em] text-[#17734f] ring-1 ring-[#bfe7d4] transition duration-200 hover:-translate-y-0.5 disabled:opacity-60"
                                                    :disabled="actionIsBusy(order.id)"
                                                    @click="performOrderAction(order, 'merchant.orders.complete-pickup')"
                                                >
                                                    Complete pickup
                                                </button>

                                                <a
                                                    v-if="order.destination.mapsUrl"
                                                    :href="order.destination.mapsUrl"
                                                    target="_blank"
                                                    rel="noreferrer"
                                                    class="inline-flex items-center justify-center rounded-full border border-[#d0e2e3] bg-[#f7fbfb] px-4 py-2 text-[11px] font-semibold uppercase tracking-[0.18em] text-[var(--brand-teal)] transition duration-200 hover:-translate-y-0.5"
                                                >
                                                    Open map
                                                </a>
                                            </div>

                                            <div class="mt-5 rounded-[20px] bg-white px-4 py-4 ring-1 ring-[#e5eded]">
                                                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-[var(--brand-teal)]">Fulfillment summary</p>
                                                <p class="mt-2 text-sm leading-6 text-slate-600">{{ order.destination.address }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </article>
                    </div>

                    <div v-else class="mt-6 rounded-[28px] border border-dashed border-[#d8e7e8] bg-white/80 p-8 text-sm leading-6 text-slate-500">
                        No active merchant orders match the current filters. As soon as a customer places or schedules a new order, it will appear here and sync in real time.
                    </div>
                </section>
            </div>
        </div>

        <Modal :show="Boolean(rejectingOrder)" max-width="xl" @close="closeRejectModal">
            <div class="bg-[linear-gradient(145deg,#ffffff_0%,#fff7ef_100%)] p-6 sm:p-8">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-[var(--brand-orange-deep)]">Reject order</p>
                        <h3 class="mt-2 text-2xl font-semibold text-slate-900">{{ rejectingOrder?.orderNumber }}</h3>
                        <p class="mt-2 text-sm leading-6 text-slate-600">Capture a clear reason so operations reporting and future app clients can distinguish capacity issues from inventory problems.</p>
                    </div>

                    <button type="button" class="rounded-full border border-[#d6e7e7] bg-white px-4 py-2 text-xs font-semibold uppercase tracking-[0.16em] text-slate-600" @click="closeRejectModal">
                        Close
                    </button>
                </div>

                <form class="mt-6 space-y-4" @submit.prevent="submitReject">
                    <SelectField
                        id="rejection_reason_code"
                        v-model="rejectForm.rejection_reason_code"
                        label="Reason code"
                        :options="rejectionReasons"
                        :message="rejectForm.errors.rejection_reason_code"
                    />

                    <TextareaField
                        id="rejection_reason_note"
                        v-model="rejectForm.rejection_reason_note"
                        label="Optional note"
                        placeholder="Share any operational detail the customer support team or merchant app should keep."
                        :message="rejectForm.errors.rejection_reason_note"
                    />

                    <div class="flex flex-wrap gap-3 pt-2">
                        <button
                            type="submit"
                            class="inline-flex items-center justify-center rounded-full bg-[#b44d2d] px-5 py-3 text-xs font-semibold uppercase tracking-[0.2em] text-white shadow-[0_18px_40px_-26px_rgba(180,77,45,0.55)] transition duration-200 hover:-translate-y-0.5 disabled:opacity-60"
                            :disabled="rejectForm.processing"
                        >
                            {{ rejectForm.processing ? 'Rejecting…' : 'Confirm rejection' }}
                        </button>

                        <button
                            type="button"
                            class="inline-flex items-center justify-center rounded-full border border-[#d6e7e7] bg-white px-5 py-3 text-xs font-semibold uppercase tracking-[0.18em] text-slate-700"
                            @click="closeRejectModal"
                        >
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </Modal>

        <Modal :show="Boolean(editingOrder)" max-width="2xl" @close="closeEditModal">
            <div class="bg-[linear-gradient(145deg,#ffffff_0%,#f6fbfb_100%)] p-6 sm:p-8">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-[var(--brand-teal)]">Edit order before prep</p>
                        <h3 class="mt-2 text-2xl font-semibold text-slate-900">{{ editingOrder?.orderNumber }}</h3>
                        <p class="mt-2 text-sm leading-6 text-slate-600">Pending and accepted orders can still be corrected before the kitchen starts production.</p>
                    </div>

                    <button type="button" class="rounded-full border border-[#d6e7e7] bg-white px-4 py-2 text-xs font-semibold uppercase tracking-[0.16em] text-slate-600" @click="closeEditModal">
                        Close
                    </button>
                </div>

                <form class="mt-6 grid gap-4 md:grid-cols-2" @submit.prevent="submitEdit">
                    <SelectField
                        id="edit_fulfillment_type"
                        v-model="editForm.fulfillment_type"
                        label="Fulfillment type"
                        :options="fulfillmentOptions.slice(1)"
                        :message="editForm.errors.fulfillment_type"
                    />

                    <TextField
                        id="edit_payment_method"
                        v-model="editForm.payment_method"
                        label="Payment method"
                        placeholder="Cash on delivery, bKash, card"
                        :message="editForm.errors.payment_method"
                    />

                    <TextField
                        v-if="editForm.fulfillment_type === 'delivery'"
                        id="edit_delivery_address"
                        v-model="editForm.delivery_address"
                        label="Delivery address"
                        placeholder="House, road, area, city"
                        :message="editForm.errors.delivery_address"
                        class="md:col-span-2"
                    />

                    <TextField
                        v-if="editForm.fulfillment_type === 'delivery'"
                        id="edit_delivery_latitude"
                        v-model="editForm.delivery_latitude"
                        label="Latitude"
                        placeholder="23.7808874"
                        :message="editForm.errors.delivery_latitude"
                    />

                    <TextField
                        v-if="editForm.fulfillment_type === 'delivery'"
                        id="edit_delivery_longitude"
                        v-model="editForm.delivery_longitude"
                        label="Longitude"
                        placeholder="90.4073486"
                        :message="editForm.errors.delivery_longitude"
                    />

                    <TextField
                        id="edit_scheduled_for"
                        v-model="editForm.scheduled_for"
                        type="datetime-local"
                        label="Scheduled for"
                        :message="editForm.errors.scheduled_for"
                    />

                    <TextareaField
                        id="edit_customer_notes"
                        v-model="editForm.customer_notes"
                        label="Customer notes"
                        :message="editForm.errors.customer_notes"
                        class="md:col-span-2"
                    />

                    <TextareaField
                        id="edit_driver_notes"
                        v-model="editForm.driver_notes"
                        label="Driver notes"
                        :message="editForm.errors.driver_notes"
                    />

                    <TextareaField
                        id="edit_merchant_notes"
                        v-model="editForm.merchant_notes"
                        label="Merchant notes"
                        :message="editForm.errors.merchant_notes"
                    />

                    <div class="md:col-span-2 flex flex-wrap gap-3 pt-2">
                        <button
                            type="submit"
                            class="inline-flex items-center justify-center rounded-full bg-[var(--brand-teal)] px-5 py-3 text-xs font-semibold uppercase tracking-[0.2em] text-white shadow-[0_18px_40px_-26px_rgba(11,77,89,0.72)] transition duration-200 hover:-translate-y-0.5 disabled:opacity-60"
                            :disabled="editForm.processing"
                        >
                            {{ editForm.processing ? 'Saving…' : 'Save order changes' }}
                        </button>

                        <button
                            type="button"
                            class="inline-flex items-center justify-center rounded-full border border-[#d6e7e7] bg-white px-5 py-3 text-xs font-semibold uppercase tracking-[0.18em] text-slate-700"
                            @click="closeEditModal"
                        >
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </Modal>

        <SlideOver :show="Boolean(focusedOrder)" max-width="2xl" @close="closeOrderDrawer">
            <div class="min-h-full bg-[linear-gradient(180deg,#ffffff_0%,#f7fbfb_100%)]">
                <div class="border-b border-[#e3ecec] px-6 py-5 sm:px-8">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-[var(--brand-orange-deep)]">Order detail drawer</p>
                            <h3 class="mt-2 text-2xl font-semibold text-slate-900">{{ focusedOrder?.orderNumber }}</h3>
                            <div class="mt-3 flex flex-wrap items-center gap-3">
                                <span :class="focusedOrder?.statusAccent" class="rounded-full px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] ring-1">
                                    {{ focusedOrder?.statusLabel }}
                                </span>
                                <span class="rounded-full bg-[#f6fbfb] px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-[var(--brand-teal)] ring-1 ring-[#d8eaea]">
                                    {{ focusedOrder?.fulfillment.label }}
                                </span>
                                <span v-if="focusedOrder?.isScheduled" class="rounded-full bg-[#f7f1ff] px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-[#7d4aa5] ring-1 ring-[#e6d7f4]">
                                    Scheduled {{ focusedOrder?.scheduledFor }}
                                </span>
                            </div>
                        </div>

                        <button type="button" class="rounded-full border border-[#d6e7e7] bg-white px-4 py-2 text-xs font-semibold uppercase tracking-[0.16em] text-slate-600" @click="closeOrderDrawer">
                            Close
                        </button>
                    </div>
                </div>

                <div class="space-y-6 px-6 py-6 sm:px-8">
                    <section class="rounded-[28px] border border-[#e5eded] bg-white p-5 shadow-[0_20px_55px_-42px_rgba(11,77,89,0.35)]">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[var(--brand-teal)]">Merchant summary</p>
                                <h4 class="mt-2 text-lg font-semibold text-slate-900">{{ focusedOrder?.restaurantName }}</h4>
                                <p class="mt-1 text-sm leading-6 text-slate-600">{{ focusedOrder?.summary }}</p>
                            </div>

                            <div class="rounded-[22px] bg-[#f8fbfb] px-4 py-4 ring-1 ring-[#e1ecec]">
                                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-[var(--brand-teal)]">Customer</p>
                                <p class="mt-2 text-sm font-medium text-slate-900">{{ focusedOrder?.customerName }}</p>
                                <p class="mt-1 text-xs text-slate-500">Placed {{ focusedOrder?.placedAt }}</p>
                            </div>
                        </div>

                        <div class="mt-5 flex flex-wrap gap-3">
                            <button
                                v-if="focusedOrder?.actions.canAccept"
                                type="button"
                                class="inline-flex items-center justify-center rounded-full bg-[var(--brand-teal)] px-4 py-2 text-[11px] font-semibold uppercase tracking-[0.18em] text-white shadow-[0_18px_40px_-26px_rgba(11,77,89,0.72)] transition duration-200 hover:-translate-y-0.5 disabled:opacity-60"
                                :disabled="actionIsBusy(focusedOrder.id)"
                                @click="performOrderAction(focusedOrder, 'merchant.orders.accept')"
                            >
                                Accept
                            </button>

                            <button
                                v-if="focusedOrder?.actions.canReject"
                                type="button"
                                class="inline-flex items-center justify-center rounded-full bg-[#fff2ef] px-4 py-2 text-[11px] font-semibold uppercase tracking-[0.18em] text-[#b44d2d] ring-1 ring-[#f2cabd] transition duration-200 hover:-translate-y-0.5"
                                :disabled="actionIsBusy(focusedOrder.id)"
                                @click="openRejectModal(focusedOrder)"
                            >
                                Reject
                            </button>

                            <button
                                v-if="focusedOrder?.actions.canEdit"
                                type="button"
                                class="inline-flex items-center justify-center rounded-full border border-[#d6e7e7] bg-white px-4 py-2 text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-700 transition duration-200 hover:-translate-y-0.5"
                                :disabled="actionIsBusy(focusedOrder.id)"
                                @click="openEditModal(focusedOrder)"
                            >
                                Edit
                            </button>

                            <button
                                v-if="focusedOrder?.actions.canStartPreparing"
                                type="button"
                                class="inline-flex items-center justify-center rounded-full bg-[#fff4dc] px-4 py-2 text-[11px] font-semibold uppercase tracking-[0.18em] text-[#8f5604] ring-1 ring-[#f0d6a8] transition duration-200 hover:-translate-y-0.5 disabled:opacity-60"
                                :disabled="actionIsBusy(focusedOrder.id)"
                                @click="performOrderAction(focusedOrder, 'merchant.orders.start-preparing')"
                            >
                                Start prep
                            </button>

                            <button
                                v-if="focusedOrder?.actions.canMarkReady"
                                type="button"
                                class="inline-flex items-center justify-center rounded-full bg-[var(--brand-orange)] px-4 py-2 text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-950 shadow-[0_18px_40px_-26px_rgba(242,126,33,0.75)] transition duration-200 hover:-translate-y-0.5 disabled:opacity-60"
                                :disabled="actionIsBusy(focusedOrder.id)"
                                @click="performOrderAction(focusedOrder, 'merchant.orders.dispatch')"
                            >
                                Mark ready
                            </button>

                            <button
                                v-if="focusedOrder?.actions.canCompletePickup"
                                type="button"
                                class="inline-flex items-center justify-center rounded-full bg-[#e8fbf3] px-4 py-2 text-[11px] font-semibold uppercase tracking-[0.18em] text-[#17734f] ring-1 ring-[#bfe7d4] transition duration-200 hover:-translate-y-0.5 disabled:opacity-60"
                                :disabled="actionIsBusy(focusedOrder.id)"
                                @click="performOrderAction(focusedOrder, 'merchant.orders.complete-pickup')"
                            >
                                Complete pickup
                            </button>

                            <a
                                v-if="focusedOrder?.destination.mapsUrl"
                                :href="focusedOrder.destination.mapsUrl"
                                target="_blank"
                                rel="noreferrer"
                                class="inline-flex items-center justify-center rounded-full border border-[#d0e2e3] bg-[#f7fbfb] px-4 py-2 text-[11px] font-semibold uppercase tracking-[0.18em] text-[var(--brand-teal)] transition duration-200 hover:-translate-y-0.5"
                            >
                                Open map
                            </a>
                        </div>
                    </section>

                    <section class="grid gap-4 xl:grid-cols-[1.1fr_0.9fr]">
                        <div class="space-y-4">
                            <div class="rounded-[28px] border border-[#e5eded] bg-white p-5 shadow-[0_20px_55px_-42px_rgba(11,77,89,0.35)]">
                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[var(--brand-orange-deep)]">Order items</p>
                                <div class="mt-4 space-y-3">
                                    <div v-for="item in focusedOrder?.items ?? []" :key="`${focusedOrder?.id}-${item.name}-${item.quantity}`" class="rounded-[20px] bg-[#f8fbfb] px-4 py-4 ring-1 ring-[#e5eded]">
                                        <div class="flex items-start justify-between gap-4">
                                            <div>
                                                <p class="text-sm font-semibold text-slate-900">{{ item.quantity }}x {{ item.name }}</p>
                                                <p class="mt-1 text-xs text-slate-500">Unit {{ item.unitPrice }}</p>
                                            </div>
                                            <p class="text-sm font-semibold text-slate-900">{{ item.lineTotal }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="rounded-[28px] border border-[#e5eded] bg-white p-5 shadow-[0_20px_55px_-42px_rgba(11,77,89,0.35)]">
                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[var(--brand-orange-deep)]">Notes and exceptions</p>
                                <div class="mt-4 space-y-3">
                                    <div class="rounded-[20px] bg-[#f8fbfb] px-4 py-4 ring-1 ring-[#e5eded]">
                                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-[var(--brand-teal)]">Customer note</p>
                                        <p class="mt-2 text-sm leading-6 text-slate-600">{{ focusedOrder?.notes.customer || 'No allergy or delivery instruction attached.' }}</p>
                                    </div>
                                    <div class="rounded-[20px] bg-[#f8fbfb] px-4 py-4 ring-1 ring-[#e5eded]">
                                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-[var(--brand-teal)]">Driver note</p>
                                        <p class="mt-2 text-sm leading-6 text-slate-600">{{ focusedOrder?.notes.delivery || 'No rider-specific note attached.' }}</p>
                                    </div>
                                    <div class="rounded-[20px] bg-[#f8fbfb] px-4 py-4 ring-1 ring-[#e5eded]">
                                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-[var(--brand-teal)]">Merchant note</p>
                                        <p class="mt-2 text-sm leading-6 text-slate-600">{{ focusedOrder?.notes.merchant || 'No internal merchant note yet.' }}</p>
                                    </div>
                                    <div v-if="focusedOrder?.rejection.code" class="rounded-[20px] bg-[#fff7f3] px-4 py-4 ring-1 ring-[#f2d3c8]">
                                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-[#b95a3c]">Rejection</p>
                                        <p class="mt-2 text-sm font-medium text-slate-900">{{ focusedOrder?.rejection.label }}</p>
                                        <p class="mt-1 text-sm leading-6 text-slate-600">{{ focusedOrder?.rejection.note || 'No additional note provided.' }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <div class="rounded-[28px] border border-[#e5eded] bg-white p-5 shadow-[0_20px_55px_-42px_rgba(11,77,89,0.35)]">
                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[var(--brand-orange-deep)]">Fulfillment and payment</p>
                                <div class="mt-4 grid gap-3">
                                    <div class="rounded-[20px] bg-[#f8fbfb] px-4 py-4 ring-1 ring-[#e5eded]">
                                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-[var(--brand-teal)]">Destination</p>
                                        <p class="mt-2 text-sm leading-6 text-slate-700">{{ focusedOrder?.destination.address }}</p>
                                    </div>
                                    <div v-if="focusedOrder?.destination.coordinatesLabel" class="rounded-[20px] bg-[#f8fbfb] px-4 py-4 ring-1 ring-[#e5eded]">
                                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-[var(--brand-teal)]">Coordinates</p>
                                        <p class="mt-2 text-sm font-medium text-slate-900">{{ focusedOrder?.destination.coordinatesLabel }}</p>
                                    </div>
                                    <div class="rounded-[20px] bg-[#f8fbfb] px-4 py-4 ring-1 ring-[#e5eded]">
                                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-[var(--brand-teal)]">Payment</p>
                                        <p class="mt-2 text-sm font-medium text-slate-900">{{ focusedOrder?.paymentMethod }}</p>
                                        <p class="mt-1 text-xs text-slate-500">Order total {{ focusedOrder?.total }}</p>
                                    </div>
                                    <div v-if="focusedOrder?.scheduledForDate" class="rounded-[20px] bg-[#f8fbfb] px-4 py-4 ring-1 ring-[#e5eded]">
                                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-[var(--brand-teal)]">Scheduled handoff</p>
                                        <p class="mt-2 text-sm font-medium text-slate-900">{{ focusedOrder?.scheduledForDate }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="rounded-[28px] border border-[#e5eded] bg-white p-5 shadow-[0_20px_55px_-42px_rgba(11,77,89,0.35)]">
                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[var(--brand-orange-deep)]">Lifecycle timeline</p>
                                <div class="mt-4 space-y-3">
                                    <div v-for="entry in focusedOrder?.timeline ?? []" :key="`${focusedOrder?.id}-${entry.key}`" class="flex items-start gap-3 rounded-[20px] bg-[#f8fbfb] px-4 py-4 ring-1 ring-[#e5eded]">
                                        <div class="mt-1 h-2.5 w-2.5 rounded-full bg-[var(--brand-orange)]" />
                                        <div>
                                            <p class="text-sm font-semibold text-slate-900">{{ entry.label }}</p>
                                            <p class="mt-1 text-sm text-slate-600">{{ entry.relative }}</p>
                                            <p class="mt-1 text-xs text-slate-500">{{ entry.date }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </SlideOver>
    </MerchantLayout>
</template>