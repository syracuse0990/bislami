<script setup>
import MerchantLayout from '@/Layouts/MerchantLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    logs: { type: Object, default: () => ({ data: [], links: [], meta: {} }) },
});

const actionMeta = {
    'order.accepted':         { label: 'Accepted',         icon: 'check',   color: 'bg-emerald-100 text-emerald-600' },
    'order.rejected':         { label: 'Rejected',         icon: 'x',       color: 'bg-red-100 text-red-600' },
    'order.started_preparing':{ label: 'Preparing',        icon: 'clock',   color: 'bg-orange-100 text-orange-600' },
    'order.dispatched':       { label: 'Dispatched',       icon: 'truck',   color: 'bg-[#e4f3f3] text-[var(--brand-teal)]' },
    'order.completed_pickup': { label: 'Pickup complete',  icon: 'bag',     color: 'bg-emerald-100 text-emerald-600' },
    'menu.item.created':      { label: 'Item added',       icon: 'plus',    color: 'bg-[#e4f3f3] text-[var(--brand-teal)]' },
    'menu.item.updated':      { label: 'Item updated',     icon: 'pencil',  color: 'bg-[#fff3e0] text-orange-600' },
    'menu.item.deleted':      { label: 'Item deleted',     icon: 'trash',   color: 'bg-red-100 text-red-600' },
};

function getMeta(action) {
    return actionMeta[action] ?? { label: action, icon: 'dot', color: 'bg-slate-100 text-slate-500' };
}
</script>

<template>
    <Head title="Activity Log" />

    <MerchantLayout>
        <div class="py-8">
            <div class="space-y-6 px-4 sm:px-6 lg:px-0">

                <!-- Header -->
                <section class="rounded-[32px] border border-white/80 bg-[linear-gradient(145deg,#ffffff_0%,#fff8f1_58%,#f4fbfb_100%)] p-6 shadow-[0_34px_85px_-56px_rgba(11,77,89,0.6)] sm:p-8">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-[var(--brand-teal)]">Audit trail</p>
                            <h2 class="mt-2 text-2xl font-semibold text-slate-900">Activity Log</h2>
                            <p class="mt-2 max-w-xl text-sm leading-6 text-slate-500">
                                A chronological record of who did what across your restaurants — order actions, menu changes, and more.
                            </p>
                        </div>
                        <Link
                            :href="route('merchant.staff.index')"
                            class="inline-flex shrink-0 items-center justify-center gap-2 rounded-full border border-[#d6e7e7] bg-white px-5 py-3 text-xs font-semibold uppercase tracking-[0.18em] text-[var(--brand-teal)] transition duration-200 hover:-translate-y-0.5"
                        >
                            ← Team
                        </Link>
                    </div>
                </section>

                <!-- Log entries -->
                <section class="overflow-hidden rounded-[32px] border border-white/80 bg-white/90 shadow-[0_30px_75px_-50px_rgba(11,77,89,0.5)]">
                    <div v-if="logs.data.length" class="divide-y divide-[#eef3f3]">
                        <div
                            v-for="log in logs.data"
                            :key="log.id"
                            class="flex items-start gap-4 px-6 py-5 transition duration-150 hover:bg-[#fcfefe] sm:px-8"
                        >
                            <!-- Action icon -->
                            <div
                                class="mt-0.5 flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl"
                                :class="getMeta(log.action).color"
                            >
                                <!-- check -->
                                <svg v-if="getMeta(log.action).icon === 'check'" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M20 6L9 17l-5-5" />
                                </svg>
                                <!-- x -->
                                <svg v-else-if="getMeta(log.action).icon === 'x'" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M18 6L6 18M6 6l12 12" />
                                </svg>
                                <!-- clock -->
                                <svg v-else-if="getMeta(log.action).icon === 'clock'" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <circle cx="12" cy="12" r="10" /><path d="M12 6v6l4 2" />
                                </svg>
                                <!-- truck -->
                                <svg v-else-if="getMeta(log.action).icon === 'truck'" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <rect x="1" y="3" width="15" height="13" /><path d="M16 8h4l3 3v5h-7V8zm-8 14a2 2 0 100-4 2 2 0 000 4zm12 0a2 2 0 100-4 2 2 0 000 4z" />
                                </svg>
                                <!-- bag -->
                                <svg v-else-if="getMeta(log.action).icon === 'bag'" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z" /><line x1="3" y1="6" x2="21" y2="6" />
                                </svg>
                                <!-- plus -->
                                <svg v-else-if="getMeta(log.action).icon === 'plus'" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M12 4v16m8-8H4" />
                                </svg>
                                <!-- pencil -->
                                <svg v-else-if="getMeta(log.action).icon === 'pencil'" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7" /><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z" />
                                </svg>
                                <!-- trash -->
                                <svg v-else-if="getMeta(log.action).icon === 'trash'" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                                <!-- dot fallback -->
                                <span v-else class="h-2.5 w-2.5 rounded-full bg-current" />
                            </div>

                            <!-- Content -->
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-baseline gap-x-2 gap-y-0.5">
                                    <span class="text-sm font-semibold text-slate-900">{{ log.actorName }}</span>
                                    <span
                                        class="rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase tracking-[0.14em] ring-1"
                                        :class="getMeta(log.action).color.replace('text-', 'text-') + ' ring-current/20'"
                                    >{{ getMeta(log.action).label }}</span>
                                </div>
                                <p class="mt-0.5 text-sm text-slate-600">{{ log.description }}</p>
                                <p class="mt-1 text-xs text-slate-400" :title="log.createdAtFull">{{ log.createdAt }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Empty state -->
                    <div v-else class="p-8 sm:p-10">
                        <div class="rounded-[28px] border border-dashed border-[#d7e7e8] bg-[#fcfefe] p-6 text-sm leading-6 text-slate-500">
                            <p class="text-base font-semibold text-slate-900">No activity recorded yet.</p>
                            <p class="mt-2">Activity will appear here as your team accepts orders, updates the menu, and more.</p>
                        </div>
                    </div>

                    <!-- Pagination -->
                    <div v-if="logs.meta?.last_page > 1" class="flex items-center justify-between border-t border-[#eef3f3] px-6 py-4 sm:px-8">
                        <p class="text-sm text-slate-500">Page {{ logs.meta.current_page }} of {{ logs.meta.last_page }}</p>
                        <div class="flex gap-2">
                            <Link
                                v-if="logs.links?.prev"
                                :href="logs.links.prev"
                                class="inline-flex items-center justify-center rounded-full border border-[#d6e7e7] bg-white px-4 py-2 text-xs font-semibold uppercase tracking-[0.16em] text-[var(--brand-teal)] transition hover:-translate-y-0.5"
                            >
                                Previous
                            </Link>
                            <Link
                                v-if="logs.links?.next"
                                :href="logs.links.next"
                                class="inline-flex items-center justify-center rounded-full bg-[var(--brand-orange)] px-4 py-2 text-xs font-semibold uppercase tracking-[0.16em] text-slate-800 transition hover:-translate-y-0.5"
                            >
                                Next
                            </Link>
                        </div>
                    </div>
                </section>

            </div>
        </div>
    </MerchantLayout>
</template>
