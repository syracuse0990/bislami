import { router } from '@inertiajs/vue3';
import { onBeforeUnmount, ref, watch } from 'vue';

export function useDiscoveryFilterState(initialFilters = {}) {
    const search = ref(initialFilters.search ?? '');
    const activeCuisine = ref(initialFilters.cuisine || 'All');
    const activeCategory = ref(initialFilters.category || 'All');
    let debounceHandle = null;

    const visitWithFilters = ([searchValue, cuisineValue, categoryValue]) => {
        if (typeof window === 'undefined') {
            return;
        }

        const query = {};

        if (searchValue.trim()) {
            query.search = searchValue.trim();
        }

        if (cuisineValue !== 'All') {
            query.cuisine = cuisineValue;
        }

        if (categoryValue !== 'All') {
            query.category = categoryValue;
        }

        router.get(window.location.pathname, query, {
            preserveScroll: true,
            preserveState: true,
            replace: true,
        });
    };

    watch([search, activeCuisine, activeCategory], (nextValues, previousValues) => {
        if (typeof window === 'undefined') {
            return;
        }

        if (debounceHandle) {
            window.clearTimeout(debounceHandle);
        }

        const [nextSearch] = nextValues;
        const [previousSearch = ''] = previousValues ?? [];
        const delay = nextSearch === previousSearch ? 0 : 250;

        debounceHandle = window.setTimeout(() => {
            visitWithFilters(nextValues);
        }, delay);
    }, { flush: 'post' });

    onBeforeUnmount(() => {
        if (debounceHandle && typeof window !== 'undefined') {
            window.clearTimeout(debounceHandle);
        }
    });

    return {
        search,
        activeCuisine,
        activeCategory,
    };
}