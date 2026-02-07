import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { DefineComponent } from 'vue';
import { createApp, h } from 'vue';
import '../css/app.css';
import { initializeTheme } from './composables/useAppearance';
import { i18nVue } from 'laravel-vue-i18n'
import './echo'

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({

    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) =>
        resolvePageComponent(
            `./pages/${name}.vue`,
            import.meta.glob<DefineComponent>('./pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        // Determine initial locale from server-injected Inertia props (fallback to 'uz')
        const initialLocale = (props as any)?.initialPage?.props?.locale ?? 'uz'
        
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(i18nVue, {
                lang: initialLocale,
                resolve: async (lang: string) => {
                    const langs = import.meta.glob('../../lang/*.json');
                    return await langs[`../../lang/${lang}.json`]();
                },
            })
            .mount(el);
    },
    progress: {
        showSpinner: true,
        color: '#4B5563',
    },
});

// This will set light / dark mode on page load...
initializeTheme();
