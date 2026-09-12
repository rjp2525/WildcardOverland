<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import { Clock, Users } from 'lucide-vue-next'
import { ResponsiveImage, type ResponsiveImageData } from '@/components/ui/image'
import CookingIcon from '@/components/pages/recipe/CookingIcon.vue'

export interface RecipeCardData {
  name: string
  slug: string
  headline: string | null
  url: string
  meal_type: string
  difficulty: string | null
  total_minutes: number | null
  servings: number | null
  dietary: string[]
  cooked_on: Array<{ value: string; label: string }>
  image: ResponsiveImageData | null
}

defineProps<{ recipe: RecipeCardData }>()
</script>

<template>
  <Link
    :href="recipe.url"
    class="group flex flex-col overflow-hidden rounded-lg bg-white shadow-md transition-shadow hover:shadow-xl dark:bg-dark/60"
  >
    <div class="relative aspect-[2/1] overflow-hidden bg-zinc-200 dark:bg-zinc-800">
      <ResponsiveImage
        v-if="recipe.image"
        :image="recipe.image"
        class="transition-transform duration-500 group-hover:scale-105"
      />
      <div v-else class="flex h-full w-full items-center justify-center bg-brand-radial-gradient">
        <span class="font-brand text-2xl font-extrabold uppercase text-brand/40">Wildcard</span>
      </div>
      <span class="absolute left-3 top-3 rounded-full bg-brand px-3 py-1 text-xs font-bold uppercase tracking-wide text-white">
        {{ recipe.meal_type }}
      </span>
    </div>

    <div class="flex flex-1 flex-col gap-2 p-5">
      <h3 class="text-lg font-extrabold uppercase text-black transition-colors group-hover:text-brand dark:text-white">
        {{ recipe.name }}
      </h3>
      <p v-if="recipe.headline" class="flex-1 text-sm text-slate-600 dark:text-white/70">
        {{ recipe.headline }}
      </p>

      <div class="flex flex-wrap items-center gap-3 pt-1 text-xs font-medium text-slate-500 dark:text-white/60">
        <span v-if="recipe.total_minutes" class="inline-flex items-center gap-1">
          <Clock class="h-3.5 w-3.5" /> {{ recipe.total_minutes }} min
        </span>
        <span v-if="recipe.servings" class="inline-flex items-center gap-1">
          <Users class="h-3.5 w-3.5" /> serves {{ recipe.servings }}
        </span>
        <span v-if="recipe.difficulty">{{ recipe.difficulty }}</span>

        <span v-if="recipe.cooked_on?.length" class="ml-auto inline-flex items-center gap-1.5 text-brand">
          <CookingIcon
            v-for="method in recipe.cooked_on.slice(0, 3)"
            :key="method.value"
            :method="method.value"
            class="h-4 w-4"
          />
          <span class="sr-only">Cooked on {{ recipe.cooked_on.map((m) => m.label).join(', ') }}</span>
        </span>
      </div>

      <div v-if="recipe.dietary.length" class="flex flex-wrap gap-1.5 pt-1">
        <span
          v-for="tag in recipe.dietary"
          :key="tag"
          class="rounded-full bg-slate-100 px-2 py-0.5 text-[0.7rem] font-medium text-slate-600 dark:bg-white/10 dark:text-white/70"
        >
          {{ tag }}
        </span>
      </div>
    </div>
  </Link>
</template>
