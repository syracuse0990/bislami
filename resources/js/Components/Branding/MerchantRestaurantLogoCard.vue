<script setup>
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    title: {
        type: String,
        default: 'Restaurant branding',
    },
    note: {
        type: String,
        default: 'Use your restaurant logo to keep merchant tools visually consistent.',
    },
    restaurantName: {
        type: String,
        default: '',
    },
});

const page = usePage();
const restaurantBrand = computed(() => page.props.auth?.restaurantBrand ?? null);
const logoUrl = computed(() => restaurantBrand.value?.logoUrl ?? null);
const resolvedName = computed(() => props.restaurantName || restaurantBrand.value?.name || 'Restaurant profile');
</script>

<template>
    <section class="overflow-hidden rounded-[28px] border border-white/80 bg-[linear-gradient(145deg,#ffffff_0%,#fff8f1_58%,#f4fbfb_100%)] p-5 shadow-[0_28px_70px_-52px_rgba(11,77,89,0.45)] sm:p-6">
        <div class="flex items-center gap-4">
            <div class="flex h-16 w-16 shrink-0 items-center justify-center overflow-hidden rounded-[22px] bg-white ring-1 ring-[#dceced] shadow-sm sm:h-[72px] sm:w-[72px]">
                <img
                    v-if="logoUrl"
                    :src="logoUrl"
                    :alt="`${resolvedName} logo`"
                    class="h-full w-full object-cover"
                >
                <img
                    v-else
                    src="/images/bizlami_icon.png"
                    alt="BizLami icon"
                    class="h-10 w-10 object-contain"
                >
            </div>

            <div class="min-w-0 flex-1">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-[var(--brand-orange-deep)]">{{ title }}</p>
                <h3 class="mt-2 truncate text-lg font-semibold text-slate-900 sm:text-xl">{{ resolvedName }}</h3>
                <p class="mt-1 text-sm leading-6 text-slate-500">{{ note }}</p>
            </div>
        </div>
    </section>
</template>