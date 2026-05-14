<script setup>
import InputError from '@/Components/Forms/InputError.vue';
import InputLabel from '@/Components/Forms/InputLabel.vue';
import { onBeforeUnmount, onMounted, ref, useAttrs, watch } from 'vue';

defineOptions({ inheritAttrs: false });

const DEFAULT_CENTER = { lat: 23.8103, lng: 90.4125 };

let googleMapsPromise;

const loadGoogleMaps = () => {
    if (typeof window === 'undefined') {
        return Promise.reject(new Error('Google Maps is only available in the browser.'));
    }

    if (window.google?.maps?.places) {
        return Promise.resolve(window.google);
    }

    if (googleMapsPromise) {
        return googleMapsPromise;
    }

    const apiKey = import.meta.env.VITE_GOOGLE_MAPS_API_KEY;

    if (!apiKey) {
        return Promise.reject(new Error('VITE_GOOGLE_MAPS_API_KEY is not configured.'));
    }

    googleMapsPromise = new Promise((resolve, reject) => {
        const existingScript = document.querySelector('script[data-google-maps-loader="true"]');

        if (existingScript) {
            existingScript.addEventListener('load', () => resolve(window.google), { once: true });
            existingScript.addEventListener('error', () => reject(new Error('Unable to load Google Maps.')), { once: true });

            return;
        }

        const script = document.createElement('script');
        script.src = `https://maps.googleapis.com/maps/api/js?key=${apiKey}&libraries=places&v=weekly`;
        script.async = true;
        script.defer = true;
        script.dataset.googleMapsLoader = 'true';
        script.onload = () => resolve(window.google);
        script.onerror = () => reject(new Error('Unable to load Google Maps.'));
        document.head.appendChild(script);
    });

    return googleMapsPromise;
};

const props = defineProps({
    id: {
        type: String,
        required: true,
    },
    label: {
        type: String,
        required: true,
    },
    message: {
        type: String,
        default: '',
    },
    helper: {
        type: String,
        default: 'Search by address or choose a suggested location to pin the map.',
    },
});

const model = defineModel({
    type: String,
    required: true,
});

const latitude = defineModel('latitude', {
    default: null,
});

const longitude = defineModel('longitude', {
    default: null,
});

const attrs = useAttrs();
const input = ref(null);
const mapContainer = ref(null);
const mapsError = ref('');
const mapsReady = ref(false);

let autocomplete;
let geocoder;
let map;
let marker;
let geocodeTimeout;

const extractPosition = (location) => {
    if (!location) {
        return null;
    }

    const position = typeof location.lat === 'function'
        ? { lat: location.lat(), lng: location.lng() }
        : { lat: Number(location.lat), lng: Number(location.lng) };

    if (Number.isNaN(position.lat) || Number.isNaN(position.lng)) {
        return null;
    }

    return position;
};

const focusMap = (location, zoom = 16) => {
    const position = extractPosition(location);

    if (!map || !marker || !position) {
        return;
    }

    map.setCenter(position);
    map.setZoom(zoom);
    marker.setPosition(position);
};

const setCoordinates = (location, zoom = 16) => {
    const position = extractPosition(location);

    if (!position) {
        latitude.value = null;
        longitude.value = null;

        return;
    }

    latitude.value = position.lat;
    longitude.value = position.lng;
    focusMap(position, zoom);
};

const resetMap = () => {
    if (!map || !marker) {
        return;
    }

    map.setCenter(DEFAULT_CENTER);
    map.setZoom(12);
    marker.setPosition(DEFAULT_CENTER);
};

const handleInput = () => {
    latitude.value = null;
    longitude.value = null;
};

const geocodeAddress = (address) => {
    if (!geocoder || !address?.trim()) {
        return;
    }

    geocoder.geocode({ address }, (results, status) => {
        if (status !== 'OK' || !results?.length) {
            return;
        }

        const [firstResult] = results;
        setCoordinates(firstResult.geometry.location);

        if (!model.value) {
            model.value = firstResult.formatted_address;
        }
    });
};

