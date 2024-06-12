<script setup lang="ts">
import { ref } from "vue";
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
import { Menu } from 'lucide-vue-next';

const mobileNavigationOpen = ref<boolean>(false);
</script>

<template>
  <header class="header-nav">
  <div class="container flex justify-between py-4">
    <Link :href="route('homepage')" class="text-white/80 hover:text-white py-0.5">
      <HorizontalLogo class="h-14 w-auto" />
    </Link>

    <!-- Mobile Navigation -->
    <div class="flex items-center lg:hidden">
      <Sheet v-model:open="mobileNavigationOpen">
        <SheetTrigger as-child>
          <Menu
            @click="mobileNavigationOpen = true"
            class="cursor-pointer"
          />
        </SheetTrigger>
        <SheetContent
          side="left"
          class="flex flex-col justify-between bg-black/20 backdrop-blur-md border-brand/30"
        >
          <div>
            <SheetHeader class="mb-4 ml-4">
              <SheetTitle class="flex items-center">
                <Link
                  :href="route('homepage')"
                  class="flex items-center text-white"
                >
                  <HorizontalLogo class="h-16 w-auto" />
              </Link>
              </SheetTitle>
            </SheetHeader>

            <div class="flex flex-col gap-2">
              <!--<Button
                v-for="{ href, label } in routeList"
                :key="label"
                as-child
                variant="ghost"
                class="justify-start text-base"
              >
                <a
                  @click="isOpen = false"
                  :href="href"
                >
                  {{ label }}
                </a>
              </Button>-->
            </div>
          </div>

          <SheetFooter class="grid grid-cols-2 gap-4">
            <Button size="sm" variant="outline">Register</Button>
            <Button size="sm">Login</Button>
          </SheetFooter>
        </SheetContent>
      </Sheet>
    </div>

    <div class="hidden lg:inline-flex items-center">
      <NavigationMenu class="flex items-center">
        <NavigationMenuList>
          <NavigationMenuItem>
            <NavigationMenuLink asChild>
              <a href="#" :class="{ '!text-brand': $page.component === 'Homepage' }">
                Home
              </a>
            </NavigationMenuLink>
          </NavigationMenuItem>

          <NavigationMenuItem>
            <NavigationMenuLink asChild>
              <a href="#">
                About
              </a>
            </NavigationMenuLink>
          </NavigationMenuItem>

          <NavigationMenuItem>
            <NavigationMenuTrigger>
              Recipes
            </NavigationMenuTrigger>
            <NavigationMenuContent>
              <div class="grid w-44 grid-cols-2 gap-5 p-4">
                <ul class="flex flex-col gap-2">
                  <li class="rounded-md p-3 text-sm hover:bg-muted">
                    <p class="mb-1 font-semibold leading-none text-foreground">
                      Breakfast
                    </p>
                  </li>
                  <li class="rounded-md p-3 text-sm hover:bg-muted">
                    <p class="mb-1 font-semibold leading-none text-foreground">
                      Lunch
                    </p>
                  </li>
                  <li class="rounded-md p-3 text-sm hover:bg-muted">
                    <p class="mb-1 font-semibold leading-none text-foreground">
                      Dinner
                    </p>
                  </li>
                  <li class="rounded-md p-3 text-sm hover:bg-muted">
                    <p class="mb-1 font-semibold leading-none text-foreground">
                      Snacks &amp; Desserts
                    </p>
                  </li>
                </ul>
              </div>
            </NavigationMenuContent>
          </NavigationMenuItem>

          <NavigationMenuItem>
            <NavigationMenuLink asChild>
              <a href="#">
                Photos
              </a>
            </NavigationMenuLink>
          </NavigationMenuItem>

          <NavigationMenuItem>
            <NavigationMenuLink asChild>
              <a href="#">
                Trip Recaps
              </a>
            </NavigationMenuLink>
          </NavigationMenuItem>

          <NavigationMenuItem>
            <NavigationMenuLink asChild>
              <a href="#">
                Store
              </a>
            </NavigationMenuLink>
          </NavigationMenuItem>
        </NavigationMenuList>
      </NavigationMenu>

      <Button
        as-child
        size="lg"
        variant="outline"
        aria-label="View on GitHub"
        class="ml-4"
      >
        <a
          aria-label="View on GitHub"
          href="#"
          target="_blank"
        >
          Supporter Login
        </a>
      </Button>
    </div>
  </div>
  </header>
</template>
