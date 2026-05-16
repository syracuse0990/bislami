<script setup>
import MerchantRestaurantLogoCard from '@/Components/Branding/MerchantRestaurantLogoCard.vue';
import MerchantLayout from '@/Layouts/MerchantLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { computed, onUnmounted, reactive, ref } from 'vue';

const CANVAS_W = 880;
const CANVAS_H = 500;
const SHAPES = {
    square: { w: 120, h: 84 },
    round:  { w: 88,  h: 88 },
};
const TABLE_LIMITS = {
    square: {
        minWidth:   72,
        maxWidth:   260,
        minHeight:  48,
        maxHeight:  180,
        widthStep:  16,
        heightStep: 12,
    },
    round: {
        minDiameter: 72,
        maxDiameter: 168,
        step:        12,
    },
};
const FIXTURE_TYPES = {
    partition: {
        label:         'Partition',
        defaultName:   'Partition',
        defaultWidth:  190,
        defaultHeight: 14,
        minWidth:      10,
        maxWidth:      520,
        minHeight:     10,
        maxHeight:     300,
    },
    counter: {
        label:         'Counter',
        defaultName:   'Counter',
        defaultWidth:  180,
        defaultHeight: 72,
        minWidth:      48,
        maxWidth:      320,
        minHeight:     48,
        maxHeight:     320,
    },
};
const CHAIR_R = 6.5;
const CHAIR_GAP = 5;

const props = defineProps({
    hasRestaurant:    { type: Boolean, default: true },
    restaurant:       { type: Object, required: true },
    isOwner:          { type: Boolean, default: false },
    discountSettings: { type: Object, default: () => ({ scDiscountRate: 20, pwdDiscountRate: 20 }) },
    floorPlan:        { type: Object, default: () => ({ room: { points: [] }, tables: [], fixtures: [] }) },
});

const activeSection = ref('discounts');

const sections = [
    { id: 'discounts',  label: 'SC / PWD Discounts' },
    { id: 'floor-plan', label: 'Floor Plan' },
];

const discountForm = useForm({
    sc_discount_rate:  props.discountSettings.scDiscountRate,
    pwd_discount_rate: props.discountSettings.pwdDiscountRate,
});

function saveDiscounts() {
    discountForm.put(route('merchant.maintenance.update'), { preserveScroll: true });
}

function createDefaultRoomPoints() {
    return [
        { x: 18, y: 18 },
        { x: 862, y: 18 },
        { x: 862, y: 482 },
        { x: 18, y: 482 },
    ];
}

function clampNumber(value, min, max, fallback = min) {
    const numericValue = Number(value);

    if (!Number.isFinite(numericValue)) {
        return fallback;
    }

    return Math.min(max, Math.max(min, numericValue));
}

function centerPlacement(width, height) {
    return {
        x: Math.round((CANVAS_W - width) / 2),
        y: Math.round((CANVAS_H - height) / 2),
    };
}

function normalizeRoomPoints(points) {
    if (!Array.isArray(points) || points.length < 3) {
        return createDefaultRoomPoints();
    }

    return points.slice(0, 24).map(point => ({
        x: clampNumber(point?.x, 8, CANVAS_W - 8, 18),
        y: clampNumber(point?.y, 8, CANVAS_H - 8, 18),
    }));
}

function normalizeRotation(value) {
    const numericValue = Number(value);

    if (!Number.isFinite(numericValue)) {
        return 0;
    }

    const snapped = Math.round(numericValue / 90) * 90;

    return ((snapped % 360) + 360) % 360;
}

function tableFrame(table) {
    const shape = table?.shape === 'round' ? 'round' : 'square';
    const defaults = SHAPES[shape] ?? SHAPES.square;
    let contentWidth;
    let contentHeight;
    let rotation;

    if (shape === 'round') {
        const diameter = clampNumber(
            table?.w ?? table?.h,
            TABLE_LIMITS.round.minDiameter,
            TABLE_LIMITS.round.maxDiameter,
            defaults.w,
        );

        contentWidth = diameter;
        contentHeight = diameter;
        rotation = 0;
    } else {
        contentWidth = clampNumber(table?.w, TABLE_LIMITS.square.minWidth, TABLE_LIMITS.square.maxWidth, defaults.w);
        contentHeight = clampNumber(table?.h, TABLE_LIMITS.square.minHeight, TABLE_LIMITS.square.maxHeight, defaults.h);
        rotation = normalizeRotation(table?.rotation);
    }

    const rotated = rotation % 180 !== 0;
    const width = rotated ? contentHeight : contentWidth;
    const height = rotated ? contentWidth : contentHeight;

    return {
        width,
        height,
        contentWidth,
        contentHeight,
        offsetX: (width - contentWidth) / 2,
        offsetY: (height - contentHeight) / 2,
        rotation,
    };
}

function clampTablePosition(table) {
    const frame = tableFrame(table);

    table.x = clampNumber(table.x, 0, CANVAS_W - frame.width, 0);
    table.y = clampNumber(table.y, 0, CANVAS_H - frame.height, 0);
}

function tableRotationLabel(table) {
    return `${tableFrame(table).rotation}°`;
}

function tableOrientationLabel(table) {
    const frame = tableFrame(table);

    return frame.width >= frame.height ? 'Horizontal footprint' : 'Vertical footprint';
}

function tableWrapperStyle(table) {
    const frame = tableFrame(table);

    return {
        left: table.x + 'px',
        top: table.y + 'px',
        width: frame.width + 'px',
        height: frame.height + 'px',
        zIndex: ((selection.type === 'table' && selection.id === table.id) || isDragging('table', table.id)) ? 24 : 10,
    };
}

function tableInnerStyle(table) {
    const frame = tableFrame(table);

    return {
        left: frame.offsetX + 'px',
        top: frame.offsetY + 'px',
        width: frame.contentWidth + 'px',
        height: frame.contentHeight + 'px',
        transform: `rotate(${frame.rotation}deg)`,
        transformOrigin: 'center center',
    };
}

function useVerticalTableLabel(table) {
    if (table.shape !== 'square') {
        return false;
    }

    const frame = tableFrame(table);

    return frame.width < 104 || frame.height > frame.width;
}

function tableLabelClass(table) {
    return useVerticalTableLabel(table)
        ? 'block px-1 py-1 text-center text-[8px] font-bold leading-none text-[#0b4d59]'
        : 'max-w-full truncate px-2 text-[11px] font-bold leading-tight text-[#0b4d59]';
}

function tableLabelStyle(table) {
    if (!useVerticalTableLabel(table)) {
        return null;
    }

    const frame = tableFrame(table);

    return {
        writingMode: 'vertical-rl',
        textOrientation: 'upright',
        letterSpacing: '0.12em',
        maxHeight: `${Math.max(frame.height - 20, 24)}px`,
        transform: `rotate(${-frame.rotation}deg)`,
        transformOrigin: 'center center',
    };
}

function normalizeTable(table, index) {
    const shape = table?.shape === 'round' ? 'round' : 'square';
    const frame = tableFrame({
        shape,
        w: table?.w,
        h: table?.h,
        rotation: table?.rotation,
    });
    const fallbackPosition = centerPlacement(frame.width, frame.height);

    return {
        id:       String(table?.id ?? `t_seed_${index + 1}`),
        name:     String(table?.name ?? `Table ${index + 1}`),
        seats:    clampNumber(table?.seats, 1, 50, 4),
        shape,
        w:        frame.contentWidth,
        h:        frame.contentHeight,
        rotation: frame.rotation,
        x:        clampNumber(table?.x, 0, CANVAS_W - frame.width, fallbackPosition.x),
        y:        clampNumber(table?.y, 0, CANVAS_H - frame.height, fallbackPosition.y),
    };
}

function fixtureConfig(type) {
    return FIXTURE_TYPES[type] ?? FIXTURE_TYPES.partition;
}

function normalizeFixture(fixture, index) {
    const type = fixture?.type === 'counter' ? 'counter' : 'partition';
    const meta = fixtureConfig(type);
    const fallbackPosition = centerPlacement(meta.defaultWidth, meta.defaultHeight);

    return {
        id:   String(fixture?.id ?? `f_seed_${index + 1}`),
        type,
        name: String(fixture?.name ?? `${meta.defaultName} ${index + 1}`),
        x:    clampNumber(fixture?.x, 0, CANVAS_W - meta.minWidth, fallbackPosition.x),
        y:    clampNumber(fixture?.y, 0, CANVAS_H - meta.minHeight, fallbackPosition.y),
        w:    clampNumber(fixture?.w, meta.minWidth, meta.maxWidth, meta.defaultWidth),
        h:    clampNumber(fixture?.h, meta.minHeight, meta.maxHeight, meta.defaultHeight),
    };
}

const roomPoints = ref(normalizeRoomPoints(props.floorPlan?.room?.points));
const tables = ref((props.floorPlan?.tables ?? []).map(normalizeTable));
const fixtures = ref((props.floorPlan?.fixtures ?? []).map(normalizeFixture));

const selection = reactive({ type: null, id: null, pointIndex: null });
const dragState = reactive({ mode: null, itemType: null, id: null, pointIndex: null });

