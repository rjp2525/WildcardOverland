import { type VariantProps, cva } from "class-variance-authority";

export { default as Button } from "./Button.vue";

export const buttonVariants = cva(
    "button relative overflow-hidden inline-flex items-center justify-center whitespace-nowrap rounded-sm font-medium disabled:pointer-events-none disabled:opacity-50 transition-all duration-500 ease-in-out text-base uppercase font-bold outline-0 outline-hidden",
    {
        variants: {
            variant: {
                default: "bg-brand text-white hover:bg-brand/90",
                destructive:
                    "bg-red-500 text-slate-50 hover:bg-red-500/90 dark:bg-red-900 dark:text-slate-50 dark:hover:bg-red-900/90",
                outline:
                    "border-2 border-brand/80 bg-transparent text-white hover:bg-brand",
                secondary:
                    "bg-slate-100 text-slate-900 hover:bg-slate-100/80 dark:bg-slate-800 dark:text-slate-50 dark:hover:bg-slate-800/80",
                ghost: "hover:bg-slate-100 hover:text-slate-900 dark:hover:bg-slate-800 dark:hover:text-slate-50",
                link: "text-slate-900 underline-offset-4 hover:underline dark:text-slate-50",
            },
            size: {
                default: "py-3.5 px-10",
                xs: "h-7 rounded-sm px-2",
                sm: "h-9 rounded-md px-3",
                lg: "h-11 rounded-md px-8",
                icon: "h-10 w-10",
            },
        },
        defaultVariants: {
            variant: "default",
            size: "default",
        },
    }
);

export type ButtonVariants = VariantProps<typeof buttonVariants>;
