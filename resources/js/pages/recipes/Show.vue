<script setup lang="ts">
import { computed, ref } from 'vue'
import { Head } from '@inertiajs/vue3'
import { Check, Clock, ExternalLink, Flame, Users } from 'lucide-vue-next'
import { AnimatedContent } from '@/components/ui/motion'
import ShoppingList from '@/components/pages/recipe/ShoppingList.vue'
import { RecipeCard } from '@/components/cards'
import type { RecipeCardData } from '@/components/cards/RecipeCard.vue'
import { ResponsiveImage, type ResponsiveImageData } from '@/components/ui/image'

interface Ingredient {
  label: string
  note: string | null
}

interface Step {
  body: string
  note: string | null
  image: ResponsiveImageData | null
}

interface Source {
  kind: string
  label: string
  url: string | null
  note: string | null
}

const props = defineProps<{
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
    hero: ResponsiveImageData | null
    ingredients: Ingredient[]
    steps: Step[]
    sources: Source[]
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

const shoppingList = computed(() =>
  props.recipe.ingredients.map((ingredient) =>
    ingredient.note ? `${ingredient.label} (${ingredient.note})` : ingredient.label,
  ),
)
</script>

<template>
  <Head :title="recipe.name" />

  <div class="relative w-full bg-dark">
    <div class="relative h-80 w-full overflow-hidden sm:h-[26rem]">
      <ResponsiveImage v-if="recipe.hero" :image="recipe.hero" priority />
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
            <label class="group flex cursor-pointer items-start gap-3 text-slate-700 dark:text-white/80">
              <input
                type="checkbox"
                :checked="checked.has(i)"
                class="peer sr-only"
                @change="toggle(i)"
              >
              <!--
                Drawn rather than native: accent-color leaves an unfilled box
                on a dark background, which reads as disabled.
              -->
              <span
                class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-[0.3rem] border-2 border-slate-300 bg-white transition-colors peer-checked:border-brand peer-checked:bg-brand peer-focus-visible:ring-2 peer-focus-visible:ring-brand/50 peer-focus-visible:ring-offset-2 peer-focus-visible:ring-offset-white group-hover:border-brand dark:border-white/25 dark:bg-white/5 dark:peer-focus-visible:ring-offset-dark"
                aria-hidden="true"
              >
                <Check
                  class="h-3.5 w-3.5 text-white transition-opacity"
                  :class="checked.has(i) ? 'opacity-100' : 'opacity-0'"
                />
              </span>
              <span :class="checked.has(i) ? 'line-through opacity-50' : ''">
                {{ ingredient.label }}
                <span v-if="ingredient.note" class="text-slate-500 dark:text-white/60">
                  ({{ ingredient.note }})
                </span>
              </span>
            </label>
          </li>
        </ul>

        <ShoppingList :name="`${recipe.name} shopping list`" :items="shoppingList" />
      </section>

      <section v-if="recipe.steps.length">
        <h2 class="mb-4 text-2xl font-extrabold uppercase text-brand">Method</h2>
        <ol class="space-y-8">
            <li v-for="(step, i) in recipe.steps" :key="i" class="flex gap-4">
              <span
                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-brand text-sm font-bold text-white"
                aria-hidden="true"
              >
                {{ i + 1 }}
              </span>
              <div class="min-w-0 flex-1 pt-1">
                <p class="leading-relaxed text-slate-700 dark:text-white/80">{{ step.body }}</p>

                <p
                  v-if="step.note"
                  class="mt-2 border-l-2 border-brand/50 pl-3 text-sm italic text-slate-500 dark:text-white/60"
                >
                  {{ step.note }}
                </p>

                <ResponsiveImage
                  v-if="step.image"
                  :image="step.image"
                  class="mt-3 aspect-[4/3] max-w-xs rounded-lg sm:max-w-sm"
                  sizes="(min-width: 640px) 24rem, 20rem"
                />
              </div>
            </li>
        </ol>
      </section>
    </div>

    <section v-if="recipe.notes" class="mt-12 max-w-3xl rounded-lg bg-slate-50 p-6 dark:bg-white/5">
      <h2 class="mb-3 text-xl font-extrabold uppercase text-brand">Notes</h2>
      <div class="recipe-notes text-slate-700 dark:text-white/80" v-html="recipe.notes" />
    </section>

    <!-- Credit where it is due. Almost nothing here started with me. -->
    <AnimatedContent v-if="recipe.sources.length" class="mt-12 max-w-3xl">
      <h2 class="mb-4 text-xl font-extrabold uppercase text-brand">Where this came from</h2>
      <ul class="space-y-3">
        <li
          v-for="(source, i) in recipe.sources"
          :key="i"
          class="flex flex-wrap items-baseline gap-x-2 text-slate-700 dark:text-white/80"
        >
          <span class="text-xs font-bold uppercase tracking-widest text-slate-400 dark:text-white/40">
            {{ source.kind }}
          </span>
          <a
            v-if="source.url"
            :href="source.url"
            target="_blank"
            rel="noopener noreferrer"
            class="inline-flex items-center gap-1 font-medium text-brand hover:underline"
          >
            {{ source.label }} <ExternalLink class="h-3 w-3" />
          </a>
          <span v-else class="font-medium">{{ source.label }}</span>
          <span v-if="source.note" class="text-sm text-slate-500 dark:text-white/55">
            {{ source.note }}
          </span>
        </li>
      </ul>
    </AnimatedContent>
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
