export const DEFAULT_CENTER = { lat: 8.187464, lng: 126.352700 };

let googleMapsPromise;

export function loadGoogleMaps() {
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
        script.src = `https://maps.googleapis.com/maps/api/js?key=${apiKey}&libraries=places,drawing&v=weekly`;
        script.async = true;
        script.defer = true;
        script.dataset.googleMapsLoader = 'true';
        script.onload = () => resolve(window.google);
        script.onerror = () => reject(new Error('Unable to load Google Maps.'));
        document.head.appendChild(script);
    });

    return googleMapsPromise;
}
