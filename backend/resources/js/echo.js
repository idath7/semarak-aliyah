import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

export function initEcho(reverbConfig = {}) {
    if (window.Echo && !reverbConfig.forceNew) return window.Echo;

    const pageProps = window.__pageProps || {};
    const serverReverb = pageProps.reverb || {};

    const key = reverbConfig.key || serverReverb.key || import.meta.env.VITE_REVERB_APP_KEY || 'semarak-key-2026';
    const host = reverbConfig.host || serverReverb.host || import.meta.env.VITE_REVERB_HOST || window.location.hostname;
    const port = reverbConfig.port || serverReverb.port || (window.location.protocol === 'https:' ? 443 : 8085);
    const scheme = reverbConfig.scheme || serverReverb.scheme || (window.location.protocol === 'https:' ? 'https' : 'http');

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
