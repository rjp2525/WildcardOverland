<script setup lang="ts">
import { computed, ref } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { Star } from 'lucide-vue-next'
import RatingStars from './RatingStars.vue'
import HoneypotField from './HoneypotField.vue'
import { useHoneypot } from '@/composables/useHoneypot'

export interface RatingSummary {
  average: number | null
  count: number
  /** How many gave it each of the five, keyed "1" to "5". */
  stars: Record<string, number>
}

export interface Honeypot {
  stamp: string
  trap: string
  stampField: string
}

const props = defineProps<{
  url: string
  rating: RatingSummary
  /** What this browser gave it last time, if it has been here before. */
  yours: number | null
  honeypot: Honeypot
}>()

/** Whatever a form filler put in the field nobody can see. */
const trap = ref('')

const form = useForm({ stars: props.yours ?? 0 })

/*
 * The two bot checks are added on the way out rather than kept as form
 * fields. Their names come from the server, so they cannot be part of a
 * typed shape, and they are not something the widget has an opinion about.
 */
form.transform((data) => ({
  ...data,
  [props.honeypot.stampField]: props.honeypot.stamp,
  [props.honeypot.trap]: trap.value,
}))

/** Keyed by names only the server knows, so not in the form's own shape. */
const errors = computed(() => form.errors as Record<string, string | undefined>)

const { whenSettled } = useHoneypot()

/** Lights the row up under the cursor without committing to it. */
const hovered = ref(0)

const sending = ref(false)

const saved = ref(false)

const chosen = computed(() => props.yours ?? 0)

const lit = computed(() => hovered.value || form.stars || chosen.value)

const labels = ['', 'Not for me', 'It was all right', 'Good', 'Really good', 'Making it again']

/*
 * How the stars actually fell, five down to one. An average on its own
 * hides the difference between everybody thinking it was fine and half of
 * them loving it while half could not get it to work, which is exactly the
 * thing somebody deciding whether to cook it wants to know.
 *
 * Only worth drawing once there is a spread to see.
 */
const breakdown = computed(() =>
  [5, 4, 3, 2, 1].map((star) => {
    const count = props.rating.stars[String(star)] ?? 0

    return { star, count, share: props.rating.count ? (count / props.rating.count) * 100 : 0 }
  }),
)

function rate(stars: number): void {
  if (sending.value) {
    return
  }

  form.stars = stars
  sending.value = true
  saved.value = false

  /*
   * Shown as sent the moment it is clicked even though it may sit here for
   * a second or two first, otherwise the star seems not to have taken and
   * gets clicked again.
   */
  whenSettled(() =>
    form.post(props.url, {
      preserveScroll: true,
      preserveState: true,
      onSuccess: () => {
        saved.value = true
      },
      onFinish: () => {
        sending.value = false
      },
    }),
  )
}
</script>

<template>
  <div class="flex flex-col gap-6 sm:flex-row sm:flex-wrap sm:items-center sm:justify-between">
    <!-- What everyone else made of it. -->
    <div v-if="rating.count" class="flex items-center gap-4">
      <p class="font-brand text-4xl font-extrabold leading-none text-slate-900 dark:text-white">
        {{ rating.average?.toFixed(1) }}
      </p>
      <div>
        <RatingStars :value="rating.average" />
        <p class="mt-1 text-sm text-slate-500 dark:text-white/55">
          {{ rating.count }} {{ rating.count === 1 ? 'rating' : 'ratings' }}
        </p>
      </div>
    </div>

    <p v-else class="text-sm text-slate-500 dark:text-white/55">
      Nobody has rated this one yet.
    </p>

    <!-- The spread behind the average. -->
    <ul v-if="rating.count > 1" class="w-full max-w-[13rem] space-y-1">
      <li
        v-for="row in breakdown"
        :key="row.star"
        class="flex items-center gap-2 text-xs text-slate-500 dark:text-white/55"
      >
        <span class="w-3 text-right tabular-nums">{{ row.star }}</span>
        <Star class="h-3 w-3 shrink-0 text-amber-400" fill="currentColor" stroke-width="0" />
        <span class="h-1.5 flex-1 overflow-hidden rounded-full bg-slate-200 dark:bg-white/10">
          <span class="block h-full rounded-full bg-amber-400" :style="{ width: `${row.share}%` }" />
        </span>
        <span class="w-4 tabular-nums">{{ row.count }}</span>
      </li>
    </ul>

    <!-- And what you made of it. -->
    <div class="sm:text-right">
      <p class="font-brand text-sm font-extrabold uppercase tracking-widest text-slate-900 dark:text-white">
        {{ chosen ? 'You rated this' : 'Cooked it?' }}
      </p>

      <div
        class="relative mt-2 flex gap-1 sm:justify-end"
        @mouseleave="hovered = 0"
      >
        <HoneypotField v-model="trap" :name="honeypot.trap" />

        <button
          v-for="n in 5"
          :key="n"
          type="button"
          :disabled="sending"
          class="rounded-sm p-0.5 transition-transform hover:scale-110 focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-brand disabled:cursor-wait"
          :aria-label="`${n} out of 5 — ${labels[n]}`"
          :aria-pressed="chosen === n"
          @mouseenter="hovered = n"
          @focus="hovered = n"
          @blur="hovered = 0"
          @click="rate(n)"
        >
          <Star
            class="h-7 w-7 transition-colors"
            :class="n <= lit
              ? 'text-amber-400'
              : 'text-slate-300 dark:text-white/20'"
            :fill="n <= lit ? 'currentColor' : 'none'"
            :stroke-width="n <= lit ? 0 : 1.75"
          />
        </button>
      </div>

      <p
        class="mt-1.5 h-5 text-sm text-slate-500 dark:text-white/55"
        aria-live="polite"
      >
        <template v-if="form.errors.stars || errors[honeypot.stampField]">
          <span class="text-red-600 dark:text-red-400">
            {{ form.errors.stars ?? errors[honeypot.stampField] }}
          </span>
        </template>
        <template v-else-if="saved">Thanks, that is noted.</template>
        <template v-else-if="hovered">{{ labels[hovered] }}</template>
        <template v-else-if="chosen">You gave it {{ chosen }}. Tap another to change it.</template>
      </p>
    </div>
  </div>
</template>
