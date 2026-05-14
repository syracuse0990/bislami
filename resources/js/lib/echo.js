import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

let echoInstance = null;

export function bootEcho(config) {
    if (echoInstance) {
        return echoInstance;
    }

    if (!config?.enabled || !config.key || !config.wsHost) {
        return null;
    }

    window.Pusher = Pusher;

    echoInstance = new Echo({
        broadcaster: 'pusher',
        key: config.key,
        cluster: config.cluster ?? 'mt1',
        wsHost: config.wsHost,
        wsPort: config.wsPort ?? 80,
        wssPort: config.wssPort ?? 443,
        forceTLS: Boolean(config.forceTLS),
        enabledTransports: ['ws', 'wss'],
        authEndpoint: config.authEndpoint ?? '/broadcasting/auth',
        withCredentials: true,
        disableStats: true,
    });

    window.Echo = echoInstance;

    return echoInstance;
}

export function getEcho() {
    return echoInstance;
}