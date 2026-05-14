<script setup>
import DeliveryAreaField from '@/Components/Maps/DeliveryAreaField.vue';
import CheckboxField from '@/Components/Forms/Fields/CheckboxField.vue';
import FileField from '@/Components/Forms/Fields/FileField.vue';
import GoogleAddressField from '@/Components/Maps/GoogleAddressField.vue';
import PrimaryButton from '@/Components/UI/Buttons/PrimaryButton.vue';
import Time12HourField from '@/Components/Forms/Fields/Time12HourField.vue';
import TextareaField from '@/Components/Forms/Fields/TextareaField.vue';
import TextField from '@/Components/Forms/Fields/TextField.vue';
import { format24HourTo12Hour } from '@/Support/time12Hour';
import { sileo } from '@llayz46/sileo-vue';
import { useForm, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, ref, watch } from 'vue';

const props = defineProps({
    profile: {
        type: Object,
        required: true,
    },
    hasProfile: {
        type: Boolean,
        default: false,
    },
});

const page = usePage();
const googleMapsEnabled = computed(() => Boolean(page.props.services?.googleMaps?.enabled));

const form = useForm({
    name: props.profile.name ?? '',
    featured_text: props.profile.featuredText ?? '',
    contact_phone: props.profile.contactPhone ?? '',
    location_address: props.profile.locationAddress ?? '',
    location_latitude: props.profile.locationLatitude ?? null,
    location_longitude: props.profile.locationLongitude ?? null,
    delivery_radius_km: props.profile.deliveryRadiusKm ?? '5',
    delivery_area_coordinates: props.profile.deliveryAreaCoordinates?.map((point) => ({
        lat: point.lat,
        lng: point.lng,
    })) ?? [],
    minimum_order_value: props.profile.minimumOrderValue ?? '0',
    preparation_time_min: props.profile.preparationTimeMin ?? '15',
    preparation_time_max: props.profile.preparationTimeMax ?? '30',
    operating_hours: props.profile.operatingHours?.map((hour) => ({
        day: hour.day,
        label: hour.label,
        enabled: Boolean(hour.enabled),
        open: hour.open ?? '07:00',
        close: hour.close ?? '17:00',
    })) ?? [],
    closure_dates: props.profile.closureDates?.length ? [...props.profile.closureDates] : [''],
    logo: null,
});

const tabs = [
    {
        key: 'business',
        label: 'Business details',
        step: '01',
        hint: 'Brand, contacts, logo, address, and storefront pin.',
    },
    {
        key: 'hours',
        label: 'Operating hours',
        step: '02',
        hint: 'Weekly schedule in a clear 12-hour table layout.',
    },
    {
        key: 'delivery',
        label: 'Delivery and closures',
        step: '03',
        hint: 'Service area polygon, prep window, and closure dates.',
    },
];

const activeTab = ref('business');
const activeTabMeta = computed(() => tabs.find((tab) => tab.key === activeTab.value) ?? tabs[0]);
const logoPreviewUrl = ref(null);
const currentLogoUrl = computed(() => logoPreviewUrl.value ?? props.profile.logoUrl ?? null);
const summaryCards = computed(() => [
    {
        label: 'Menu items',
        value: props.profile.menuItemsCount ?? 0,
        detail: 'Items linked to this restaurant profile',
    },
    {
        label: 'Orders',
        value: props.profile.ordersCount ?? 0,
        detail: 'Orders recorded against this restaurant',
    },
    {
        label: 'Map pin',
        value: props.profile.hasPin ? 'Saved' : 'Pending',
        detail: props.profile.hasPin ? 'Pin is ready for map-based workflows' : 'Add coordinates for precise routing',
    },
    {
        label: 'Delivery area',
        value: form.delivery_area_coordinates.length >= 3 ? 'Drawn' : 'Pending',
        detail: form.delivery_area_coordinates.length >= 3
            ? `${form.delivery_area_coordinates.length} polygon point${form.delivery_area_coordinates.length === 1 ? '' : 's'} saved`
            : 'Draw a polygon to show the zones this restaurant can serve',
    },
]);

function formattedHoursLabel(hour) {
    if (!hour.enabled) {
        return 'Closed';
    }

    return `${format24HourTo12Hour(hour.open)} - ${format24HourTo12Hour(hour.close)}`;
}

function hasValue(value) {
    if (typeof value === 'string') {
        return value.trim().length > 0;
    }

    return value !== null && value !== undefined && `${value}`.trim() !== '';
}

