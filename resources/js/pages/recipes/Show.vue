<script setup lang="ts">
import { computed } from 'vue'
import { Head } from '@inertiajs/vue3'
import { Clock, ExternalLink, Flame, Printer, Users, UtensilsCrossed } from 'lucide-vue-next'
import { AnimatedContent } from '@/components/ui/motion'
import ShoppingList from '@/components/pages/recipe/ShoppingList.vue'
import CookingIcon from '@/components/pages/recipe/CookingIcon.vue'
import IngredientGroups, {
  type Ingredient,
  type IngredientGroup,
} from '@/components/pages/recipe/IngredientGroups.vue'
import MethodSteps, { type Step } from '@/components/pages/recipe/MethodSteps.vue'
import RecipeSection, { type Section } from '@/components/pages/recipe/RecipeSection.vue'
import RecipeFeedback, { type Feedback } from '@/components/pages/recipe/RecipeFeedback.vue'
import RatingStars from '@/components/pages/recipe/RatingStars.vue'
import { RecipeCard } from '@/components/cards'
import type { RecipeCardData } from '@/components/cards/RecipeCard.vue'
import { ResponsiveImage, type ResponsiveImageData } from '@/components/ui/image'
import RichText from '@/components/ui/RichText.vue'

interface Source {
  kind: string
  label: string
  url: string | null
  note: string | null
}

const props = defineProps<{
  recipe: {
    slug: string
    name: string
    headline: string | null
    summary: string | null
    notes: string
    meal_type: string
    difficulty: string | null
    dietary: string[]
    cooked_on: Array<{ value: string; label: string }>
    prep_minutes: number | null
    cook_minutes: number | null
    total_minutes: number | null
    servings: number | null
    yield: string | null
    method_title: string
    method_intro: string
    hero: ResponsiveImageData | null
    ingredient_groups: IngredientGroup[]
    ingredients: Ingredient[]
    steps: Step[]
    sections: Section[]
    sources: Source[]
  }
  more: RecipeCardData[]
  feedback: Feedback
}>()

const hasIngredients = computed(
  () => props.recipe.ingredient_groups.length > 0 || props.recipe.ingredients.length > 0,
)

/**
 * How much there is of each half of the page.
 *
 * A recipe with four ingredients and twelve steps wants a different split
 * from one with thirty ingredients and three steps, and a fixed ratio gets
 * one of them wrong. Steps count for more than ingredients because a step
 * is a paragraph and an ingredient is a line.
 */
const ingredientLines = computed(
  () =>
    props.recipe.ingredients.length +
    props.recipe.ingredient_groups.reduce((n, group) => n + group.items.length + 2, 0),
)

const methodWeight = computed(
  () => props.recipe.steps.reduce((n, step) => n + 4 + step.tips.length, 0),
)

/**
 * The column split, chosen from the content rather than fixed.
 *
 * Only three options, because a continuous ratio would mean every recipe
 * sat at a slightly different width and none of them looked deliberate.
 */
const columns = computed(() => {
  const ratio = ingredientLines.value / Math.max(methodWeight.value, 1)

  if (ratio > 0.85) return 'lg:grid-cols-2'
  if (ratio < 0.35) return 'lg:grid-cols-[minmax(0,1fr)_minmax(0,2.2fr)]'

  return 'lg:grid-cols-[minmax(0,1fr)_minmax(0,1.6fr)]'
})

/*
 * A long list of short lines is a tall thin ribbon next to a page of
 * method. Past about twenty it runs in two columns instead, and the parts
 * are kept whole so a heading never ends up alone at the foot of one.
 */
const splitIngredients = computed(() => ingredientLines.value > 20)

/*
 * Prep you have to have read days ago, so it sits above the cooking. A tip
 * about crispy rice is no use after dinner is served, but it would clutter
 * the top, so it goes below.
 */
const before = computed(() => props.recipe.sections.filter((s) => s.placement === 'before_method'))
const after = computed(() => props.recipe.sections.filter((s) => s.placement === 'after_method'))

/**
 * Everything you actually have to buy, from every part of the cook.
 *
 * Flat rather than grouped: you shop by aisle, not by which part of the
 * recipe a thing belongs to.
 */
const shoppingList = computed(() =>
  [...props.recipe.ingredient_groups.flatMap((group) => group.items), ...props.recipe.ingredients]
    .filter((item) => item.shopping)
    .map((item) => item.label),
)

function printPage() {
  window.print()
}

/** How much of it there is, in whichever way the recipe says it. */
const servingLabel = computed(() => {
  if (props.recipe.yield) return props.recipe.yield
  if (props.recipe.servings) return `serves ${props.recipe.servings}`

  return null
})
</script>

