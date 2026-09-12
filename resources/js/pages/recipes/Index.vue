<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import { PageHeader } from '@/components/page-header'
import { RecipeCard } from '@/components/cards'
import type { RecipeCardData } from '@/components/cards/RecipeCard.vue'
import Pagination from '@/components/ui/Pagination.vue'
import { cn } from '@/lib/utils'
import { useRoute } from '@/lib/route'

const route = useRoute()

defineProps<{
  recipes: {
    data: RecipeCardData[]
    links: Array<{ url: string | null; label: string; active: boolean }>
    total: number
  }
  mealTypes: Array<{ value: string; label: string }>
  activeMeal: string | null
}>()
</script>

<template>
  <Head title="Camp Recipes" />

  <PageHeader title="Camp Recipes" subtitle="Food worth making a long way from a kitchen." />

  <div class="container py-12">
    <div class="mb-8 flex flex-wrap justify-center gap-2">
      <Link
        :href="route('recipes.index')"
        :class="
          cn(
            'rounded-full px-4 py-1.5 text-sm font-bold uppercase tracking-wide transition-colors',
            activeMeal === null
              ? 'bg-brand text-white'
              : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-white/10 dark:text-white/70 dark:hover:bg-white/20',
          )
        "
      >
        All
      </Link>
      <Link
        v-for="meal in mealTypes"
        :key="meal.value"
        :href="route('recipes.index', { meal: meal.value })"
        :class="
          cn(
            'rounded-full px-4 py-1.5 text-sm font-bold uppercase tracking-wide transition-colors',
            activeMeal === meal.value
              ? 'bg-brand text-white'
              : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-white/10 dark:text-white/70 dark:hover:bg-white/20',
          )
        "
      >
        {{ meal.label }}
      </Link>
    </div>

    <div v-if="recipes.data.length" class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
      <RecipeCard v-for="recipe in recipes.data" :key="recipe.slug" :recipe="recipe" />
    </div>

    <p v-else class="py-16 text-center text-slate-500 dark:text-white/60">
      Nothing here yet<span v-if="activeMeal"> for this meal</span>. Check back soon.
    </p>

    <Pagination :links="recipes.links" />
  </div>
</template>