const selectedTable = computed(() => (
    selection.type === 'table'
        ? tables.value.find(table => table.id === selection.id) ?? null
        : null
));
const selectedFixture = computed(() => (
    selection.type === 'fixture'
        ? fixtures.value.find(fixture => fixture.id === selection.id) ?? null
        : null
));
const selectedFixtureMeta = computed(() => (
    selectedFixture.value ? fixtureConfig(selectedFixture.value.type) : null
));
const selectedCorner = computed(() => (
    selection.type === 'room' && Number.isInteger(selection.pointIndex)
        ? roomPoints.value[selection.pointIndex] ?? null
        : null
));
const selectedCornerDisplay = computed(() => (
    selectedCorner.value
        ? {
            x: Math.round(selectedCorner.value.x),
            y: Math.round(selectedCorner.value.y),
        }
        : null
));
const roomPolygonPoints = computed(() => roomPoints.value.map(point => `${point.x},${point.y}`).join(' '));
const totalSeats = computed(() => tables.value.reduce((sum, table) => sum + table.seats, 0));

let activeDrag = null;

function clearSelection() {
    selection.type = null;
    selection.id = null;
    selection.pointIndex = null;
}

function selectRoom(pointIndex = null) {
    selection.type = 'room';
    selection.id = 'room';
    selection.pointIndex = Number.isInteger(pointIndex) ? pointIndex : null;
}

function selectTable(tableId) {
    selection.type = 'table';
    selection.id = tableId;
    selection.pointIndex = null;
}

function selectFixture(fixtureId) {
    selection.type = 'fixture';
    selection.id = fixtureId;
    selection.pointIndex = null;
}

function resetDragState() {
    dragState.mode = null;
    dragState.itemType = null;
    dragState.id = null;
    dragState.pointIndex = null;
    activeDrag = null;
}

function getPointerClient(event) {
    if ('touches' in event && event.touches.length > 0) {
        return { x: event.touches[0].clientX, y: event.touches[0].clientY };
    }

    if ('changedTouches' in event && event.changedTouches.length > 0) {
        return { x: event.changedTouches[0].clientX, y: event.changedTouches[0].clientY };
    }

    return { x: event.clientX, y: event.clientY };
}

function bindPointerEvents() {
    window.addEventListener('mousemove', onMove);
    window.addEventListener('mouseup', onUp);
    window.addEventListener('touchmove', onMove, { passive: false });
    window.addEventListener('touchend', onUp);
    window.addEventListener('touchcancel', onUp);
}

function unbindPointerEvents() {
    window.removeEventListener('mousemove', onMove);
    window.removeEventListener('mouseup', onUp);
    window.removeEventListener('touchmove', onMove);
    window.removeEventListener('touchend', onUp);
    window.removeEventListener('touchcancel', onUp);
}

function startDrag(event, itemType, itemId) {
    if (event.cancelable) {
        event.preventDefault();
    }

    if (itemType === 'table') {
        selectTable(itemId);
    } else {
        selectFixture(itemId);
    }

    if (!props.isOwner) {
        return;
    }

    const { x, y } = getPointerClient(event);
    const item = itemType === 'table'
        ? tables.value.find(table => table.id === itemId)
        : fixtures.value.find(fixture => fixture.id === itemId);

    if (!item) {
        return;
    }

    const width = itemType === 'table'
        ? tableFrame(item).width
        : item.w;
    const height = itemType === 'table'
        ? tableFrame(item).height
        : item.h;

    activeDrag = {
        mode:    'item',
        itemType,
        id:      itemId,
        startX:  x,
        startY:  y,
        originX: item.x,
        originY: item.y,
        width,
        height,
    };

    dragState.mode = 'item';
    dragState.itemType = itemType;
    dragState.id = itemId;
    dragState.pointIndex = null;

    bindPointerEvents();
}

function startCornerDrag(event, pointIndex) {
    if (event.cancelable) {
        event.preventDefault();
    }

    selectRoom(pointIndex);

    if (!props.isOwner) {
        return;
    }

    const point = roomPoints.value[pointIndex];

    if (!point) {
        return;
    }

    const { x, y } = getPointerClient(event);

    activeDrag = {
        mode:      'corner',
        pointIndex,
        startX:    x,
        startY:    y,
        originX:   point.x,
        originY:   point.y,
    };

    dragState.mode = 'corner';
    dragState.itemType = null;
    dragState.id = null;
    dragState.pointIndex = pointIndex;

    bindPointerEvents();
}

function onMove(event) {
    if (!activeDrag) {
        return;
    }

    if (event.cancelable) {
        event.preventDefault();
    }

    const { x, y } = getPointerClient(event);
    const dx = x - activeDrag.startX;
    const dy = y - activeDrag.startY;

    if (activeDrag.mode === 'corner') {
        const point = roomPoints.value[activeDrag.pointIndex];

        if (!point) {
            return;
        }

        point.x = clampNumber(activeDrag.originX + dx, 8, CANVAS_W - 8, point.x);
        point.y = clampNumber(activeDrag.originY + dy, 8, CANVAS_H - 8, point.y);

        return;
    }

    if (activeDrag.itemType === 'table') {
        const table = tables.value.find(candidate => candidate.id === activeDrag.id);

        if (!table) {
            return;
        }

        table.x = clampNumber(activeDrag.originX + dx, 0, CANVAS_W - activeDrag.width, table.x);
        table.y = clampNumber(activeDrag.originY + dy, 0, CANVAS_H - activeDrag.height, table.y);

        return;
    }

    const fixture = fixtures.value.find(candidate => candidate.id === activeDrag.id);

    if (!fixture) {
        return;
    }

    fixture.x = clampNumber(activeDrag.originX + dx, 0, CANVAS_W - activeDrag.width, fixture.x);
    fixture.y = clampNumber(activeDrag.originY + dy, 0, CANVAS_H - activeDrag.height, fixture.y);
}

function onUp() {
    unbindPointerEvents();
    resetDragState();
}

onUnmounted(() => {
    unbindPointerEvents();
    resetDragState();
});

function chairsForTable(table) {
    const { contentWidth: w, contentHeight: h } = tableFrame(table);
    const seats = Math.min(table.seats, 14);

    if (table.shape === 'round') {
        const centerX = w / 2;
        const centerY = h / 2;
        const radius = (w / 2) + CHAIR_GAP + CHAIR_R;

        return Array.from({ length: seats }, (_, index) => {
            const angle = ((index / seats) * Math.PI * 2) - (Math.PI / 2);

            return {
                x: centerX + (Math.cos(angle) * radius) - CHAIR_R,
                y: centerY + (Math.sin(angle) * radius) - CHAIR_R,
            };
        });
    }

    const topSeats = Math.ceil(seats / 2);
    const bottomSeats = seats - topSeats;
    const chairs = [];

    for (let index = 0; index < topSeats; index += 1) {
        chairs.push({
            x: ((w * (index + 1)) / (topSeats + 1)) - CHAIR_R,
            y: -(CHAIR_GAP + (CHAIR_R * 2)),
        });
    }

    for (let index = 0; index < bottomSeats; index += 1) {
        chairs.push({
            x: ((w * (index + 1)) / (bottomSeats + 1)) - CHAIR_R,
            y: h + CHAIR_GAP,
        });
    }

    return chairs;
}

const showAddModal = ref(false);
const addForm = reactive({ name: '', seats: 4, shape: 'square' });

function openAddModal() {
    if (!props.isOwner) {
        return;
    }

    addForm.name = `Table ${tables.value.length + 1}`;
    addForm.seats = 4;
    addForm.shape = 'square';
    showAddModal.value = true;
}

function confirmAdd() {
    if (!props.isOwner || !String(addForm.name).trim()) {
        return;
    }

    const { w, h } = SHAPES[addForm.shape];
    const position = centerPlacement(w, h);
    const id = `t_${Date.now()}`;

    tables.value.push({
        id,
        name: String(addForm.name).trim(),
        seats: clampNumber(addForm.seats, 1, 20, 4),
        shape: addForm.shape,
        w,
        h,
        rotation: 0,
        x: position.x,
        y: position.y,
    });

    showAddModal.value = false;
    selectTable(id);
}

function setSelectedTableShape(shape) {
    if (!props.isOwner || !selectedTable.value) {
        return;
    }

    const table = selectedTable.value;

    if (table.shape === shape) {
        return;
    }

    if (shape === 'round') {
        const diameter = clampNumber(
            Math.max(table.w, table.h),
            TABLE_LIMITS.round.minDiameter,
            TABLE_LIMITS.round.maxDiameter,
            SHAPES.round.w,
        );

        table.shape = 'round';
        table.w = diameter;
        table.h = diameter;
        table.rotation = 0;
    } else {
        table.shape = 'square';
        table.w = clampNumber(table.w, TABLE_LIMITS.square.minWidth, TABLE_LIMITS.square.maxWidth, SHAPES.square.w);
        table.h = clampNumber(table.h, TABLE_LIMITS.square.minHeight, TABLE_LIMITS.square.maxHeight, SHAPES.square.h);
        table.rotation = normalizeRotation(table.rotation);
    }

    clampTablePosition(table);
}

