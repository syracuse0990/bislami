<script setup>
import MerchantLayout from '@/Layouts/MerchantLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { computed, reactive } from 'vue';

const props = defineProps({
    groups: {
        type: Array,
        default: () => [],
    },
    today: {
        type: String,
        required: true,
    },
    multipleRestaurants: {
        type: Boolean,
        default: false,
    },
    hasYesterdayData: {
        type: Boolean,
        default: false,
    },
});

const formattedDate = computed(() =>
    new Date(props.today + 'T00:00:00').toLocaleDateString('en-US', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    }),
);

const allItems = computed(() => props.groups.flatMap((g) => g.items));

const groupedByCategory = computed(() =>
    props.groups.map((group) => ({
        ...group,
        categories: Object.entries(
            group.items.reduce((acc, item) => {
                (acc[item.category] ??= []).push(item);
                return acc;
            }, {}),
        ).map(([name, items]) => ({ name, items })),
    })),
);

// Capacities keyed by menu item id; empty string = unlimited (null on save)
const form = useForm({
    capacities: Object.fromEntries(
        allItems.value.map((item) => [item.id, item.capacity ?? '']),
    ),
});

const limitedCount = computed(
    () => allItems.value.filter((item) => form.capacities[item.id] !== '' && form.capacities[item.id] !== null).length,
);

function clearAll() {
    allItems.value.forEach((item) => {
        form.capacities[item.id] = '';
    });
}

function copyFromYesterday() {
    allItems.value.forEach((item) => {
        if (item.yesterdayCapacity != null) {
            form.capacities[item.id] = item.yesterdayCapacity;
        }
    });
}

// Local availability overrides for optimistic toggle UI
const localAvailability = reactive(
    Object.fromEntries(allItems.value.map((item) => [item.id, item.isAvailable])),
);
const togglingItem = reactive({});

function toggleAvailability(item) {
    localAvailability[item.id] = !localAvailability[item.id];
    togglingItem[item.id] = true;
    router.patch(
        route('merchant.menu.availability', item.id),
        {},
        {
            preserveScroll: true,
            preserveState: true,
            only: ['groups'],
            onSuccess: () => { togglingItem[item.id] = false; },
            onError: () => {
                localAvailability[item.id] = !localAvailability[item.id]; // revert
                togglingItem[item.id] = false;
            },
        },
    );
}

// Tab state keyed by group id
const activeTab = reactive({});

function activeCategory(group) {
    return activeTab[group.id] ?? group.categories[0]?.name;
}

function setActiveTab(groupId, catName) {
    activeTab[groupId] = catName;
}

function categoryLimitedCount(cat) {
    return cat.items.filter(
        (item) => form.capacities[item.id] !== '' && form.capacities[item.id] !== null,
    ).length;
}

function save() {
    form.put(route('merchant.menu.daily.save'), { preserveScroll: true });
}
</script>

