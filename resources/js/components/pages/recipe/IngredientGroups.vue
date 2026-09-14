<script setup lang="ts">
import { computed } from 'vue'
import { Check } from 'lucide-vue-next'
import RichText from '@/components/ui/RichText.vue'
import { useChecklist } from '@/composables/useChecklist'

export interface Ingredient {
  label: string
  note: string | null
  /** Sub-bullets: which cut to buy, what to use instead. */
  details: string[]
  optional: boolean
  /** False for things you do not buy: what is left in the cooler, water. */
  shopping: boolean
}

export interface IngredientGroup {
  name: string
  /** Rendered from the stored document by the server. */
  note: string
  items: Ingredient[]
}

const props = withDefaults(
  defineProps<{
    groups: IngredientGroup[]
    /** Ingredients belonging to no part. A short recipe is all of these. */
    loose: Ingredient[]
    /** Allow the parts to run in more than one column when there is room. */
    split?: boolean
    /** Identifies this recipe's saved ticks. The slug. */
    storageKey: string
  }>(),
  { split: false },
)

/*
 * Parts first, then whatever belongs to none. The unnamed one only gets a
 * heading when it is sharing the page with real parts, otherwise a lone
 * "Everything else" above a plain list reads as a mistake.
 */
const blocks = computed(() => {
  const parts = props.groups.map((group) => ({ ...group, headed: true }))

  if (props.loose.length) {
    parts.push({
      name: 'Everything else',
      note: '',
      items: props.loose,
      headed: props.groups.length > 0,
    })
  }

  /*
   * Every line gets an id built from its part and its own words, because the
   * ticks outlive the page now. Position will not do: add one ingredient to
   * the top of a part and every tick below it slides onto the wrong line,
   * and a list that lies about what you already have is worse than one that
   * forgot. Reword an ingredient and it comes back unticked, which is right:
   * it is not quite the same thing any more.
   *
   * The counter only matters where a part genuinely lists the same words
   * twice, which happens with "salt, to taste".
   */
  const seen = new Map<string, number>()

  return parts.map((part) => ({
    ...part,
    items: part.items.map((item) => {
      const base = `${part.name}|${item.label}`
      const nth = seen.get(base) ?? 0

      seen.set(base, nth + 1)

      return { ...item, id: nth === 0 ? base : `${base}#${nth}` }
    }),
  }))
})

/** Ticking off while shopping and cooking, kept between visits. */
const { ticked, toggle, clear } = useChecklist(computed(() => props.storageKey))

const total = computed(() => blocks.value.reduce((n, block) => n + block.items.length, 0))

const tickedCount = computed(
  () => blocks.value.flatMap((block) => block.items).filter((i) => ticked.value.has(i.id)).length,
)
</script>

<template>
  <!--
    A column count would be a guess about how wide this ends up. A column
    width is not: the browser fits as many 17rem columns as the space
    actually allows, so a narrow sidebar stays a single readable list and a
    wide one fills up instead of running on for a page and a half.
  -->
  <div :class="['ingredient-parts space-y-8', split && 'sm:columns-[17rem] sm:gap-x-10 sm:space-y-0']">
    <section v-for="block in blocks" :key="block.name" :class="split && 'mb-8 break-inside-avoid'">
      <h3
        v-if="block.headed"
        class="font-brand text-sm font-extrabold uppercase tracking-widest text-slate-900 dark:text-white"
      >
        {{ block.name }}
      </h3>

      <!--
        Under the heading, not after the list. It explains the part, so it
        has to be read before the part rather than found underneath it.
      -->
      <RichText
        v-if="block.note"
        :html="block.note"
        compact
        class="mt-1.5 text-sm text-slate-600 dark:text-white/65"
      />

      <ul class="mt-3 space-y-2.5">
        <li v-for="ingredient in block.items" :key="ingredient.id">
          <label class="group flex cursor-pointer items-start gap-3 text-slate-700 dark:text-white/80">
            <input
              type="checkbox"
              :checked="ticked.has(ingredient.id)"
              class="peer sr-only"
              @change="toggle(ingredient.id)"
            >
            <!--
              Drawn rather than native: accent-color leaves an unfilled box
              on a dark background, which reads as disabled.
            -->
            <span
              class="ingredient-tick mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-[0.3rem] border-2 border-slate-300 bg-white transition-colors peer-checked:border-brand peer-checked:bg-brand peer-focus-visible:ring-2 peer-focus-visible:ring-brand/50 peer-focus-visible:ring-offset-2 peer-focus-visible:ring-offset-white group-hover:border-brand dark:border-white/25 dark:bg-white/5 dark:peer-focus-visible:ring-offset-dark"
              aria-hidden="true"
            >
              <Check
                class="h-3.5 w-3.5 text-white transition-opacity"
                :class="ticked.has(ingredient.id) ? 'opacity-100' : 'opacity-0'"
              />
            </span>

            <span class="min-w-0" :class="ticked.has(ingredient.id) ? 'line-through opacity-50' : ''">
              {{ ingredient.label }}
              <span v-if="ingredient.note" class="text-slate-500 dark:text-white/60">
                ({{ ingredient.note }})
              </span>
              <span
                v-if="ingredient.optional"
                class="ml-1 rounded bg-slate-100 px-1.5 py-0.5 align-middle text-[0.65rem] font-bold uppercase tracking-wide text-slate-500 dark:bg-white/10 dark:text-white/55"
              >
                optional
              </span>

              <!-- Which cut, what to swap in. Guidance, not more shopping. -->
              <ul
                v-if="ingredient.details.length"
                class="mt-1 list-disc space-y-0.5 pl-5 text-sm text-slate-500 dark:text-white/55"
              >
                <li v-for="(detail, d) in ingredient.details" :key="d">{{ detail }}</li>
              </ul>
            </span>
          </label>
        </li>
      </ul>
    </section>

    <!--
      Ticks are kept between visits now, so there has to be a way out of
      them. Only shown once there is something to undo, and hidden on paper
      where it means nothing.
    -->
    <p v-if="tickedCount" class="print-hide pt-1 text-sm text-slate-500 dark:text-white/55">
      {{ tickedCount }} of {{ total }} ticked off.
      <button
        type="button"
        class="font-medium text-brand underline underline-offset-2 hover:no-underline"
        @click="clear"
      >
        Start over
      </button>
    </p>
  </div>
</template>
