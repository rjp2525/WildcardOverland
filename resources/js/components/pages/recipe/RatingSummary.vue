<script setup lang="ts">
import { computed } from 'vue'
import { Star } from 'lucide-vue-next'
import RatingStars from './RatingStars.vue'

export interface RatingSummaryData {
  average: number | null
  count: number
  /** How many gave it each of the five, keyed "1" to "5". */
  stars: Record<string, number>
}

const props = defineProps<{ rating: RatingSummaryData }>()

/*
 * How the stars actually fell, five down to one. An average on its own
 * hides the difference between everybody thinking it was fine and half of
 * them loving it while half could not get it to work, which is exactly the
 * thing somebody deciding whether to cook it wants to know.
 */
const breakdown = computed(() =>
  [5, 4, 3, 2, 1].map((star) => {
    const count = props.rating.stars[String(star)] ?? 0

    return { star, count, share: props.rating.count ? (count / props.rating.count) * 100 : 0 }
  }),
)
</script>

<template>
  <div v-if="rating.count" class="flex flex-wrap items-center gap-x-10 gap-y-5">
    <div class="flex items-center gap-4">
      <p class="font-brand text-4xl font-extrabold leading-none text-slate-900 dark:text-white">
        {{ rating.average?.toFixed(1) }}
      </p>
      <div>
        <RatingStars :value="rating.average" />
        <p class="mt-1 text-sm text-slate-500 dark:text-white/55">
          {{ rating.count }} {{ rating.count === 1 ? 'review' : 'reviews' }}
        </p>
      </div>
    </div>

    <!-- The spread behind the average, once there is a spread to see. -->
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
  </div>

  <p v-else class="text-sm text-slate-500 dark:text-white/55">
    Nobody has reviewed this one yet.
  </p>
</template>
