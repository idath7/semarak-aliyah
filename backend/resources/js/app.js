import { createApp, h } from 'vue';
import { createInertiaApp, router } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import 'remixicon/fonts/remixicon.css';
import { initEcho } from './echo';
import CustomSelect from './Components/CustomSelect.vue';

// Expose Echo to window for real-time Reverb events
window.initEcho = initEcho;

// Cross-boundary navigation safeguard between Public Portal and Pendaftar/Admin Portals
if (typeof window !== 'undefined') {
    router.on('before', (event) => {
        try {
            const visitUrl = event.detail.visit.url;
            const currentPath = window.location.pathname;
            const targetPath = (visitUrl instanceof URL ? visitUrl.pathname : new URL(visitUrl, window.location.origin).pathname);

            const isCurrentPendaftar = currentPath.startsWith('/pendaftar') || currentPath.startsWith('/admin') || currentPath.startsWith('/operasional');
            const isTargetPendaftar = targetPath.startsWith('/pendaftar') || targetPath.startsWith('/admin') || targetPath.startsWith('/operasional');

            if (isCurrentPendaftar !== isTargetPendaftar) {
                event.preventDefault();
                const targetHref = visitUrl instanceof URL ? visitUrl.href : new URL(visitUrl, window.location.origin).href;
                window.location.href = targetHref;
            }
        } catch (e) {
            // fallback gracefully to standard visit
        }
    });
}
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
        app.component('CustomSelect', CustomSelect);
        app.component('custom-select', CustomSelect);
        app.config.globalProperties.$echo = window.Echo;
        app.mount(el);
        try {
            window.dispatchEvent(new CustomEvent('semarak:page-ready'));
        } catch (e) {}
        return app;
    },
    progress: {
        color: '#f59e0b',
        showSpinner: true,
    },
});
