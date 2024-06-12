import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import path from "path";
import vue from "@vitejs/plugin-vue";
import svgLoader from "vite-svg-loader";

export default defineConfig({
    resolve: {
        alias: {
            "@": path.resolve(__dirname, "./resources/js"),
            "@svg": path.resolve(__dirname, "./resources/svg"),
            "@img": path.resolve(__dirname, "./resources/img"),
        },
    },
    plugins: [
        laravel({
            input: ["resources/scss/app.scss", "resources/js/app.ts"],
            ssr: "resources/js/ssr.ts",
            refresh: true,
        }),
        vue({
            devtools: true,
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