const initialiseMap = async () => {
    if (!mapContainer.value || !input.value) {
        return;
    }

    try {
        await loadGoogleMaps();

        const initialPosition = latitude.value !== null && longitude.value !== null
            ? { lat: Number(latitude.value), lng: Number(longitude.value) }
            : DEFAULT_CENTER;

        geocoder = new window.google.maps.Geocoder();
        map = new window.google.maps.Map(mapContainer.value, {
            center: initialPosition,
            zoom: 12,
            mapTypeControl: false,
            streetViewControl: false,
            fullscreenControl: false,
        });
        marker = new window.google.maps.Marker({
            map,
            position: initialPosition,
        });
        autocomplete = new window.google.maps.places.Autocomplete(input.value, {
            fields: ['formatted_address', 'geometry', 'name'],
        });

        autocomplete.addListener('place_changed', () => {
            const place = autocomplete.getPlace();

            if (!place) {
                return;
            }

            model.value = place.formatted_address || place.name || model.value;

            if (place.geometry?.location) {
                setCoordinates(place.geometry.location);
            }
        });

        if (latitude.value !== null && longitude.value !== null) {
            focusMap(initialPosition);
        } else if (model.value) {
            geocodeAddress(model.value);
        }

        mapsReady.value = true;
    } catch (error) {
        mapsError.value = error instanceof Error ? error.message : 'Google Maps is unavailable right now.';
    }
};

watch(
    () => model.value,
    (value) => {
        if (!mapsReady.value) {
            return;
        }

        clearTimeout(geocodeTimeout);

        if (!value?.trim()) {
            latitude.value = null;
            longitude.value = null;
            resetMap();

            return;
        }

        geocodeTimeout = window.setTimeout(() => {
            geocodeAddress(value);
        }, 500);
    },
);

onMounted(() => {
    initialiseMap();
});

onBeforeUnmount(() => {
    clearTimeout(geocodeTimeout);
});
</script>

<template>
    <div>
        <InputLabel :for="id" :value="label" />

        <div class="mt-2 overflow-hidden rounded-[30px] border border-[#edf2f2] bg-white/92 shadow-[0_24px_60px_-38px_rgba(11,77,89,0.35)]">
            <div class="border-b border-[#edf2f2] p-4">
                <input
                    :id="id"
                    ref="input"
                    v-model="model"
                    type="text"
                    class="block w-full rounded-2xl border border-[#d7e7e8] bg-white/95 px-4 py-3 text-sm text-slate-900 shadow-[0_18px_40px_-28px_rgba(11,77,89,0.18)] placeholder:text-slate-400 focus:border-[var(--brand-orange)] focus:ring-[#ffd6b6]"
                    @input="handleInput"
                    v-bind="attrs"
                >
                <p class="mt-3 text-sm leading-6 text-slate-500">{{ helper }}</p>
            </div>

            <div class="grid gap-0 lg:grid-cols-[1.05fr_0.95fr]">
                <div class="p-4 lg:p-5">
                    <div class="rounded-[24px] bg-[#f4fbfb] p-4 ring-1 ring-[#dceced]">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[var(--brand-teal)]">
                            Maps assistance
                        </p>
                        <p class="mt-2 text-sm leading-6 text-slate-600">
                            Start typing an address, then choose a suggestion to pin the delivery destination.
                        </p>

                        <div class="mt-4 flex flex-wrap gap-3 text-xs font-semibold uppercase tracking-[0.18em]">
                            <span class="rounded-full bg-white px-3 py-2 text-slate-600 ring-1 ring-[#e4eded]">
                                Autocomplete
                            </span>
                            <span class="rounded-full bg-white px-3 py-2 text-slate-600 ring-1 ring-[#e4eded]">
                                Address preview
                            </span>
                            <span
                                class="rounded-full px-3 py-2 ring-1"
                                :class="latitude !== null && longitude !== null
                                    ? 'bg-[#e9fbf8] text-[var(--brand-teal)] ring-[#bfe3da]'
                                    : 'bg-white text-slate-600 ring-[#e4eded]'"
                            >
                                {{ latitude !== null && longitude !== null ? 'Pin ready' : 'Pin pending' }}
                            </span>
                        </div>
                    </div>

                    <p v-if="mapsError" class="mt-4 rounded-[20px] border border-[#ffd8bf] bg-[#fff4e9] px-4 py-3 text-sm font-medium text-[var(--brand-orange-deep)]">
                        {{ mapsError }}
                    </p>
                </div>

                <div class="border-t border-[#edf2f2] bg-[#fff9f1] p-4 lg:border-l lg:border-t-0 lg:p-5">
                    <div ref="mapContainer" class="h-[280px] overflow-hidden rounded-[24px] border border-[#f3dfcc] bg-[#fff2e4]"></div>
                </div>
            </div>
        </div>

        <InputError :message="message" class="mt-3" />
    </div>
</template>