<script setup>
import InputError from '@/Components/Forms/InputError.vue';
import InputLabel from '@/Components/Forms/InputLabel.vue';
import { DEFAULT_CENTER, loadGoogleMaps } from '@/Support/googleMapsLoader';
import { onBeforeUnmount, onMounted, ref, useAttrs, watch } from 'vue';

defineOptions({ inheritAttrs: false });

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
        default: 'Search by address, click the map, or drag the pin to the exact location.',
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
const mapType = ref('roadmap');

let autocomplete;
let geocoder;
let map;
let marker;
let geocodeTimeout;
let mapClickListener;
let markerDragListener;

function setMapType(nextMapType) {
    mapType.value = nextMapType;

    if (map) {
        map.setMapTypeId(nextMapType);
    }
}

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

    marker.setVisible(true);
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
    map.setZoom(14);
    marker.setPosition(DEFAULT_CENTER);
    marker.setVisible(true);
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

const reverseGeocodePosition = (location) => {
    const position = extractPosition(location);

    if (!geocoder || !position) {
        return;
    }

    geocoder.geocode({ location: position }, (results, status) => {
        if (status !== 'OK' || !results?.length) {
            return;
        }

        model.value = results[0].formatted_address || model.value;
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
            zoom: latitude.value !== null && longitude.value !== null ? 15 : 14,
            mapTypeId: mapType.value,
            mapTypeControl: false,
            streetViewControl: false,
            fullscreenControl: false,
        });
        marker = new window.google.maps.Marker({
            map,
            position: initialPosition,
            draggable: true,
            visible: true,
        });
        autocomplete = new window.google.maps.places.Autocomplete(input.value, {
            fields: ['formatted_address', 'geometry', 'name'],
        });

        mapClickListener = map.addListener('click', (event) => {
            if (!event.latLng) {
                return;
            }

            setCoordinates(event.latLng);
            reverseGeocodePosition(event.latLng);
        });

        markerDragListener = marker.addListener('dragend', (event) => {
            if (!event.latLng) {
                return;
            }

            setCoordinates(event.latLng);
            reverseGeocodePosition(event.latLng);
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
    mapClickListener?.remove();
    markerDragListener?.remove();
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

            <div class="border-t border-[#edf2f2] bg-[#fff9f1] p-4 lg:p-5">
                <div class="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-[20px] bg-white/70 px-4 py-3 ring-1 ring-[#f1ddc9]">
                    <p class="text-[10px] font-semibold uppercase tracking-[0.22em] text-[var(--brand-teal)]">
                        Map style
                    </p>

                    <div class="inline-flex items-center gap-2 rounded-full bg-[#f7fbfb] p-1 ring-1 ring-[#dceced]">
                        <button
                            type="button"
                            class="rounded-full px-4 py-2 text-[11px] font-semibold uppercase tracking-[0.18em] transition duration-200"
                            :class="mapType === 'roadmap'
                                ? 'bg-[var(--brand-teal)] text-white shadow-[0_12px_28px_-18px_rgba(11,77,89,0.7)]'
                                : 'text-slate-600 hover:text-[var(--brand-teal)]'"
                            @click="setMapType('roadmap')"
                        >
                            Street
                        </button>

                        <button
                            type="button"
                            class="rounded-full px-4 py-2 text-[11px] font-semibold uppercase tracking-[0.18em] transition duration-200"
                            :class="mapType === 'satellite'
                                ? 'bg-[var(--brand-teal)] text-white shadow-[0_12px_28px_-18px_rgba(11,77,89,0.7)]'
                                : 'text-slate-600 hover:text-[var(--brand-teal)]'"
                            @click="setMapType('satellite')"
                        >
                            Satellite
                        </button>
                    </div>
                </div>

                <div ref="mapContainer" class="h-[340px] overflow-hidden rounded-[24px] border border-[#f3dfcc] bg-[#fff2e4]"></div>

                <p v-if="mapsError" class="mt-4 rounded-[20px] border border-[#ffd8bf] bg-[#fff4e9] px-4 py-3 text-sm font-medium text-[var(--brand-orange-deep)]">
                    {{ mapsError }}
                </p>
            </div>
        </div>

        <InputError :message="message" class="mt-3" />
    </div>
</template>