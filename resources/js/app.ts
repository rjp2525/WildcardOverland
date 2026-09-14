import "./bootstrap";
import "../css/app.css";

import { createApp, h, DefineComponent } from "vue";
import { createInertiaApp, router } from "@inertiajs/vue3";
import { resolvePageComponent } from "laravel-vite-plugin/inertia-helpers";
import { ZiggyVue } from "../../vendor/tightenco/ziggy";
import { MainLayout } from "./layouts";
import { syncHead, type SeoMeta } from "./lib/head";
import { Head, Link } from "@inertiajs/vue3";

const appName = import.meta.env.VITE_APP_NAME || "Wildcard Overland";

createInertiaApp({
    /*
     * The same shape Seo::title() builds on the server, so the title the
     * layout renders and the one Inertia swaps in do not disagree.
     */
    title: (title) => (title.includes(appName) ? title : `${title} | ${appName}`),
    resolve: (name) => {
        const page = resolvePageComponent(
            `./pages/${name}.vue`,
            import.meta.glob<DefineComponent>("./pages/**/*.vue")
        );

        page.then((module) => {
            // `layout: null` is meaningful - admin pages opt out of the
            // public chrome - so only default when nothing was declared.
            if (module.default.layout === undefined) {
                module.default.layout = MainLayout;
            }
        });

        return page;
    },
    setup({ el, App, props, plugin }) {
        /*
         * The layout renders the head tags into the first response, and
         * Inertia swaps pages without touching them, so they have to be
         * brought along by hand on every navigation after that.
         */
        router.on("navigate", (event) => {
            syncHead(event.detail.page.props.seo as SeoMeta | undefined);
        });

        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .component("Head", Head)
            .component("Link", Link)
            .mount(el);
    },
    progress: {
        color: "#e85a2f",
    },
});