<template>
  <Head :title="recipe.name" />

  <div class="recipe-hero relative w-full bg-dark">
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
  <div class="recipe-glance bg-brand">
    <div class="container flex flex-wrap items-center justify-center gap-x-10 gap-y-3 py-4 text-white">
      <span v-if="recipe.prep_minutes" class="inline-flex items-center gap-2 text-sm font-bold uppercase">
        <Clock class="h-4 w-4" /> {{ recipe.prep_minutes }} min prep
      </span>
      <span v-if="recipe.cook_minutes" class="inline-flex items-center gap-2 text-sm font-bold uppercase">
        <Flame class="h-4 w-4" /> {{ recipe.cook_minutes }} min cook
      </span>
      <span v-if="servingLabel" class="inline-flex items-center gap-2 text-sm font-bold uppercase">
        <Users class="h-4 w-4" /> {{ servingLabel }}
      </span>
      <span v-if="recipe.difficulty" class="text-sm font-bold uppercase">{{ recipe.difficulty }}</span>

      <!--
        What people made of it, up here rather than only at the foot of the
        page. The stars take the band's own colour: amber on orange cannot
        be read. Anyone tapping it wants the ratings, so it goes there.
      -->
      <a
        v-if="feedback.rating.count"
        href="#notes"
        class="inline-flex items-center gap-2 rounded-full px-1 text-sm font-bold uppercase transition-opacity hover:opacity-80 focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-white/70"
      >
        <RatingStars :value="feedback.rating.average" size="sm" tone="current" />
        {{ feedback.rating.average?.toFixed(1) }}
        <span class="sr-only">out of 5, from {{ feedback.rating.count }} ratings</span>
        <span aria-hidden="true" class="font-medium normal-case opacity-80">
          ({{ feedback.rating.count }})
        </span>
      </a>

      <button
        type="button"
        class="print-hide inline-flex items-center gap-2 rounded-full border border-white/40 px-3 py-1 text-sm font-bold uppercase transition-colors hover:bg-white/15"
        @click="printPage"
      >
        <Printer class="h-4 w-4" /> Print
      </button>
    </div>
  </div>

  <div class="container py-12">
    <!--
      The lede and the labels share a row on a wide screen, pushed to
      opposite edges. A paragraph has to keep a readable line length, so on
      its own it stopped halfway across and left a ragged gap while
      everything under it ran the full width. Anchoring the labels to the
      right gives the row two edges that line up with the rest of the page,
      and the space between them reads as spacing rather than as a paragraph
      that gave up early.
    -->
    <div
      v-if="recipe.summary || recipe.cooked_on.length || recipe.dietary.length"
      class="recipe-intro mb-12 grid items-start gap-x-12 gap-y-6 lg:grid-cols-[minmax(0,1fr)_auto]"
    >
      <p
        v-if="recipe.summary"
        class="recipe-summary max-w-3xl text-lg leading-relaxed text-slate-700 dark:text-white/80"
      >
        {{ recipe.summary }}
      </p>

      <div class="recipe-intro-meta space-y-5 lg:justify-self-end lg:text-right">
        <!-- What it gets cooked on. The kit is half the recipe out here. -->
        <div v-if="recipe.cooked_on.length" class="recipe-kit">
          <h2 class="mb-3 text-sm font-bold uppercase tracking-widest text-brand">Cooked on</h2>
          <ul class="flex flex-wrap gap-3 lg:justify-end">
            <li
              v-for="method in recipe.cooked_on"
              :key="method.value"
              class="flex items-center gap-2.5 rounded-lg border border-slate-200 px-3.5 py-2.5 text-sm font-medium text-slate-700 dark:border-white/12 dark:text-white/80"
            >
              <CookingIcon :method="method.value" class="h-7 w-7 shrink-0 text-brand" />
              {{ method.label }}
            </li>
          </ul>
        </div>

        <div v-if="recipe.dietary.length" class="flex flex-wrap gap-2 lg:justify-end">
          <span
            v-for="tag in recipe.dietary"
            :key="tag"
            class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-600 dark:bg-white/10 dark:text-white/70"
          >
            {{ tag }}
          </span>
        </div>
      </div>
    </div>

    <!-- Prep at home, kit lists. Read days before the burner is lit. -->
    <div v-if="before.length" class="recipe-sections mb-12 space-y-3">
      <RecipeSection v-for="(section, i) in before" :key="i" :section="section" />
    </div>

    <div :class="['recipe-body grid gap-12', columns]">
      <section v-if="hasIngredients" class="recipe-ingredients">
        <h2 class="mb-5 text-2xl font-extrabold uppercase text-brand">Ingredients</h2>

        <IngredientGroups
          :groups="recipe.ingredient_groups"
          :loose="recipe.ingredients"
          :split="splitIngredients"
          :storage-key="recipe.slug"
        />

        <ShoppingList v-if="shoppingList.length" :name="recipe.name" :items="shoppingList" />
      </section>

      <section v-if="recipe.steps.length" class="recipe-method">
        <h2 class="mb-3 text-2xl font-extrabold uppercase text-brand">{{ recipe.method_title }}</h2>

        <RichText
          :html="recipe.method_intro"
          class="mb-7 max-w-2xl text-slate-600 dark:text-white/70"
        />

        <MethodSteps :steps="recipe.steps" />
      </section>
    </div>

    <!-- Technique, tips, scaling. Everything read after the cook. -->
    <div v-if="after.length" class="recipe-sections mt-12 space-y-3">
      <RecipeSection v-for="(section, i) in after" :key="i" :section="section" />
    </div>

    <section
      v-if="recipe.notes"
      class="recipe-notes-block mt-12 max-w-3xl rounded-lg bg-slate-50 p-6 dark:bg-white/5"
    >
      <h2 class="mb-3 flex items-center gap-2 text-xl font-extrabold uppercase text-brand">
        <UtensilsCrossed class="h-5 w-5" aria-hidden="true" /> Notes
      </h2>
      <RichText :html="recipe.notes" class="text-slate-700 dark:text-white/80" />
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

  <RecipeFeedback
    :feedback="feedback"
    :review-url="route('recipes.review', recipe.slug)"
  />

  <section v-if="more.length" class="print-hide border-t border-slate-200 py-12 dark:border-white/10">
    <div class="container">
      <h2 class="mb-6 text-2xl font-extrabold uppercase text-brand">More recipes</h2>
      <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
        <RecipeCard v-for="other in more" :key="other.slug" :recipe="other" />
      </div>
    </div>
  </section>
</template>

