import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import 'remixicon/fonts/remixicon.css';
import { initEcho } from './echo';

// Expose Echo to window for real-time Reverb events
window.initEcho = initEcho;
createInertiaApp({
    title: (title) => title || 'SEMARAK ALIYAH XIV 2026',
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        window.__pageProps = props.initialPage.props;
        if (props.initialPage.props.reverb) {
            window.Echo = initEcho({
                ...props.initialPage.props.reverb,
                forceNew: true
            });
        }

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