<template>
    <Head title="Today's Service" />

    <MerchantLayout>
        <template #header>
            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-[var(--brand-teal)]">
                Today's Service
            </p>
            <h1 class="mt-2 text-2xl font-bold text-slate-900 sm:text-3xl">
                {{ formattedDate }}
            </h1>
            <p class="mt-2 text-sm text-slate-500">
                Toggle item availability and set order limits for today's service. Leave the limit blank for no cap.
            </p>
        </template>

        <div class="py-8">
            <div class="space-y-5 px-4 sm:px-6 lg:px-0">

                <!-- Action bar -->
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="inline-flex items-center rounded-full border border-white/80 bg-white/90 px-3.5 py-1.5 text-sm font-medium text-slate-600 shadow-sm">
                            {{ allItems.length }} item{{ allItems.length === 1 ? '' : 's' }}
                        </span>
                        <span
                            v-if="limitedCount > 0"
                            class="inline-flex items-center gap-1.5 rounded-full border border-amber-200 bg-amber-50 px-3.5 py-1.5 text-sm font-medium text-amber-700"
                        >
                            <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                            {{ limitedCount }} with a limit
                        </span>
                    </div>

                    <div class="flex items-center gap-3">
                        <button
                            v-if="hasYesterdayData && limitedCount === 0"
                            type="button"
                            @click="copyFromYesterday"
                            class="rounded-full border border-[#e0ecec] bg-white px-4 py-2 text-sm font-semibold text-slate-600 transition duration-150 hover:border-[var(--brand-teal)]/50 hover:text-[var(--brand-teal)]"
                        >
                            Copy from yesterday
                        </button>
                        <button
                            v-if="limitedCount > 0"
                            type="button"
                            @click="clearAll"
                            class="rounded-full border border-[#e0ecec] bg-white px-4 py-2 text-sm font-semibold text-slate-600 transition duration-150 hover:border-[var(--brand-teal)]/50 hover:text-[var(--brand-teal)]"
                        >
                            Clear all limits
                        </button>

                        <button
                            type="button"
                            @click="save"
                            :disabled="form.processing"
                            class="inline-flex items-center gap-2 rounded-full bg-gradient-to-r from-[var(--brand-teal)] to-[var(--brand-orange)] px-6 py-2.5 text-sm font-semibold text-white shadow-[0_20px_45px_-24px_rgba(11,77,89,0.85)] transition duration-200 hover:-translate-y-0.5 disabled:cursor-not-allowed disabled:opacity-60"
                        >
                            <svg v-if="form.processing" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                            </svg>
                            Save
                        </button>

                        <transition
                            enter-active-class="transition duration-300"
                            enter-from-class="opacity-0 translate-y-1"
                            enter-to-class="opacity-100 translate-y-0"
                            leave-active-class="transition duration-150"
                            leave-from-class="opacity-100"
                            leave-to-class="opacity-0"
                        >
                            <span
                                v-if="form.recentlySuccessful"
                                class="flex items-center gap-1.5 text-sm font-semibold text-[var(--brand-teal)]"
                            >
                                <svg viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                </svg>
                                Saved!
                            </span>
                        </transition>
                    </div>
                </div>

                <!-- Empty state -->
                <div
                    v-if="groups.length === 0"
                    class="rounded-[32px] border border-white/80 bg-white/90 p-12 text-center shadow-sm"
                >
                    <p class="text-sm text-slate-500">No menu items found. Add items to your menu first.</p>
                </div>

                <!-- Per-restaurant group -->
                <div
                    v-for="group in groupedByCategory"
                    :key="group.id"
                    class="overflow-hidden rounded-[32px] border border-white/80 bg-[linear-gradient(145deg,#ffffff_0%,#fff8f1_58%,#f4fbfb_100%)] shadow-[0_34px_85px_-56px_rgba(11,77,89,0.6)]"
                >
                    <!-- Restaurant header (only when merchant runs multiple restaurants) -->
                    <div v-if="multipleRestaurants" class="flex items-center gap-3 border-b border-[#edf2f2] px-6 py-4">
                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-[12px] bg-gradient-to-br from-[var(--brand-teal)] to-[var(--brand-orange)]">
                            <svg viewBox="0 0 24 24" fill="none" class="h-4 w-4 text-white" aria-hidden="true">
                                <path d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </div>
                        <span class="text-sm font-semibold text-slate-900">{{ group.name }}</span>
                    </div>

                    <!-- Tab bar -->
                    <div class="flex gap-0 overflow-x-auto border-b border-[#edf2f2] scrollbar-none">
                        <button
                            v-for="cat in group.categories"
                            :key="cat.name"
                            type="button"
                            @click="setActiveTab(group.id, cat.name)"
                            :class="[
                                'relative shrink-0 px-5 py-3.5 text-sm font-semibold transition-colors duration-150 focus:outline-none',
                                activeCategory(group) === cat.name
                                    ? 'text-[var(--brand-teal)] after:absolute after:bottom-0 after:left-0 after:right-0 after:h-0.5 after:rounded-full after:bg-[var(--brand-teal)] after:content-[\'\']'
                                    : 'text-slate-400 hover:text-slate-700',
                            ]"
                        >
                            {{ cat.name }}
                            <span
                                v-if="categoryLimitedCount(cat) > 0"
                                class="ml-1.5 inline-flex items-center rounded-full bg-amber-100 px-1.5 py-0.5 text-[10px] font-bold text-amber-700"
                            >{{ categoryLimitedCount(cat) }}</span>
                        </button>
                    </div>

                    <!-- Table for active category -->
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-[#edf2f2]">
                                <th class="px-6 py-3 text-left text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-400">
                                    Item
                                </th>
                                <th class="hidden px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-400 md:table-cell" style="width:160px">
                                    Status
                                </th>
                                <th class="px-6 py-3 text-right text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-400" style="width:170px">
                                    Today's limit
                                </th>
                            </tr>
                        </thead>

                        <tbody>
                            <template
                                v-for="cat in group.categories"
                                :key="cat.name"
                            >
                            <template v-if="activeCategory(group) === cat.name">

                            <tr
                                v-for="item in cat.items"
                                :key="item.id"
                                :class="[
                                    'border-b border-[#f0f5f5] last:border-0 transition-colors duration-100',
                                    localAvailability[item.id] ? 'hover:bg-[#fcfefe]' : 'bg-slate-50/50 hover:bg-slate-50',
                                ]"
                            >
                                <!-- Item thumbnail + name -->
                                <td class="px-6 py-3">
                                    <div class="flex items-center gap-3">
                                        <div class="hidden h-11 w-11 shrink-0 items-center justify-center overflow-hidden rounded-[14px] bg-[#f4fbfb] ring-1 ring-[#dceced] sm:flex">
                                            <img
                                                v-if="item.imageUrl"
                                                :src="item.imageUrl"
                                                :alt="item.name"
                                                class="h-full w-full object-cover"
                                            >
                                            <svg v-else viewBox="0 0 24 24" fill="none" class="h-5 w-5 text-[#b2d4d5]" aria-hidden="true">
                                                <path d="M4 16l4-4 4 4 4-8 4 8M4 20h16" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round" />
                                            </svg>
                                        </div>
                                        <div class="min-w-0">
                                            <p class="truncate text-sm font-semibold text-slate-900">{{ item.name }}</p>
                                        </div>
                                    </div>
                                </td>

                                <!-- Availability toggle -->
                                <td class="hidden px-4 py-3 md:table-cell" style="width:160px">
                                    <button
                                        type="button"
                                        @click="toggleAvailability(item)"
                                        :disabled="togglingItem[item.id]"
                                        :aria-label="localAvailability[item.id] ? 'Pause item' : 'Make item live'"
                                        :aria-checked="localAvailability[item.id]"
                                        role="switch"
                                        :class="[
                                            'relative inline-flex h-5 w-9 shrink-0 items-center rounded-full border-2 border-transparent transition-colors duration-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-[var(--brand-teal)] disabled:opacity-50',
                                            localAvailability[item.id] ? 'bg-emerald-500' : 'bg-slate-200',
                                        ]"
                                    >
                                        <span
                                            :class="[
                                                'pointer-events-none inline-block h-4 w-4 rounded-full bg-white shadow transition-transform duration-200',
                                                localAvailability[item.id] ? 'translate-x-4' : 'translate-x-0',
                                            ]"
                                        />
                                    </button>
                                    <span class="ml-2 inline-block w-10 text-xs font-medium" :class="localAvailability[item.id] ? 'text-emerald-700' : 'text-slate-400'">
                                        {{ localAvailability[item.id] ? 'Live' : 'Paused' }}
                                    </span>
                                </td>

                                <!-- Capacity input -->
                                <td class="px-6 py-3" style="width:170px">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <input
                                            type="number"
                                            min="1"
                                            max="9999"
                                            v-model="form.capacities[item.id]"
                                            placeholder="Unlimited"
                                            :class="[
                                                'w-28 rounded-[12px] border py-2 pl-3 pr-3 text-right text-sm font-semibold transition duration-150 focus:outline-none focus:ring-2 focus:ring-[var(--brand-teal)]/25',
                                                form.capacities[item.id] !== '' && form.capacities[item.id] !== null
                                                    ? 'border-[var(--brand-teal)]/40 bg-[var(--brand-teal)]/5 text-slate-900 focus:border-[var(--brand-teal)]/60'
                                                    : 'border-[#d8e8e8] bg-white text-slate-400 placeholder:text-slate-300 focus:border-[#aacfcf]',
                                            ]"
                                        >
                                        <!-- Clear button -->
                                        <button
                                            v-if="form.capacities[item.id] !== '' && form.capacities[item.id] !== null"
                                            type="button"
                                            @click="form.capacities[item.id] = ''"
                                            title="Remove limit"
                                            class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
                                        >
                                            <svg viewBox="0 0 20 20" fill="currentColor" class="h-3.5 w-3.5" aria-hidden="true">
                                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            </template>
                            </template>
                        </tbody>
                    </table>
                </div>

                <!-- Bottom save (shown only for longer lists) -->
                <div v-if="allItems.length > 5" class="flex items-center justify-end gap-4 pb-2">
                    <transition
                        enter-active-class="transition duration-300"
                        enter-from-class="opacity-0"
                        enter-to-class="opacity-100"
                        leave-active-class="transition duration-150"
                        leave-from-class="opacity-100"
                        leave-to-class="opacity-0"
                    >
                        <span v-if="form.recentlySuccessful" class="flex items-center gap-1.5 text-sm font-semibold text-[var(--brand-teal)]">
                            <svg viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4" aria-hidden="true">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                            Saved!
                        </span>
                    </transition>
                    <button
                        type="button"
                        @click="save"
                        :disabled="form.processing"
                        class="inline-flex items-center gap-2 rounded-full bg-gradient-to-r from-[var(--brand-teal)] to-[var(--brand-orange)] px-6 py-2.5 text-sm font-semibold text-white shadow-[0_20px_45px_-24px_rgba(11,77,89,0.85)] transition duration-200 hover:-translate-y-0.5 disabled:cursor-not-allowed disabled:opacity-60"
                    >
                        Save
                    </button>
                </div>

            </div>
        </div>
    </MerchantLayout>
</template>
