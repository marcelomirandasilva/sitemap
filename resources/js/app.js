import '../css/app.css';
import './bootstrap';
import './echo';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { i18nVue } from 'laravel-vue-i18n';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';

// 1. Importe seus botões do Design System aqui
import PrimaryButton from './Components/PrimaryButton.vue';
import SecondaryButton from './Components/SecondaryButton.vue';
import DangerButton from './Components/DangerButton.vue';


const appName = import.meta.env.VITE_APP_NAME || 'Laravel';
const normalizarIdioma = (idioma) => {
    const idiomaNormalizado = String(idioma || '').toLowerCase();

    if (idiomaNormalizado.startsWith('en')) {
        return 'en';
    }

    return 'pt';
};

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        const idiomaInicial = normalizarIdioma(props.initialPage.props.locale || 'pt');

        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(i18nVue, {
                lang: idiomaInicial,
                resolve: async lang => {
                    const langs = import.meta.glob('../../lang/*.json');
                    return await langs[`../../lang/${lang}.json`]();
                }
            })
            .use(ZiggyVue)
            // 2. Registre eles globalmente aqui!
            .component('PrimaryButton', PrimaryButton)
            .component('SecondaryButton', SecondaryButton)
            .component('DangerButton', DangerButton)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});
