import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

export function initEcho(reverbConfig = {}) {
    if (window.Echo && !reverbConfig.forceNew) return window.Echo;

    const pageProps = window.__pageProps || {};
    const serverReverb = pageProps.reverb || {};

    const key = reverbConfig.key || serverReverb.key || import.meta.env.VITE_REVERB_APP_KEY || 'semarak-key-2026';
    let host = reverbConfig.host || serverReverb.host || import.meta.env.VITE_REVERB_HOST || window.location.hostname;
    const isHttps = window.location.protocol === 'https:';

    // Mencegah browser menampilkan dialog Chrome Private Network Access jika host bernilai localhost
    if ((host === 'localhost' || host === '127.0.0.1' || host === '0.0.0.0') && window.location.hostname !== 'localhost' && window.location.hostname !== '127.0.0.1') {
        host = window.location.hostname;
    }

    const port = reverbConfig.port || serverReverb.port || (isHttps ? 443 : 8085);
    const scheme = isHttps ? 'https' : (reverbConfig.scheme || serverReverb.scheme || 'http');

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
