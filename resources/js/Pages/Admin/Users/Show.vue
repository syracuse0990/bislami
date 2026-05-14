<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    user: {
        type: Object,
        required: true,
    },
    managedRestaurants: {
        type: Array,
        default: () => [],
    },
    recentOrders: {
        type: Array,
        default: () => [],
    },
    assignedDeliveries: {
        type: Array,
        default: () => [],
    },
});
</script>

<template>
    <Head :title="`Admin User - ${user.name}`" />

    <AdminLayout>
        <template #header>
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div class="space-y-3">
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-[var(--brand-orange-deep)]">
                        User profile
                    </p>
                    <div class="flex flex-wrap items-center gap-3">
                        <h2 class="text-3xl font-semibold leading-tight text-slate-900">
                            {{ user.name }}
                        </h2>
                        <span
                            class="inline-flex rounded-full px-3 py-1 text-xs font-semibold ring-1"
                            :class="user.roleAccent"
                        >
                            {{ user.roleLabel }}
                        </span>
                        <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold ring-1" :class="user.accessAccent">
                            {{ user.accessLabel }}
                        </span>
                        <span
                            v-if="user.merchantApprovalLabel"
                            class="inline-flex rounded-full px-3 py-1 text-xs font-semibold ring-1"
                            :class="user.merchantApprovalAccent"
                        >
                            {{ user.merchantApprovalLabel }}
                        </span>
                    </div>
                    <p class="max-w-2xl text-sm leading-6 text-slate-600">
                        {{ user.email }} · {{ user.workloadLabel }} · {{ user.verificationLabel }}
                    </p>
                </div>

                <div class="flex flex-col gap-3 sm:flex-row">
                    <Link
                        :href="route('admin.users.index')"
                        class="inline-flex items-center justify-center rounded-full border border-white/80 bg-white/80 px-5 py-3 text-sm font-semibold text-slate-700 transition duration-200 hover:text-[var(--brand-teal)]"
                    >
                        Back to users
                    </Link>
                    <a
                        :href="user.emailUrl"
                        class="inline-flex items-center justify-center rounded-full bg-gradient-to-r from-[var(--brand-teal)] to-[var(--brand-orange)] px-5 py-3 text-sm font-semibold text-white shadow-[0_20px_45px_-24px_rgba(11,77,89,0.85)] transition duration-200 hover:-translate-y-0.5"
                    >
                        Email {{ user.name }}
                    </a>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                <section class="rounded-[32px] border border-white/80 bg-[linear-gradient(135deg,#ffffff_0%,#fff8f1_58%,#f3fbfb_100%)] p-6 shadow-[0_34px_85px_-56px_rgba(11,77,89,0.6)] sm:p-8">
                    <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-4">
                        <article class="rounded-[28px] border border-white/80 bg-white/88 p-6 shadow-[0_28px_70px_-48px_rgba(11,77,89,0.55)]">
                            <p class="text-sm uppercase tracking-[0.2em] text-[var(--brand-teal)]">Joined</p>
                            <p class="mt-3 text-2xl font-semibold text-slate-900">{{ user.joinedAt }}</p>
                            <p class="mt-3 text-sm leading-6 text-slate-500">Account age and onboarding recency.</p>
                        </article>

                        <article class="rounded-[28px] border border-white/80 bg-white/88 p-6 shadow-[0_28px_70px_-48px_rgba(11,77,89,0.55)]">
                            <p class="text-sm uppercase tracking-[0.2em] text-[var(--brand-orange-deep)]">Managed restaurants</p>
                            <p class="mt-3 text-2xl font-semibold text-slate-900">{{ user.managedRestaurantsCount }}</p>
                            <p class="mt-3 text-sm leading-6 text-slate-500">Useful when the account belongs to a merchant operator.</p>
                        </article>

                        <article class="rounded-[28px] border border-white/80 bg-white/88 p-6 shadow-[0_28px_70px_-48px_rgba(11,77,89,0.55)]">
                            <p class="text-sm uppercase tracking-[0.2em] text-sky-700">Orders placed</p>
                            <p class="mt-3 text-2xl font-semibold text-slate-900">{{ user.ordersCount }}</p>
                            <p class="mt-3 text-sm leading-6 text-slate-500">Customer-side order history tied to this account.</p>
                        </article>

                        <article class="rounded-[28px] bg-[var(--brand-teal)] p-6 text-white shadow-[0_28px_70px_-40px_rgba(11,77,89,0.68)]">
                            <p class="text-sm uppercase tracking-[0.2em] text-white/70">Active deliveries</p>
                            <p class="mt-3 text-2xl font-semibold">{{ user.activeAssignedDeliveriesCount }}</p>
                            <p class="mt-3 text-sm leading-6 text-white/80">Current courier workload assigned to this account.</p>
                        </article>
                    </div>
                </section>

                <section class="grid gap-6 xl:grid-cols-[1.1fr_0.9fr]">
                    <div class="space-y-6">
                        <section class="rounded-[32px] border border-white/80 bg-white/88 p-6 shadow-[0_34px_85px_-56px_rgba(11,77,89,0.6)] sm:p-8">
                            <div class="flex items-end justify-between gap-4">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.22em] text-[var(--brand-teal)]">Recent orders</p>
                                    <h3 class="mt-2 text-2xl font-semibold text-slate-900">Latest customer-side activity</h3>
                                </div>

                                <span class="rounded-full border border-white/80 bg-[#f4fbfb] px-4 py-2 text-sm font-medium text-slate-600 shadow-[0_18px_44px_-30px_rgba(11,77,89,0.4)]">
                                    {{ recentOrders.length }} records
                                </span>
                            </div>

                            <div v-if="recentOrders.length" class="mt-6 grid gap-4">
                                <article
                                    v-for="order in recentOrders"
                                    :key="`recent-order-${order.id}`"
                                    class="rounded-[26px] border border-[#edf2f2] bg-[linear-gradient(135deg,#ffffff_0%,#fff8f1_62%,#f4fbfb_100%)] p-5"
                                >
                                    <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                                        <div>
                                            <div class="flex flex-wrap items-center gap-3">
                                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[var(--brand-orange-deep)]">{{ order.orderNumber }}</p>
                                                <span :class="order.statusAccent" class="rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-[0.16em] ring-1">
                                                    {{ order.statusLabel }}
                                                </span>
                                            </div>
                                            <h4 class="mt-2 text-lg font-semibold text-slate-900">{{ order.restaurantName }}</h4>
                                            <p class="mt-2 text-sm leading-6 text-slate-600">{{ order.summary }}</p>
                                        </div>

                                        <div class="grid gap-2 text-sm text-slate-600 md:text-right">
                                            <p><span class="font-semibold text-slate-900">Placed:</span> {{ order.placedAt }}</p>
                                            <p><span class="font-semibold text-slate-900">Total:</span> {{ order.total }}</p>
                                            <p><span class="font-semibold text-slate-900">Destination:</span> {{ order.destinationShortLabel }}</p>
                                        </div>
                                    </div>
                                </article>
                            </div>

                            <div v-else class="mt-6 rounded-[28px] border border-dashed border-[#d8e7e8] bg-[#fffcf8] p-8 text-sm leading-6 text-slate-500">
                                No recent customer-side orders are associated with this user yet.
                            </div>
                        </section>

                        <section class="rounded-[32px] border border-white/80 bg-white/88 p-6 shadow-[0_34px_85px_-56px_rgba(11,77,89,0.6)] sm:p-8">
                            <div class="flex items-end justify-between gap-4">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.22em] text-sky-700">Assigned deliveries</p>
                                    <h3 class="mt-2 text-2xl font-semibold text-slate-900">Courier-side workload</h3>
                                </div>

                                <span class="rounded-full border border-white/80 bg-[#f4fbfb] px-4 py-2 text-sm font-medium text-slate-600 shadow-[0_18px_44px_-30px_rgba(11,77,89,0.4)]">
                                    {{ assignedDeliveries.length }} records
                                </span>
                            </div>

                            <div v-if="assignedDeliveries.length" class="mt-6 grid gap-4">
                                <article
                                    v-for="delivery in assignedDeliveries"
                                    :key="`assigned-delivery-${delivery.id}`"
                                    class="rounded-[26px] border border-[#edf2f2] bg-[linear-gradient(135deg,#ffffff_0%,#eef8ff_62%,#f4fbfb_100%)] p-5"
                                >
                                    <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                                        <div>
                                            <div class="flex flex-wrap items-center gap-3">
                                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-sky-700">{{ delivery.orderNumber }}</p>
                                                <span :class="delivery.statusAccent" class="rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-[0.16em] ring-1">
                                                    {{ delivery.statusLabel }}
                                                </span>
                                            </div>
                                            <h4 class="mt-2 text-lg font-semibold text-slate-900">{{ delivery.restaurantName }}</h4>
                                            <p class="mt-2 text-sm leading-6 text-slate-600">{{ delivery.summary }}</p>
                                        </div>

                                        <div class="grid gap-2 text-sm text-slate-600 md:text-right">
                                            <p><span class="font-semibold text-slate-900">Placed:</span> {{ delivery.placedAt }}</p>
                                            <p><span class="font-semibold text-slate-900">Total:</span> {{ delivery.total }}</p>
                                            <p><span class="font-semibold text-slate-900">Stop:</span> {{ delivery.destinationShortLabel }}</p>
                                        </div>
                                    </div>
                                </article>
                            </div>

                            <div v-else class="mt-6 rounded-[28px] border border-dashed border-[#d8e7e8] bg-[#fffcf8] p-8 text-sm leading-6 text-slate-500">
                                This user has no assigned delivery workload right now.
                            </div>
                        </section>
                    </div>

                    <div class="space-y-6">
                        <section class="rounded-[32px] border border-white/80 bg-white/88 p-6 shadow-[0_34px_85px_-56px_rgba(11,77,89,0.6)] sm:p-8">
                            <div class="flex items-end justify-between gap-4">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.22em] text-[var(--brand-teal)]">Access controls</p>
                                    <h3 class="mt-2 text-2xl font-semibold text-slate-900">Trust and account moderation</h3>
                                </div>
                            </div>

                            <div class="mt-6 grid gap-4">
                                <article class="rounded-[26px] border border-[#edf2f2] bg-[linear-gradient(135deg,#ffffff_0%,#fff8f1_62%,#f4fbfb_100%)] p-5">
                                    <div class="flex flex-wrap items-start justify-between gap-4">
                                        <div class="max-w-md">
                                            <div class="flex flex-wrap items-center gap-3">
                                                <h4 class="text-lg font-semibold text-slate-900">Account access</h4>
                                                <span :class="user.accessAccent" class="rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-[0.16em] ring-1">
                                                    {{ user.accessLabel }}
                                                </span>
                                            </div>
                                            <p class="mt-3 text-sm leading-6 text-slate-600">
                                                Suspension blocks this account from web sessions, token creation, and authenticated API access.
                                            </p>
                                            <p v-if="user.suspendedAt" class="mt-2 text-sm text-slate-500">
                                                Suspended {{ user.suspendedAt }}.
                                            </p>
                                        </div>

                                        <div class="flex flex-col gap-3 md:items-end">
                                            <Link
                                                v-if="user.canSuspend"
                                                as="button"
                                                method="post"
                                                :href="user.suspendUrl"
                                                preserve-scroll
                                                class="inline-flex items-center justify-center rounded-full bg-[#9f2d2d] px-5 py-3 text-sm font-semibold text-white shadow-[0_20px_45px_-24px_rgba(159,45,45,0.45)] transition duration-200 hover:-translate-y-0.5"
                                            >
                                                Suspend account
                                            </Link>
                                            <Link
                                                v-else-if="user.canRestore"
                                                as="button"
                                                method="post"
                                                :href="user.restoreUrl"
                                                preserve-scroll
                                                class="inline-flex items-center justify-center rounded-full bg-gradient-to-r from-[var(--brand-teal)] to-[var(--brand-orange)] px-5 py-3 text-sm font-semibold text-white shadow-[0_20px_45px_-24px_rgba(11,77,89,0.85)] transition duration-200 hover:-translate-y-0.5"
                                            >
                                                Restore access
                                            </Link>
                                            <p v-else class="max-w-xs text-sm leading-6 text-slate-500 md:text-right">
                                                Self-suspension is disabled for the current admin session.
                                            </p>
                                        </div>
                                    </div>
                                </article>

                                <article v-if="user.merchantApprovalLabel" class="rounded-[26px] border border-[#edf2f2] bg-[linear-gradient(135deg,#ffffff_0%,#eef8ff_62%,#f4fbfb_100%)] p-5">
                                    <div class="flex flex-wrap items-start justify-between gap-4">
                                        <div class="max-w-md">
                                            <div class="flex flex-wrap items-center gap-3">
                                                <h4 class="text-lg font-semibold text-slate-900">Merchant approval</h4>
                                                <span :class="user.merchantApprovalAccent" class="rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-[0.16em] ring-1">
                                                    {{ user.merchantApprovalLabel }}
                                                </span>
                                            </div>
                                            <p class="mt-3 text-sm leading-6 text-slate-600">
                                                Track whether this merchant account is currently approved for operational trust and onboarding review.
                                            </p>
                                            <p v-if="user.merchantApprovedAt" class="mt-2 text-sm text-slate-500">
                                                Approved {{ user.merchantApprovedAt }}.
                                            </p>
                                        </div>

                                        <div class="flex flex-col gap-3 md:items-end">
                                            <Link
                                                v-if="user.canApproveMerchant"
                                                as="button"
                                                method="post"
                                                :href="user.approveMerchantUrl"
                                                preserve-scroll
                                                class="inline-flex items-center justify-center rounded-full bg-gradient-to-r from-[var(--brand-teal)] to-[var(--brand-orange)] px-5 py-3 text-sm font-semibold text-white shadow-[0_20px_45px_-24px_rgba(11,77,89,0.85)] transition duration-200 hover:-translate-y-0.5"
                                            >
                                                Approve merchant
                                            </Link>
                                            <Link
                                                v-else-if="user.canRevokeMerchantApproval"
                                                as="button"
                                                method="post"
                                                :href="user.revokeMerchantApprovalUrl"
                                                preserve-scroll
                                                class="inline-flex items-center justify-center rounded-full border border-white/80 bg-white px-5 py-3 text-sm font-semibold text-slate-700 shadow-[0_18px_42px_-32px_rgba(11,77,89,0.35)] transition duration-200 hover:text-[var(--brand-teal)]"
                                            >
                                                Revoke approval
                                            </Link>
                                        </div>
                                    </div>
                                </article>
                            </div>
                        </section>

                        <section class="rounded-[32px] border border-white/80 bg-white/88 p-6 shadow-[0_34px_85px_-56px_rgba(11,77,89,0.6)] sm:p-8">
                            <div class="flex items-end justify-between gap-4">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.22em] text-[var(--brand-orange-deep)]">Managed restaurants</p>
                                    <h3 class="mt-2 text-2xl font-semibold text-slate-900">Merchant ownership overview</h3>
                                </div>

                                <span class="rounded-full border border-white/80 bg-[#fff7ef] px-4 py-2 text-sm font-medium text-slate-600 shadow-[0_18px_44px_-30px_rgba(11,77,89,0.4)]">
                                    {{ managedRestaurants.length }} records
                                </span>
                            </div>

                            <div v-if="managedRestaurants.length" class="mt-6 grid gap-4">
                                <article
                                    v-for="restaurant in managedRestaurants"
                                    :key="restaurant.id"
                                    class="rounded-[26px] border border-[#edf2f2] bg-[linear-gradient(135deg,#ffffff_0%,#fff8f1_62%,#f4fbfb_100%)] p-5"
                                >
                                    <div class="flex flex-wrap items-start justify-between gap-4">
                                        <div>
                                            <div class="flex flex-wrap items-center gap-3">
                                                <h4 class="text-lg font-semibold text-slate-900">{{ restaurant.name }}</h4>
                                                <span :class="restaurant.statusAccent" class="rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-[0.16em] ring-1">
                                                    {{ restaurant.statusLabel }}
                                                </span>
                                                <span :class="restaurant.visibilityAccent" class="rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-[0.16em] ring-1">
                                                    {{ restaurant.visibilityLabel }}
                                                </span>
                                            </div>
                                            <p class="mt-2 text-sm text-slate-500">{{ restaurant.category }} · {{ restaurant.cuisine }}</p>
                                        </div>

                                        <div class="grid gap-2 text-sm text-slate-600 md:text-right">
                                            <p><span class="font-semibold text-slate-900">Live dishes:</span> {{ restaurant.availableMenuItemsCount }}</p>
                                            <p><span class="font-semibold text-slate-900">Active orders:</span> {{ restaurant.activeOrdersCount }}</p>
                                        </div>
                                    </div>

                                    <div class="mt-4 flex flex-wrap gap-3 border-t border-[#edf2f2] pt-4">
                                        <Link
                                            :href="restaurant.detailUrl"
                                            class="inline-flex items-center justify-center rounded-full bg-gradient-to-r from-[var(--brand-teal)] to-[var(--brand-orange)] px-5 py-3 text-sm font-semibold text-white shadow-[0_20px_45px_-24px_rgba(11,77,89,0.85)] transition duration-200 hover:-translate-y-0.5"
                                        >
                                            Open restaurant
                                        </Link>
                                        <Link
                                            v-if="restaurant.publicMenuUrl"
                                            :href="restaurant.publicMenuUrl"
                                            class="inline-flex items-center justify-center rounded-full border border-white/80 bg-white px-5 py-3 text-sm font-semibold text-slate-700 shadow-[0_18px_42px_-32px_rgba(11,77,89,0.35)] transition duration-200 hover:text-[var(--brand-teal)]"
                                        >
                                            Open public menu
                                        </Link>
                                    </div>
                                </article>
                            </div>

                            <div v-else class="mt-6 rounded-[28px] border border-dashed border-[#d8e7e8] bg-[#fffcf8] p-8 text-sm leading-6 text-slate-500">
                                This account does not currently manage any restaurants.
                            </div>
                        </section>
                    </div>
                </section>
            </div>
        </div>
    </AdminLayout>
</template>