import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import 'remixicon/fonts/remixicon.css';
import { initEcho } from './echo';

// Expose Echo to window for real-time Reverb events
window.initEcho = initEcho;
try {
    const reverbMeta = document.querySelector('meta[name="reverb-key"]');
    const key = reverbMeta ? reverbMeta.getAttribute('content') : 'semarak_reverb_key';
    window.Echo = initEcho({ key: key, host: window.location.hostname, port: 8085 });
} catch (e) {
    // Echo fallback
}

createInertiaApp({
    title: (title) => title || 'SEMARAK ALIYAH XIV 2026',
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) });
        app.use(plugin);
        app.config.globalProperties.$echo = window.Echo;
        app.mount(el);
        return app;
    },
    progress: {
        color: '#f59e0b',
        showSpinner: true,
    },
});