function adjustSelectedTableSize(dimension, direction) {
    if (!props.isOwner || !selectedTable.value) {
        return;
    }

    const table = selectedTable.value;

    if (table.shape === 'round') {
        const diameter = clampNumber(
            table.w + (direction * TABLE_LIMITS.round.step),
            TABLE_LIMITS.round.minDiameter,
            TABLE_LIMITS.round.maxDiameter,
            SHAPES.round.w,
        );

        table.w = diameter;
        table.h = diameter;
        clampTablePosition(table);

        return;
    }

    if (dimension === 'width') {
        table.w = clampNumber(
            table.w + (direction * TABLE_LIMITS.square.widthStep),
            TABLE_LIMITS.square.minWidth,
            TABLE_LIMITS.square.maxWidth,
            SHAPES.square.w,
        );
    } else {
        table.h = clampNumber(
            table.h + (direction * TABLE_LIMITS.square.heightStep),
            TABLE_LIMITS.square.minHeight,
            TABLE_LIMITS.square.maxHeight,
            SHAPES.square.h,
        );
    }

    clampTablePosition(table);
}

function rotateSelectedTable() {
    if (!props.isOwner || !selectedTable.value || selectedTable.value.shape !== 'square') {
        return;
    }

    selectedTable.value.rotation = normalizeRotation(selectedTable.value.rotation + 90);
    clampTablePosition(selectedTable.value);
}

function nextFixtureName(type) {
    const count = fixtures.value.filter(fixture => fixture.type === type).length + 1;

    return `${fixtureConfig(type).defaultName} ${count}`;
}

function addFixture(type) {
    if (!props.isOwner) {
        return;
    }

    const meta = fixtureConfig(type);
    const position = centerPlacement(meta.defaultWidth, meta.defaultHeight);
    const id = `f_${Date.now()}`;

    fixtures.value.push({
        id,
        type,
        name: nextFixtureName(type),
        x: position.x,
        y: position.y,
        w: meta.defaultWidth,
        h: meta.defaultHeight,
    });

    selectFixture(id);
}

function clearLayout() {
    if (!props.isOwner) {
        return;
    }

    tables.value = [];
    fixtures.value = [];
    clearSelection();
}

function deleteSelectedItem() {
    if (!props.isOwner) {
        return;
    }

    if (selection.type === 'table') {
        tables.value = tables.value.filter(table => table.id !== selection.id);
        clearSelection();

        return;
    }

    if (selection.type === 'fixture') {
        fixtures.value = fixtures.value.filter(fixture => fixture.id !== selection.id);
        clearSelection();
    }
}

function longestRoomEdgeIndex() {
    return roomPoints.value.reduce((bestIndex, point, index, points) => {
        const next = points[(index + 1) % points.length];
        const bestNext = points[(bestIndex + 1) % points.length];
        const edgeLength = Math.hypot(next.x - point.x, next.y - point.y);
        const bestLength = Math.hypot(bestNext.x - points[bestIndex].x, bestNext.y - points[bestIndex].y);

        return edgeLength > bestLength ? index : bestIndex;
    }, 0);
}

function addRoomCorner() {
    if (!props.isOwner) {
        return;
    }

    const insertAfter = selection.type === 'room' && Number.isInteger(selection.pointIndex)
        ? selection.pointIndex
        : longestRoomEdgeIndex();
    const current = roomPoints.value[insertAfter];
    const nextIndex = (insertAfter + 1) % roomPoints.value.length;
    const next = roomPoints.value[nextIndex];

    roomPoints.value.splice(insertAfter + 1, 0, {
        x: Math.round((current.x + next.x) / 2),
        y: Math.round((current.y + next.y) / 2),
    });

    selectRoom(insertAfter + 1);
}

function removeRoomCorner() {
    if (!props.isOwner || roomPoints.value.length <= 3 || selection.type !== 'room' || !Number.isInteger(selection.pointIndex)) {
        return;
    }

    roomPoints.value.splice(selection.pointIndex, 1);
    selectRoom(Math.min(selection.pointIndex, roomPoints.value.length - 1));
}

function sizeStep(type, dimension) {
    if (type === 'partition') {
        return dimension === 'w' ? 24 : 6;
    }

    return dimension === 'w' ? 16 : 10;
}

function adjustSelectedFixtureSize(dimension, direction) {
    if (!props.isOwner || !selectedFixture.value) {
        return;
    }

    const fixture = selectedFixture.value;
    const meta = fixtureConfig(fixture.type);
    const key = dimension === 'width' ? 'w' : 'h';
    const min = key === 'w' ? meta.minWidth : meta.minHeight;
    const max = key === 'w' ? meta.maxWidth : meta.maxHeight;

    fixture[key] = clampNumber(
        fixture[key] + (direction * sizeStep(fixture.type, key)),
        min,
        max,
        key === 'w' ? meta.defaultWidth : meta.defaultHeight,
    );
    fixture.x = clampNumber(fixture.x, 0, CANVAS_W - fixture.w, 0);
    fixture.y = clampNumber(fixture.y, 0, CANVAS_H - fixture.h, 0);
}

function partitionOrientation(fixture) {
    return fixture.w >= fixture.h ? 'horizontal' : 'vertical';
}

function counterOrientation(fixture) {
    return fixture.w >= fixture.h ? 'horizontal' : 'vertical';
}

function useVerticalCounterLabel(fixture) {
    return fixture.type === 'counter' && (fixture.h > fixture.w || fixture.w < 104);
}

function counterLabelClass(fixture) {
    return useVerticalCounterLabel(fixture)
        ? 'block px-1 py-1 text-center text-[8px] font-bold leading-none'
        : 'max-w-full truncate px-2 text-center';
}

function counterLabelStyle(fixture) {
    if (!useVerticalCounterLabel(fixture)) {
        return null;
    }

    return {
        writingMode: 'vertical-rl',
        textOrientation: 'upright',
        letterSpacing: '0.12em',
        maxHeight: `${Math.max(fixture.h - 12, 20)}px`,
    };
}

function setPartitionOrientation(orientation) {
    if (!props.isOwner || !selectedFixture.value || selectedFixture.value.type !== 'partition') {
        return;
    }

    const fixture = selectedFixture.value;
    const length = Math.max(fixture.w, fixture.h, 120);

    if (orientation === 'horizontal') {
        fixture.w = clampNumber(length, FIXTURE_TYPES.partition.minWidth, FIXTURE_TYPES.partition.maxWidth, FIXTURE_TYPES.partition.defaultWidth);
        fixture.h = 14;
    } else {
        fixture.w = 14;
        fixture.h = clampNumber(length, FIXTURE_TYPES.partition.minHeight, FIXTURE_TYPES.partition.maxHeight, 120);
    }

    fixture.x = clampNumber(fixture.x, 0, CANVAS_W - fixture.w, 0);
    fixture.y = clampNumber(fixture.y, 0, CANVAS_H - fixture.h, 0);
}

function rotateSelectedCounter() {
    if (!props.isOwner || !selectedFixture.value || selectedFixture.value.type !== 'counter') {
        return;
    }

    const fixture = selectedFixture.value;
    const meta = fixtureConfig(fixture.type);
    const rotatedWidth = clampNumber(fixture.h, meta.minWidth, meta.maxWidth, meta.defaultWidth);
    const rotatedHeight = clampNumber(fixture.w, meta.minHeight, meta.maxHeight, meta.defaultHeight);

    fixture.w = rotatedWidth;
    fixture.h = rotatedHeight;
    fixture.x = clampNumber(fixture.x, 0, CANVAS_W - fixture.w, 0);
    fixture.y = clampNumber(fixture.y, 0, CANVAS_H - fixture.h, 0);
}

function isDragging(itemType, itemId) {
    return dragState.mode === 'item' && dragState.itemType === itemType && dragState.id === itemId;
}

function floorPlanPayload() {
    return {
        room: {
            points: roomPoints.value.map(point => ({
                x: Math.round(point.x),
                y: Math.round(point.y),
            })),
        },
        tables: tables.value.map(table => ({
            ...table,
            x: Math.round(table.x),
            y: Math.round(table.y),
            w: Math.round(table.w),
            h: Math.round(table.h),
            rotation: table.shape === 'round' ? 0 : normalizeRotation(table.rotation),
        })),
        fixtures: fixtures.value.map(fixture => ({
            ...fixture,
            x: Math.round(fixture.x),
            y: Math.round(fixture.y),
            w: Math.round(fixture.w),
            h: Math.round(fixture.h),
        })),
    };
}

const fpSaving = ref(false);
const fpSaved = ref(false);

function saveFloorPlan() {
    fpSaving.value = true;
    router.put(route('merchant.maintenance.floor-plan'), floorPlanPayload(), {
        preserveScroll: true,
        onSuccess: () => {
            fpSaved.value = true;
            setTimeout(() => (fpSaved.value = false), 3000);
        },
        onFinish: () => {
            fpSaving.value = false;
        },
    });
}
</script>

