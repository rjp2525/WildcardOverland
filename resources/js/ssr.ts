import { createSSRApp, h, DefineComponent } from "vue";
import { renderToString } from "@vue/server-renderer";
import { createInertiaApp } from "@inertiajs/vue3";
import createServer from "@inertiajs/vue3/server";
import { resolvePageComponent } from "laravel-vite-plugin/inertia-helpers";
import { ZiggyVue } from "../../vendor/tightenco/ziggy";
import type { Config as ZiggyConfig } from "../../vendor/tightenco/ziggy";
import { MainLayout } from "./layouts";
import { Head, Link } from "@inertiajs/vue3";

const appName = import.meta.env.VITE_APP_NAME || "Laravel";

createServer((page) =>
    createInertiaApp({
        page,
        render: renderToString,
        title: (title) => `${title} - ${appName}`,
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
        setup({ App, props, plugin }) {
            // Inertia types page props as Record<string, unknown>, so the
            // Ziggy config shared from the server needs an explicit type.
            const ziggy = page.props.ziggy as ZiggyConfig & { location: string };

            return createSSRApp({ render: () => h(App, props) })
                .use(plugin)
                .use(ZiggyVue, {
                    ...ziggy,
                    location: new URL(ziggy.location),
                })
                .component("Head", Head)
                .component("Link", Link);
        },
    })
);
