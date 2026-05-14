<script setup>
import InputError from '@/Components/Forms/InputError.vue';
import InputLabel from '@/Components/Forms/InputLabel.vue';
import { DEFAULT_CENTER, loadGoogleMaps } from '@/Support/googleMapsLoader';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';

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
        default: 'Use Draw area to place a polygon around the zones this restaurant serves. Drag the points to refine the boundary.',
    },
    latitude: {
        type: [Number, String],
        default: null,
    },
    longitude: {
        type: [Number, String],
        default: null,
    },
});

const model = defineModel({
    type: Array,
    default: () => [],
});

const mapContainer = ref(null);
const mapsError = ref('');
const mapsReady = ref(false);
const mapType = ref('roadmap');

let map;
let marker;
let polygon;
let drawingManager;
let overlayCompleteListener;
let polygonPathListeners = [];
let syncingFromMap = false;

function setMapType(nextMapType) {
    mapType.value = nextMapType;

    if (map) {
        map.setMapTypeId(nextMapType);
    }
}

const hasValidCenter = computed(() => {
    const latitude = Number(props.latitude);
    const longitude = Number(props.longitude);

    return Number.isFinite(latitude) && Number.isFinite(longitude);
});

const pointCount = computed(() => model.value.length ?? 0);

function normalizePoint(point) {
    const latitude = Number(point?.lat);
    const longitude = Number(point?.lng);

    if (!Number.isFinite(latitude) || !Number.isFinite(longitude)) {
        return null;
    }

    return {
        lat: Number(latitude.toFixed(6)),
        lng: Number(longitude.toFixed(6)),
    };
}

function currentCenter() {
    if (hasValidCenter.value) {
        return {
            lat: Number(props.latitude),
            lng: Number(props.longitude),
        };
    }

    return DEFAULT_CENTER;
}

function clearPolygonListeners() {
    polygonPathListeners.forEach((listener) => listener.remove());
    polygonPathListeners = [];
}

function syncModelFromPolygon() {
    if (!polygon) {
        syncingFromMap = true;
        model.value = [];
        queueMicrotask(() => {
            syncingFromMap = false;
        });

        return;
    }

    syncingFromMap = true;
    model.value = polygon.getPath().getArray()
        .map((point) => normalizePoint({ lat: point.lat(), lng: point.lng() }))
        .filter(Boolean);
    queueMicrotask(() => {
        syncingFromMap = false;
    });
}

function bindPolygonListeners() {
    if (!polygon) {
        return;
    }

    clearPolygonListeners();

    const path = polygon.getPath();

    polygonPathListeners = [
        path.addListener('insert_at', syncModelFromPolygon),
        path.addListener('remove_at', syncModelFromPolygon),
        path.addListener('set_at', syncModelFromPolygon),
    ];
}

function removePolygon() {
    clearPolygonListeners();

    if (polygon) {
        polygon.setMap(null);
        polygon = null;
    }
}

function createPolygon(paths) {
    removePolygon();

    polygon = new window.google.maps.Polygon({
        map,
        paths,
        editable: true,
        fillColor: '#0b4d59',
        fillOpacity: 0.18,
        strokeColor: '#0b4d59',
        strokeOpacity: 0.85,
        strokeWeight: 2,
    });

    bindPolygonListeners();
}

function renderPolygon(points) {
    if (!map) {
        return;
    }

    const normalizedPoints = (points ?? [])
        .map(normalizePoint)
        .filter(Boolean);

    if (!normalizedPoints.length) {
        removePolygon();

        return;
    }

    createPolygon(normalizedPoints);
}

function fitPolygon() {
    if (!polygon || !map) {
        return;
    }

    const bounds = new window.google.maps.LatLngBounds();
    polygon.getPath().forEach((point) => bounds.extend(point));
    map.fitBounds(bounds, 48);
}

function updateMarkerPosition() {
    if (!map || !marker) {
        return;
    }

    if (hasValidCenter.value) {
        const center = currentCenter();
        marker.setVisible(true);
        marker.setPosition(center);

        if (!polygon) {
            map.setCenter(center);
            map.setZoom(13);
        }

        return;
    }

    marker.setVisible(false);
    map.setCenter(DEFAULT_CENTER);
    map.setZoom(11);
}

function startDrawing() {
    if (!drawingManager) {
        return;
    }

    removePolygon();
    drawingManager.setDrawingMode(window.google.maps.drawing.OverlayType.POLYGON);
}

function clearArea() {
    removePolygon();
    model.value = [];

    if (drawingManager) {
        drawingManager.setDrawingMode(null);
    }
}