<template>
    <MerchantLayout>
        <Head title="Maintenance" />

        <template #header>
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-[var(--brand-orange-deep)]">
                    Restaurant configuration
                </p>
                <h2 class="mt-2 text-3xl font-semibold leading-tight text-slate-900 sm:text-[2rem]">
                    Maintenance
                </h2>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">
                    <template v-if="hasRestaurant">
                        Manage system-wide settings for <strong class="font-semibold text-slate-800">{{ restaurant.name }}</strong> — discounts, POS behaviour, and more.
                    </template>
                    <template v-else>
                        Save your restaurant profile first, then come back here to manage discounts, POS behaviour, and the floor plan.
                    </template>
                </p>
            </div>
        </template>

        <div class="px-4 py-8 sm:px-6 lg:px-0">
            <div v-if="!hasRestaurant" class="max-w-3xl rounded-3xl border border-[#e8e3da] bg-white p-6 shadow-sm sm:p-8">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-[#f0ece6] text-[#0b4d59]">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 21h7.5m-7.5 0V7.5A2.25 2.25 0 0110.5 5.25h3A2.25 2.25 0 0115.75 7.5V21m-7.5 0H5.625A1.875 1.875 0 013.75 19.125V10.5a1.5 1.5 0 011.5-1.5H7.5m9 12h2.625A1.875 1.875 0 0021 19.125V10.5a1.5 1.5 0 00-1.5-1.5H16.5m-9-3.75h9" />
                    </svg>
                </div>

                <h3 class="mt-5 text-xl font-semibold text-slate-900">Restaurant profile required</h3>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">
                    This page needs a linked restaurant record. Your local seeded accounts already have one, but the merchant account on the server does not have a row in the restaurants table yet.
                </p>
                <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600">
                    Open the restaurant profile, save it once, and this maintenance page will start working normally.
                </p>

                <div class="mt-6 flex flex-wrap items-center gap-3">
                    <Link
                        :href="route('merchant.profile.show')"
                        class="inline-flex items-center justify-center rounded-xl bg-[#0b4d59] px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-[#093e49]"
                    >
                        Open Restaurant Profile
                    </Link>
                </div>
            </div>

            <template v-else>
            <MerchantRestaurantLogoCard
                class="mb-6 max-w-3xl"
                :restaurant-name="restaurant.name"
                note="Use this restaurant logo as the shared visual marker while you manage discounts and the floor plan."
            />
            <div v-if="!isOwner" class="mb-6 flex items-start gap-3 rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4">
                <svg class="mt-0.5 h-5 w-5 flex-shrink-0 text-amber-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
                </svg>
                <p class="text-sm text-amber-700">
                    You are viewing maintenance settings in <strong class="font-semibold">read-only mode</strong>.
                    Only the restaurant owner can make changes.
                </p>
            </div>

            <div class="flex flex-col gap-8 lg:flex-row lg:items-start lg:gap-10">
                <nav class="flex-shrink-0 lg:sticky lg:top-6 lg:w-56">
                    <p class="mb-2 px-1 text-[10.5px] font-semibold uppercase tracking-[0.2em] text-slate-400">Settings</p>
                    <ul class="space-y-0.5">
                        <li v-for="section in sections" :key="section.id">
                            <button
                                type="button"
                                class="group flex w-full items-center gap-2.5 rounded-xl px-3.5 py-2.5 text-sm font-medium transition-all"
                                :class="activeSection === section.id
                                    ? 'bg-[#0b4d59] text-white shadow-sm'
                                    : 'text-slate-600 hover:bg-[#f0ece6] hover:text-[#0b4d59]'"
                                @click="activeSection = section.id"
                            >
                                <svg v-if="section.id === 'discounts'" class="h-4 w-4 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                                    <line x1="7" y1="17" x2="17" y2="7" stroke-linecap="round" />
                                    <circle cx="7.5" cy="7.5" r="1.5" />
                                    <circle cx="16.5" cy="16.5" r="1.5" />
                                </svg>
                                <svg v-if="section.id === 'floor-plan'" class="h-4 w-4 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                                    <rect x="3" y="3" width="7" height="7" rx="1.5" />
                                    <rect x="14" y="3" width="7" height="7" rx="1.5" />
                                    <rect x="3" y="14" width="7" height="7" rx="1.5" />
                                    <rect x="14" y="14" width="7" height="4" rx="1.5" />
                                </svg>
                                {{ section.label }}
                            </button>
                        </li>
                    </ul>
                </nav>

                <div class="min-w-0 flex-1">
                    <section v-show="activeSection === 'discounts'" class="space-y-1">
                        <div class="mb-5">
                            <h3 class="text-base font-semibold text-slate-900">SC / PWD Discount Rates</h3>
                            <p class="mt-1 text-sm text-slate-500">
                                Configure VAT-exempt discount rates for Senior Citizens and Persons with Disability
                                as mandated by Philippine law (RA 9994 &amp; RA 10754). The legal minimum is 20%.
                            </p>
                        </div>

                        <div class="overflow-hidden rounded-2xl border border-[#e8e3da] bg-white shadow-sm">
                            <div class="flex items-center gap-3 border-b border-[#e8e3da] bg-[#faf7f3] px-6 py-4">
                                <span class="inline-flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-xl bg-[#0b4d59]/10">
                                    <svg class="h-4 w-4 text-[#0b4d59]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 14.25l6-6m4.5-3.493V21.75l-3.75-1.5-3.75 1.5-3.75-1.5-3.75 1.5V4.757c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0111.186 0c1.1.128 1.907 1.077 1.907 2.185zM9.75 9h.008v.008H9.75V9zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm4.125 4.5h.008v.008h-.008V13.5zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                                    </svg>
                                </span>
                                <div>
                                    <p class="text-sm font-semibold text-slate-900">Discount Rate Configuration</p>
                                    <p class="text-xs text-slate-400">Applied when a valid SC or PWD ID is entered at the POS terminal.</p>
                                </div>
                            </div>

                            <div class="px-6 py-6">
                                <div class="grid gap-6 sm:grid-cols-2">
                                    <div>
                                        <label for="sc_discount_rate" class="mb-1.5 block text-sm font-medium text-slate-700">Senior Citizen (SC)</label>
                                        <div class="relative">
                                            <input id="sc_discount_rate" v-model.number="discountForm.sc_discount_rate" type="number" min="0" max="100" :disabled="!isOwner"
                                                class="block w-full rounded-xl border border-[#e8e3da] bg-white py-2.5 pl-4 pr-12 text-sm text-slate-900 shadow-sm transition focus:border-[#0b4d59] focus:outline-none focus:ring-2 focus:ring-[#0b4d59]/20 disabled:cursor-not-allowed disabled:bg-[#f3efe9] disabled:text-slate-400" />
                                            <span class="pointer-events-none absolute inset-y-0 right-4 flex items-center text-sm font-semibold text-slate-400">%</span>
                                        </div>
                                        <p class="mt-1.5 text-xs text-slate-400">RA 9994 minimum: 20%</p>
                                        <p v-if="discountForm.errors.sc_discount_rate" class="mt-1 text-xs text-red-600">{{ discountForm.errors.sc_discount_rate }}</p>
                                    </div>
                                    <div>
                                        <label for="pwd_discount_rate" class="mb-1.5 block text-sm font-medium text-slate-700">Person with Disability (PWD)</label>
                                        <div class="relative">
                                            <input id="pwd_discount_rate" v-model.number="discountForm.pwd_discount_rate" type="number" min="0" max="100" :disabled="!isOwner"
                                                class="block w-full rounded-xl border border-[#e8e3da] bg-white py-2.5 pl-4 pr-12 text-sm text-slate-900 shadow-sm transition focus:border-[#0b4d59] focus:outline-none focus:ring-2 focus:ring-[#0b4d59]/20 disabled:cursor-not-allowed disabled:bg-[#f3efe9] disabled:text-slate-400" />
                                            <span class="pointer-events-none absolute inset-y-0 right-4 flex items-center text-sm font-semibold text-slate-400">%</span>
                                        </div>
                                        <p class="mt-1.5 text-xs text-slate-400">RA 10754 minimum: 20%</p>
                                        <p v-if="discountForm.errors.pwd_discount_rate" class="mt-1 text-xs text-red-600">{{ discountForm.errors.pwd_discount_rate }}</p>
                                    </div>
                                </div>

                                <div class="mt-6 flex flex-col gap-3 border-t border-[#f0ece6] pt-5 sm:flex-row sm:items-center sm:justify-between">
                                    <p class="text-xs text-slate-400">Changes take effect immediately on the POS terminal.</p>
                                    <button v-if="isOwner" type="button" :disabled="discountForm.processing"
                                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-[#0b4d59] px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-[#093e49] focus:outline-none focus:ring-2 focus:ring-[#0b4d59]/40 disabled:opacity-60 sm:self-end"
                                        @click="saveDiscounts">
                                        <svg v-if="discountForm.processing" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z" />
                                        </svg>
                                        <span>{{ discountForm.processing ? 'Saving…' : 'Save Changes' }}</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section v-show="activeSection === 'floor-plan'">
                        <div class="mb-5">
                            <h3 class="text-base font-semibold text-slate-900">Floor Plan</h3>
                            <p class="mt-1 text-sm text-slate-500">
                                Map the real room shape, including angled walls and extra corners, then place tables, partitions, and the counter area.
                            </p>
                        </div>

                        <div class="overflow-hidden rounded-2xl border border-[#e8e3da] bg-white shadow-sm">
                            <div class="flex flex-wrap items-center justify-between gap-4 border-b border-[#e8e3da] bg-[#faf7f3] px-5 py-3">
                                <div class="flex flex-wrap items-center gap-2.5">
                                    <span class="inline-flex h-7 w-7 items-center justify-center rounded-lg bg-[#0b4d59]/10">
                                        <svg class="h-3.5 w-3.5 text-[#0b4d59]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75L9 3.75l7.5 2.25 3.75 6-4.5 8.25H6L3.75 6.75z" />
                                        </svg>
                                    </span>
                                    <span class="text-sm font-semibold text-slate-700">Restaurant Floor</span>
                                    <span class="rounded-full bg-[#0b4d59]/10 px-2 py-0.5 text-xs font-semibold text-[#0b4d59]">
                                        {{ roomPoints.length }} corners
                                    </span>
                                    <span class="rounded-full bg-[#f0ece6] px-2 py-0.5 text-xs font-semibold text-slate-500">
                                        {{ tables.length }} {{ tables.length === 1 ? 'table' : 'tables' }}
                                    </span>
                                    <span class="rounded-full bg-[#f0ece6] px-2 py-0.5 text-xs font-semibold text-slate-500">
                                        {{ fixtures.length }} {{ fixtures.length === 1 ? 'fixture' : 'fixtures' }}
                                    </span>
                                </div>
                                <div v-if="isOwner" class="flex flex-wrap items-center gap-2">
                                    <button type="button"
                                        class="rounded-xl px-3 py-2 text-xs font-semibold transition"
                                        :class="selection.type === 'room'
                                            ? 'bg-[#0b4d59] text-white shadow-sm'
                                            : 'border border-[#e8e3da] bg-white text-slate-600 hover:bg-[#f0ece6]'"
                                        @click="selectRoom(selection.pointIndex)">
                                        Room Shape
                                    </button>
                                    <button type="button"
                                        class="inline-flex items-center gap-1.5 rounded-xl bg-[#0b4d59] px-3.5 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-[#093e49]"
                                        @click="openAddModal">
                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                        </svg>
                                        Add Table
                                    </button>
                                    <button type="button"
                                        class="rounded-xl border border-[#e8e3da] bg-white px-3 py-2 text-xs font-semibold text-slate-600 transition hover:bg-[#f0ece6]"
                                        @click="addFixture('partition')">
                                        Add Partition
                                    </button>
                                    <button type="button"
                                        class="rounded-xl border border-[#e8e3da] bg-white px-3 py-2 text-xs font-semibold text-slate-600 transition hover:bg-[#f0ece6]"
                                        @click="addFixture('counter')">
                                        Add Counter
                                    </button>
                                    <button v-if="tables.length > 0 || fixtures.length > 0" type="button"
                                        class="rounded-lg px-3 py-1.5 text-xs font-medium text-slate-400 transition hover:bg-red-50 hover:text-red-500"
                                        @click="clearLayout">
                                        Clear items
                                    </button>
                                </div>
                            </div>

                            <div class="flex min-h-0 overflow-x-auto">
                                <div
                                    class="relative shrink-0 overflow-hidden"
                                    :style="{
                                        width: `${CANVAS_W}px`,
                                        height: `${CANVAS_H}px`,
                                        backgroundColor: '#f5f1eb',
                                        backgroundImage: 'radial-gradient(circle, #c9c3bb 1.5px, transparent 1.5px)',
                                        backgroundSize: '28px 28px',
                                    }"
                                    @click.self="clearSelection"
                                >
                                    <svg class="absolute inset-0 h-full w-full" :viewBox="`0 0 ${CANVAS_W} ${CANVAS_H}`" fill="none">
                                        <polygon
                                            :points="roomPolygonPoints"
                                            fill="rgba(255,255,255,0.42)"
                                            stroke="#cbb9a2"
                                            stroke-linejoin="round"
                                            stroke-width="3"
                                            stroke-dasharray="10 8"
                                            class="cursor-pointer transition"
                                            @click.stop="selectRoom(selection.pointIndex)"
                                        />

                                        <g v-if="selection.type === 'room'">
                                            <circle
                                                v-for="(point, index) in roomPoints"
                                                :key="`corner-ring-${index}`"
                                                :cx="point.x"
                                                :cy="point.y"
                                                r="10"
                                                fill="rgba(11,77,89,0.14)"
                                                class="cursor-move"
                                                @mousedown.stop="startCornerDrag($event, index)"
                                                @touchstart.stop.prevent="startCornerDrag($event, index)"
                                            />
                                            <circle
                                                v-for="(point, index) in roomPoints"
                                                :key="`corner-core-${index}`"
                                                :cx="point.x"
                                                :cy="point.y"
                                                r="5.5"
                                                :fill="selection.pointIndex === index ? '#0b4d59' : '#ffffff'"
                                                :stroke="selection.pointIndex === index ? '#d8eef1' : '#0b4d59'"
                                                stroke-width="2.5"
                                                class="cursor-move transition"
                                                @mousedown.stop="startCornerDrag($event, index)"
                                                @touchstart.stop.prevent="startCornerDrag($event, index)"
                                            />
                                        </g>
                                    </svg>

                                    <div class="pointer-events-none absolute left-4 top-4 rounded-full bg-white/85 px-3 py-1 text-[10.5px] font-semibold uppercase tracking-[0.18em] text-slate-500 shadow-sm">
                                        Room Outline
                                    </div>

                                    <div v-if="tables.length === 0 && fixtures.length === 0" class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center gap-3">
                                        <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-white/70 shadow-sm">
                                            <svg class="h-7 w-7 text-slate-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 8.25L12 4.5l7.5 3.75m-15 0v7.5L12 19.5l7.5-3.75v-7.5m-15 0L12 12m7.5-3.75L12 12m0 0v7.5" />
                                            </svg>
                                        </div>
                                        <p class="text-sm font-medium text-slate-400">Room outline is ready</p>
                                        <p v-if="isOwner" class="text-xs text-slate-400">Add tables, partitions, or the counter, then drag corners to match the real space</p>
                                    </div>

                                    <div
                                        v-for="fixture in fixtures"
                                        :key="fixture.id"
                                        class="absolute select-none"
                                        :class="isOwner ? (isDragging('fixture', fixture.id) ? 'cursor-grabbing' : 'cursor-grab') : 'cursor-default'"
                                        :style="{
                                            left: fixture.x + 'px',
                                            top: fixture.y + 'px',
                                            width: fixture.w + 'px',
                                            height: fixture.h + 'px',
                                            zIndex: ((selection.type === 'fixture' && selection.id === fixture.id) || isDragging('fixture', fixture.id)) ? 18 : 8,
                                        }"
                                        @mousedown.stop="startDrag($event, 'fixture', fixture.id)"
                                        @touchstart.stop.prevent="startDrag($event, 'fixture', fixture.id)"
                                    >
                                        <div
                                            class="absolute inset-0 flex items-center justify-center border-2 text-[10px] font-semibold uppercase tracking-[0.14em] transition-all duration-150"
                                            :class="[
                                                fixture.type === 'partition' ? 'rounded-full' : 'rounded-2xl',
                                                fixture.type === 'partition'
                                                    ? ((selection.type === 'fixture' && selection.id === fixture.id)
                                                        ? 'border-[#8f8172] bg-[#e8ded1] text-[#6f6459] shadow-[0_6px_16px_-6px_rgba(93,77,55,0.25)]'
                                                        : 'border-[#c9bcae] bg-[#f2ebe2] text-[#8d8377] shadow-[0_3px_10px_-6px_rgba(93,77,55,0.2)]')
                                                    : ((selection.type === 'fixture' && selection.id === fixture.id)
                                                        ? 'border-[#0b4d59] bg-[#0b4d59] text-white shadow-[0_8px_24px_-8px_rgba(11,77,89,0.45)]'
                                                        : 'border-[#8cc0c8] bg-[#dff0f3] text-[#0b4d59] shadow-[0_4px_14px_-7px_rgba(11,77,89,0.28)]'),
                                                isDragging('fixture', fixture.id) ? 'scale-[1.02] opacity-90' : '',
                                            ]"
                                        >
                                            <span
                                                v-if="fixture.type === 'counter' || fixture.w > 80"
                                                :class="fixture.type === 'counter' ? counterLabelClass(fixture) : 'max-w-full truncate px-2 text-center'"
                                                :style="fixture.type === 'counter' ? counterLabelStyle(fixture) : null"
                                            >
                                                {{ fixture.name }}
                                            </span>
                                        </div>
                                    </div>

                                    <div
                                        v-for="table in tables"
                                        :key="table.id"
                                        class="absolute select-none"
                                        :class="isOwner ? (isDragging('table', table.id) ? 'cursor-grabbing' : 'cursor-grab') : 'cursor-default'"
                                        :style="tableWrapperStyle(table)"
                                        @mousedown.stop="startDrag($event, 'table', table.id)"
                                        @touchstart.stop.prevent="startDrag($event, 'table', table.id)"
                                    >
                                        <div class="absolute" :style="tableInnerStyle(table)">
                                            <div
                                                v-for="(chair, chairIndex) in chairsForTable(table)"
                                                :key="chairIndex"
                                                class="pointer-events-none absolute rounded-full border transition-colors duration-150"
                                                :class="selection.type === 'table' && selection.id === table.id ? 'border-[#86bec8] bg-[#b4d7de]' : 'border-[#b8b1a8] bg-[#cec8c0]'"
                                                :style="{ width: (CHAIR_R * 2) + 'px', height: (CHAIR_R * 2) + 'px', left: chair.x + 'px', top: chair.y + 'px' }"
                                            />

                                            <div
                                                class="absolute inset-0 flex flex-col items-center justify-center gap-0.5 border-2 bg-white transition-all duration-150"
                                                :class="[
                                                    table.shape === 'round' ? 'rounded-full' : 'rounded-xl',
                                                    selection.type === 'table' && selection.id === table.id
                                                        ? 'border-[#0b4d59] bg-[#f3fafb] shadow-[0_6px_20px_-6px_rgba(11,77,89,0.35)]'
                                                        : 'border-[#ddd7ce] shadow-[0_2px_8px_-3px_rgba(0,0,0,0.12)] hover:border-[#9dbfc5] hover:shadow-[0_4px_14px_-5px_rgba(11,77,89,0.2)]',
                                                    isDragging('table', table.id) ? 'scale-105 opacity-90' : '',
                                                ]"
                                            >
                                                <span :class="tableLabelClass(table)" :style="tableLabelStyle(table)">{{ table.name }}</span>
                                                <div class="flex items-center gap-0.5 text-slate-400">
                                                    <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24">
                                                        <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z" />
                                                    </svg>
                                                    <span class="text-[10px] font-semibold text-slate-500">{{ table.seats }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div v-if="isOwner" class="pointer-events-none absolute bottom-3 left-1/2 -translate-x-1/2">
                                        <span class="rounded-full bg-black/10 px-3 py-1 text-[10px] font-medium text-slate-500 backdrop-blur-sm">
                                            Drag tables, counters, and partitions · select the room to add more corners
                                        </span>
                                    </div>
                                </div>

                                <div class="min-w-[22rem] flex-1 border-l border-[#e8e3da]">
                                    <div v-if="selection.type === 'room'" class="flex h-full flex-col p-5">
                                        <div class="mb-4 flex items-center justify-between">
                                            <p class="text-[10.5px] font-semibold uppercase tracking-[0.18em] text-slate-400">Room Shape</p>
                                            <button type="button"
                                                class="flex h-6 w-6 items-center justify-center rounded-lg text-slate-400 transition hover:bg-[#f0ece6] hover:text-slate-600"
                                                @click="clearSelection">
                                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </button>
                                        </div>

                                        <div class="rounded-2xl border border-[#e8e3da] bg-[#faf7f3] p-4">
                                            <p class="text-sm font-semibold text-slate-900">Custom room outline</p>
                                            <p class="mt-1 text-xs leading-5 text-slate-500">
                                                Drag the corner handles on the canvas to follow angled walls, alcoves, or service nooks.
                                            </p>
                                        </div>

                                        <div class="mt-4 grid grid-cols-2 gap-3">
                                            <div class="rounded-2xl border border-[#e8e3da] bg-white p-3">
                                                <p class="text-[10px] font-semibold uppercase tracking-[0.16em] text-slate-400">Corners</p>
                                                <p class="mt-1 text-lg font-semibold text-slate-900">{{ roomPoints.length }}</p>
                                            </div>
                                            <div class="rounded-2xl border border-[#e8e3da] bg-white p-3">
                                                <p class="text-[10px] font-semibold uppercase tracking-[0.16em] text-slate-400">Selected</p>
                                                <p class="mt-1 text-sm font-semibold text-slate-900">
                                                    {{ selectedCorner ? 'Corner ' + (selection.pointIndex + 1) : 'Longest wall' }}
                                                </p>
                                            </div>
                                        </div>

                                        <div class="mt-4 grid grid-cols-2 gap-2">
                                            <button type="button" :disabled="!isOwner"
                                                class="rounded-xl bg-[#0b4d59] px-3 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-[#093e49] disabled:opacity-50"
                                                @click="addRoomCorner">
                                                Add Corner
                                            </button>
                                            <button type="button" :disabled="!isOwner || roomPoints.length <= 3 || selection.pointIndex === null"
                                                class="rounded-xl border border-[#e8e3da] bg-white px-3 py-2 text-xs font-semibold text-slate-600 transition hover:bg-[#f0ece6] disabled:opacity-40"
                                                @click="removeRoomCorner">
                                                Remove Corner
                                            </button>
                                        </div>

                                        <div v-if="selectedCornerDisplay" class="mt-4 grid grid-cols-2 gap-3">
                                            <div class="rounded-2xl border border-[#e8e3da] bg-white p-3">
                                                <p class="text-[10px] font-semibold uppercase tracking-[0.16em] text-slate-400">X</p>
                                                <p class="mt-1 text-base font-semibold text-slate-900">{{ selectedCornerDisplay.x }}</p>
                                            </div>
                                            <div class="rounded-2xl border border-[#e8e3da] bg-white p-3">
                                                <p class="text-[10px] font-semibold uppercase tracking-[0.16em] text-slate-400">Y</p>
                                                <p class="mt-1 text-base font-semibold text-slate-900">{{ selectedCornerDisplay.y }}</p>
                                            </div>
                                        </div>

                                        <div class="mt-4 rounded-2xl border border-dashed border-[#d8d0c6] bg-[#fcfaf7] p-4">
                                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">Tip</p>
                                            <p class="mt-1 text-xs leading-5 text-slate-500">
                                                Select a corner first if you want the new point inserted on a specific wall. Otherwise the editor adds it to the longest edge.
                                            </p>
                                        </div>
                                    </div>

                                    <div v-else-if="selectedTable" class="flex h-full flex-col p-5">
                                        <div class="mb-4 flex items-center justify-between">
                                            <p class="text-[10.5px] font-semibold uppercase tracking-[0.18em] text-slate-400">Selected Table</p>
                                            <button type="button"
                                                class="flex h-6 w-6 items-center justify-center rounded-lg text-slate-400 transition hover:bg-[#f0ece6] hover:text-slate-600"
                                                @click="clearSelection">
                                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </button>
                                        </div>

                                        <div class="mb-4">
                                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Name</label>
                                            <input v-model="selectedTable.name" type="text" maxlength="30" :disabled="!isOwner"
                                                class="block w-full rounded-xl border border-[#e8e3da] bg-white px-3 py-2 text-sm font-medium text-slate-800 shadow-sm transition focus:border-[#0b4d59] focus:outline-none focus:ring-2 focus:ring-[#0b4d59]/20 disabled:bg-[#f3efe9] disabled:text-slate-400" />
                                        </div>

                                        <div class="mb-4">
                                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Seats</label>
                                            <div class="flex items-center overflow-hidden rounded-xl border border-[#e8e3da] bg-white shadow-sm">
                                                <button type="button" :disabled="!isOwner || selectedTable.seats <= 1"
                                                    class="flex h-9 w-9 shrink-0 items-center justify-center text-base font-bold text-slate-500 transition hover:bg-[#f0ece6] disabled:opacity-30"
                                                    @click="selectedTable.seats = Math.max(1, selectedTable.seats - 1)">−</button>
                                                <span class="flex-1 text-center text-sm font-bold text-slate-800">{{ selectedTable.seats }}</span>
                                                <button type="button" :disabled="!isOwner || selectedTable.seats >= 20"
                                                    class="flex h-9 w-9 shrink-0 items-center justify-center text-base font-bold text-slate-500 transition hover:bg-[#f0ece6] disabled:opacity-30"
                                                    @click="selectedTable.seats = Math.min(20, selectedTable.seats + 1)">+</button>
                                            </div>
                                        </div>

                                        <div class="mb-6">
                                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Shape</label>
                                            <div class="grid grid-cols-2 gap-1.5">
                                                <button v-for="shape in [{ id: 'square', label: 'Rect' }, { id: 'round', label: 'Round' }]" :key="shape.id"
                                                    type="button" :disabled="!isOwner"
                                                    class="flex flex-col items-center gap-1.5 rounded-xl border-2 p-2 text-xs font-semibold transition"
                                                    :class="selectedTable.shape === shape.id
                                                        ? 'border-[#0b4d59] bg-[#f0f7f8] text-[#0b4d59]'
                                                        : 'border-[#e8e3da] text-slate-500 hover:border-[#9dbfc5] disabled:opacity-40'"
                                                    @click="setSelectedTableShape(shape.id)">
                                                    <div class="h-4 w-6 border-2 transition"
                                                        :class="[
                                                            shape.id === 'round' ? 'rounded-full' : 'rounded',
                                                            selectedTable.shape === shape.id ? 'border-[#0b4d59] bg-[#0b4d59]/10' : 'border-slate-300',
                                                        ]" />
                                                    {{ shape.label }}
                                                </button>
                                            </div>
                                        </div>

                                        <div v-if="selectedTable.shape === 'round'" class="mb-6">
                                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Diameter</label>
                                            <div class="flex items-center overflow-hidden rounded-xl border border-[#e8e3da] bg-white shadow-sm">
                                                <button type="button" :disabled="!isOwner || selectedTable.w <= TABLE_LIMITS.round.minDiameter"
                                                    class="flex h-9 w-9 shrink-0 items-center justify-center text-base font-bold text-slate-500 transition hover:bg-[#f0ece6] disabled:opacity-30"
                                                    @click="adjustSelectedTableSize('diameter', -1)">−</button>
                                                <span class="flex-1 text-center text-sm font-bold text-slate-800">{{ Math.round(selectedTable.w) }} px</span>
                                                <button type="button" :disabled="!isOwner || selectedTable.w >= TABLE_LIMITS.round.maxDiameter"
                                                    class="flex h-9 w-9 shrink-0 items-center justify-center text-base font-bold text-slate-500 transition hover:bg-[#f0ece6] disabled:opacity-30"
                                                    @click="adjustSelectedTableSize('diameter', 1)">+</button>
                                            </div>
                                        </div>

                                        <div v-else class="mb-4 grid gap-4">
                                            <div>
                                                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Width</label>
                                                <div class="flex items-center overflow-hidden rounded-xl border border-[#e8e3da] bg-white shadow-sm">
                                                    <button type="button" :disabled="!isOwner || selectedTable.w <= TABLE_LIMITS.square.minWidth"
                                                        class="flex h-9 w-9 shrink-0 items-center justify-center text-base font-bold text-slate-500 transition hover:bg-[#f0ece6] disabled:opacity-30"
                                                        @click="adjustSelectedTableSize('width', -1)">−</button>
                                                    <span class="flex-1 text-center text-sm font-bold text-slate-800">{{ Math.round(selectedTable.w) }} px</span>
                                                    <button type="button" :disabled="!isOwner || selectedTable.w >= TABLE_LIMITS.square.maxWidth"
                                                        class="flex h-9 w-9 shrink-0 items-center justify-center text-base font-bold text-slate-500 transition hover:bg-[#f0ece6] disabled:opacity-30"
                                                        @click="adjustSelectedTableSize('width', 1)">+</button>
                                                </div>
                                            </div>

                                            <div>
                                                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Depth</label>
                                                <div class="flex items-center overflow-hidden rounded-xl border border-[#e8e3da] bg-white shadow-sm">
                                                    <button type="button" :disabled="!isOwner || selectedTable.h <= TABLE_LIMITS.square.minHeight"
                                                        class="flex h-9 w-9 shrink-0 items-center justify-center text-base font-bold text-slate-500 transition hover:bg-[#f0ece6] disabled:opacity-30"
                                                        @click="adjustSelectedTableSize('height', -1)">−</button>
                                                    <span class="flex-1 text-center text-sm font-bold text-slate-800">{{ Math.round(selectedTable.h) }} px</span>
                                                    <button type="button" :disabled="!isOwner || selectedTable.h >= TABLE_LIMITS.square.maxHeight"
                                                        class="flex h-9 w-9 shrink-0 items-center justify-center text-base font-bold text-slate-500 transition hover:bg-[#f0ece6] disabled:opacity-30"
                                                        @click="adjustSelectedTableSize('height', 1)">+</button>
                                                </div>
                                            </div>
                                        </div>

                                        <div v-if="selectedTable.shape === 'square'" class="mb-6 space-y-2.5">
                                            <div class="flex items-center justify-between rounded-2xl border border-[#e8e3da] bg-[#faf7f3] px-3.5 py-3">
                                                <div>
                                                    <p class="text-[10px] font-semibold uppercase tracking-[0.16em] text-slate-400">Rotation</p>
                                                    <p class="mt-1 text-sm font-semibold text-slate-900">{{ tableRotationLabel(selectedTable) }}</p>
                                                    <p class="text-[11px] text-slate-500">{{ tableOrientationLabel(selectedTable) }}</p>
                                                </div>
                                                <button type="button" :disabled="!isOwner"
                                                    class="inline-flex items-center gap-1.5 rounded-xl bg-[#0b4d59] px-3 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-[#093e49] disabled:opacity-50"
                                                    @click="rotateSelectedTable">
                                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992V4.356" />
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.985 19.644v-4.992h4.992" />
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.93 9.347a8 8 0 0113.643-3.172l2.442 2.442" />
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.07 14.653a8 8 0 01-13.643 3.172l-2.442-2.442" />
                                                    </svg>
                                                    Rotate 90°
                                                </button>
                                            </div>
                                        </div>

                                        <div v-if="isOwner" class="mt-auto pt-4">
                                            <button type="button"
                                                class="flex w-full items-center justify-center gap-2 rounded-xl border border-red-200 bg-red-50 px-4 py-2.5 text-sm font-medium text-red-600 transition hover:bg-red-100 hover:border-red-300"
                                                @click="deleteSelectedItem">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                                </svg>
                                                Delete Table
                                            </button>
                                        </div>
                                    </div>

                                    <div v-else-if="selectedFixture && selectedFixtureMeta" class="flex h-full flex-col p-5">
                                        <div class="mb-4 flex items-center justify-between">
                                            <div>
                                                <p class="text-[10.5px] font-semibold uppercase tracking-[0.18em] text-slate-400">Selected Fixture</p>
                                                <p class="mt-1 text-sm font-semibold text-slate-900">{{ selectedFixtureMeta.label }}</p>
                                            </div>
                                            <button type="button"
                                                class="flex h-6 w-6 items-center justify-center rounded-lg text-slate-400 transition hover:bg-[#f0ece6] hover:text-slate-600"
                                                @click="clearSelection">
                                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </button>
                                        </div>

                                        <div class="mb-4">
                                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Label</label>
                                            <input v-model="selectedFixture.name" type="text" maxlength="30" :disabled="!isOwner"
                                                class="block w-full rounded-xl border border-[#e8e3da] bg-white px-3 py-2 text-sm font-medium text-slate-800 shadow-sm transition focus:border-[#0b4d59] focus:outline-none focus:ring-2 focus:ring-[#0b4d59]/20 disabled:bg-[#f3efe9] disabled:text-slate-400" />
                                        </div>

                                        <div class="grid gap-4">
                                            <div>
                                                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Width</label>
                                                <div class="flex items-center overflow-hidden rounded-xl border border-[#e8e3da] bg-white shadow-sm">
                                                    <button type="button" :disabled="!isOwner || selectedFixture.w <= selectedFixtureMeta.minWidth"
                                                        class="flex h-9 w-9 shrink-0 items-center justify-center text-base font-bold text-slate-500 transition hover:bg-[#f0ece6] disabled:opacity-30"
                                                        @click="adjustSelectedFixtureSize('width', -1)">−</button>
                                                    <span class="flex-1 text-center text-sm font-bold text-slate-800">{{ Math.round(selectedFixture.w) }} px</span>
                                                    <button type="button" :disabled="!isOwner || selectedFixture.w >= selectedFixtureMeta.maxWidth"
                                                        class="flex h-9 w-9 shrink-0 items-center justify-center text-base font-bold text-slate-500 transition hover:bg-[#f0ece6] disabled:opacity-30"
                                                        @click="adjustSelectedFixtureSize('width', 1)">+</button>
                                                </div>
                                            </div>

                                            <div>
                                                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">
                                                    {{ selectedFixture.type === 'partition' ? 'Thickness / Length' : 'Depth' }}
                                                </label>
                                                <div class="flex items-center overflow-hidden rounded-xl border border-[#e8e3da] bg-white shadow-sm">
                                                    <button type="button" :disabled="!isOwner || selectedFixture.h <= selectedFixtureMeta.minHeight"
                                                        class="flex h-9 w-9 shrink-0 items-center justify-center text-base font-bold text-slate-500 transition hover:bg-[#f0ece6] disabled:opacity-30"
                                                        @click="adjustSelectedFixtureSize('height', -1)">−</button>
                                                    <span class="flex-1 text-center text-sm font-bold text-slate-800">{{ Math.round(selectedFixture.h) }} px</span>
                                                    <button type="button" :disabled="!isOwner || selectedFixture.h >= selectedFixtureMeta.maxHeight"
                                                        class="flex h-9 w-9 shrink-0 items-center justify-center text-base font-bold text-slate-500 transition hover:bg-[#f0ece6] disabled:opacity-30"
                                                        @click="adjustSelectedFixtureSize('height', 1)">+</button>
                                                </div>
                                            </div>
                                        </div>

                                        <div v-if="selectedFixture.type === 'partition'" class="mt-4">
                                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Orientation</label>
                                            <div class="grid grid-cols-2 gap-1.5">
                                                <button type="button" :disabled="!isOwner"
                                                    class="rounded-xl border-2 px-3 py-2 text-xs font-semibold transition"
                                                    :class="partitionOrientation(selectedFixture) === 'horizontal'
                                                        ? 'border-[#0b4d59] bg-[#f0f7f8] text-[#0b4d59]'
                                                        : 'border-[#e8e3da] text-slate-500 hover:border-[#9dbfc5] disabled:opacity-40'"
                                                    @click="setPartitionOrientation('horizontal')">
                                                    Horizontal
                                                </button>
                                                <button type="button" :disabled="!isOwner"
                                                    class="rounded-xl border-2 px-3 py-2 text-xs font-semibold transition"
                                                    :class="partitionOrientation(selectedFixture) === 'vertical'
                                                        ? 'border-[#0b4d59] bg-[#f0f7f8] text-[#0b4d59]'
                                                        : 'border-[#e8e3da] text-slate-500 hover:border-[#9dbfc5] disabled:opacity-40'"
                                                    @click="setPartitionOrientation('vertical')">
                                                    Vertical
                                                </button>
                                            </div>
                                        </div>

                                        <div v-else-if="selectedFixture.type === 'counter'" class="mt-4 space-y-2.5">
                                            <div class="flex items-center justify-between rounded-2xl border border-[#e8e3da] bg-[#faf7f3] px-3.5 py-3">
                                                <div>
                                                    <p class="text-[10px] font-semibold uppercase tracking-[0.16em] text-slate-400">Orientation</p>
                                                    <p class="mt-1 text-sm font-semibold text-slate-900">
                                                        {{ counterOrientation(selectedFixture) === 'horizontal' ? 'Horizontal' : 'Vertical' }}
                                                    </p>
                                                </div>
                                                <button type="button" :disabled="!isOwner"
                                                    class="inline-flex items-center gap-1.5 rounded-xl bg-[#0b4d59] px-3 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-[#093e49] disabled:opacity-50"
                                                    @click="rotateSelectedCounter">
                                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992V4.356" />
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.985 19.644v-4.992h4.992" />
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.93 9.347a8 8 0 0113.643-3.172l2.442 2.442" />
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.07 14.653a8 8 0 01-13.643 3.172l-2.442-2.442" />
                                                    </svg>
                                                    Rotate 90°
                                                </button>
                                            </div>
                                        </div>

                                        <div class="mt-4 rounded-2xl border border-[#e8e3da] bg-[#faf7f3] p-4">
                                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">Use Case</p>
                                            <p class="mt-1 text-xs leading-5 text-slate-500">
                                                {{ selectedFixture.type === 'partition'
                                                    ? 'Use partitions for walls, dividers, glass separators, or queue barriers.'
                                                    : 'Use the counter block for cashier, pickup, bar, or service station areas.' }}
                                            </p>
                                        </div>

                                        <div v-if="isOwner" class="mt-auto pt-4">
                                            <button type="button"
                                                class="flex w-full items-center justify-center gap-2 rounded-xl border border-red-200 bg-red-50 px-4 py-2.5 text-sm font-medium text-red-600 transition hover:bg-red-100 hover:border-red-300"
                                                @click="deleteSelectedItem">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                                </svg>
                                                Delete {{ selectedFixtureMeta.label }}
                                            </button>
                                        </div>
                                    </div>

                                    <div v-else class="flex h-full flex-col items-center justify-center gap-3 p-6 text-center">
                                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-[#f0ece6]">
                                            <svg class="h-6 w-6 text-slate-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 8.25L12 4.5l7.5 3.75m-15 0v7.5L12 19.5l7.5-3.75v-7.5m-15 0L12 12m7.5-3.75L12 12m0 0v7.5" />
                                            </svg>
                                        </div>
                                        <p class="text-xs font-medium leading-5 text-slate-400">
                                            Select the room, a table, or a fixture<br>to edit properties
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center justify-between border-t border-[#e8e3da] bg-[#faf7f3] px-5 py-3.5">
                                <p class="text-xs text-slate-400">
                                    {{ tables.length === 0 && fixtures.length === 0
                                        ? 'No tables, counters, or partitions on the floor yet'
                                        : `${tables.length} ${tables.length === 1 ? 'table' : 'tables'} · ${fixtures.length} ${fixtures.length === 1 ? 'fixture' : 'fixtures'} · ${totalSeats} total seats` }}
                                </p>
                                <button v-if="isOwner" type="button" :disabled="fpSaving"
                                    class="inline-flex items-center gap-2 rounded-xl px-4 py-2 text-sm font-semibold shadow-sm transition focus:outline-none focus:ring-2 focus:ring-[#0b4d59]/40 disabled:opacity-60"
                                    :class="fpSaved ? 'bg-emerald-600 text-white' : 'bg-[#0b4d59] text-white hover:bg-[#093e49]'"
                                    @click="saveFloorPlan">
                                    <svg v-if="fpSaving" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z" />
                                    </svg>
                                    <svg v-else-if="fpSaved" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                    </svg>
                                    <span>{{ fpSaving ? 'Saving…' : fpSaved ? 'Saved!' : 'Save Floor Plan' }}</span>
                                </button>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
            </template>
        </div>

        <Teleport to="body">
            <Transition
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="opacity-0 scale-95"
                enter-to-class="opacity-100 scale-100"
                leave-active-class="transition duration-150 ease-in"
                leave-from-class="opacity-100 scale-100"
                leave-to-class="opacity-0 scale-95"
            >
                <div v-if="showAddModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <div class="absolute inset-0 bg-black/30 backdrop-blur-sm" @click="showAddModal = false" />
                    <div class="relative z-10 w-full max-w-sm overflow-hidden rounded-3xl bg-white shadow-2xl ring-1 ring-black/5">
                        <div class="flex items-center justify-between border-b border-[#e8e3da] bg-[#faf7f3] px-6 py-4">
                            <h3 class="text-base font-semibold text-slate-900">Add Table</h3>
                            <button type="button"
                                class="flex h-7 w-7 items-center justify-center rounded-xl text-slate-400 transition hover:bg-[#ede8e1] hover:text-slate-600"
                                @click="showAddModal = false">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <div class="space-y-5 p-6">
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-slate-700">Table Name</label>
                                <input v-model="addForm.name" type="text" maxlength="30" placeholder="e.g. Table 1, Window Seat…"
                                    class="block w-full rounded-xl border border-[#e8e3da] bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm transition focus:border-[#0b4d59] focus:outline-none focus:ring-2 focus:ring-[#0b4d59]/20"
                                    @keydown.enter="confirmAdd" />
                            </div>

                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-slate-700">Number of Seats</label>
                                <div class="flex items-center overflow-hidden rounded-xl border border-[#e8e3da] bg-white shadow-sm">
                                    <button type="button" :disabled="addForm.seats <= 1"
                                        class="flex h-11 w-12 shrink-0 items-center justify-center text-xl font-bold text-slate-500 transition hover:bg-[#f0ece6] disabled:opacity-30"
                                        @click="addForm.seats = Math.max(1, addForm.seats - 1)">−</button>
                                    <span class="flex-1 text-center text-xl font-bold text-slate-800">{{ addForm.seats }}</span>
                                    <button type="button" :disabled="addForm.seats >= 20"
                                        class="flex h-11 w-12 shrink-0 items-center justify-center text-xl font-bold text-slate-500 transition hover:bg-[#f0ece6] disabled:opacity-30"
                                        @click="addForm.seats = Math.min(20, addForm.seats + 1)">+</button>
                                </div>
                            </div>

                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-slate-700">Table Shape</label>
                                <div class="grid grid-cols-2 gap-2.5">
                                    <button v-for="shape in [{ id: 'square', label: 'Rectangle' }, { id: 'round', label: 'Round' }]" :key="shape.id"
                                        type="button"
                                        class="flex flex-col items-center gap-2 rounded-2xl border-2 py-3.5 text-sm font-semibold transition"
                                        :class="addForm.shape === shape.id
                                            ? 'border-[#0b4d59] bg-[#f0f7f8] text-[#0b4d59]'
                                            : 'border-[#e8e3da] text-slate-500 hover:border-[#9dbfc5]'"
                                        @click="addForm.shape = shape.id">
                                        <div class="h-6 w-9 border-2 transition"
                                            :class="[
                                                shape.id === 'round' ? 'rounded-full' : 'rounded-lg',
                                                addForm.shape === shape.id ? 'border-[#0b4d59] bg-[#0b4d59]/10' : 'border-slate-300 bg-slate-50',
                                            ]" />
                                        {{ shape.label }}
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="flex gap-3 border-t border-[#e8e3da] bg-[#faf7f3] px-6 py-4">
                            <button type="button"
                                class="flex-1 rounded-xl border border-[#e8e3da] bg-white px-4 py-2.5 text-sm font-semibold text-slate-600 shadow-sm transition hover:bg-[#f0ece6]"
                                @click="showAddModal = false">Cancel</button>
                            <button type="button" :disabled="!String(addForm.name).trim()"
                                class="flex-1 rounded-xl bg-[#0b4d59] px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-[#093e49] disabled:opacity-50"
                                @click="confirmAdd">Add Table</button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </MerchantLayout>
</template>