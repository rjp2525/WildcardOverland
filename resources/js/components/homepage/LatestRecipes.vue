<script setup lang="ts">
import { RecipeCard } from '@/components/cards'
import type { RecipeCardData } from '@/components/cards/RecipeCard.vue'
import SectionHeading from './SectionHeading.vue'
import { useRoute } from '@/lib/route'

const route = useRoute()

defineProps<{ recipes: RecipeCardData[] }>()
</script>

<template>
  <section v-if="recipes.length" class="border-t border-slate-200 py-16 dark:border-white/10">
    <div class="container">
      <SectionHeading
        title="Camp recipes"
        subtitle="Food worth making a long way from a kitchen."
        :more-href="route('recipes.index')"
        more-label="All recipes"
      />
      <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
        <RecipeCard v-for="recipe in recipes" :key="recipe.slug" :recipe="recipe" />
      </div>
    </div>
  </section>
</template>
