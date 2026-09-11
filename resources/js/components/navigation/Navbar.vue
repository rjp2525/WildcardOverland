<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref } from "vue";
import {
  NavigationMenu,
  NavigationMenuContent,
  NavigationMenuItem,
  NavigationMenuLink,
  NavigationMenuList,
  NavigationMenuTrigger,
} from "@/components/ui/navigation-menu";
import {
  Sheet,
  SheetContent,
  SheetFooter,
  SheetHeader,
  SheetTitle,
  SheetTrigger,
} from "@/components/ui/sheet";
import { Button } from "@/components/ui/button";
import { HorizontalLogo } from "@/components/icon";
import { Menu } from "lucide-vue-next";
import { DarkModeToggle } from "@/components/dark-mode";

const mobileNavigationOpen = ref<boolean>(false);

// Registered on mount rather than during setup: setup also runs on the
// server, where `window` does not exist, and the listener needs removing
// when the component goes away.
const onScroll = () => {
  const navbar = document.getElementById("navbar");
  if (!navbar) {
      return;
  }
  if (
      document.body.scrollTop >= 50 ||
      document.documentElement.scrollTop >= 50
  ) {
      navbar.classList.add("is-sticky");
  } else {
      navbar.classList.remove("is-sticky");
  }
};

onMounted(() => {
  onScroll();
  window.addEventListener("scroll", onScroll, { passive: true });
});

onBeforeUnmount(() => window.removeEventListener("scroll", onScroll));
</script>

<template>
    <nav class="navbar" id="navbar">
    <div class="container flex flex-wrap items-center justify-between">
      <Link :href="route('homepage')" class="text-white/80 hover:text-white py-0.5">
        <HorizontalLogo class="h-14 w-auto" />
      </Link>

      <div class="navigation hidden lg:order-1 lg:flex" id="menu-collapse">
        <ul class="navbar-nav">
          <li class="nav-item" :class="{ 'active': $page.component === 'Homepage' }">
            <Link :href="route('homepage')" class="nav-link">
              Home
            </Link>
          </li>
          <li class="nav-item" :class="{ 'active': $page.component === 'About' }">
            <Link :href="route('about')" class="nav-link">
              About
            </Link>
          </li>
          <li>
            <DarkModeToggle />
          </li>
        </ul>
      </div>
    </div>
  </nav>
</template>
