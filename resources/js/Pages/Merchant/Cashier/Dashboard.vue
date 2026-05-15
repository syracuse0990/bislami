<script setup>
import CashierLayout from '@/Layouts/CashierLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    restaurant:       { type: Object, required: true },
    restaurants:      { type: Array,  required: true },
    stats:            { type: Object, required: true },
    activeOrders:     { type: Object, required: true },
    riderQueue:       { type: Array,  required: true },
    topItems:         { type: Array,  required: true },
    problemOrders:    { type: Array,  required: true },
    ledger:           { type: Array,  required: true },
    paymentBreakdown: { type: Array,  required: true },
    shift:            { type: Object, required: true },
    generatedAt:      { type: String, required: true },
    generatedTime:    { type: String, required: true },
});

// â”€â”€ Helpers â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
const fmt = (n) => '₱' + Number(n || 0).toLocaleString('en-PH');

const paymentLabels = { cash: 'Cash', card: 'Card', gcash: 'GCash', maya: 'Maya' };
const paymentColors = {
    cash:  'bg-emerald-50 text-emerald-700 ring-emerald-200',
    card:  'bg-blue-50 text-blue-700 ring-blue-200',
    gcash: 'bg-sky-50 text-sky-700 ring-sky-200',
    maya:  'bg-violet-50 text-violet-700 ring-violet-200',
};

const fulfillmentLabel = (t) => t === 'dine_in' ? 'Dine-In' : 'Take-Out';
const fulfillmentColor = (t) =>
    t === 'dine_in' ? 'bg-teal-50 text-teal-700 ring-teal-200' : 'bg-orange-50 text-orange-700 ring-orange-200';

const statusLabel = { pending: 'Pending', accepted: 'Accepted', preparing: 'Preparing', ready: 'Ready' };
const statusColor = {
    pending:   'bg-amber-50 text-amber-700 ring-amber-200',
    accepted:  'bg-blue-50 text-blue-700 ring-blue-200',
    preparing: 'bg-orange-50 text-orange-700 ring-orange-200',
    ready:     'bg-emerald-50 text-emerald-700 ring-emerald-200',
};

const eodTotal = computed(() => props.paymentBreakdown.reduce((s, r) => s + r.total, 0));

