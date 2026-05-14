function normalizeTimeValue(value) {
    if (typeof value !== 'string') {
        return null;
    }

    const match = value.match(/^(\d{1,2}):(\d{2})$/);

    if (!match) {
        return null;
    }

    const hours = Number(match[1]);
    const minutes = Number(match[2]);

    if (Number.isNaN(hours) || Number.isNaN(minutes) || hours < 0 || hours > 23 || minutes < 0 || minutes > 59) {
        return null;
    }

    return `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}`;
}

export function format24HourTo12Hour(value) {
    const normalized = normalizeTimeValue(value);

    if (!normalized) {
        return '--:--';
    }

    const [hoursText, minutesText] = normalized.split(':');
    const hours = Number(hoursText);
    const period = hours >= 12 ? 'PM' : 'AM';
    const displayHour = hours % 12 === 0 ? 12 : hours % 12;

    return `${displayHour}:${minutesText} ${period}`;
}

export function buildTime12HourOptions(stepMinutes = 15, extraValues = []) {
    const optionMap = new Map();

    for (let totalMinutes = 0; totalMinutes < 24 * 60; totalMinutes += stepMinutes) {
        const hours = Math.floor(totalMinutes / 60);
        const minutes = totalMinutes % 60;
        const value = `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}`;
        optionMap.set(value, {
            value,
            label: format24HourTo12Hour(value),
        });
    }

    extraValues
        .map(normalizeTimeValue)
        .filter(Boolean)
        .forEach((value) => {
            if (!optionMap.has(value)) {
                optionMap.set(value, {
                    value,
                    label: format24HourTo12Hour(value),
                });
            }
        });

    return [...optionMap.values()].sort((left, right) => left.value.localeCompare(right.value));
}
