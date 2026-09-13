import '../css/app.css';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, DefineComponent, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import { FontAwesomeIcon } from './plugins/fontawesome';
import { vReveal } from './directives/reveal';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob<DefineComponent>('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .component('font-awesome-icon', FontAwesomeIcon)
            .directive('reveal', vReveal)
            .mount(el);
    },
    // Inertia's own top-of-screen progress bar. It only appears on real,
    // full-page visits (background usePoll reloads set showProgress: false
    // internally), so it never flashes during the 3s polling elsewhere in
    // the app — see resources/js/app.css for the brand-matched styling.
    progress: {
        color: '#2547E0',
        showSpinner: false,
    },
});
