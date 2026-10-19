import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

export function initEcho(reverbConfig = {}) {
    if (window.Echo) return window.Echo;

    const key = reverbConfig.key || import.meta.env.VITE_REVERB_APP_KEY || 'semarak_app_key';
    const host = reverbConfig.host || import.meta.env.VITE_REVERB_HOST || window.location.hostname;
    const port = reverbConfig.port || import.meta.env.VITE_REVERB_PORT || 8085;
    const scheme = reverbConfig.scheme || import.meta.env.VITE_REVERB_SCHEME || 'http';

    window.Echo = new Echo({
        broadcaster: 'reverb',
        key: key,
        wsHost: host,
        wsPort: port,
        wssPort: port,
        forceTLS: scheme === 'https',
        enabledTransports: ['ws', 'wss'],
    });

    return window.Echo;
}

export default initEcho;
