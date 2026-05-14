export function toAbsoluteUrl(baseUrl, value) {
    if (!value) {
        return null;
    }

    if (/^https?:\/\//i.test(value)) {
        return value;
    }

    const normalizedBaseUrl = String(baseUrl ?? '').replace(/\/$/, '');

    if (!normalizedBaseUrl) {
        return value;
    }

    const normalizedPath = value.startsWith('/') ? value : `/${value}`;

    return `${normalizedBaseUrl}${normalizedPath}`;
}