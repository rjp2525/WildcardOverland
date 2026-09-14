<script setup lang="ts">
import StepTip, { type Tip } from './StepTip.vue'
import StepPhoto, { type StepImage } from './StepPhoto.vue'
import RichText from '@/components/ui/RichText.vue'

export interface Step {
  title: string | null
  /** Rendered from the stored document by the server. */
  body: string
  tips: Tip[]
  image: StepImage | null
}

defineProps<{ steps: Step[] }>()
</script>

<template>
  <ol class="space-y-9">
    <li v-for="(step, i) in steps" :key="i" class="method-step flex gap-4">
      <span
        class="step-number flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-brand text-sm font-bold text-white"
        aria-hidden="true"
      >
        {{ i + 1 }}
      </span>

      <div class="min-w-0 flex-1">
        <h3
          v-if="step.title"
          class="mb-2 font-brand text-lg font-extrabold uppercase text-slate-900 dark:text-white"
        >
          {{ step.title }}
        </h3>

        <RichText
          :html="step.body"
          :class="['text-slate-700 dark:text-white/80', step.title ? '' : 'pt-1']"
        />

        <!--
          The photograph shows what this step should look like, so it sits
          with the instructions. The tips are asides and come after it.
        -->
        <StepPhoto
          v-if="step.image"
          :image="step.image"
          :label="step.title ?? `Step ${i + 1}`"
        />

        <div v-if="step.tips.length" class="mt-3 flex flex-wrap gap-2 print:block print:space-y-2">
          <StepTip v-for="(tip, t) in step.tips" :key="t" :tip="tip" />
        </div>
      </div>
    </li>
  </ol>
</template>