function clearFieldError(field, shouldClear = true) {
    if (shouldClear) {
        form.clearErrors(field);
    }
}

function tabForError(key) {
    if (!key) {
        return null;
    }

    if (key.startsWith('operating_hours')) {
        return 'hours';
    }

    if (key.startsWith('delivery_area_coordinates')
        || key.startsWith('minimum_order_value')
        || key.startsWith('preparation_time_')
        || key.startsWith('closure_dates')) {
        return 'delivery';
    }

    return 'business';
}

watch(
    () => ({ ...form.errors }),
    (errors) => {
        const firstErrorKey = Object.keys(errors)[0];
        const tab = tabForError(firstErrorKey);

        if (tab) {
            activeTab.value = tab;
        }
    },
    { deep: true },
);

watch(() => form.name, (value) => clearFieldError('name', hasValue(value)));
watch(() => form.contact_phone, (value) => clearFieldError('contact_phone', hasValue(value)));
watch(() => form.featured_text, (value) => clearFieldError('featured_text', hasValue(value)));
watch(() => form.location_address, (value) => clearFieldError('location_address', hasValue(value)));
watch(() => form.minimum_order_value, (value) => clearFieldError('minimum_order_value', hasValue(value)));
watch(() => form.preparation_time_min, (value) => clearFieldError('preparation_time_min', hasValue(value)));
watch(() => form.preparation_time_max, (value) => clearFieldError('preparation_time_max', hasValue(value)));
watch(
    () => [form.location_latitude, form.location_longitude],
    ([latitude, longitude]) => {
        if (hasValue(latitude) && hasValue(longitude)) {
            form.clearErrors('location_latitude', 'location_longitude');
        }
    },
);
watch(
    () => JSON.stringify(form.delivery_area_coordinates ?? []),
    () => clearFieldError('delivery_area_coordinates', (form.delivery_area_coordinates?.length ?? 0) >= 3),
);
watch(
    () => JSON.stringify(form.operating_hours ?? []),
    () => {
        let allEnabledHoursComplete = true;

        form.operating_hours.forEach((hour, index) => {
            if (!hour.enabled) {
                return;
            }

            if (hasValue(hour.open)) {
                form.clearErrors(`operating_hours.${index}.open`);
            } else {
                allEnabledHoursComplete = false;
            }

            if (hasValue(hour.close)) {
                form.clearErrors(`operating_hours.${index}.close`);
            } else {
                allEnabledHoursComplete = false;
            }
        });

        if (form.operating_hours.length === 7 && allEnabledHoursComplete) {
            form.clearErrors('operating_hours');
        }
    },
);

onBeforeUnmount(() => {
    if (logoPreviewUrl.value) {
        URL.revokeObjectURL(logoPreviewUrl.value);
    }
});

function setLogo(file) {
    if (logoPreviewUrl.value) {
        URL.revokeObjectURL(logoPreviewUrl.value);
    }

    form.logo = file;
    logoPreviewUrl.value = file ? URL.createObjectURL(file) : null;
    clearFieldError('logo', Boolean(file));
}

function addClosureDate() {
    form.closure_dates.push('');
}

function removeClosureDate(index) {
    if (form.closure_dates.length === 1) {
        form.closure_dates[0] = '';

        return;
    }

    form.closure_dates.splice(index, 1);
}

function submit() {
    form.clearErrors();

    form.transform((data) => ({
        ...data,
        _method: 'put',
        closure_dates: data.closure_dates.filter(Boolean),
        delivery_area_coordinates: data.delivery_area_coordinates.filter((point) => point?.lat !== undefined && point?.lng !== undefined),
        operating_hours: data.operating_hours.map((hour) => ({
            day: hour.day,
            enabled: Boolean(hour.enabled),
            open: hour.enabled ? hour.open : null,
            close: hour.enabled ? hour.close : null,
        })),
    }));

    form.post(route('merchant.profile.update'), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            form.clearErrors();
        },
        onError: (errors) => {
            const errorMessages = Object.values(errors).filter(Boolean);
            const description = errorMessages.length === 1
                ? String(errorMessages[0])
                : `${errorMessages.length} fields need attention before the profile can be saved.`;

            sileo.warning({
                title: 'Profile needs attention',
                description,
                duration: 5200,
            });
        },
    });
}
</script>

