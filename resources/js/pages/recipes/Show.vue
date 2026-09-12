<script setup lang="ts">
import { ref } from 'vue'
import { Head } from '@inertiajs/vue3'
import { Clock, Flame, Users } from 'lucide-vue-next'
import { RecipeCard } from '@/components/cards'
import type { RecipeCardData } from '@/components/cards/RecipeCard.vue'

interface Ingredient {
  label: string
  note: string | null
}

defineProps<{
  recipe: {
    name: string
    headline: string | null
    summary: string | null
    notes: string | null
    meal_type: string
    difficulty: string | null
    dietary: string[]
    prep_minutes: number | null
    cook_minutes: number | null
    total_minutes: number | null
    servings: number | null
    hero: { url: string; alt: string } | null
    ingredients: Ingredient[]
    steps: string[]
  }
  more: RecipeCardData[]
}>()

/**
 * Ticking off ingredients while cooking. Deliberately not persisted - it is
 * scratch state for one session at the camp table.
 */
const checked = ref<Set<number>>(new Set())

function toggle(index: number) {
  const next = new Set(checked.value)
  next.has(index) ? next.delete(index) : next.add(index)
  checked.value = next
}
</script>

<template>
  <Head :title="recipe.name" />

  <div class="relative w-full bg-dark">
    <div class="relative h-80 w-full overflow-hidden sm:h-[26rem]">
      <img v-if="recipe.hero" :src="recipe.hero.url" :alt="recipe.hero.alt" class="h-full w-full object-cover">
      <div v-else class="h-full w-full bg-page-header bg-cover bg-center" />
      <div class="absolute inset-0 bg-linear-to-t from-black/85 via-black/40 to-black/30" />

      <div class="absolute inset-x-0 bottom-0">
        <div class="container pb-10">
          <p class="mb-2 text-sm font-bold uppercase tracking-widest text-brand">
            {{ recipe.meal_type }}
          </p>
          <h1 class="font-brand text-4xl font-extrabold uppercase text-white drop-shadow sm:text-6xl">
            {{ recipe.name }}
          </h1>
          <p v-if="recipe.headline" class="mt-3 max-w-2xl text-lg text-white/80">
            {{ recipe.headline }}
          </p>
        </div>
      </div>
    </div>
  </div>

  <!-- At a glance -->
  <div class="bg-brand">
    <div class="container flex flex-wrap items-center justify-center gap-x-10 gap-y-3 py-4 text-white">
      <span v-if="recipe.prep_minutes" class="inline-flex items-center gap-2 text-sm font-bold uppercase">
        <Clock class="h-4 w-4" /> {{ recipe.prep_minutes }} min prep
      </span>
      <span v-if="recipe.cook_minutes" class="inline-flex items-center gap-2 text-sm font-bold uppercase">
        <Flame class="h-4 w-4" /> {{ recipe.cook_minutes }} min cook
      </span>
      <span v-if="recipe.servings" class="inline-flex items-center gap-2 text-sm font-bold uppercase">
        <Users class="h-4 w-4" /> serves {{ recipe.servings }}
      </span>
      <span v-if="recipe.difficulty" class="text-sm font-bold uppercase">{{ recipe.difficulty }}</span>
    </div>
  </div>

  <div class="container py-12">
    <p v-if="recipe.summary" class="mb-8 max-w-3xl text-lg text-slate-700 dark:text-white/80">
      {{ recipe.summary }}
    </p>

    <div v-if="recipe.dietary.length" class="mb-10 flex flex-wrap gap-2">
      <span
        v-for="tag in recipe.dietary"
        :key="tag"
        class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-600 dark:bg-white/10 dark:text-white/70"
      >
        {{ tag }}
      </span>
    </div>

    <div class="grid gap-12 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.6fr)]">
      <section v-if="recipe.ingredients.length">
        <h2 class="mb-4 text-2xl font-extrabold uppercase text-brand">Ingredients</h2>
        <ul class="space-y-2">
          <li v-for="(ingredient, i) in recipe.ingredients" :key="i">
            <label class="flex cursor-pointer items-start gap-3 text-slate-700 dark:text-white/80">
              <input
                type="checkbox"
                :checked="checked.has(i)"
                class="mt-1 h-4 w-4 shrink-0 accent-[var(--color-brand)]"
                @change="toggle(i)"
              >
              <span :class="checked.has(i) ? 'line-through opacity-50' : ''">
                {{ ingredient.label }}
                <span v-if="ingredient.note" class="text-slate-500 dark:text-white/60">
                  ({{ ingredient.note }})
                </span>
              </span>
            </label>
          </li>
        </ul>
      </section>

      <section v-if="recipe.steps.length">
        <h2 class="mb-4 text-2xl font-extrabold uppercase text-brand">Method</h2>
        <ol class="space-y-5">
          <li v-for="(step, i) in recipe.steps" :key="i" class="flex gap-4">
            <span
              class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-brand text-sm font-bold text-white"
              aria-hidden="true"
            >
              {{ i + 1 }}
            </span>
            <p class="pt-1 leading-relaxed text-slate-700 dark:text-white/80">{{ step }}</p>
          </li>
        </ol>
      </section>
    </div>

    <section v-if="recipe.notes" class="mt-12 max-w-3xl rounded-lg bg-slate-50 p-6 dark:bg-white/5">
      <h2 class="mb-3 text-xl font-extrabold uppercase text-brand">Notes</h2>
      <div class="recipe-notes text-slate-700 dark:text-white/80" v-html="recipe.notes" />
    </section>
  </div>

  <section v-if="more.length" class="border-t border-slate-200 py-12 dark:border-white/10">
    <div class="container">
      <h2 class="mb-6 text-2xl font-extrabold uppercase text-brand">More recipes</h2>
      <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
        <RecipeCard v-for="other in more" :key="other.slug" :recipe="other" />
      </div>
    </div>
  </section>
</template>

<style>
.recipe-notes :where(p) {
  margin: 0.7rem 0;
  line-height: 1.7;
}
.recipe-notes :where(ul) {
  list-style: disc;
  padding-left: 1.3rem;
  margin: 0.7rem 0;
}
.recipe-notes :where(ol) {
  list-style: decimal;
  padding-left: 1.3rem;
  margin: 0.7rem 0;
}
.recipe-notes :where(a) {
  color: var(--color-brand);
  text-decoration: underline;
}
</style>
