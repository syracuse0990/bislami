<script setup>
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    routeName: {
        type: String,
        required: true,
    },
    query: {
        type: Object,
        default: () => ({}),
    },
    pagination: {
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
    itemLabel: {
        type: String,
        default: 'results',
    },
});

const visiblePages = computed(() => {
    if (props.pagination.lastPage <= 1) {
        return [];
    }

    const pages = new Set([1, props.pagination.currentPage - 1, props.pagination.currentPage, props.pagination.currentPage + 1, props.pagination.lastPage]);

    return [...pages]
        .filter((page) => page >= 1 && page <= props.pagination.lastPage)
        .sort((left, right) => left - right);
});

const sanitizedQuery = computed(() => Object.fromEntries(
    Object.entries(props.query).filter(([, value]) => value !== undefined && value !== null && value !== ''),
));

const queryFor = (page) => {
    if (page <= 1) {
        return sanitizedQuery.value;
    }

    return {
        ...sanitizedQuery.value,
        page,
    };
};
</script>

<template>
    <div
        v-if="pagination.lastPage > 1"
        class="flex flex-col gap-4 rounded-[28px] border border-white/80 bg-white/82 px-5 py-4 shadow-[0_24px_60px_-42px_rgba(11,77,89,0.35)] sm:flex-row sm:items-center sm:justify-between"
    >
        <div class="text-sm text-slate-600">
            Showing <span class="font-semibold text-slate-900">{{ pagination.from ?? 0 }}-{{ pagination.to ?? 0 }}</span> of <span class="font-semibold text-slate-900">{{ pagination.total }}</span> {{ itemLabel }}
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <Link
                v-if="pagination.currentPage > 1"
                :href="route(routeName, queryFor(pagination.currentPage - 1))"
                preserve-scroll
                class="inline-flex items-center justify-center rounded-full border border-[#dceced] bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition duration-200 hover:text-[var(--brand-teal)]"
            >
                Previous
            </Link>

            <span
                v-else
                class="inline-flex items-center justify-center rounded-full border border-[#eef4f4] bg-[#f8fbfb] px-4 py-2 text-sm font-semibold text-slate-400"
            >
                Previous
            </span>

            <Link
                v-for="page in visiblePages"
                :key="`page-${page}`"
                :href="route(routeName, queryFor(page))"
                preserve-scroll
                :class="page === pagination.currentPage
                    ? 'bg-[var(--brand-teal)] text-white shadow-[0_16px_36px_-28px_rgba(11,77,89,0.7)]'
                    : 'border border-[#dceced] bg-white text-slate-700 hover:text-[var(--brand-teal)]'"
                class="inline-flex h-11 min-w-11 items-center justify-center rounded-full px-4 text-sm font-semibold transition duration-200"
            >
                {{ page }}
            </Link>

            <Link
                v-if="pagination.currentPage < pagination.lastPage"
                :href="route(routeName, queryFor(pagination.currentPage + 1))"
                preserve-scroll
                class="inline-flex items-center justify-center rounded-full border border-[#dceced] bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition duration-200 hover:text-[var(--brand-teal)]"
            >
                Next
            </Link>

            <span
                v-else
                class="inline-flex items-center justify-center rounded-full border border-[#eef4f4] bg-[#f8fbfb] px-4 py-2 text-sm font-semibold text-slate-400"
            >
                Next
            </span>
        </div>
    </div>
</template>