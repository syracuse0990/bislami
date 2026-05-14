<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import QueryPaginationLinks from '@/Components/Navigation/QueryPaginationLinks.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, reactive } from 'vue';

const props = defineProps({
    overview: {
        type: Object,
        default: () => ({
            totalUsers: 0,
            customerUsers: 0,
            merchantUsers: 0,
            courierUsers: 0,
            adminUsers: 0,
        }),
    },
    roles: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({
            search: '',
            role: '',
        }),
    },
    users: {
        type: Array,
        default: () => [],
    },
    usersPagination: {
        type: Object,
        default: () => ({
            currentPage: 1,
            lastPage: 1,
            perPage: 0,
            total: 0,
            from: null,
            to: null,
        }),
    },
});

const filterForm = reactive({
    search: props.filters.search ?? '',
    role: props.filters.role || 'All',
});

const roleOptions = computed(() => ['All', ...props.roles]);
const appliedQuery = computed(() => ({
    search: props.filters.search || undefined,
    role: props.filters.role || undefined,
}));

const applyFilters = () => {
    router.get(route('admin.users.index'), {
        search: filterForm.search.trim() || undefined,
        role: filterForm.role !== 'All' ? filterForm.role : undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const resetFilters = () => {
    filterForm.search = '';
    filterForm.role = 'All';
    router.get(route('admin.users.index'), {}, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};
</script>

<template>
    <Head title="Admin Users" />

    <AdminLayout>
        <template #header>
            <div class="space-y-3">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-[var(--brand-orange-deep)]">
                    Team access
                </p>
                <h2 class="text-3xl font-semibold leading-tight text-slate-900">
                    Manage real users, roles, and operational coverage.
                </h2>
                <p class="max-w-2xl text-sm leading-6 text-slate-600">
                    Review customers, merchants, couriers, and admins from one workspace instead of a placeholder panel.
                </p>
            </div>
        </template>

        <div class="py-8">
            <div class="space-y-6 px-4 sm:px-6 lg:px-0">
                <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
                    <article class="rounded-[28px] border border-white/80 bg-white/82 p-5 shadow-[0_26px_70px_-46px_rgba(11,77,89,0.5)]">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-[var(--brand-teal)]">Total users</p>
                        <p class="mt-2 text-3xl font-semibold text-slate-900">{{ overview.totalUsers }}</p>
                        <p class="mt-2 text-sm leading-6 text-slate-600">Every account currently in the platform.</p>
                    </article>

                    <article class="rounded-[28px] border border-white/80 bg-white/82 p-5 shadow-[0_26px_70px_-46px_rgba(11,77,89,0.5)]">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-[var(--brand-orange-deep)]">Customers</p>
                        <p class="mt-2 text-3xl font-semibold text-slate-900">{{ overview.customerUsers }}</p>
                        <p class="mt-2 text-sm leading-6 text-slate-600">People placing orders and returning to browse.</p>
                    </article>

                    <article class="rounded-[28px] border border-white/80 bg-white/82 p-5 shadow-[0_26px_70px_-46px_rgba(11,77,89,0.5)]">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-[var(--brand-orange-deep)]">Merchants</p>
                        <p class="mt-2 text-3xl font-semibold text-slate-900">{{ overview.merchantUsers }}</p>
                        <p class="mt-2 text-sm leading-6 text-slate-600">Kitchens and restaurant operators on the system.</p>
                    </article>

                    <article class="rounded-[28px] border border-white/80 bg-white/82 p-5 shadow-[0_26px_70px_-46px_rgba(11,77,89,0.5)]">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-[var(--brand-orange-deep)]">Couriers</p>
                        <p class="mt-2 text-3xl font-semibold text-slate-900">{{ overview.courierUsers }}</p>
                        <p class="mt-2 text-sm leading-6 text-slate-600">Delivery staff currently available for dispatch.</p>
                    </article>

                    <article class="rounded-[28px] bg-[var(--brand-teal)] p-5 text-white shadow-[0_26px_70px_-40px_rgba(11,77,89,0.68)]">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-white/70">Admins</p>
                        <p class="mt-2 text-3xl font-semibold">{{ overview.adminUsers }}</p>
                        <p class="mt-2 text-sm leading-6 text-white/80">Platform operators with full visibility.</p>
                    </article>
                </section>

                <section class="rounded-[32px] border border-white/80 bg-[linear-gradient(135deg,#ffffff_0%,#fff8f1_58%,#f3fbfb_100%)] p-6 shadow-[0_34px_85px_-56px_rgba(11,77,89,0.6)] sm:p-8">
                    <form class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_220px_auto_auto] lg:items-end" @submit.prevent="applyFilters">
                        <label class="space-y-2">
                            <span class="text-xs font-semibold uppercase tracking-[0.18em] text-[var(--brand-teal)]">Search people</span>
                            <input
                                v-model="filterForm.search"
                                type="search"
                                placeholder="Search by name or email"
                                class="w-full rounded-full border border-[#dde7e8] bg-white px-5 py-3 text-sm text-slate-700 shadow-[0_18px_45px_-34px_rgba(11,77,89,0.25)] outline-none transition placeholder:text-slate-400 focus:border-[#c4e0e4] focus:ring-2 focus:ring-[#d8edf0]"
                            >
                        </label>

                        <label class="space-y-2">
                            <span class="text-xs font-semibold uppercase tracking-[0.18em] text-[var(--brand-teal)]">Role</span>
                            <select
                                v-model="filterForm.role"
                                class="w-full rounded-full border border-[#dde7e8] bg-white px-5 py-3 text-sm text-slate-700 shadow-[0_18px_45px_-34px_rgba(11,77,89,0.25)] outline-none transition focus:border-[#c4e0e4] focus:ring-2 focus:ring-[#d8edf0]"
                            >
                                <option v-for="role in roleOptions" :key="role" :value="role">
                                    {{ role === 'All' ? 'All roles' : role }}
                                </option>
                            </select>
                        </label>

                        <button
                            type="submit"
                            class="inline-flex items-center justify-center rounded-full bg-[var(--brand-teal)] px-5 py-3 text-sm font-semibold text-white shadow-[0_20px_45px_-24px_rgba(11,77,89,0.85)] transition duration-200 hover:-translate-y-0.5"
                        >
                            Apply filters
                        </button>

                        <button
                            type="button"
                            class="inline-flex items-center justify-center rounded-full border border-white/80 bg-white px-5 py-3 text-sm font-semibold text-slate-700 shadow-[0_18px_42px_-32px_rgba(11,77,89,0.35)] transition duration-200 hover:text-[var(--brand-teal)]"
                            @click="resetFilters"
                        >
                            Reset
                        </button>
                    </form>
                </section>

                <section class="space-y-4">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-[var(--brand-teal)]">User directory</p>
                            <h3 class="mt-2 text-2xl font-semibold text-slate-900">Live platform accounts with operational context.</h3>
                        </div>

                        <div class="rounded-full border border-white/80 bg-white/82 px-4 py-2 text-sm font-medium text-slate-600 shadow-[0_18px_44px_-30px_rgba(11,77,89,0.4)]">
                            {{ usersPagination.total }} users found
                        </div>
                    </div>

                    <div v-if="users.length" class="grid gap-4">
                        <article
                            v-for="user in users"
                            :key="user.id"
                            class="rounded-[28px] border border-white/80 bg-white/88 p-5 shadow-[0_28px_70px_-48px_rgba(11,77,89,0.55)]"
                        >
                            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                <div class="flex items-start gap-4">
                                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-[#f4fbfb] p-2 ring-1 ring-[#dceced]">
                                        <img src="/images/bizlami_icon.png" alt="BizLami icon" class="h-full w-full object-contain">
                                    </div>

                                    <div class="space-y-3">
                                        <div>
                                            <div class="flex flex-wrap items-center gap-3">
                                                <h4 class="text-lg font-semibold text-slate-900">{{ user.name }}</h4>
                                                <span
                                                    class="inline-flex rounded-full px-3 py-1 text-xs font-semibold ring-1"
                                                    :class="user.roleAccent"
                                                >
                                                    {{ user.roleLabel }}
                                                </span>
                                            </div>
                                            <p class="mt-1 text-sm text-slate-500">{{ user.email }}</p>
                                        </div>

                                        <div class="flex flex-wrap gap-2">
                                            <span class="rounded-full px-3 py-1 text-xs font-medium ring-1" :class="user.accessAccent">
                                                {{ user.accessLabel }}
                                            </span>
                                            <span class="rounded-full border border-[#dceced] bg-[#f4fbfb] px-3 py-1 text-xs font-medium text-slate-700">
                                                {{ user.verificationLabel }}
                                            </span>
                                            <span
                                                v-if="user.merchantApprovalLabel"
                                                class="rounded-full px-3 py-1 text-xs font-medium ring-1"
                                                :class="user.merchantApprovalAccent"
                                            >
                                                {{ user.merchantApprovalLabel }}
                                            </span>
                                            <span class="rounded-full border border-[#ece7de] bg-white px-3 py-1 text-xs font-medium text-slate-700">
                                                Joined {{ user.joinedAt }}
                                            </span>
                                        </div>

                                        <p class="text-sm leading-6 text-slate-600">{{ user.workloadLabel }}</p>
                                    </div>
                                </div>

                                <div class="grid gap-3 sm:grid-cols-3 lg:min-w-[320px] lg:max-w-[340px]">
                                    <div class="rounded-[22px] bg-[#f4fbfb] px-4 py-3 ring-1 ring-[#dceced]">
                                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[var(--brand-teal)]">Restaurants</p>
                                        <p class="mt-2 text-sm font-semibold text-slate-900">{{ user.managedRestaurantsCount }}</p>
                                    </div>

                                    <div class="rounded-[22px] bg-[#fff7ef] px-4 py-3 ring-1 ring-[#f6dcc5]">
                                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[var(--brand-orange-deep)]">Orders</p>
                                        <p class="mt-2 text-sm font-semibold text-slate-900">{{ user.ordersCount }}</p>
                                    </div>

                                    <div class="rounded-[22px] bg-white px-4 py-3 ring-1 ring-[#ececec]">
                                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Deliveries</p>
                                        <p class="mt-2 text-sm font-semibold text-slate-900">{{ user.activeAssignedDeliveriesCount }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-4 flex flex-wrap gap-3 border-t border-[#edf2f2] pt-4">
                                <Link
                                    :href="user.detailUrl"
                                    class="inline-flex items-center justify-center rounded-full bg-gradient-to-r from-[var(--brand-teal)] to-[var(--brand-orange)] px-5 py-3 text-sm font-semibold text-white shadow-[0_20px_45px_-24px_rgba(11,77,89,0.85)] transition duration-200 hover:-translate-y-0.5"
                                >
                                    Open profile
                                </Link>

                                <a
                                    :href="user.emailUrl"
                                    class="inline-flex items-center justify-center rounded-full border border-white/80 bg-white px-5 py-3 text-sm font-semibold text-slate-700 shadow-[0_18px_42px_-32px_rgba(11,77,89,0.35)] transition duration-200 hover:text-[var(--brand-teal)]"
                                >
                                    Email user
                                </a>

                                <Link
                                    v-if="user.restaurantsIndexUrl"
                                    :href="user.restaurantsIndexUrl"
                                    class="inline-flex items-center justify-center rounded-full border border-white/80 bg-white px-5 py-3 text-sm font-semibold text-slate-700 shadow-[0_18px_42px_-32px_rgba(11,77,89,0.35)] transition duration-200 hover:text-[var(--brand-teal)]"
                                >
                                    View managed restaurants
                                </Link>
                            </div>
                        </article>
                    </div>

                    <QueryPaginationLinks
                        v-if="users.length"
                        route-name="admin.users.index"
                        :query="appliedQuery"
                        :pagination="usersPagination"
                        item-label="users"
                    />

                    <section v-else class="rounded-[32px] border border-white/80 bg-white/82 p-8 shadow-[0_30px_80px_-52px_rgba(11,77,89,0.55)]">
                        <h3 class="text-2xl font-semibold text-slate-900">No users match the current filters.</h3>
                        <p class="mt-3 max-w-2xl text-sm leading-7 text-slate-600">
                            Broaden the search term or reset the role filter to bring more accounts back into view.
                        </p>
                    </section>
                </section>
            </div>
        </div>
    </AdminLayout>
</template>