async function initialiseMap() {
    if (!mapContainer.value) {
        return;
    }

    try {
        await loadGoogleMaps();

        map = new window.google.maps.Map(mapContainer.value, {
            center: currentCenter(),
            zoom: hasValidCenter.value ? 13 : 11,
            mapTypeId: mapType.value,
            mapTypeControl: false,
            streetViewControl: false,
            fullscreenControl: false,
        });

        marker = new window.google.maps.Marker({
            map,
            position: currentCenter(),
            visible: hasValidCenter.value,
            icon: {
                path: window.google.maps.SymbolPath.CIRCLE,
                fillColor: '#f27e21',
                fillOpacity: 1,
                strokeColor: '#ffffff',
                strokeWeight: 3,
                scale: 7,
            },
        });

        drawingManager = new window.google.maps.drawing.DrawingManager({
            drawingControl: false,
            drawingMode: null,
            polygonOptions: {
                editable: true,
                fillColor: '#0b4d59',
                fillOpacity: 0.18,
                strokeColor: '#0b4d59',
                strokeOpacity: 0.85,
                strokeWeight: 2,
            },
        });
        drawingManager.setMap(map);

        overlayCompleteListener = drawingManager.addListener('overlaycomplete', (event) => {
            if (event.type !== window.google.maps.drawing.OverlayType.POLYGON) {
                return;
            }

            removePolygon();
            polygon = event.overlay;
            polygon.setEditable(true);
            drawingManager.setDrawingMode(null);
            bindPolygonListeners();
            syncModelFromPolygon();
        });

        updateMarkerPosition();

        if (model.value.length) {
            renderPolygon(model.value);
            fitPolygon();
        }

        mapsReady.value = true;
    } catch (error) {
        mapsError.value = error instanceof Error ? error.message : 'Google Maps is unavailable right now.';
    }
}

watch(
    () => JSON.stringify(model.value ?? []),
    (value) => {
        if (!mapsReady.value || syncingFromMap) {
            return;
        }

        renderPolygon(JSON.parse(value || '[]'));
    },
);

watch(
    () => [props.latitude, props.longitude],
    () => {
        if (!mapsReady.value) {
            return;
        }

        updateMarkerPosition();
    },
);

onMounted(() => {
    initialiseMap();
});

onBeforeUnmount(() => {
    clearPolygonListeners();
    overlayCompleteListener?.remove();

    if (drawingManager) {
        drawingManager.setMap(null);
    }

    if (marker) {
        marker.setMap(null);
    }

    removePolygon();
});
</script>

<template>
    <div>
        <InputLabel :for="id" :value="label" />

        <div class="mt-2 overflow-hidden rounded-[30px] border border-[#edf2f2] bg-white/92 shadow-[0_24px_60px_-38px_rgba(11,77,89,0.35)]">
            <div class="border-b border-[#edf2f2] p-4">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <p class="text-sm leading-6 text-slate-500">{{ helper }}</p>
                        <p v-if="!hasValidCenter" class="mt-2 text-sm font-medium text-[var(--brand-orange-deep)]">
                            Set the restaurant pin first so the map centers on the business location.
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <button
                            type="button"
                            class="inline-flex items-center justify-center rounded-full bg-[var(--brand-teal)] px-4 py-2 text-[11px] font-semibold uppercase tracking-[0.18em] text-white transition duration-200 hover:-translate-y-0.5"
                            @click="startDrawing"
                        >
                            Draw area
                        </button>

                        <button
                            type="button"
                            class="inline-flex items-center justify-center rounded-full border border-[#d0e2e3] bg-white px-4 py-2 text-[11px] font-semibold uppercase tracking-[0.18em] text-[var(--brand-teal)] transition duration-200 hover:-translate-y-0.5"
                            @click="fitPolygon"
                        >
                            Fit area
                        </button>

                        <button
                            type="button"
                            class="inline-flex items-center justify-center rounded-full border border-[#ecd6d2] bg-[#fff5f3] px-4 py-2 text-[11px] font-semibold uppercase tracking-[0.18em] text-[#9b3c2a] transition duration-200 hover:-translate-y-0.5"
                            @click="clearArea"
                        >
                            Clear
                        </button>
                    </div>
                </div>

                <div class="mt-4 flex flex-wrap gap-3 text-xs font-semibold uppercase tracking-[0.18em]">
                    <span class="rounded-full bg-white px-3 py-2 text-slate-600 ring-1 ring-[#e4eded]">
                        Polygon editor
                    </span>
                    <span class="rounded-full bg-white px-3 py-2 text-slate-600 ring-1 ring-[#e4eded]">
                        {{ pointCount }} point{{ pointCount === 1 ? '' : 's' }}
                    </span>
                    <span
                        class="rounded-full px-3 py-2 ring-1"
                        :class="pointCount >= 3
                            ? 'bg-[#e9fbf8] text-[var(--brand-teal)] ring-[#bfe3da]'
                            : 'bg-white text-slate-600 ring-[#e4eded]'"
                    >
                        {{ pointCount >= 3 ? 'Area ready' : 'Area pending' }}
                    </span>
                </div>

                <div class="mt-4 flex flex-wrap items-center justify-between gap-3 rounded-[20px] bg-white/72 px-4 py-3 ring-1 ring-[#dceced]">
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
            </div>

            <div class="p-4 lg:p-5">
                <div ref="mapContainer" :id="id" class="h-[360px] overflow-hidden rounded-[24px] border border-[#dceced] bg-[#f4fbfb]"></div>

                <p v-if="mapsError" class="mt-4 rounded-[20px] border border-[#ffd8bf] bg-[#fff4e9] px-4 py-3 text-sm font-medium text-[var(--brand-orange-deep)]">
                    {{ mapsError }}
                </p>
            </div>
        </div>

        <InputError :message="message" class="mt-3" />
    </div>
</template>
