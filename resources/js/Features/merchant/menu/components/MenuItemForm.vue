<script setup>
import CheckboxField from '@/Components/Forms/Fields/CheckboxField.vue';
import FileField from '@/Components/Forms/Fields/FileField.vue';
import SelectField from '@/Components/Forms/Fields/SelectField.vue';
import TextareaField from '@/Components/Forms/Fields/TextareaField.vue';
import TextField from '@/Components/Forms/Fields/TextField.vue';
import PrimaryButton from '@/Components/UI/Buttons/PrimaryButton.vue';
import SecondaryButton from '@/Components/UI/Buttons/SecondaryButton.vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    restaurants: {
        type: Array,
        default: () => [],
    },
    categoryOptions: {
        type: Array,
        default: () => [],
    },
    menuItem: {
        type: Object,
        default: null,
    },
    submitLabel: {
        type: String,
        required: true,
    },
    title: {
        type: String,
        required: true,
    },
    description: {
        type: String,
        required: true,
    },
    cancelHref: {
        type: String,
        default: null,
    },
});

const createVariant = (variant = {}) => ({
    name: variant.name ?? '',
    price_delta: variant.price_delta !== undefined && variant.price_delta !== null ? String(variant.price_delta) : '',
});

const createAddOn = (addOn = {}) => ({
    name: addOn.name ?? '',
    price: addOn.price !== undefined && addOn.price !== null ? String(addOn.price) : '',
});

const createModifier = (modifier = {}) => ({
    name: modifier.name ?? '',
    options_text: Array.isArray(modifier.options) ? modifier.options.join(', ') : '',
});

const createBundleItem = (bundleItem = {}) => ({
    name: bundleItem.name ?? '',
    quantity: bundleItem.quantity !== undefined && bundleItem.quantity !== null ? String(bundleItem.quantity) : '1',
});

const defaultState = () => ({
    restaurant_id: props.menuItem?.restaurantId ?? props.restaurants[0]?.value ?? '',
    name: props.menuItem?.name ?? '',
    category: props.menuItem?.category ?? props.categoryOptions[0]?.value ?? '',
    description: props.menuItem?.description ?? '',
    image: null,
    price: props.menuItem?.priceValue?.toString() ?? '',
    promo_price: props.menuItem?.promoPriceValue?.toString() ?? '',
    availability_starts_at: props.menuItem?.availabilityStartsAt ?? '',
    availability_ends_at: props.menuItem?.availabilityEndsAt ?? '',
    variants: props.menuItem?.variants?.length ? props.menuItem.variants.map(createVariant) : [createVariant()],
    add_ons: props.menuItem?.addOns?.length ? props.menuItem.addOns.map(createAddOn) : [createAddOn()],
    modifiers: props.menuItem?.modifiers?.length ? props.menuItem.modifiers.map(createModifier) : [createModifier()],
    bundle_items: props.menuItem?.bundleItems?.length ? props.menuItem.bundleItems.map(createBundleItem) : [createBundleItem()],
    is_available: props.menuItem?.isAvailable ?? true,
});

const form = useForm(defaultState());

const hasSingleRestaurant = computed(() => props.restaurants.length === 1);
const defaultRestaurant = computed(() => props.restaurants[0] ?? null);

const repeaterFactories = {
    variants: createVariant,
    add_ons: createAddOn,
    modifiers: createModifier,
    bundle_items: createBundleItem,
};

const setImage = (file) => {
    form.image = file;
};

const addRepeaterRow = (key) => {
    form[key].push(repeaterFactories[key]());
};

const removeRepeaterRow = (key, index) => {
    if (form[key].length === 1) {
        form[key][0] = repeaterFactories[key]();

        return;
    }

    form[key].splice(index, 1);
};

const normalizedPayload = (data) => ({
    ...data,
    promo_price: data.promo_price === '' ? null : Number(data.promo_price),
    availability_starts_at: data.availability_starts_at || null,
    availability_ends_at: data.availability_ends_at || null,
    variants: data.variants
        .map((variant) => ({
            name: variant.name.trim(),
            price_delta: variant.price_delta === '' ? 0 : Number(variant.price_delta),
        }))
        .filter((variant) => variant.name !== ''),
    add_ons: data.add_ons
        .map((addOn) => ({
            name: addOn.name.trim(),
            price: addOn.price === '' ? 0 : Number(addOn.price),
        }))
        .filter((addOn) => addOn.name !== ''),
    modifiers: data.modifiers
        .map((modifier) => ({
            name: modifier.name.trim(),
            options: modifier.options_text
                .split(',')
                .map((option) => option.trim())
                .filter(Boolean),
        }))
        .filter((modifier) => modifier.name !== '' && modifier.options.length),
    bundle_items: data.bundle_items
        .map((bundleItem) => ({
            name: bundleItem.name.trim(),
            quantity: bundleItem.quantity === '' ? 1 : Number(bundleItem.quantity),
        }))
        .filter((bundleItem) => bundleItem.name !== ''),
});

