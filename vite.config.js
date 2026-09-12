import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import path from "path";
import tailwindcss from "@tailwindcss/vite";
import vue from "@vitejs/plugin-vue";
import svgLoader from "vite-svg-loader";

export default defineConfig({
    ssr: {
        // Ships an ESM build via `module` but a UMD file via `main`.
        // Externalised, Node picks `main` and the default import fails during
        // SSR, so let Vite bundle it instead.
        noExternal: ["vue3-marquee"],
    },
    resolve: {
        alias: {
            "@": path.resolve(import.meta.dirname, "./resources/js"),
            "@svg": path.resolve(import.meta.dirname, "./resources/svg"),
            "@img": path.resolve(import.meta.dirname, "./resources/img"),
        },
    },
    plugins: [
        laravel({
            input: ["resources/css/app.css", "resources/js/app.ts"],
            ssr: "resources/js/ssr.ts",
            refresh: true,
        }),
        tailwindcss(),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        svgLoader({
            svgoConfig: {
                plugins: [
                    {
                        name: "preset-default",
                        params: {
                            overrides: {
                                removeViewBox: false,
                            },
                        },
                    },
                ],
            },
        }),
    ],
});