const quickActions = [
    { label: 'New Walk-in Order', href: 'merchant.cashier.pos', icon: 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z', primary: true },
    { label: 'Open POS',          href: 'merchant.cashier.pos', icon: 'M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7' },
    { label: 'Refund Transaction', href: null, icon: 'M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6' },
    { label: 'Void Sale',          href: null, icon: 'M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16' },
    { label: 'Reprint Receipt',    href: null, icon: 'M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z' },
    { label: 'Customer Lookup',    href: null, icon: 'M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z' },
    { label: 'Hold Order',         href: null, icon: 'M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z' },
    { label: 'Resume Held Order',  href: null, icon: 'M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z M21 12a9 9 0 11-18 0 9 9 0 0118 0z' },
];
</script>

<template>
    <CashierLayout>
        <template #restaurant-name>{{ restaurant.name }}</template>

        <template #header-action>
            <Link
                :href="route('merchant.cashier.pos')"
                class="flex items-center gap-1.5 rounded-full border border-white/20 bg-white/10 px-3.5 py-1.5 text-xs font-semibold text-white transition hover:bg-white/20"
            >
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                </svg>
                Open POS
            </Link>
        </template>

        <Head title="Cashier Dashboard" />

        <div class="h-full overflow-y-auto">
            <!-- Page header -->
            <div class="border-b border-[#e8e3da] bg-white px-6 py-4">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 class="text-lg font-bold text-[#0b4d59]">Cashier Dashboard</h1>
                        <p class="mt-0.5 text-sm text-gray-500">{{ shift.date }} · {{ restaurant.name }}</p>
                    </div>
                    <span class="text-xs text-gray-400">Updated {{ generatedTime }}</span>
                </div>
            </div>

            <div class="space-y-6 p-6">

                <!-- â”€â”€ 3. Quick Actions â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ -->
                <section>
                    <h2 class="mb-3 text-xs font-semibold uppercase tracking-widest text-gray-400">Quick Actions</h2>
                    <div class="flex flex-wrap gap-2">
                        <template v-for="action in quickActions" :key="action.label">
                            <Link
                                v-if="action.href"
                                :href="route(action.href)"
                                :class="[
                                    'flex items-center gap-1.5 rounded-xl px-3.5 py-2 text-xs font-semibold transition',
                                    action.primary
                                        ? 'bg-gradient-to-r from-[#0b4d59] to-[#1a7d8e] text-white shadow-sm hover:shadow-md'
                                        : 'border border-[#e8e3da] bg-white text-[#0b4d59] hover:border-[#0b4d59]/30 hover:bg-[#f4fbfb]',
                                ]"
                            >
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" :d="action.icon" />
                                </svg>
                                {{ action.label }}
                            </Link>
                            <button
                                v-else
                                type="button"
                                class="flex cursor-not-allowed items-center gap-1.5 rounded-xl border border-[#e8e3da] bg-white px-3.5 py-2 text-xs font-semibold text-gray-400"
                                title="Coming soon"
                            >
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" :d="action.icon" />
                                </svg>
                                {{ action.label }}
                            </button>
                        </template>
                    </div>
                </section>

                <!-- â”€â”€ Revenue stats + Shift Info â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ -->
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <!-- Today -->
                    <div class="overflow-hidden rounded-2xl border border-[#e8e3da] bg-white shadow-sm">
                        <div class="h-1 bg-gradient-to-r from-[#0b4d59] to-[#1a7d8e]" />
                        <div class="p-5">
                            <p class="text-xs font-semibold uppercase tracking-widest text-gray-400">Today's Revenue</p>
                            <p class="mt-2 text-3xl font-bold tracking-tight text-[#0b4d59]">{{ stats.todayRevenueFormatted }}</p>
                            <p class="mt-1 text-sm text-gray-500"><span class="font-semibold text-gray-700">{{ stats.todayOrders }}</span> orders</p>
                        </div>
                    </div>

                    <!-- Top Selling Today -->
                    <div class="overflow-hidden rounded-2xl border border-[#e8e3da] bg-white shadow-sm">
                        <div class="h-1 bg-gradient-to-r from-amber-400 to-orange-400" />
                        <div class="border-b border-[#f0ebe3] px-4 py-3">
                            <p class="text-xs font-semibold uppercase tracking-widest text-gray-400">Top Selling Today</p>
                        </div>
                        <div v-if="topItems.length === 0" class="flex items-center justify-center py-8 text-xs text-gray-400">No sales yet</div>
                        <ul v-else class="divide-y divide-[#f3efe9]">
                            <li
                                v-for="(item, idx) in topItems.slice(0, 5)"
                                :key="item.name"
                                class="flex items-center gap-2 px-4 py-2 hover:bg-[#faf7f3]"
                            >
                                <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full text-[10px] font-bold"
                                    :class="idx === 0 ? 'bg-amber-100 text-amber-700' : idx === 1 ? 'bg-gray-100 text-gray-600' : idx === 2 ? 'bg-orange-50 text-orange-600' : 'bg-[#f3efe9] text-gray-500'">
                                    {{ idx + 1 }}
                                </span>
                                <p class="min-w-0 flex-1 truncate text-xs font-medium text-gray-800">{{ item.name }}</p>
                                <span class="shrink-0 text-xs font-bold text-[#0b4d59]">{{ item.sold }}x</span>
                            </li>
                        </ul>
                    </div>

                    <!-- Rider Queue -->
                    <div class="overflow-hidden rounded-2xl border border-[#e8e3da] bg-white shadow-sm">
                        <div class="h-1 bg-gradient-to-r from-sky-400 to-cyan-500" />
                        <div class="border-b border-[#f0ebe3] px-4 py-3">
                            <p class="text-xs font-semibold uppercase tracking-widest text-gray-400">Rider Queue</p>
                        </div>
                        <div v-if="riderQueue.length === 0" class="flex items-center justify-center py-8 text-xs text-gray-400">No deliveries waiting</div>
                        <ul v-else class="divide-y divide-[#f3efe9]">
                            <li
                                v-for="order in riderQueue"
                                :key="order.id"
                                class="px-4 py-2.5 hover:bg-[#faf7f3]"
                            >
                                <div class="flex items-center justify-between gap-2">
                                    <span class="font-mono text-xs font-semibold text-[#0b4d59]">{{ order.orderNumber }}</span>
                                    <span :class="['inline-flex items-center rounded-full px-1.5 py-0.5 text-[10px] font-semibold ring-1 ring-inset', order.isDelayed ? 'bg-red-50 text-red-600 ring-red-200' : 'bg-emerald-50 text-emerald-700 ring-emerald-200']">
                                        {{ order.isDelayed ? '⚠ Late' : 'On time' }}
                                    </span>
                                </div>
                                <p class="mt-0.5 text-[10px] text-gray-400">{{ order.waitMinutes }}m waiting &middot; {{ order.totalFormatted }}</p>
                            </li>
                        </ul>
                    </div>

                    <!-- 2. Current Shift Info -->
                    <div class="overflow-hidden rounded-2xl border border-[#e8e3da] bg-white shadow-sm">
                        <div class="h-1 bg-gradient-to-r from-violet-400 to-violet-600" />
                        <div class="p-5">
                            <p class="text-xs font-semibold uppercase tracking-widest text-gray-400">Current Shift</p>
                            <p class="mt-2 truncate text-lg font-bold text-[#0b4d59]">{{ shift.cashierName }}</p>
                            <p class="mt-0.5 text-xs text-gray-500">{{ shift.role }}</p>
                            <div class="mt-3 space-y-1 border-t border-[#f0ebe3] pt-3">
                                <div class="flex justify-between text-xs">
                                    <span class="text-gray-400">Supervisor</span>
                                    <span class="font-medium text-gray-700">{{ shift.supervisorName }}</span>
                                </div>
                                <div class="flex justify-between text-xs">
                                    <span class="text-gray-400">Date</span>
                                    <span class="font-medium text-gray-700">{{ generatedAt }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- â”€â”€ 1. Active Orders Widget â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ -->
                <section class="overflow-hidden rounded-2xl border border-[#e8e3da] bg-white shadow-sm">
                    <div class="border-b border-[#e8e3da] px-5 py-3.5">
                        <div class="flex items-center justify-between">
                            <div>
                                <h2 class="text-sm font-semibold text-[#0b4d59]">Active Orders</h2>
                                <p class="text-xs text-gray-400">Live queue — orders needing attention</p>
                            </div>
                            <Link :href="route('merchant.orders.index')" class="text-xs font-semibold text-[#0b4d59] hover:underline">Full queue â†’</Link>
                        </div>
                    </div>

                    <!-- Status tiles -->
                    <div class="grid grid-cols-4 divide-x divide-[#f0ebe3] border-b border-[#e8e3da]">
                        <div class="p-4 text-center">
                            <p class="text-2xl font-bold text-amber-600">{{ activeOrders.counts.pending }}</p>
                            <p class="mt-0.5 text-xs font-medium text-gray-500">Pending</p>
                        </div>
                        <div class="p-4 text-center">
                            <p class="text-2xl font-bold text-orange-500">{{ activeOrders.counts.preparing }}</p>
                            <p class="mt-0.5 text-xs font-medium text-gray-500">Preparing</p>
                        </div>
                        <div class="p-4 text-center">
                            <p class="text-2xl font-bold text-emerald-600">{{ activeOrders.counts.ready }}</p>
                            <p class="mt-0.5 text-xs font-medium text-gray-500">Ready</p>
                        </div>
                        <div class="p-4 text-center">
                            <p class="text-2xl font-bold text-[#0b4d59]">{{ activeOrders.counts.total }}</p>
                            <p class="mt-0.5 text-xs font-medium text-gray-500">Total Active</p>
                        </div>
                    </div>

                    <!-- Order rows -->
                    <div v-if="activeOrders.list.length === 0" class="px-5 py-8 text-center text-sm text-gray-400">
                        No active orders right now. The kitchen is clear! 🎉
                    </div>
                    <div v-else class="divide-y divide-[#f3efe9]">
                        <div
                            v-for="order in activeOrders.list"
                            :key="order.id"
                            class="flex items-center justify-between px-5 py-3 hover:bg-[#faf7f3]"
                        >
                            <div class="flex items-center gap-3">
                                <span class="font-mono text-xs font-semibold text-[#0b4d59]">{{ order.orderNumber }}</span>
                                <span :class="['inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold ring-1 ring-inset', statusColor[order.status] ?? 'bg-gray-50 text-gray-600 ring-gray-200']">
                                    {{ statusLabel[order.status] ?? order.status }}
                                </span>
                                <span :class="['inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold ring-1 ring-inset', fulfillmentColor(order.fulfillmentType)]">
                                    {{ fulfillmentLabel(order.fulfillmentType) }}
                                </span>
                                <span v-if="order.customerNotes" class="hidden max-w-[160px] truncate text-xs text-gray-400 sm:block">{{ order.customerNotes }}</span>
                            </div>
                            <div class="flex items-center gap-4">
                                <span class="hidden text-xs text-gray-400 sm:block">{{ order.placedAt }}</span>
                                <span class="text-sm font-semibold text-[#0b4d59]">{{ order.totalFormatted }}</span>
                                <span
                                    v-if="order.waitMinutes > 20"
                                    class="hidden rounded-full bg-red-50 px-2 py-0.5 text-[10px] font-semibold text-red-600 ring-1 ring-inset ring-red-200 sm:inline-flex"
                                >
                                    {{ order.waitMinutes }}m wait
                                </span>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- â”€â”€ 7 & EOD: Ledger + EOD Report â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ -->
                <div class="flex gap-6">
                    <!-- Recent Transactions (Ledger) -->
                    <div class="min-w-0 flex-1 overflow-hidden rounded-2xl border border-[#e8e3da] bg-white shadow-sm">
                        <div class="border-b border-[#e8e3da] px-5 py-3.5">
                            <h2 class="text-sm font-semibold text-[#0b4d59]">Today's Ledger</h2>
                            <p class="text-xs text-gray-400">All transactions — {{ generatedAt }}</p>
                        </div>
                        <div v-if="ledger.length === 0" class="flex flex-col items-center justify-center py-12 text-center">
                            <svg class="mb-2 h-8 w-8 text-gray-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                            <p class="text-sm text-gray-400">No transactions yet today</p>
                        </div>
                        <div v-else class="overflow-x-auto">
                            <table class="w-full min-w-[520px] text-sm">
                                <thead>
                                    <tr class="border-b border-[#f0ebe3] bg-[#faf7f3]">
                                        <th class="px-5 py-2.5 text-left text-[10px] font-semibold uppercase tracking-wider text-gray-400">Order</th>
                                        <th class="px-4 py-2.5 text-left text-[10px] font-semibold uppercase tracking-wider text-gray-400">Time</th>
                                        <th class="px-4 py-2.5 text-left text-[10px] font-semibold uppercase tracking-wider text-gray-400">Type</th>
                                        <th class="px-4 py-2.5 text-center text-[10px] font-semibold uppercase tracking-wider text-gray-400">Qty</th>
                                        <th class="px-4 py-2.5 text-left text-[10px] font-semibold uppercase tracking-wider text-gray-400">Payment</th>
                                        <th class="px-5 py-2.5 text-right text-[10px] font-semibold uppercase tracking-wider text-gray-400">Amount</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-[#f3efe9]">
                                    <tr v-for="order in ledger" :key="order.id" class="hover:bg-[#faf7f3]">
                                        <td class="px-5 py-2.5">
                                            <span class="font-mono text-xs font-semibold text-[#0b4d59]">{{ order.orderNumber }}</span>
                                            <p v-if="order.customerNotes" class="mt-0.5 max-w-[120px] truncate text-[10px] text-gray-400">{{ order.customerNotes }}</p>
                                        </td>
                                        <td class="px-4 py-2.5 text-xs text-gray-500">{{ order.placedAt }}</td>
                                        <td class="px-4 py-2.5">
                                            <span :class="['inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold ring-1 ring-inset', fulfillmentColor(order.fulfillmentType)]">
                                                {{ fulfillmentLabel(order.fulfillmentType) }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-2.5 text-center text-xs font-medium text-gray-600">{{ order.itemCount }}</td>
                                        <td class="px-4 py-2.5">
                                            <span :class="['inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold ring-1 ring-inset', paymentColors[order.paymentMethod] ?? 'bg-gray-50 text-gray-600 ring-gray-200']">
                                                {{ paymentLabels[order.paymentMethod] ?? order.paymentMethod }}
                                            </span>
                                        </td>
                                        <td class="px-5 py-2.5 text-right text-sm font-semibold text-[#0b4d59]">{{ order.totalFormatted }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- EOD Report -->
                    <div class="w-60 shrink-0 xl:w-68">
                        <div class="overflow-hidden rounded-2xl border border-[#e8e3da] bg-white shadow-sm">
                            <div class="border-b border-[#e8e3da] px-5 py-3.5">
                                <h2 class="text-sm font-semibold text-[#0b4d59]">End of Day Report</h2>
                                <p class="text-xs text-gray-400">{{ generatedAt }}</p>
                            </div>
                            <div class="p-5">
                                <p class="mb-3 text-[10px] font-semibold uppercase tracking-widest text-gray-400">By Payment</p>
                                <div v-if="paymentBreakdown.length === 0" class="py-3 text-center text-xs text-gray-400">No sales yet</div>
                                <ul v-else class="space-y-2">
                                    <li v-for="row in paymentBreakdown" :key="row.method" class="flex items-center justify-between">
                                        <div class="flex items-center gap-2">
                                            <span :class="['inline-flex h-6 w-6 items-center justify-center rounded-lg text-[10px] font-bold ring-1 ring-inset', paymentColors[row.method] ?? 'bg-gray-50 text-gray-600 ring-gray-200']">
                                                {{ (paymentLabels[row.method] ?? row.method).charAt(0) }}
                                            </span>
                                            <div>
                                                <p class="text-xs font-medium text-gray-700">{{ paymentLabels[row.method] ?? row.method }}</p>
                                                <p class="text-[10px] text-gray-400">{{ row.count }} orders</p>
                                            </div>
                                        </div>
                                        <span class="text-sm font-semibold text-[#0b4d59]">{{ row.totalFormatted }}</span>
                                    </li>
                                </ul>
                                <div class="my-4 border-t border-[#e8e3da]" />
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-semibold text-gray-700">Total</span>
                                    <span class="text-lg font-bold text-[#0b4d59]">{{ fmt(eodTotal) }}</span>
                                </div>
                                <div class="mt-2 flex items-center justify-between">
                                    <span class="text-xs text-gray-500">Avg order</span>
                                    <span class="text-sm font-semibold text-gray-700">
                                        {{ stats.todayOrders > 0 ? fmt(Math.round(stats.todayRevenue / stats.todayOrders)) : '₱0' }}
                                    </span>
                                </div>
                                <button type="button" onclick="window.print()" class="mt-4 flex w-full items-center justify-center gap-1.5 rounded-xl border border-[#e8e3da] bg-[#faf7f3] py-2 text-xs font-semibold text-[#0b4d59] transition hover:bg-[#f3efe9]">
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" /></svg>
                                    Print EOD Report
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- â”€â”€ Bottom row: Top Items · Rider Queue · Problem Orders â”€â”€â”€â”€â”€â”€â”€ -->
                <!-- 5. Problem Orders + SC/PWD row -->
                <div class="grid grid-cols-3 gap-6">

                    <!-- Problem Orders (1/3) -->
                    <div class="col-span-1 overflow-hidden rounded-2xl border border-[#e8e3da] bg-white shadow-sm">
                        <div class="border-b border-[#e8e3da] px-5 py-3.5">
                            <div class="flex items-center gap-2">
                                <h2 class="text-sm font-semibold text-[#0b4d59]">Problem Orders</h2>
                                <span v-if="problemOrders.length > 0" class="flex h-4 w-4 items-center justify-center rounded-full bg-red-500 text-[9px] font-bold text-white">
                                    {{ problemOrders.length }}
                                </span>
                            </div>
                            <p class="text-xs text-gray-400">Rejected &amp; failed orders today</p>
                        </div>
                        <div v-if="problemOrders.length === 0" class="flex flex-col items-center justify-center py-10 text-center">
                            <svg class="mb-2 h-7 w-7 text-gray-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            <p class="text-xs text-gray-400">No problem orders today</p>
                        </div>
                        <ul v-else class="divide-y divide-[#f3efe9]">
                            <li
                                v-for="order in problemOrders"
                                :key="order.id"
                                class="px-5 py-3 hover:bg-red-50/30"
                            >
                                <div class="flex items-center justify-between">
                                    <span class="font-mono text-xs font-semibold text-red-600">{{ order.orderNumber }}</span>
                                    <div class="flex items-center gap-2">
                                        <span :class="['inline-flex rounded-full px-2 py-0.5 text-[10px] font-semibold ring-1 ring-inset', paymentColors[order.paymentMethod] ?? 'bg-gray-50 text-gray-600 ring-gray-200']">
                                            {{ paymentLabels[order.paymentMethod] ?? order.paymentMethod }}
                                        </span>
                                        <span class="text-sm font-bold text-gray-700">{{ order.totalFormatted }}</span>
                                    </div>
                                </div>
                                <p class="mt-1 text-[10px] text-gray-400">{{ order.placedAt }}</p>
                                <p class="mt-0.5 truncate text-[10px] text-red-500">{{ order.reason }}</p>
                            </li>
                        </ul>
                    </div>

                    <!-- SC/PWD Tracker (2/3) -->
                    <section class="col-span-2 overflow-hidden rounded-2xl border border-[#e8e3da] bg-white shadow-sm">
                        <div class="border-b border-[#e8e3da] px-5 py-3.5">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h2 class="text-sm font-semibold text-[#0b4d59]">Senior Citizen / PWD Discount Tracker</h2>
                                    <p class="text-xs text-gray-400">Validation logs and audit compliance records</p>
                                </div>
                                <span class="rounded-full bg-amber-50 px-2.5 py-1 text-[10px] font-semibold text-amber-600 ring-1 ring-inset ring-amber-200">
                                    Coming Soon
                                </span>
                            </div>
                        </div>
                        <div class="grid grid-cols-3 divide-x divide-[#f0ebe3]">
                            <div class="p-5 text-center">
                                <p class="text-2xl font-bold text-gray-300">0</p>
                                <p class="mt-0.5 text-xs text-gray-400">SC Discounts Today</p>
                            </div>
                            <div class="p-5 text-center">
                                <p class="text-2xl font-bold text-gray-300">0</p>
                                <p class="mt-0.5 text-xs text-gray-400">PWD Discounts Today</p>
                            </div>
                            <div class="p-5 text-center">
                                <p class="text-2xl font-bold text-gray-300">₱0</p>
                                <p class="mt-0.5 text-xs text-gray-400">Total Discount Given</p>
                            </div>
                        </div>
                        <div class="border-t border-[#f0ebe3] bg-[#faf7f3] px-5 py-3 text-center text-xs text-gray-400">
                            SC/PWD discount validation requires the discount module to be enabled for this restaurant. Contact your administrator to activate.
                        </div>
                    </section>

                </div>

            </div>
        </div>
    </CashierLayout>
</template>
