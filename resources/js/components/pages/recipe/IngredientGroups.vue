<script setup lang="ts">
import { computed, ref } from 'vue'
import { Check } from 'lucide-vue-next'

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
  note: string | null
  items: Ingredient[]
}

const props = defineProps<{
  groups: IngredientGroup[]
  /** Ingredients belonging to no part. A short recipe is all of these. */
  loose: Ingredient[]
}>()

/*
 * Parts first, then whatever belongs to none. The unnamed one only gets a
 * heading when it is sharing the page with real parts, otherwise a lone
 * "Everything else" above a plain list reads as a mistake.
 */
const blocks = computed(() => {
  const out = props.groups.map((group) => ({ ...group, headed: true }))

  if (props.loose.length) {
    out.push({
      name: 'Everything else',
      note: null,
      items: props.loose,
      headed: props.groups.length > 0,
    })
  }

  return out
})

/*
 * Ticking off while cooking. Deliberately not persisted: it is scratch state
 * for one session at the camp table. Keyed by part and position so adding a
 * part cannot shuffle what is already ticked.
 */
const checked = ref<Set<string>>(new Set())

function toggle(key: string) {
  const next = new Set(checked.value)
  next.has(key) ? next.delete(key) : next.add(key)
  checked.value = next
}
</script>

<template>
  <div class="space-y-8">
    <section v-for="(block, b) in blocks" :key="b">
      <h3
        v-if="block.headed"
        class="mb-3 font-brand text-sm font-extrabold uppercase tracking-widest text-slate-900 dark:text-white"
      >
        {{ block.name }}
      </h3>

      <ul class="space-y-2.5">
        <li v-for="(ingredient, i) in block.items" :key="i">
          <label class="group flex cursor-pointer items-start gap-3 text-slate-700 dark:text-white/80">
            <input
              type="checkbox"
              :checked="checked.has(`${b}:${i}`)"
              class="peer sr-only"
              @change="toggle(`${b}:${i}`)"
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
                :class="checked.has(`${b}:${i}`) ? 'opacity-100' : 'opacity-0'"
              />
            </span>

            <span class="min-w-0" :class="checked.has(`${b}:${i}`) ? 'line-through opacity-50' : ''">
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

      <p
        v-if="block.note"
        class="mt-3 border-l-2 border-brand/40 pl-3 text-sm leading-relaxed text-slate-600 dark:text-white/65"
      >
        {{ block.note }}
      </p>
    </section>
  </div>
</template>
