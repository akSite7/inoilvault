import { createInertiaApp } from '@inertiajs/vue3';
import { createSSRApp, h } from 'vue';
import { renderToString } from 'vue/server-renderer';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    resolve: (name) => resolvePage(name),
    setup: ({ App, props, plugin }) => {
        return createSSRApp({ render: () => h(App, props) })
            .use(plugin);
    },
    title: (title) => (title ? `${title} - ${appName}` : appName),
    render: renderToString,
});

function resolvePage(name) {

    const pages = import.meta.glob('./pages/**/*.vue');
    return pages[`./pages/${name}.vue`]();
}
