<script setup>
import AddToCartButton from '@/Components/Cart/AddToCartButton.vue';
import FoodSpotlightCard from '@/Features/customer/restaurants/components/FoodSpotlightCard.vue';
import RestaurantCard from '@/Features/customer/restaurants/components/RestaurantCard.vue';
import CustomerLayout from '@/Layouts/CustomerLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    cuisines: {
        type: Array,
        default: () => [],
    },
    foodCategories: {
        type: Array,
        default: () => [],
    },
    foods: {
        type: Array,
        default: () => [],
    },
    restaurants: {
        type: Array,
        default: () => [],
    },
    stats: {
        type: Object,
        default: () => ({
            restaurantsCount: 0,
            foodsCount: 0,
            averageDeliveryMinutes: 0,
            highestRatedRestaurant: null,
        }),
    },
});

const search = ref('');
const activeCuisine = ref('All');
const activeCategory = ref('All');

const cuisineOptions = computed(() => ['All', ...props.cuisines]);
const categoryOptions = computed(() => ['All', ...props.foodCategories]);

const filteredFoods = computed(() => {
    const query = search.value.trim().toLowerCase();

    return props.foods.filter((food) => {
        const matchesQuery = !query || [
            food.name,
            food.description,
            food.restaurantName,
            food.restaurantCategory,
            food.restaurantCuisine,
            food.category,
        ].some((value) => value?.toLowerCase().includes(query));

        const matchesCuisine = activeCuisine.value === 'All' || food.restaurantCategory === activeCuisine.value;
        const matchesCategory = activeCategory.value === 'All' || food.category === activeCategory.value;

        return matchesQuery && matchesCuisine && matchesCategory;
    });
});

const featuredRestaurants = computed(() => props.restaurants.slice(0, 3));
</script>

