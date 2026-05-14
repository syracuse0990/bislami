import '../css/app.css';
import '@llayz46/sileo-vue/styles.css';

import { bootEcho } from '@/lib/echo';
import { createInertiaApp } from '@inertiajs/vue3';
import FlashToaster from '@/Components/Feedback/FlashToaster.vue';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, Fragment, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        bootEcho(props.initialPage.props.services?.realtime ?? null);

        return createApp({ render: () => h(Fragment, [h(App, props), h(FlashToaster)]) })
            .use(plugin)
            .use(ZiggyVue)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});