<template>
    <section class="grid gap-6 xl:grid-cols-[0.92fr_1.08fr]">
        <div class="space-y-4 xl:sticky xl:top-6 xl:self-start">
            <section class="rounded-[30px] border border-white/80 bg-[linear-gradient(145deg,#ffffff_0%,#fff8f1_64%,#f6fbfb_100%)] p-5 shadow-[0_30px_75px_-50px_rgba(11,77,89,0.5)] sm:p-6">
                <div class="flex items-center gap-4">
                    <div class="flex h-[72px] w-[72px] shrink-0 items-center justify-center overflow-hidden rounded-[24px] bg-white ring-1 ring-[#dceced]">
                        <img
                            v-if="currentLogoUrl"
                            :src="currentLogoUrl"
                            :alt="`${form.name || 'Restaurant'} logo`"
                            class="h-full w-full max-h-full max-w-full object-cover"
                        >
                        <img
                            v-else
                            src="/images/bizlami_icon.png"
                            alt="BizLami icon"
                            class="h-12 w-12 object-contain"
                        >
                    </div>

                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-[var(--brand-orange-deep)]">Restaurant profile</p>
                        <h2 class="mt-2 text-xl font-semibold text-slate-900">{{ form.name || 'Set up your restaurant profile' }}</h2>
                        <p class="mt-1 text-sm leading-6 text-slate-600">{{ hasProfile ? 'One place for business details and operational defaults.' : 'Complete this once and menu management can attach itself to your restaurant automatically.' }}</p>
                    </div>
                </div>

                <dl class="mt-5 space-y-3 rounded-[24px] bg-white/80 p-4 ring-1 ring-[#e2ecec]">
                    <div class="flex items-start justify-between gap-4 text-sm">
                        <dt class="text-slate-500">Account email</dt>
                        <dd class="text-right font-medium text-slate-900">{{ profile.accountEmail }}</dd>
                    </div>
                    <div class="flex items-start justify-between gap-4 text-sm">
                        <dt class="text-slate-500">Contact number</dt>
                        <dd class="text-right font-medium text-slate-900">{{ form.contact_phone || 'Not set yet' }}</dd>
                    </div>
                    <div class="flex items-start justify-between gap-4 text-sm">
                        <dt class="text-slate-500">Address</dt>
                        <dd class="max-w-[16rem] text-right font-medium text-slate-900">{{ form.location_address || 'Not set yet' }}</dd>
                    </div>
                </dl>

                <a
                    v-if="profile.mapsUrl"
                    :href="profile.mapsUrl"
                    target="_blank"
                    rel="noreferrer"
                    class="mt-4 inline-flex items-center justify-center rounded-full border border-[#f0d6a8] bg-[#fff7ea] px-4 py-2 text-[11px] font-semibold uppercase tracking-[0.18em] text-[#8f5604] transition duration-200 hover:-translate-y-0.5"
                >
                    Open map pin
                </a>
            </section>

            <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-1">
                <article
                    v-for="card in summaryCards"
                    :key="card.label"
                    class="rounded-[24px] border border-white/80 bg-white/90 p-4 shadow-[0_22px_55px_-42px_rgba(11,77,89,0.48)]"
                >
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[var(--brand-teal)]">{{ card.label }}</p>
                    <p class="mt-2 text-xl font-semibold text-slate-900">{{ card.value }}</p>
                    <p class="mt-1 text-xs leading-5 text-slate-500">{{ card.detail }}</p>
                </article>
            </section>
        </div>

        <form class="space-y-4" @submit.prevent="submit">
            <section class="rounded-[30px] border border-white/80 bg-white/92 p-5 shadow-[0_30px_75px_-50px_rgba(11,77,89,0.5)] sm:p-6">
                <div class="space-y-4 border-b border-[#edf2f2] pb-4">
                    <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[var(--brand-teal)]">Tabbed profile editor</p>
                            <p class="mt-1 text-sm leading-6 text-slate-600">Choose a tab below. Only one tab panel is open at a time.</p>
                        </div>

                        <div class="inline-flex items-center gap-2 self-start rounded-full border border-[#d8e7e7] bg-[#f7fbfb] px-4 py-2 text-[10px] font-semibold uppercase tracking-[0.2em] text-[var(--brand-teal)]">
                            <span>3 tabs</span>
                            <span class="h-1 w-1 rounded-full bg-[var(--brand-orange)]"></span>
                            <span>{{ activeTabMeta.step }}</span>
                        </div>
                    </div>

                    <div class="rounded-[28px] bg-[#f7fbfb] p-3 ring-1 ring-[#d8e7e7]">
                        <nav role="tablist" aria-label="Restaurant profile sections" class="grid gap-3 md:grid-cols-3">
                            <button
                                v-for="tab in tabs"
                                :id="`restaurant_profile_tab_${tab.key}`"
                                :key="tab.key"
                                :aria-controls="`restaurant_profile_panel_${tab.key}`"
                                :aria-selected="activeTab === tab.key"
                                role="tab"
                                type="button"
                                class="rounded-[22px] border px-4 py-4 text-left transition duration-200 hover:-translate-y-0.5"
                                :class="activeTab === tab.key
                                    ? 'border-[var(--brand-teal)] bg-[var(--brand-teal)] text-white shadow-[0_22px_48px_-28px_rgba(11,77,89,0.65)]'
                                    : 'border-[#d6e7e7] bg-white text-slate-800 hover:border-[var(--brand-teal)]/45'"
                                @click="activeTab = tab.key"
                            >
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p
                                            class="text-[10px] font-semibold uppercase tracking-[0.24em]"
                                            :class="activeTab === tab.key ? 'text-white/80' : 'text-[var(--brand-teal)]'"
                                        >
                                            Tab {{ tab.step }}
                                        </p>
                                        <p class="mt-2 text-sm font-semibold leading-5">{{ tab.label }}</p>
                                        <p
                                            class="mt-2 text-xs leading-5"
                                            :class="activeTab === tab.key ? 'text-white/78' : 'text-slate-500'"
                                        >
                                            {{ tab.hint }}
                                        </p>
                                    </div>

                                    <span
                                        class="rounded-full px-3 py-1 text-[10px] font-semibold uppercase tracking-[0.18em]"
                                        :class="activeTab === tab.key ? 'bg-white/16 text-white' : 'bg-[#f5fafb] text-[var(--brand-teal)] ring-1 ring-[#dceced]'"
                                    >
                                        {{ activeTab === tab.key ? 'Open' : 'View' }}
                                    </span>
                                </div>
                            </button>
                        </nav>
                    </div>

                    <div class="flex flex-col gap-2 rounded-[22px] bg-white/85 px-4 py-3 ring-1 ring-[#e2ecec] md:flex-row md:items-center md:justify-between">
                        <div>
                            <p class="text-[10px] font-semibold uppercase tracking-[0.22em] text-[var(--brand-teal)]">Current tab panel</p>
                            <p class="mt-1 text-sm font-semibold text-slate-900">{{ activeTabMeta.label }}</p>
                        </div>

                        <p class="max-w-md text-sm leading-6 text-slate-500">{{ activeTabMeta.hint }}</p>
                    </div>
                </div>
            </section>

            <section
                v-if="activeTab === 'business'"
                id="restaurant_profile_panel_business"
                aria-labelledby="restaurant_profile_tab_business"
                role="tabpanel"
                class="rounded-[30px] border border-white/80 bg-white/92 p-5 shadow-[0_30px_75px_-50px_rgba(11,77,89,0.5)] sm:p-6"
            >
                <div class="flex flex-col gap-1 border-b border-[#edf2f2] pb-4">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[var(--brand-teal)]">Business details</p>
                    <p class="text-sm leading-6 text-slate-600">Brand, contact, logo, address, and exact map pin for the restaurant profile.</p>
                </div>

                <div class="mt-5 space-y-4">
                    <div class="grid gap-4 lg:grid-cols-2">
                        <TextField
                            id="restaurant_profile_name"
                            v-model="form.name"
                            label="Restaurant name"
                            :message="form.errors.name"
                            required
                        />

                        <TextField
                            id="restaurant_profile_contact"
                            v-model="form.contact_phone"
                            label="Contact number"
                            :message="form.errors.contact_phone"
                            placeholder="01XXXXXXXXX"
                            required
                        />
                    </div>

                    <div class="grid gap-4 lg:grid-cols-[1.15fr_0.85fr]">
                        <TextareaField
                            id="restaurant_profile_featured_text"
                            v-model="form.featured_text"
                            label="Short description"
                            rows="3"
                            :message="form.errors.featured_text"
                            placeholder="Tell customers what makes this restaurant worth ordering from."
                            required
                        />

                        <div class="space-y-3 rounded-[24px] bg-[#f8fbfb] p-4 ring-1 ring-[#e1ecec]">
                            <FileField
                                id="restaurant_profile_logo"
                                label="Restaurant logo"
                                title="Upload logo"
                                description="PNG, JPG, WEBP, or GIF up to 5 MB"
                                helper="A square image works best across cards, listings, and the later mobile app."
                                accept="image/png,image/jpeg,image/webp,image/gif"
                                :preview-src="currentLogoUrl || ''"
                                :preview-alt="`${form.name || 'Restaurant'} logo preview`"
                                :message="form.errors.logo"
                                @change="setLogo"
                            />
                        </div>
                    </div>

                    <GoogleAddressField
                        v-if="googleMapsEnabled"
                        id="restaurant_profile_address"
                        v-model="form.location_address"
                        v-model:latitude="form.location_latitude"
                        v-model:longitude="form.location_longitude"
                        label="Restaurant address and pin"
                        helper="Search the address, click the map, or drag the pin until it matches the real storefront location."
                        :message="form.errors.location_address || form.errors.location_latitude || form.errors.location_longitude"
                        placeholder="House, road, area, city"
                    />

                    <div v-else class="grid gap-4 md:grid-cols-[1.2fr_0.4fr_0.4fr]">
                        <TextField
                            id="restaurant_profile_address_fallback"
                            v-model="form.location_address"
                            label="Restaurant address"
                            :message="form.errors.location_address"
                            placeholder="House, road, area, city"
                        />

                        <TextField
                            id="restaurant_profile_latitude"
                            v-model="form.location_latitude"
                            label="Latitude"
                            :message="form.errors.location_latitude"
                        />

                        <TextField
                            id="restaurant_profile_longitude"
                            v-model="form.location_longitude"
                            label="Longitude"
                            :message="form.errors.location_longitude"
                        />
                    </div>
                </div>
            </section>

            <section
                v-if="activeTab === 'hours'"
                id="restaurant_profile_panel_hours"
                aria-labelledby="restaurant_profile_tab_hours"
                role="tabpanel"
                class="rounded-[30px] border border-white/80 bg-white/92 p-5 shadow-[0_30px_75px_-50px_rgba(11,77,89,0.5)] sm:p-6"
            >
                <div class="flex flex-col gap-1 border-b border-[#edf2f2] pb-4">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[var(--brand-teal)]">Operating hours</p>
                    <p class="text-sm leading-6 text-slate-600">Every time selector uses 12-hour formatting so the weekly schedule is easier to scan.</p>
                </div>

                <div class="mt-5 overflow-hidden rounded-[24px] border border-[#e1ecec]">
                    <div class="hidden bg-[#f8fbfb] px-4 py-3 text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500 md:grid md:grid-cols-[0.85fr_0.85fr_0.85fr_0.85fr] md:gap-3">
                        <span>Day</span>
                        <span>Opens</span>
                        <span>Closes</span>
                        <span>Status</span>
                    </div>

                    <div
                        v-for="(hour, index) in form.operating_hours"
                        :key="hour.day"
                        class="grid gap-3 border-t border-[#edf2f2] bg-white px-4 py-4 first:border-t-0 md:grid-cols-[0.85fr_0.85fr_0.85fr_0.85fr] md:items-start"
                    >
                        <div class="space-y-3">
                            <p class="text-sm font-semibold text-slate-900">{{ hour.label }}</p>
                            <CheckboxField
                                v-model:checked="hour.enabled"
                                :label="hour.enabled ? 'Open' : 'Closed'"
                            />
                        </div>

                        <Time12HourField
                            :id="`hour_open_${hour.day}`"
                            v-model="hour.open"
                            label="Opens"
                            :disabled="!hour.enabled"
                            :message="form.errors[`operating_hours.${index}.open`]"
                        />

                        <Time12HourField
                            :id="`hour_close_${hour.day}`"
                            v-model="hour.close"
                            label="Closes"
                            :disabled="!hour.enabled"
                            :message="form.errors[`operating_hours.${index}.close`]"
                        />

                        <div class="rounded-[20px] bg-[#f8fbfb] px-4 py-4 ring-1 ring-[#e3eded]">
                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-[var(--brand-teal)]">Status</p>
                            <p class="mt-2 text-sm font-medium text-slate-900">{{ formattedHoursLabel(hour) }}</p>
                        </div>
                    </div>
                </div>

                <p v-if="form.errors.operating_hours" class="mt-3 text-sm text-red-600">{{ form.errors.operating_hours }}</p>
            </section>

            <section
                v-if="activeTab === 'delivery'"
                id="restaurant_profile_panel_delivery"
                aria-labelledby="restaurant_profile_tab_delivery"
                role="tabpanel"
                class="rounded-[30px] border border-white/80 bg-white/92 p-5 shadow-[0_30px_75px_-50px_rgba(11,77,89,0.5)] sm:p-6"
            >
                <div class="flex flex-col gap-1 border-b border-[#edf2f2] pb-4">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[var(--brand-teal)]">Delivery area and closures</p>
                    <p class="text-sm leading-6 text-slate-600">Draw the delivery polygon, set the order threshold, and manage preparation windows and closure dates from one compact tab.</p>
                </div>

                <div class="mt-5 grid gap-4 lg:grid-cols-3">
                    <TextField
                        id="restaurant_profile_minimum_order"
                        v-model="form.minimum_order_value"
                        label="Minimum order value"
                        type="number"
                        min="0"
                        step="1"
                        :message="form.errors.minimum_order_value"
                        required
                    />

                    <TextField
                        id="restaurant_profile_prep_min"
                        v-model="form.preparation_time_min"
                        label="Prep min"
                        type="number"
                        min="5"
                        step="1"
                        :message="form.errors.preparation_time_min"
                        required
                    />

                    <TextField
                        id="restaurant_profile_prep_max"
                        v-model="form.preparation_time_max"
                        label="Prep max"
                        type="number"
                        min="5"
                        step="1"
                        :message="form.errors.preparation_time_max"
                        required
                    />
                </div>

                <div class="mt-5">
                    <DeliveryAreaField
                        id="restaurant_profile_delivery_area"
                        v-model="form.delivery_area_coordinates"
                        label="Delivery area"
                        :latitude="form.location_latitude"
                        :longitude="form.location_longitude"
                        :message="form.errors.delivery_area_coordinates"
                        helper="Use Draw area to outline the exact delivery boundary. The restaurant pin stays visible in orange for reference."
                    />
                </div>

                <div class="mt-5 rounded-[24px] bg-[#f8fbfb] p-4 ring-1 ring-[#e1ecec]">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[var(--brand-teal)]">Holidays / closures</p>
                            <p class="mt-1 text-sm leading-6 text-slate-600">Add dates when the restaurant should be treated as closed.</p>
                        </div>

                        <button
                            type="button"
                            class="inline-flex items-center justify-center rounded-full border border-[#d0e2e3] bg-white px-4 py-2 text-[11px] font-semibold uppercase tracking-[0.18em] text-[var(--brand-teal)] transition duration-200 hover:-translate-y-0.5"
                            @click="addClosureDate"
                        >
                            Add date
                        </button>
                    </div>

                    <div class="mt-4 space-y-3">
                        <div
                            v-for="(date, index) in form.closure_dates"
                            :key="`closure_${index}`"
                            class="grid gap-3 sm:grid-cols-[1fr_auto]"
                        >
                            <TextField
                                :id="`closure_date_${index}`"
                                v-model="form.closure_dates[index]"
                                label="Closure date"
                                type="date"
                                :message="form.errors[`closure_dates.${index}`]"
                            />

                            <button
                                type="button"
                                class="inline-flex items-center justify-center rounded-2xl border border-[#ecd6d2] bg-[#fff5f3] px-4 py-3 text-xs font-semibold uppercase tracking-[0.16em] text-[#9b3c2a] transition duration-200 hover:-translate-y-0.5"
                                @click="removeClosureDate(index)"
                            >
                                Remove
                            </button>
                        </div>
                    </div>
                </div>
            </section>

            <div class="flex items-center justify-between gap-4 rounded-[26px] border border-white/80 bg-white px-5 py-4 shadow-[0_24px_64px_-48px_rgba(11,77,89,0.45)]">
                <p class="text-sm leading-6 text-slate-600">{{ hasProfile ? 'Update the restaurant profile whenever hours, closures, or delivery rules change.' : 'Save once to create the restaurant profile for this merchant account.' }}</p>

                <PrimaryButton :disabled="form.processing">
                    {{ form.processing ? 'Saving...' : 'Save profile' }}
                </PrimaryButton>
            </div>
        </form>
    </section>
</template>