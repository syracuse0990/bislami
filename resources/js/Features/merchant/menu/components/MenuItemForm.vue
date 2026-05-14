<script setup>
import CheckboxField from '@/Components/Forms/Fields/CheckboxField.vue';
import FileField from '@/Components/Forms/Fields/FileField.vue';
import SelectField from '@/Components/Forms/Fields/SelectField.vue';
import TextareaField from '@/Components/Forms/Fields/TextareaField.vue';
import TextField from '@/Components/Forms/Fields/TextField.vue';
import PrimaryButton from '@/Components/UI/Buttons/PrimaryButton.vue';
import { useForm } from '@inertiajs/vue3';

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
});

const form = useForm({
    restaurant_id: props.menuItem?.restaurantId ?? props.restaurants[0]?.value ?? '',
    name: props.menuItem?.name ?? '',
    category: props.menuItem?.category ?? props.categoryOptions[0]?.value ?? '',
    description: props.menuItem?.description ?? '',
    image: null,
    price: props.menuItem?.priceValue?.toString() ?? '',
    is_available: props.menuItem?.isAvailable ?? true,
});

const setImage = (file) => {
    form.image = file;
};

const submit = () => {
    if (props.menuItem) {
        form.patch(route('merchant.menu.update', props.menuItem.id), {
            preserveScroll: true,
            forceFormData: true,
        });

        return;
    }

    form.post(route('merchant.menu.store'), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            form.reset('name', 'description', 'price', 'image');
            form.category = props.categoryOptions[0]?.value ?? '';
            form.is_available = true;
        },
    });
};
</script>

<template>
    <section>
        <header>
            <h3 class="text-lg font-medium text-slate-900">
                {{ title }}
            </h3>

            <p class="mt-1 text-sm text-slate-600">
                {{ description }}
            </p>
        </header>

        <form @submit.prevent="submit" class="mt-6 space-y-6">
            <div class="grid gap-4 md:grid-cols-2">
                <SelectField
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

            <div class="grid gap-4 md:grid-cols-[1.4fr_0.6fr]">
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
                    label="Price"
                    type="number"
                    min="1"
                    step="1"
                    :message="form.errors.price"
                    required
                />
            </div>

            <TextareaField
                :id="menuItem ? `description_${menuItem.id}` : 'description'"
                v-model="form.description"
                label="Description"
                rows="4"
                :message="form.errors.description"
                required
            />

            <div class="grid gap-5 md:grid-cols-[1fr_0.85fr]">
                <FileField
                    :id="menuItem ? `image_${menuItem.id}` : 'image'"
                    label="Dish image"
                    accept="image/png,image/jpeg,image/webp,image/gif"
                    helper="Uploads are stored on Wasabi and used across customer and merchant views."
                    :message="form.errors.image"
                    @change="setImage"
                />

                <div class="rounded-[24px] border border-[#edf2f2] bg-white/85 p-4">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[var(--brand-teal)]">
                        Current artwork
                    </p>

                    <div class="mt-3 flex items-center gap-4">
                        <div class="flex h-20 w-20 items-center justify-center overflow-hidden rounded-[22px] bg-[#f4fbfb] ring-1 ring-[#dceced]">
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
                                : 'No image uploaded yet. Add one to improve browse and menu discovery.' }}
                        </p>
                    </div>
                </div>
            </div>

            <CheckboxField
                :name="menuItem ? `is_available_${menuItem.id}` : 'is_available'"
                v-model:checked="form.is_available"
                label="Available for ordering"
            />

            <div class="flex items-center gap-4">
                <PrimaryButton :disabled="form.processing">
                    {{ submitLabel }}
                </PrimaryButton>

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