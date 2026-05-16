<script setup>
import CashierLayout from '@/Layouts/CashierLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { computed, reactive, ref, watch } from 'vue';

const props = defineProps({
    restaurant: { type: Object, required: true },
    restaurants: { type: Array, default: () => [] },
    menuItems: { type: Array, default: () => [] },
    categories: { type: Array, default: () => [] },
    lastPlacedOrder: { type: Object, default: null },
    discountRates: { type: Object, default: () => ({ sc: 20, pwd: 20 }) },
});

// ─── helpers ────────────────────────────────────────────────────────────────
const fmt = (n) => '₱' + Number(n || 0).toLocaleString('en-PH');

// ─── menu browsing ──────────────────────────────────────────────────────────
const search = ref('');
const activeCategory = ref('all');

const filteredItems = computed(() => {
    const q = search.value.trim().toLowerCase();
    return props.menuItems.filter((item) => {
        const matchCat =
            activeCategory.value === 'all' || item.category === activeCategory.value;
        const matchSearch =
            !q ||
            item.name.toLowerCase().includes(q) ||
            (item.category ?? '').toLowerCase().includes(q);
        return matchCat && matchSearch;
    });
});

// ─── cart ────────────────────────────────────────────────────────────────────
const cart = reactive({}); // { [menuItemId]: qty }

const cartItems = computed(() =>
    Object.entries(cart)
        .filter(([, qty]) => qty > 0)
        .map(([id, qty]) => {
            const item = props.menuItems.find((m) => String(m.id) === id);
            if (!item) return null;
            const unitPrice = item.promoPrice ?? item.price;
            return { id: item.id, name: item.name, unitPrice, quantity: qty, lineTotal: unitPrice * qty };
        })
        .filter(Boolean),
);

const subtotal = computed(() => cartItems.value.reduce((s, i) => s + i.lineTotal, 0));

// ─── SC / PWD discount ─────────────────────────────────────────────────────────────────────
const discountType = ref(null);       // null | 'sc' | 'pwd'
const discountIdInput = ref('');
const discountApplied = ref(false);
const discountIdApplied = ref('');
const showDiscountPanel = ref(false);

const discountPct = computed(() =>
    discountApplied.value && discountType.value
        ? (props.discountRates[discountType.value] ?? 20)
        : 0,
);
const discountAmount = computed(() =>
    discountApplied.value ? Math.round(subtotal.value * (discountPct.value / 100)) : 0,
);
const total = computed(() => subtotal.value - discountAmount.value);

function applyDiscount() {
    if (!discountType.value || !discountIdInput.value.trim()) return;
    discountApplied.value = true;
    discountIdApplied.value = discountIdInput.value.trim();
    showDiscountPanel.value = false;
}

function removeDiscount() {
    discountApplied.value = false;
    discountType.value = null;
    discountIdInput.value = '';
    discountIdApplied.value = '';
    showDiscountPanel.value = false;
}

function addItem(item) {
    const id = String(item.id);
    cart[id] = (cart[id] || 0) + 1;
}

function setQty(id, qty) {
    const k = String(id);
    if (qty < 1) {
        delete cart[k];
    } else {
        cart[k] = qty;
    }
}

function removeItem(id) {
    delete cart[String(id)];
}

function clearCart() {
    Object.keys(cart).forEach((k) => delete cart[k]);
    removeDiscount();
}

// ─── order form ──────────────────────────────────────────────────────────────
const orderType = ref('dine_in');
const customerName = ref('');
const tableNumber = ref('');
const orderNotes = ref('');
const paymentMethod = ref('cash');
const cashReceived = ref('');
const formErrors = ref({});

const change = computed(() => {
    if (paymentMethod.value !== 'cash') return null;
    const cash = parseFloat(cashReceived.value) || 0;
    return Math.max(0, cash - total.value);
});

const paymentMethods = [
    { value: 'cash', label: 'Cash' },
    { value: 'card', label: 'Card' },
    { value: 'gcash', label: 'GCash' },
    { value: 'maya', label: 'Maya' },
];

