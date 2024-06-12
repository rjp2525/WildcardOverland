import { cva } from "class-variance-authority";

export { default as NavigationMenu } from "./NavigationMenu.vue";
export { default as NavigationMenuList } from "./NavigationMenuList.vue";
export { default as NavigationMenuItem } from "./NavigationMenuItem.vue";
export { default as NavigationMenuTrigger } from "./NavigationMenuTrigger.vue";
export { default as NavigationMenuContent } from "./NavigationMenuContent.vue";
export { default as NavigationMenuLink } from "./NavigationMenuLink.vue";

export const navigationMenuTriggerStyle = cva(
    "group inline-flex w-max items-center justify-end bg-transparent p-4 text-lg font-bold transition-colors text-white/80 hover:text-brand focus:text-brand focus:outline-none disabled:pointer-events-none disabled:opacity-50 data-[active]:text-brand data-[state=open]:text-brand"
);