<template>
    <Head title="Browse Food" />

    <CustomerLayout>
        <template #header>
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div class="space-y-3">
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-[var(--brand-orange-deep)]">
                        Browse experience
                    </p>
                    <h2 class="text-3xl font-semibold leading-tight text-slate-900 sm:text-4xl">
                        Start with the dishes, then move to the kitchen when you want more detail.
                    </h2>
                    <p class="max-w-2xl text-sm leading-6 text-slate-600">
                        The browse workspace stays food-first: faster search, cleaner scanning, and direct ordering actions without burying the menu.
                    </p>
                </div>

                <div class="flex flex-col gap-3 sm:flex-row">
                    <Link
                        :href="route('customer.cart.index')"
                        class="inline-flex items-center justify-center rounded-full bg-[var(--brand-teal)] px-5 py-3 text-sm font-semibold text-white shadow-[0_20px_45px_-24px_rgba(11,77,89,0.85)] transition duration-200 hover:-translate-y-0.5"
                    >
                        View cart
                    </Link>
                    <Link
                        :href="route('customer.orders.index')"
                        class="inline-flex items-center justify-center rounded-full border border-white/80 bg-white/80 px-5 py-3 text-sm font-semibold text-slate-700 transition duration-200 hover:text-[var(--brand-teal)]"
                    >
                        Recent orders
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl space-y-8 sm:px-6 lg:px-8">
                <section class="overflow-hidden rounded-[32px] border border-white/80 bg-[linear-gradient(135deg,#ffffff_0%,#fff7ef_52%,#f3fbfb_100%)] p-6 shadow-[0_34px_85px_-56px_rgba(11,77,89,0.6)] sm:p-8">
                    <div class="grid gap-6 xl:grid-cols-[1.15fr_0.85fr] xl:items-start">
                        <div class="space-y-5">
                            <div class="inline-flex items-center gap-3 rounded-full border border-[#f5dcc7] bg-white/85 px-4 py-2 text-xs font-semibold uppercase tracking-[0.22em] text-[var(--brand-orange-deep)]">
                                <img src="/images/bizlami_icon.png" alt="BizLami icon" class="h-5 w-5 object-contain">
                                Live delivery catalog
                            </div>

                            <div class="space-y-3">
                                <h3 class="text-3xl font-semibold text-slate-900 sm:text-4xl">
                                    Search food the way customers actually think: by dish, vibe, and speed.
                                </h3>
                                <p class="max-w-2xl text-sm leading-7 text-slate-600">
                                    Search across dishes, cuisines, and kitchens, then add straight from the card when you already know what you want.
                                </p>
                            </div>

                            <div class="rounded-[30px] border border-white/80 bg-white/90 p-4 shadow-[0_20px_48px_-36px_rgba(11,77,89,0.42)]">
                                <label for="browse-food-search" class="text-xs font-semibold uppercase tracking-[0.2em] text-[var(--brand-teal)]">
                                    Search your next order
                                </label>

                                <div class="mt-3 relative">
                                    <span class="pointer-events-none absolute inset-y-0 left-4 flex items-center text-slate-400">
                                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M14.1667 14.1667L17.5 17.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                                            <circle cx="8.75" cy="8.75" r="5.75" stroke="currentColor" stroke-width="1.8" />
                                        </svg>
                                    </span>

                                    <input
                                        id="browse-food-search"
                                        v-model="search"
                                        type="search"
                                        placeholder="Search dish, kitchen, cuisine, or category"
                                        class="w-full rounded-full border border-[#dde7e8] bg-white px-12 py-4 text-sm text-slate-700 shadow-[0_18px_45px_-34px_rgba(11,77,89,0.35)] outline-none transition placeholder:text-slate-400 focus:border-[#c4e0e4] focus:ring-2 focus:ring-[#d8edf0]"
                                    >
                                </div>
                            </div>

                            <div class="flex flex-wrap gap-2">
                                <button
                                    v-for="cuisine in cuisineOptions"
                                    :key="cuisine"
                                    type="button"
                                    @click="activeCuisine = cuisine"
                                    :class="activeCuisine === cuisine
                                        ? 'bg-[var(--brand-teal)] text-white shadow-[0_16px_36px_-28px_rgba(11,77,89,0.7)]'
                                        : 'border border-[#e0ecec] bg-white/85 text-slate-600'"
                                    class="rounded-full px-4 py-2 text-sm font-medium transition duration-200"
                                >
                                    {{ cuisine }}
                                </button>
                            </div>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-1">
                            <div class="rounded-[28px] bg-white/88 p-5 shadow-[0_24px_60px_-42px_rgba(11,77,89,0.45)] ring-1 ring-white">
                                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-[var(--brand-teal)]">Average delivery</p>
                                <p class="mt-2 text-3xl font-semibold text-slate-900">{{ stats.averageDeliveryMinutes }} min</p>
                                <p class="mt-2 text-sm leading-6 text-slate-600">Shorter decisions, smoother checkout, and less bouncing between pages.</p>
                            </div>

                            <div class="rounded-[28px] bg-[var(--brand-teal)] p-5 text-white shadow-[0_24px_60px_-36px_rgba(11,77,89,0.6)]">
                                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-white/70">Live dishes</p>
                                <p class="mt-2 text-3xl font-semibold">{{ stats.foodsCount }}</p>
                                <p class="mt-2 text-sm leading-6 text-white/80">Direct food cards now lead the experience instead of generic browsing only.</p>
                            </div>

                            <div class="rounded-[28px] bg-white/88 p-5 shadow-[0_24px_60px_-42px_rgba(11,77,89,0.45)] ring-1 ring-white sm:col-span-2 xl:col-span-1">
                                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-[var(--brand-orange-deep)]">Highest rated kitchen</p>
                                <p class="mt-2 text-2xl font-semibold text-slate-900">{{ stats.highestRatedRestaurant ?? 'Coming soon' }}</p>
                                <p class="mt-2 text-sm leading-6 text-slate-600">Keep restaurant context nearby without forcing customers to start there.</p>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="space-y-4">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-[var(--brand-teal)]">Tonight's picks</p>
                            <h3 class="mt-2 text-2xl font-semibold text-slate-900">Food listings first, with menu and cart actions close by.</h3>
                        </div>

                        <div class="rounded-full border border-white/80 bg-white/80 px-4 py-2 text-sm font-medium text-slate-600 shadow-[0_20px_48px_-36px_rgba(11,77,89,0.35)]">
                            {{ filteredFoods.length }} dishes match your view
                        </div>
                    </div>

                    <div class="rounded-[32px] border border-white/80 bg-white/78 p-5 shadow-[0_30px_80px_-52px_rgba(11,77,89,0.55)] backdrop-blur sm:p-6">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-[var(--brand-orange-deep)]">Dish types</p>
                        <div class="mt-3 flex flex-wrap gap-2">
                            <button
                                v-for="category in categoryOptions"
                                :key="category"
                                type="button"
                                @click="activeCategory = category"
                                :class="activeCategory === category
                                    ? 'bg-[var(--brand-orange)] text-white shadow-[0_16px_36px_-28px_rgba(217,107,18,0.7)]'
                                    : 'border border-[#f0dfcf] bg-white text-slate-600'"
                                class="rounded-full px-4 py-2 text-sm font-medium transition duration-200"
                            >
                                {{ category }}
                            </button>
                        </div>
                    </div>

                    <div v-if="filteredFoods.length" class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
                        <FoodSpotlightCard
                            v-for="food in filteredFoods"
                            :key="food.id"
                            :food="food"
                        >
                            <template #actions="{ food }">
                                <Link
                                    :href="route('customer.restaurants.show', food.restaurantSlug)"
                                    class="inline-flex items-center justify-center rounded-full border border-white/80 bg-white px-5 py-3 text-sm font-semibold text-slate-700 shadow-[0_18px_42px_-32px_rgba(11,77,89,0.35)] transition duration-200 hover:text-[var(--brand-teal)]"
                                >
                                    View menu
                                </Link>

                                <AddToCartButton
                                    :menu-item-id="food.id"
                                    :restaurant-id="food.restaurantId"
                                    :restaurant-name="food.restaurantName"
                                    :item-name="food.name"
                                />
                            </template>
                        </FoodSpotlightCard>
                    </div>

                    <section v-else class="rounded-[32px] border border-white/80 bg-white/82 p-8 shadow-[0_30px_80px_-52px_rgba(11,77,89,0.55)]">
                        <h3 class="text-2xl font-semibold text-slate-900">No dishes match the current filters.</h3>
                        <p class="mt-3 max-w-2xl text-sm leading-7 text-slate-600">
                            Clear the search, switch cuisine, or widen the dish type filter to bring more of the catalog back into view.
                        </p>
                    </section>
                </section>

                <section v-if="restaurants.length" class="space-y-4">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-[var(--brand-teal)]">Kitchen browse</p>
                            <h3 class="mt-2 text-2xl font-semibold text-slate-900">Need the full menu? Jump into a kitchen.</h3>
                        </div>

                        <div class="hidden rounded-full border border-white/80 bg-white/80 px-4 py-2 text-sm font-medium text-slate-600 shadow-[0_20px_48px_-36px_rgba(11,77,89,0.35)] lg:inline-flex">
                            {{ stats.restaurantsCount }} kitchens live
                        </div>
                    </div>

                    <div class="grid gap-6 lg:grid-cols-3">
                        <RestaurantCard
                            v-for="restaurant in featuredRestaurants"
                            :key="restaurant.slug"
                            v-bind="restaurant"
                        />
                    </div>
                </section>
            </div>
        </div>
    </CustomerLayout>
</template>