<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import { ChevronDown } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { useRoute } from '@/lib/route'

const route = useRoute()

/**
 * Both calls to action are driven by what actually exists. The recipes button
 * used to link nowhere at all, and the trip button pointed at an empty anchor.
 */
defineProps<{
  latestTrip: { name: string; url: string } | null
  hasRecipes: boolean
}>()
</script>

<template>
  <div class="relative h-[100svh] w-full bg-cover bg-landing">
    <div class="relative h-full w-full bg-black/30 backdrop-blur-sm">
      <div class="container flex h-full flex-col items-center justify-center space-y-4">
        <span
          class="text-center text-base font-bold uppercase tracking-widest text-white sm:text-2xl"
        >A Tacoma and no fixed plan</span>
        <h1
          class="text-center font-brand text-6xl font-extrabold uppercase text-brand drop-shadow sm:text-8xl"
        >
          Wildcard Overland
        </h1>

        <div
          class="relative flex w-full flex-col justify-center space-y-4 px-4 md:flex-row md:space-x-4 md:space-y-0 md:px-0"
        >
          <Button v-if="hasRecipes" variant="outline" class="w-full md:w-auto" as-child>
            <Link :href="route('recipes.index')">Browse the camp recipes</Link>
          </Button>

          <Button v-if="latestTrip" class="w-full md:w-fit" as-child>
            <Link :href="latestTrip.url">Read my latest trip</Link>
          </Button>
          <Button v-else class="w-full md:w-fit" as-child>
            <Link :href="route('about')">About the truck and me</Link>
          </Button>
        </div>
      </div>

      <!-- Scroll cue, replacing the empty spacer that used to sit under the hero. -->
      <a
        href="#below-the-fold"
        class="absolute inset-x-0 bottom-8 mx-auto flex w-fit flex-col items-center gap-1 text-white/70 transition-colors hover:text-white"
        aria-label="Scroll to content"
      >
        <span class="text-xs font-bold uppercase tracking-widest">Explore</span>
        <ChevronDown class="h-5 w-5 animate-bounce" />
      </a>
    </div>
  </div>
</template>
