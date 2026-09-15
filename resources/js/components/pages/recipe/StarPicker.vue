<script setup lang="ts">
import { ref } from 'vue'
import { Star } from 'lucide-vue-next'

/** Zero until they pick one, which is how "required" knows. */
const model = defineModel<number>({ required: true })

defineProps<{ invalid?: boolean }>()

/** Lights the row up under the cursor without committing to it. */
const hovered = ref(0)

const labels = ['', 'Not for me', 'It was all right', 'Good', 'Really good', 'Making it again']
</script>

<template>
  <!--
    Five radios rather than five buttons. It is one answer out of five, which
    is what a radio group is, and it brings the keyboard and the form
    semantics with it instead of needing them rebuilt.
  -->
  <fieldset>
    <legend class="mb-1 block text-sm font-medium text-slate-700 dark:text-white/75">
      How many stars?
    </legend>

    <div class="flex items-center gap-3">
      <div class="flex gap-1" @mouseleave="hovered = 0">
        <label
          v-for="n in 5"
          :key="n"
          class="cursor-pointer rounded-sm p-0.5 transition-transform hover:scale-110"
          @mouseenter="hovered = n"
        >
          <input
            v-model.number="model"
            type="radio"
            name="stars"
            :value="n"
            class="peer sr-only"
            @focus="hovered = n"
            @blur="hovered = 0"
          >
          <Star
            class="h-8 w-8 transition-colors peer-focus-visible:ring-2 peer-focus-visible:ring-brand"
            :class="n <= (hovered || model)
              ? 'text-amber-400'
              : invalid
                ? 'text-red-400 dark:text-red-500/70'
                : 'text-slate-300 dark:text-white/20'"
            :fill="n <= (hovered || model) ? 'currentColor' : 'none'"
            :stroke-width="n <= (hovered || model) ? 0 : 1.75"
          />
          <span class="sr-only">{{ n }} out of 5 &mdash; {{ labels[n] }}</span>
        </label>
      </div>

      <!-- Reserved height, so picking a star does not nudge the form. -->
      <p class="h-5 text-sm text-slate-500 dark:text-white/55" aria-live="polite">
        {{ labels[hovered || model] }}
      </p>
    </div>
  </fieldset>
</template>