// ─── place order ─────────────────────────────────────────────────────────────
const isPlacing = ref(false);
const showReceipt = ref(false);
const receiptData = ref(null);

// snapshot the cart state before submitting so we can show a full receipt
function placeOrder() {
    if (cartItems.value.length === 0 || isPlacing.value) return;

    formErrors.value = {};
    const snapshot = {
        items: cartItems.value.map((i) => ({ ...i })),
        subtotal: subtotal.value,
        discountType: discountType.value,
        discountPct: discountPct.value,
        discountAmount: discountAmount.value,
        discountId: discountIdApplied.value,
        total: total.value,
        orderType: orderType.value,
        customerName: customerName.value,
        tableNumber: tableNumber.value,
        paymentMethod: paymentMethod.value,
        cashReceived: parseFloat(cashReceived.value) || 0,
        change: change.value ?? 0,
    };

    isPlacing.value = true;

    router.post(
        route('merchant.cashier.orders.store'),
        {
            restaurant_id: props.restaurant.id,
            fulfillment_type: orderType.value,
            customer_name: customerName.value || null,
            table_number: tableNumber.value || null,
            payment_method: paymentMethod.value,
            notes: orderNotes.value || null,
            discount_type: discountType.value || null,
            discount_id: discountIdApplied.value || null,
            items: cartItems.value.map((i) => ({
                menu_item_id: i.id,
                quantity: i.quantity,
                unit_price: i.unitPrice,
            })),
        },
        {
            preserveState: true,
            onSuccess: () => {
                receiptData.value = {
                    ...snapshot,
                    orderNumber: props.lastPlacedOrder?.orderNumber ?? '—',
                };
                showReceipt.value = true;
                clearCart();
                customerName.value = '';
                tableNumber.value = '';
                cashReceived.value = '';
                orderNotes.value = '';
                isPlacing.value = false;
            },
            onError: (errors) => {
                formErrors.value = errors;
                isPlacing.value = false;
            },
        },
    );
}

// if lastPlacedOrder arrives as a prop (e.g. after page refresh), clear it
watch(
    () => props.lastPlacedOrder,
    (val) => {
        if (val && !showReceipt.value) {
            receiptData.value = { orderNumber: val.orderNumber, items: [], total: 0 };
            showReceipt.value = true;
        }
    },
);

// ─── receipt dismiss ─────────────────────────────────────────────────────────
function startNewOrder() {
    showReceipt.value = false;
    receiptData.value = null;
}
</script>

