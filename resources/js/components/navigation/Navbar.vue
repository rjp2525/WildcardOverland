<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref } from "vue";
import { usePage } from "@inertiajs/vue3";
import {
  Sheet,
  SheetContent,
  SheetHeader,
  SheetTitle,
  SheetTrigger,
} from "@/components/ui/sheet";
import { Button } from "@/components/ui/button";
import { HorizontalLogo } from "@/components/icon";
import { Menu } from "lucide-vue-next";
import { DarkModeToggle } from "@/components/dark-mode";
import { useRoute } from "@/lib/route";

const route = useRoute();
const page = usePage();

const mobileNavigationOpen = ref<boolean>(false);

/**
 * One list drives both the desktop bar and the mobile sheet, so they cannot
 * drift apart. `match` decides the active state: page components are named
 * "Homepage", "About", "trips/Index", "recipes/Show" and so on.
 */
const links = [
  { label: "Home", routeName: "homepage", match: (c: string) => c === "Homepage" },
  { label: "Trips", routeName: "trips.index", match: (c: string) => c.startsWith("trips/") },
  { label: "Recipes", routeName: "recipes.index", match: (c: string) => c.startsWith("recipes/") },
  { label: "The Rig", routeName: "rig", match: (c: string) => c === "Rig" },
  { label: "About", routeName: "about", match: (c: string) => c === "About" },
];

function isActive(link: (typeof links)[number]): boolean {
  return link.match(page.component);
}

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
          <li
            v-for="link in links"
            :key="link.routeName"
            class="nav-item"
            :class="{ active: isActive(link) }"
          >
            <Link :href="route(link.routeName)" class="nav-link">
              {{ link.label }}
            </Link>
          </li>
          <li>
            <DarkModeToggle />
          </li>
        </ul>
      </div>

      <!-- Mobile -->
      <div class="flex items-center gap-1 lg:hidden">
        <DarkModeToggle />
        <Sheet v-model:open="mobileNavigationOpen">
          <SheetTrigger as-child>
            <Button variant="ghost" size="icon" aria-label="Open menu" class="text-white">
              <Menu class="h-6 w-6" />
            </Button>
          </SheetTrigger>
          <SheetContent side="right" class="w-72">
            <SheetHeader>
              <SheetTitle class="font-brand text-2xl font-extrabold uppercase text-brand">
                Wildcard
              </SheetTitle>
            </SheetHeader>
            <nav class="mt-8 flex flex-col gap-1">
              <Link
                v-for="link in links"
                :key="link.routeName"
                :href="route(link.routeName)"
                class="rounded-md px-3 py-2.5 text-lg font-bold uppercase transition-colors"
                :class="
                  isActive(link)
                    ? 'bg-brand/10 text-brand'
                    : 'text-slate-700 hover:bg-slate-100 dark:text-white/80 dark:hover:bg-white/10'
                "
                @click="mobileNavigationOpen = false"
              >
                {{ link.label }}
              </Link>
            </nav>
          </SheetContent>
        </Sheet>
      </div>
    </div>
  </nav>
</template>