const submit = () => {
    form.transform(normalizedPayload);

    if (props.menuItem) {
        form.patch(route('merchant.menu.update', props.menuItem.id), {
            forceFormData: true,
        });

        return;
    }

    form.post(route('merchant.menu.store'), {
        forceFormData: true,
    });
};

const destroy = () => {
    if (!props.menuItem || !window.confirm(`Delete ${props.menuItem.name}?`)) {
        return;
    }

    router.delete(route('merchant.menu.destroy', props.menuItem.id));
};
</script>

<template>
    <section class="space-y-6">
        <header class="rounded-[32px] border border-white/80 bg-[linear-gradient(145deg,#ffffff_0%,#fff8f1_100%)] p-6 shadow-[0_24px_64px_-48px_rgba(11,77,89,0.45)] sm:p-8">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-[var(--brand-orange-deep)]">Menu workflow</p>
                    <h3 class="mt-2 text-2xl font-semibold text-slate-900">{{ title }}</h3>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">{{ description }}</p>
                </div>

                <span class="rounded-full bg-white px-4 py-2 text-xs font-semibold uppercase tracking-[0.18em] text-slate-600 ring-1 ring-[#e7efef]">
                    {{ menuItem ? 'Editing existing item' : 'Creating new item' }}
                </span>
            </div>
        </header>

        <div v-if="!restaurants.length" class="rounded-[28px] border border-[#f6dcc5] bg-[#fff8f1] p-5 text-sm leading-6 text-slate-600">
            You do not have a restaurant profile yet, so menu publishing stays locked until the business profile is completed.

            <div class="mt-4">
                <Link
                    :href="route('merchant.profile.show')"
                    class="inline-flex items-center justify-center rounded-full border border-[#f0d6a8] bg-white px-4 py-2 text-xs font-semibold uppercase tracking-[0.18em] text-[var(--brand-orange-deep)] transition duration-200 hover:-translate-y-0.5"
                >
                    Complete profile first
                </Link>
            </div>
        </div>

        <form @submit.prevent="submit" class="space-y-6">
            <section class="rounded-[32px] border border-white/80 bg-white/90 p-6 shadow-[0_24px_64px_-48px_rgba(11,77,89,0.45)] sm:p-8">
                <div class="flex flex-col gap-2 border-b border-[#edf2f2] pb-5">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[var(--brand-teal)]">Basic details</p>
                    <p class="text-sm leading-6 text-slate-600">Start with the core information the customer sees first: restaurant, category, name, description, and base price.</p>
                </div>

                <div class="mt-6 grid gap-4 md:grid-cols-2">
                    <div v-if="hasSingleRestaurant" class="rounded-[24px] border border-[#edf2f2] bg-[#f8fbfb] px-4 py-4 ring-1 ring-[#e1ecec]">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[var(--brand-teal)]">Restaurant</p>
                        <p class="mt-2 text-sm font-semibold text-slate-900">{{ defaultRestaurant?.label }}</p>
                        <p class="mt-1 text-sm leading-6 text-slate-600">This dish will be created under your restaurant profile automatically.</p>
                    </div>

                    <SelectField
                        v-else
                        :id="menuItem ? `restaurant_id_${menuItem.id}` : 'restaurant_id'"
                        v-model="form.restaurant_id"
                        label="Restaurant"
                        :options="restaurants"
                        :message="form.errors.restaurant_id"
                        required
                    />

                    <SelectField
                        :id="menuItem ? `category_${menuItem.id}` : 'category'"
                        v-model="form.category"
                        label="Category"
                        :options="categoryOptions"
                        :message="form.errors.category"
                        required
                    />
                </div>

                <div class="mt-4 grid gap-4 md:grid-cols-[1.4fr_0.6fr]">
                    <TextField
                        :id="menuItem ? `name_${menuItem.id}` : 'name'"
                        v-model="form.name"
                        label="Dish name"
                        :message="form.errors.name"
                        required
                    />

                    <TextField
                        :id="menuItem ? `price_${menuItem.id}` : 'price'"
                        v-model="form.price"
                        label="Base price"
                        type="number"
                        min="1"
                        step="1"
                        :message="form.errors.price"
                        required
                    />
                </div>

                <div class="mt-4">
                    <TextareaField
                        :id="menuItem ? `description_${menuItem.id}` : 'description'"
                        v-model="form.description"
                        label="Description"
                        rows="4"
                        :message="form.errors.description"
                        required
                    />
                </div>
            </section>

            <div class="grid gap-6 xl:grid-cols-[1.1fr_0.9fr]">
                <section class="rounded-[32px] border border-white/80 bg-white/90 p-6 shadow-[0_24px_64px_-48px_rgba(11,77,89,0.45)] sm:p-8">
                    <div class="flex flex-col gap-2 border-b border-[#edf2f2] pb-5">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[var(--brand-teal)]">Pricing and availability</p>
                        <p class="text-sm leading-6 text-slate-600">Only use promo pricing or time windows if the dish truly changes by time of day or campaign.</p>
                    </div>

                    <div class="mt-6 grid gap-4 md:grid-cols-3">
                        <TextField
                            :id="menuItem ? `promo_price_${menuItem.id}` : 'promo_price'"
                            v-model="form.promo_price"
                            label="Promo price"
                            type="number"
                            min="0"
                            step="1"
                            :message="form.errors.promo_price"
                        />

                        <TextField
                            :id="menuItem ? `availability_starts_at_${menuItem.id}` : 'availability_starts_at'"
                            v-model="form.availability_starts_at"
                            label="Available from"
                            type="time"
                            :message="form.errors.availability_starts_at"
                        />

                        <TextField
                            :id="menuItem ? `availability_ends_at_${menuItem.id}` : 'availability_ends_at'"
                            v-model="form.availability_ends_at"
                            label="Available until"
                            type="time"
                            :message="form.errors.availability_ends_at"
                        />
                    </div>

                    <div class="mt-5 rounded-[24px] bg-[#f8fbfb] px-4 py-4 ring-1 ring-[#e1ecec]">
                        <CheckboxField
                            :name="menuItem ? `is_available_${menuItem.id}` : 'is_available'"
                            v-model:checked="form.is_available"
                            label="Available for ordering"
                        />
                    </div>
                </section>

                <section class="rounded-[32px] border border-white/80 bg-white/90 p-6 shadow-[0_24px_64px_-48px_rgba(11,77,89,0.45)] sm:p-8">
                    <div class="flex flex-col gap-2 border-b border-[#edf2f2] pb-5">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[var(--brand-teal)]">Artwork</p>
                        <p class="text-sm leading-6 text-slate-600">A strong image improves browsing and future mobile presentation, but it should stay optional.</p>
                    </div>

                    <div class="mt-6 space-y-5">
                        <FileField
                            :id="menuItem ? `image_${menuItem.id}` : 'image'"
                            label="Dish image"
                            accept="image/png,image/jpeg,image/webp,image/gif"
                            helper="Uploads are stored on Wasabi and reused across customer and merchant views."
                            :message="form.errors.image"
                            @change="setImage"
                        />

                        <div class="rounded-[24px] border border-[#edf2f2] bg-[#f8fbfb] p-4">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[var(--brand-teal)]">Current artwork</p>

                            <div class="mt-3 flex items-center gap-4">
                                <div class="flex h-20 w-20 items-center justify-center overflow-hidden rounded-[22px] bg-white ring-1 ring-[#dceced]">
                                    <img
                                        v-if="menuItem?.imageUrl"
                                        :src="menuItem.imageUrl"
                                        :alt="`${menuItem.name} image`"
                                        class="h-full w-full object-cover"
                                    >
                                    <img
                                        v-else
                                        src="/images/bizlami_icon.png"
                                        alt="BizLami icon"
                                        class="h-12 w-12 object-contain"
                                    >
                                </div>

                                <p class="text-sm leading-6 text-slate-600">
                                    {{ menuItem?.imageUrl
                                        ? 'A live image is already attached to this dish.'
                                        : 'No image uploaded yet. Add one when the dish needs a stronger visual presence in the storefront.' }}
                                </p>
                            </div>
                        </div>
                    </div>
                </section>
            </div>

            <div class="grid gap-6 xl:grid-cols-2">
                <section class="rounded-[32px] border border-white/80 bg-white/90 p-6 shadow-[0_24px_64px_-48px_rgba(11,77,89,0.45)]">
                    <div class="flex items-center justify-between gap-3 border-b border-[#edf2f2] pb-5">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[var(--brand-teal)]">Variants</p>
                            <p class="mt-1 text-sm text-slate-500">Add size or portion changes with price deltas.</p>
                        </div>

                        <SecondaryButton type="button" @click="addRepeaterRow('variants')">
                            Add variant
                        </SecondaryButton>
                    </div>

                    <div class="mt-4 space-y-4">
                        <div v-for="(variant, index) in form.variants" :key="`variant-${index}`" class="rounded-[22px] bg-[#f8fbfb] p-4 ring-1 ring-[#e4eded]">
                            <div class="grid gap-4 md:grid-cols-[1fr_0.45fr_auto] md:items-end">
                                <TextField
                                    :id="`${menuItem ? `variant_name_${menuItem.id}` : 'variant_name'}_${index}`"
                                    v-model="variant.name"
                                    label="Variant name"
                                    :message="form.errors[`variants.${index}.name`]"
                                />

                                <TextField
                                    :id="`${menuItem ? `variant_price_${menuItem.id}` : 'variant_price'}_${index}`"
                                    v-model="variant.price_delta"
                                    label="Price delta"
                                    type="number"
                                    step="1"
                                    :message="form.errors[`variants.${index}.price_delta`]"
                                />

                                <button type="button" class="rounded-full border border-[#f2d3c8] bg-[#fff7f3] px-4 py-3 text-xs font-semibold uppercase tracking-[0.16em] text-[#b95a3c]" @click="removeRepeaterRow('variants', index)">
                                    Remove
                                </button>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="rounded-[32px] border border-white/80 bg-white/90 p-6 shadow-[0_24px_64px_-48px_rgba(11,77,89,0.45)]">
                    <div class="flex items-center justify-between gap-3 border-b border-[#edf2f2] pb-5">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[var(--brand-teal)]">Add-ons</p>
                            <p class="mt-1 text-sm text-slate-500">Use this for extras like sauces, toppings, or drinks.</p>
                        </div>

                        <SecondaryButton type="button" @click="addRepeaterRow('add_ons')">
                            Add add-on
                        </SecondaryButton>
                    </div>

                    <div class="mt-4 space-y-4">
                        <div v-for="(addOn, index) in form.add_ons" :key="`addon-${index}`" class="rounded-[22px] bg-[#f8fbfb] p-4 ring-1 ring-[#e4eded]">
                            <div class="grid gap-4 md:grid-cols-[1fr_0.45fr_auto] md:items-end">
                                <TextField
                                    :id="`${menuItem ? `addon_name_${menuItem.id}` : 'addon_name'}_${index}`"
                                    v-model="addOn.name"
                                    label="Add-on name"
                                    :message="form.errors[`add_ons.${index}.name`]"
                                />

                                <TextField
                                    :id="`${menuItem ? `addon_price_${menuItem.id}` : 'addon_price'}_${index}`"
                                    v-model="addOn.price"
                                    label="Price"
                                    type="number"
                                    step="1"
                                    :message="form.errors[`add_ons.${index}.price`]"
                                />

                                <button type="button" class="rounded-full border border-[#f2d3c8] bg-[#fff7f3] px-4 py-3 text-xs font-semibold uppercase tracking-[0.16em] text-[#b95a3c]" @click="removeRepeaterRow('add_ons', index)">
                                    Remove
                                </button>
                            </div>
                        </div>
                    </div>
                </section>
            </div>

            <div class="grid gap-6 xl:grid-cols-2">
                <section class="rounded-[32px] border border-white/80 bg-white/90 p-6 shadow-[0_24px_64px_-48px_rgba(11,77,89,0.45)]">
                    <div class="flex items-center justify-between gap-3 border-b border-[#edf2f2] pb-5">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[var(--brand-teal)]">Custom modifiers</p>
                            <p class="mt-1 text-sm text-slate-500">Use comma-separated options for things like spice level or protein choice.</p>
                        </div>

                        <SecondaryButton type="button" @click="addRepeaterRow('modifiers')">
                            Add modifier
                        </SecondaryButton>
                    </div>

                    <div class="mt-4 space-y-4">
                        <div v-for="(modifier, index) in form.modifiers" :key="`modifier-${index}`" class="rounded-[22px] bg-[#f8fbfb] p-4 ring-1 ring-[#e4eded]">
                            <div class="grid gap-4">
                                <TextField
                                    :id="`${menuItem ? `modifier_name_${menuItem.id}` : 'modifier_name'}_${index}`"
                                    v-model="modifier.name"
                                    label="Modifier name"
                                    :message="form.errors[`modifiers.${index}.name`]"
                                />

                                <TextareaField
                                    :id="`${menuItem ? `modifier_options_${menuItem.id}` : 'modifier_options'}_${index}`"
                                    v-model="modifier.options_text"
                                    label="Options"
                                    rows="3"
                                    placeholder="Mild, Hot, Extra hot"
                                    :message="form.errors[`modifiers.${index}.options`]"
                                />

                                <div class="flex justify-end">
                                    <button type="button" class="rounded-full border border-[#f2d3c8] bg-[#fff7f3] px-4 py-3 text-xs font-semibold uppercase tracking-[0.16em] text-[#b95a3c]" @click="removeRepeaterRow('modifiers', index)">
                                        Remove
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="rounded-[32px] border border-white/80 bg-white/90 p-6 shadow-[0_24px_64px_-48px_rgba(11,77,89,0.45)]">
                    <div class="flex items-center justify-between gap-3 border-b border-[#edf2f2] pb-5">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[var(--brand-teal)]">Bundle items</p>
                            <p class="mt-1 text-sm text-slate-500">Configure combo inclusions only when the dish is sold as a bundle.</p>
                        </div>

                        <SecondaryButton type="button" @click="addRepeaterRow('bundle_items')">
                            Add bundle item
                        </SecondaryButton>
                    </div>

                    <div class="mt-4 space-y-4">
                        <div v-for="(bundleItem, index) in form.bundle_items" :key="`bundle-${index}`" class="rounded-[22px] bg-[#f8fbfb] p-4 ring-1 ring-[#e4eded]">
                            <div class="grid gap-4 md:grid-cols-[1fr_0.4fr_auto] md:items-end">
                                <TextField
                                    :id="`${menuItem ? `bundle_name_${menuItem.id}` : 'bundle_name'}_${index}`"
                                    v-model="bundleItem.name"
                                    label="Bundle item name"
                                    :message="form.errors[`bundle_items.${index}.name`]"
                                />

                                <TextField
                                    :id="`${menuItem ? `bundle_quantity_${menuItem.id}` : 'bundle_quantity'}_${index}`"
                                    v-model="bundleItem.quantity"
                                    label="Qty"
                                    type="number"
                                    min="1"
                                    step="1"
                                    :message="form.errors[`bundle_items.${index}.quantity`]"
                                />

                                <button type="button" class="rounded-full border border-[#f2d3c8] bg-[#fff7f3] px-4 py-3 text-xs font-semibold uppercase tracking-[0.16em] text-[#b95a3c]" @click="removeRepeaterRow('bundle_items', index)">
                                    Remove
                                </button>
                            </div>
                        </div>
                    </div>
                </section>
            </div>

            <div class="flex flex-wrap items-center gap-3 rounded-[28px] border border-white/80 bg-white px-5 py-4 shadow-[0_24px_64px_-48px_rgba(11,77,89,0.45)]">
                <PrimaryButton :disabled="form.processing || !restaurants.length || !categoryOptions.length">
                    {{ form.processing ? 'Saving...' : submitLabel }}
                </PrimaryButton>

                <Link
                    v-if="cancelHref"
                    :href="cancelHref"
                    class="inline-flex items-center justify-center rounded-2xl border border-[#d6e7e7] bg-white px-5 py-3 text-xs font-semibold uppercase tracking-[0.18em] text-slate-700 transition duration-200 hover:-translate-y-0.5"
                >
                    Cancel
                </Link>

                <button
                    v-if="menuItem"
                    type="button"
                    class="inline-flex items-center justify-center rounded-2xl border border-[#f2d3c8] bg-[#fff7f3] px-5 py-3 text-xs font-semibold uppercase tracking-[0.18em] text-[#b95a3c] transition duration-200 hover:-translate-y-0.5"
                    @click="destroy"
                >
                    Delete item
                </button>

                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <p v-if="form.recentlySuccessful" class="text-sm text-slate-600">
                        Saved.
                    </p>
                </Transition>
            </div>
        </form>
    </section>
</template>