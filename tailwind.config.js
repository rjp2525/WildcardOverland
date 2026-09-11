const animate = require("tailwindcss-animate");

/** @type {import('tailwindcss').Config} */
module.exports = {
    darkMode: ["class"],
    content: [
        "./resources/**/*.blade.php",
        "./resources/**/*.ts",
        "./resources/**/*.vue",
    ],
    prefix: "",
    theme: {
        container: {
            center: true,
            padding: {
                DEFAULT: "1rem",
                lg: 0,
                xl: 0,
                "2xl": 0,
            },
            screens: {
                "2xl": "1400px",
            },
        },
        extend: {
            colors: {
                brand: "#e85a2f",
                // prev. "#0f172a",
                dark: "#3b3f42", // according to chatgpt, this is the hex equivalent of my trucks paint code
                black: "#212121",
            },
            backgroundImage: {
                landing: "url('@img/homepage-banner-bg.jpg')",
                "page-header": "url('@img/page-banner-bg.jpg')",
                "brand-radial-gradient":
                    "radial-gradient(at bottom left, #e85a2f 0%, transparent 70%)",
            },
            boxShadow: {
                sm: "0 2px 4px 0 rgb(60 72 88 / 0.15)",
                DEFAULT: "0 0 3px rgb(60 72 88 / 0.15)",
                md: "0 5px 13px rgb(60 72 88 / 0.20)",
                lg: "0 10px 25px -3px rgb(60 72 88 / 0.15)",
                xl: "0 20px 25px -5px rgb(60 72 88 / 0.1), 0 8px 10px -6px rgb(60 72 88 / 0.1)",
                "2xl": "0 25px 50px -12px rgb(60 72 88 / 0.25)",
                inner: "inset 0 2px 4px 0 rgb(60 72 88 / 0.05)",
            },
            fontFamily: {
                brand: ["Geared Slab"],
            },
            keyframes: {
                "accordion-down": {
                    from: { height: 0 },
                    to: { height: "var(--radix-accordion-content-height)" },
                },
                "accordion-up": {
                    from: { height: "var(--radix-accordion-content-height)" },
                    to: { height: 0 },
                },
            },
            animation: {
                "accordion-down": "accordion-down 0.2s ease-out",
                "accordion-up": "accordion-up 0.2s ease-out",
            },
            transitionProperty: {
                "gradient-custom": "background, border-radius, opacity",
            },
        },
    },
    plugins: [
        animate,
        function ({ addUtilities }) {
            addUtilities({
                ".mix-blend-lighten": {
                    "mix-blend-mode": "lighten",
                },
            });
        },
    ],
};