<template>
    <CashierLayout>
        <template #restaurant-name>
            <p class="text-sm font-semibold text-white">{{ restaurant.name }}</p>
        </template>

        <Head :title="`POS — ${restaurant.name}`" />

        <!-- ══════════════════════════════════════════════════════════════
             Two-panel POS layout
        ═══════════════════════════════════════════════════════════════ -->
        <div class="flex h-full overflow-hidden">

            <!-- ┌─────────────────────────────────────────────────────────
                 LEFT — Menu Browser
            ──────────────────────────────────────────────────────────── -->
            <div class="flex min-w-0 flex-1 flex-col overflow-hidden">

                <!-- Search + Categories -->
                <div class="shrink-0 border-b border-[#e8e3da] bg-white px-4 pb-3 pt-4 shadow-sm">
                    <!-- Search -->
                    <div class="relative mb-3">
                        <svg
                            class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
                            fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11A6 6 0 115 11a6 6 0 0112 0z" />
                        </svg>
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Search menu items…"
                            class="w-full rounded-xl border border-[#e4ddd4] bg-[#faf7f3] py-2.5 pl-10 pr-4 text-sm text-slate-800 placeholder-slate-400 outline-none transition focus:border-[#0b4d59] focus:ring-2 focus:ring-[#0b4d59]/15"
                        />
                        <button
                            v-if="search"
                            @click="search = ''"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600"
                        >
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Category tabs -->
                    <div class="flex gap-1.5 overflow-x-auto pb-0.5 scrollbar-none">
                        <button
                            @click="activeCategory = 'all'"
                            :class="[
                                'shrink-0 rounded-full px-3.5 py-1.5 text-xs font-semibold transition',
                                activeCategory === 'all'
                                    ? 'bg-[#0b4d59] text-white shadow-sm'
                                    : 'bg-[#f0ece6] text-slate-600 hover:bg-[#e8e3da]',
                            ]"
                        >
                            All Items
                            <span
                                :class="activeCategory === 'all' ? 'bg-white/20 text-white' : 'bg-white text-slate-500'"
                                class="ml-1.5 rounded-full px-1.5 py-0.5 text-[10px] font-bold"
                            >{{ props.menuItems.length }}</span>
                        </button>
                        <button
                            v-for="cat in categories"
                            :key="cat"
                            @click="activeCategory = cat"
                            :class="[
                                'shrink-0 rounded-full px-3.5 py-1.5 text-xs font-semibold transition',
                                activeCategory === cat
                                    ? 'bg-[#0b4d59] text-white shadow-sm'
                                    : 'bg-[#f0ece6] text-slate-600 hover:bg-[#e8e3da]',
                            ]"
                        >{{ cat }}</button>
                    </div>
                </div>

                <!-- Item grid -->
                <div class="flex-1 overflow-y-auto p-4">
                    <!-- Empty state -->
                    <div
                        v-if="filteredItems.length === 0"
                        class="flex h-full flex-col items-center justify-center gap-3 text-center"
                    >
                        <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-[#e8e3da]">
                            <svg class="h-7 w-7 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11A6 6 0 115 11a6 6 0 0112 0z" />
                            </svg>
                        </div>
                        <p class="text-sm font-medium text-slate-500">No items found</p>
                        <button @click="search = ''; activeCategory = 'all'" class="text-xs text-[#0b4d59] underline">
                            Clear filters
                        </button>
                    </div>

                    <!-- Items grid -->
                    <div v-else class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-3 xl:grid-cols-4">
                        <button
                            v-for="item in filteredItems"
                            :key="item.id"
                            type="button"
                            @click="addItem(item)"
                            :class="[
                                'group relative flex flex-col overflow-hidden rounded-2xl border bg-white text-left transition',
                                (cart[String(item.id)] || 0) > 0
                                    ? 'border-[#0b4d59]/30 shadow-[0_0_0_2px_rgba(11,77,89,0.12)] ring-1 ring-[#0b4d59]/15'
                                    : 'border-[#e8e3da] shadow-sm hover:border-[#c5bdb5] hover:shadow-md',
                            ]"
                        >
                            <!-- Image -->
                            <div class="relative aspect-[4/3] w-full overflow-hidden bg-[#f3efe9]">
                                <img
                                    :src="item.imageUrl"
                                    :alt="item.name"
                                    class="h-full w-full object-cover transition group-hover:scale-105"
                                    loading="lazy"
                                />
                                <!-- Cart badge -->
                                <div
                                    v-if="(cart[String(item.id)] || 0) > 0"
                                    class="absolute right-2 top-2 flex h-6 w-6 items-center justify-center rounded-full bg-[#0b4d59] text-xs font-bold text-white shadow-md"
                                >{{ cart[String(item.id)] }}</div>
                            </div>

                            <!-- Info -->
                            <div class="flex flex-1 flex-col p-3">
                                <p class="mb-1 text-[9px] font-bold uppercase tracking-[0.18em] text-[#e87c2a]">
                                    {{ item.category }}
                                </p>
                                <p class="flex-1 text-sm font-semibold leading-tight text-slate-900 line-clamp-2">
                                    {{ item.name }}
                                </p>

                                <div class="mt-2.5 flex items-center justify-between gap-2">
                                    <!-- Price -->
                                    <div>
                                        <span class="text-sm font-bold text-[#0b4d59]">
                                            {{ fmt(item.promoPrice ?? item.price) }}
                                        </span>
                                        <span
                                            v-if="item.promoPrice"
                                            class="ml-1 text-xs text-slate-400 line-through"
                                        >{{ fmt(item.price) }}</span>
                                    </div>

                                    <!-- Qty control (when in cart) or Add button -->
                                    <div
                                        v-if="(cart[String(item.id)] || 0) > 0"
                                        class="flex items-center gap-1"
                                        @click.stop
                                    >
                                        <button
                                            @click="setQty(item.id, cart[String(item.id)] - 1)"
                                            class="flex h-6 w-6 items-center justify-center rounded-lg bg-[#f0ece6] text-sm font-bold text-slate-700 transition hover:bg-[#e0d8d0]"
                                        >−</button>
                                        <span class="w-5 text-center text-sm font-bold text-slate-900">
                                            {{ cart[String(item.id)] }}
                                        </span>
                                        <button
                                            @click="setQty(item.id, cart[String(item.id)] + 1)"
                                            class="flex h-6 w-6 items-center justify-center rounded-lg bg-[#0b4d59] text-sm font-bold text-white transition hover:bg-[#093e48]"
                                        >+</button>
                                    </div>
                                    <div v-else class="flex h-6 w-6 items-center justify-center rounded-lg bg-[#0b4d59] text-base font-bold text-white transition group-hover:bg-[#093e48]">
                                        +
                                    </div>
                                </div>
                            </div>
                        </button>
                    </div>
                </div>
            </div>

            <!-- ┌─────────────────────────────────────────────────────────
                 RIGHT — Order Panel
            ──────────────────────────────────────────────────────────── -->
            <div class="flex w-[340px] shrink-0 flex-col border-l border-[#e8e3da] bg-white shadow-[-4px_0_20px_-8px_rgba(11,77,89,0.08)] xl:w-[380px]">

                <!-- Panel header: type toggle + clear -->
                <div class="shrink-0 border-b border-[#f0ece6] px-5 pb-4 pt-4">
                    <div class="mb-3 flex items-center justify-between">
                        <h2 class="text-base font-bold text-slate-900">New Order</h2>
                        <button
                            v-if="cartItems.length > 0"
                            @click="clearCart"
                            class="text-xs font-semibold text-slate-400 transition hover:text-red-500"
                        >Clear all</button>
                    </div>

                    <!-- Dine-in / Take-out toggle -->
                    <div class="flex overflow-hidden rounded-xl border border-[#e4ddd4] bg-[#f5f0ea] p-0.5">
                        <button
                            @click="orderType = 'dine_in'"
                            :class="[
                                'flex flex-1 items-center justify-center gap-1.5 rounded-[10px] py-2 text-xs font-semibold transition',
                                orderType === 'dine_in'
                                    ? 'bg-[#0b4d59] text-white shadow-sm'
                                    : 'text-slate-500 hover:text-slate-700',
                            ]"
                        >
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M3 6h18M3 14h18M3 18h18" />
                            </svg>
                            Dine-In
                        </button>
                        <button
                            @click="orderType = 'pickup'"
                            :class="[
                                'flex flex-1 items-center justify-center gap-1.5 rounded-[10px] py-2 text-xs font-semibold transition',
                                orderType === 'pickup'
                                    ? 'bg-[#0b4d59] text-white shadow-sm'
                                    : 'text-slate-500 hover:text-slate-700',
                            ]"
                        >
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                            </svg>
                            Take-Out
                        </button>
                    </div>
                </div>

                <!-- Customer info -->
                <div class="shrink-0 border-b border-[#f0ece6] px-5 py-3">
                    <div :class="['grid gap-2.5', orderType === 'dine_in' ? 'grid-cols-2' : 'grid-cols-1']">
                        <div v-if="orderType === 'dine_in'">
                            <label class="mb-1 block text-[10px] font-bold uppercase tracking-[0.14em] text-slate-500">
                                Table No.
                            </label>
                            <input
                                v-model="tableNumber"
                                type="text"
                                placeholder="e.g. T-05"
                                class="w-full rounded-xl border border-[#e4ddd4] bg-[#faf7f3] px-3 py-2 text-sm text-slate-800 placeholder-slate-400 outline-none transition focus:border-[#0b4d59] focus:ring-2 focus:ring-[#0b4d59]/15"
                            />
                        </div>
                        <div>
                            <label class="mb-1 block text-[10px] font-bold uppercase tracking-[0.14em] text-slate-500">
                                Customer Name
                            </label>
                            <input
                                v-model="customerName"
                                type="text"
                                placeholder="Optional"
                                class="w-full rounded-xl border border-[#e4ddd4] bg-[#faf7f3] px-3 py-2 text-sm text-slate-800 placeholder-slate-400 outline-none transition focus:border-[#0b4d59] focus:ring-2 focus:ring-[#0b4d59]/15"
                            />
                        </div>
                    </div>
                </div>

                <!-- Order items list -->
                <div class="min-h-0 flex-1 overflow-y-auto px-5 py-3">
                    <!-- Empty cart state -->
                    <div
                        v-if="cartItems.length === 0"
                        class="flex h-full flex-col items-center justify-center gap-3 py-8 text-center"
                    >
                        <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-[#f0ece6]">
                            <svg class="h-7 w-7 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-slate-600">No items yet</p>
                            <p class="mt-0.5 text-xs text-slate-400">Tap a menu item to add it</p>
                        </div>
                    </div>

                    <!-- Cart items -->
                    <div v-else class="space-y-1">
                        <div
                            v-for="item in cartItems"
                            :key="item.id"
                            class="flex items-center gap-2 rounded-xl px-2 py-2.5 transition hover:bg-[#faf7f3]"
                        >
                            <!-- Name + unit price -->
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-semibold text-slate-900">{{ item.name }}</p>
                                <p class="text-xs text-slate-400">{{ fmt(item.unitPrice) }} each</p>
                            </div>

                            <!-- Qty control -->
                            <div class="flex shrink-0 items-center gap-1">
                                <button
                                    @click="setQty(item.id, item.quantity - 1)"
                                    class="flex h-6 w-6 items-center justify-center rounded-lg border border-[#e4ddd4] bg-[#f5f0ea] text-xs font-bold text-slate-700 transition hover:bg-[#e8e3da]"
                                >−</button>
                                <span class="w-6 text-center text-sm font-bold text-slate-900">{{ item.quantity }}</span>
                                <button
                                    @click="setQty(item.id, item.quantity + 1)"
                                    class="flex h-6 w-6 items-center justify-center rounded-lg bg-[#0b4d59] text-xs font-bold text-white transition hover:bg-[#093e48]"
                                >+</button>
                            </div>

                            <!-- Line total -->
                            <span class="w-16 shrink-0 text-right text-sm font-semibold text-slate-900">
                                {{ fmt(item.lineTotal) }}
                            </span>

                            <!-- Remove -->
                            <button
                                @click="removeItem(item.id)"
                                class="flex h-6 w-6 shrink-0 items-center justify-center rounded-lg text-slate-300 transition hover:bg-red-50 hover:text-red-500"
                            >
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Totals + Payment + Place Order -->
                <div class="shrink-0 border-t border-[#e8e3da] bg-white px-5 pb-5 pt-4">

                    <!-- SC / PWD Discount -->
                    <div class="mb-3">
                        <!-- Applied badge -->
                        <div
                            v-if="discountApplied"
                            class="flex items-center justify-between rounded-xl bg-emerald-50 px-3 py-2 ring-1 ring-inset ring-emerald-200"
                        >
                            <div class="flex items-center gap-2">
                                <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-emerald-700">
                                    {{ discountType === 'sc' ? 'Senior Citizen' : 'PWD' }}
                                </span>
                                <span class="text-xs text-emerald-700">ID: {{ discountIdApplied }}</span>
                                <span class="text-xs font-semibold text-emerald-600">&middot; {{ discountPct }}% off</span>
                            </div>
                            <button @click="removeDiscount" class="ml-2 text-emerald-400 transition hover:text-red-500">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <!-- Toggle button (panel closed) -->
                        <button
                            v-else-if="!showDiscountPanel"
                            @click="showDiscountPanel = true; if (!discountType) discountType = 'sc'"
                            class="flex w-full items-center justify-center gap-1.5 rounded-xl border border-dashed border-[#c5bdb5] py-2 text-xs font-semibold text-slate-500 transition hover:border-[#0b4d59] hover:text-[#0b4d59]"
                        >
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                            </svg>
                            Apply SC / PWD Discount
                        </button>

                        <!-- Input panel -->
                        <div v-else class="overflow-hidden rounded-xl border border-[#e4ddd4] bg-[#faf7f3] p-3">
                            <p class="mb-2 text-[10px] font-bold uppercase tracking-[0.14em] text-slate-500">Discount Type</p>
                            <div class="mb-3 flex gap-2">
                                <button
                                    @click="discountType = 'sc'"
                                    :class="['flex-1 rounded-lg py-1.5 text-xs font-semibold transition', discountType === 'sc' ? 'bg-[#0b4d59] text-white' : 'border border-[#e4ddd4] bg-white text-slate-600 hover:bg-[#f0ece6]']"
                                >Senior Citizen</button>
                                <button
                                    @click="discountType = 'pwd'"
                                    :class="['flex-1 rounded-lg py-1.5 text-xs font-semibold transition', discountType === 'pwd' ? 'bg-[#0b4d59] text-white' : 'border border-[#e4ddd4] bg-white text-slate-600 hover:bg-[#f0ece6]']"
                                >PWD</button>
                            </div>
                            <p class="mb-1.5 text-[10px] font-bold uppercase tracking-[0.14em] text-slate-500">
                                {{ discountType === 'sc' ? 'Senior Citizen' : 'PWD' }} ID Number
                            </p>
                            <div class="flex gap-2">
                                <input
                                    v-model="discountIdInput"
                                    type="text"
                                    :placeholder="discountType === 'sc' ? 'e.g. SC-0012345' : 'e.g. PWD-0012345'"
                                    maxlength="50"
                                    @keydown.enter="applyDiscount"
                                    class="min-w-0 flex-1 rounded-xl border border-[#e4ddd4] bg-white px-3 py-2 text-sm text-slate-800 placeholder-slate-400 outline-none transition focus:border-[#0b4d59] focus:ring-2 focus:ring-[#0b4d59]/15"
                                />
                                <button
                                    @click="applyDiscount"
                                    :disabled="!discountIdInput.trim()"
                                    class="shrink-0 rounded-xl bg-[#0b4d59] px-3 py-2 text-xs font-semibold text-white transition hover:bg-[#093e48] disabled:cursor-not-allowed disabled:opacity-40"
                                >Apply</button>
                                <button
                                    @click="showDiscountPanel = false; discountIdInput = ''"
                                    class="shrink-0 rounded-xl border border-[#e4ddd4] bg-white px-3 py-2 text-xs font-semibold text-slate-500 transition hover:bg-[#f0ece6]"
                                >Cancel</button>
                            </div>
                        </div>
                    </div>

                    <!-- Totals -->
                    <div class="mb-4 space-y-1.5 text-sm">
                        <div class="flex items-center justify-between text-slate-500">
                            <span>Subtotal</span>
                            <span>{{ fmt(subtotal) }}</span>
                        </div>
                        <div v-if="discountApplied" class="flex items-center justify-between text-emerald-600">
                            <span>{{ discountType === 'sc' ? 'SC' : 'PWD' }} Discount ({{ discountPct }}%)</span>
                            <span>&minus;{{ fmt(discountAmount) }}</span>
                        </div>
                        <div class="flex items-center justify-between border-t border-[#f0ece6] pt-2 text-[15px] font-bold text-[#0b4d59]">
                            <span>TOTAL</span>
                            <span>{{ fmt(total) }}</span>
                        </div>
                    </div>

                    <!-- Payment method -->
                    <div class="mb-3">
                        <p class="mb-1.5 text-[10px] font-bold uppercase tracking-[0.14em] text-slate-500">
                            Payment Method
                        </p>
                        <div class="grid grid-cols-4 gap-1.5">
                            <button
                                v-for="m in paymentMethods"
                                :key="m.value"
                                @click="paymentMethod = m.value"
                                :class="[
                                    'rounded-xl py-2 text-xs font-semibold transition',
                                    paymentMethod === m.value
                                        ? 'bg-[#0b4d59] text-white shadow-sm'
                                        : 'border border-[#e4ddd4] bg-[#f5f0ea] text-slate-600 hover:bg-[#e8e3da]',
                                ]"
                            >{{ m.label }}</button>
                        </div>
                    </div>

                    <!-- Cash received + change -->
                    <div v-if="paymentMethod === 'cash'" class="mb-3 grid grid-cols-2 gap-2.5">
                        <div>
                            <label class="mb-1 block text-[10px] font-bold uppercase tracking-[0.14em] text-slate-500">
                                Cash Received
                            </label>
                            <input
                                v-model="cashReceived"
                                type="number"
                                min="0"
                                step="1"
                                placeholder="₱0"
                                class="w-full rounded-xl border border-[#e4ddd4] bg-[#faf7f3] px-3 py-2 text-sm font-semibold text-slate-800 outline-none transition focus:border-[#0b4d59] focus:ring-2 focus:ring-[#0b4d59]/15"
                            />
                        </div>
                        <div>
                            <label class="mb-1 block text-[10px] font-bold uppercase tracking-[0.14em] text-slate-500">
                                Change
                            </label>
                            <div
                                :class="[
                                    'flex h-[38px] items-center rounded-xl border px-3 text-sm font-bold',
                                    change !== null && change > 0
                                        ? 'border-emerald-200 bg-emerald-50 text-emerald-700'
                                        : 'border-[#e4ddd4] bg-[#f5f0ea] text-slate-500',
                                ]"
                            >
                                {{ change !== null ? fmt(change) : '—' }}
                            </div>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div class="mb-4">
                        <label class="mb-1 block text-[10px] font-bold uppercase tracking-[0.14em] text-slate-500">
                            Order Notes
                        </label>
                        <textarea
                            v-model="orderNotes"
                            rows="2"
                            placeholder="Any special instructions…"
                            class="w-full resize-none rounded-xl border border-[#e4ddd4] bg-[#faf7f3] px-3 py-2 text-sm text-slate-800 placeholder-slate-400 outline-none transition focus:border-[#0b4d59] focus:ring-2 focus:ring-[#0b4d59]/15"
                        />
                    </div>

                    <!-- Validation errors -->
                    <div v-if="Object.keys(formErrors).length > 0" class="mb-3 rounded-xl border border-red-200 bg-red-50 px-3 py-2">
                        <p v-for="(msg, key) in formErrors" :key="key" class="text-xs text-red-600">{{ msg }}</p>
                    </div>

                    <!-- Place Order button -->
                    <button
                        @click="placeOrder"
                        :disabled="cartItems.length === 0 || isPlacing"
                        :class="[
                            'flex w-full items-center justify-center gap-2 rounded-2xl py-3.5 text-sm font-bold text-white transition',
                            cartItems.length > 0 && !isPlacing
                                ? 'bg-[#e87c2a] shadow-[0_8px_24px_-8px_rgba(232,124,42,0.6)] hover:bg-[#d06e22] active:scale-[0.98]'
                                : 'cursor-not-allowed bg-slate-300',
                        ]"
                    >
                        <svg v-if="isPlacing" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                        </svg>
                        <span v-if="isPlacing">Placing Order…</span>
                        <span v-else-if="cartItems.length === 0">Add Items to Order</span>
                        <span v-else>Place Order · {{ fmt(total) }}</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- ══════════════════════════════════════════════════════════════
             Receipt / Success Modal
        ═══════════════════════════════════════════════════════════════ -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition duration-150 ease-in"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div
                    v-if="showReceipt && receiptData"
                    class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4 backdrop-blur-sm"
                    @click.self="startNewOrder"
                >
                    <Transition
                        enter-active-class="transition duration-200 ease-out"
                        enter-from-class="scale-95 opacity-0"
                        enter-to-class="scale-100 opacity-100"
                    >
                        <div
                            v-if="showReceipt"
                            class="w-full max-w-sm overflow-hidden rounded-3xl bg-white shadow-[0_40px_100px_-20px_rgba(0,0,0,0.3)]"
                        >
                            <!-- Receipt header -->
                            <div class="bg-[#0b4d59] px-6 py-5 text-white">
                                <div class="mb-1 flex items-center gap-2">
                                    <svg class="h-5 w-5 text-emerald-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <p class="text-sm font-semibold text-white/70">Order Placed Successfully</p>
                                </div>
                                <p class="text-2xl font-bold">{{ receiptData.orderNumber }}</p>
                                <div class="mt-2 flex items-center gap-2 text-sm">
                                    <span class="rounded-full bg-white/15 px-2.5 py-1 text-xs font-semibold">
                                        {{ receiptData.orderType === 'dine_in' ? 'Dine-In' : 'Take-Out' }}
                                    </span>
                                    <span v-if="receiptData.tableNumber" class="rounded-full bg-white/15 px-2.5 py-1 text-xs font-semibold">
                                        Table {{ receiptData.tableNumber }}
                                    </span>
                                    <span v-if="receiptData.customerName" class="truncate text-white/70">
                                        {{ receiptData.customerName }}
                                    </span>
                                </div>
                            </div>

                            <!-- Items -->
                            <div class="max-h-52 overflow-y-auto px-6 py-4">
                                <div
                                    v-for="item in receiptData.items"
                                    :key="item.id"
                                    class="flex items-center justify-between py-1.5 text-sm"
                                >
                                    <span class="flex-1 truncate pr-4 text-slate-700">
                                        <span class="font-semibold text-slate-900">{{ item.quantity }}×</span>
                                        {{ item.name }}
                                    </span>
                                    <span class="font-semibold text-slate-800">{{ fmt(item.lineTotal) }}</span>
                                </div>
                            </div>

                            <!-- Totals + payment -->
                            <div class="border-t border-[#f0ece6] px-6 py-4">
                                <div v-if="receiptData.discountAmount > 0" class="mb-1 flex items-center justify-between text-sm text-slate-500">
                                    <span>Subtotal</span>
                                    <span>{{ fmt(receiptData.subtotal) }}</span>
                                </div>
                                <div v-if="receiptData.discountAmount > 0" class="mb-2 flex items-center justify-between text-sm text-emerald-600">
                                    <span>{{ receiptData.discountType === 'sc' ? 'SC' : 'PWD' }} Discount ({{ receiptData.discountPct ?? 20 }}%) &middot; ID: {{ receiptData.discountId }}</span>
                                    <span>&minus;{{ fmt(receiptData.discountAmount) }}</span>
                                </div>
                                <div class="mb-2 flex items-center justify-between text-base font-bold text-[#0b4d59]">
                                    <span>Total</span>
                                    <span>{{ fmt(receiptData.total) }}</span>
                                </div>
                                <div class="flex items-center justify-between text-sm text-slate-500">
                                    <span>Payment</span>
                                    <span class="font-semibold capitalize text-slate-700">{{ receiptData.paymentMethod }}</span>
                                </div>
                                <div
                                    v-if="receiptData.paymentMethod === 'cash' && receiptData.cashReceived"
                                    class="mt-1 flex items-center justify-between text-sm text-slate-500"
                                >
                                    <span>Cash / Change</span>
                                    <span class="font-semibold text-slate-700">
                                        {{ fmt(receiptData.cashReceived) }} / {{ fmt(receiptData.change) }}
                                    </span>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="flex gap-2 px-6 pb-5">
                                <button
                                    @click="startNewOrder"
                                    class="flex-1 rounded-2xl bg-[#0b4d59] py-3 text-sm font-bold text-white transition hover:bg-[#093e48]"
                                >
                                    New Order
                                </button>
                            </div>
                        </div>
                    </Transition>
                </div>
            </Transition>
        </Teleport>
    </CashierLayout>
</template>
