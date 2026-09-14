<script setup lang="ts">
import StepTip, { type Tip } from './StepTip.vue'
import { ResponsiveImage, type ResponsiveImageData } from '@/components/ui/image'

export interface Step {
  title: string | null
  /** Split server side: a real step is several beats, not one block. */
  paragraphs: string[]
  tips: Tip[]
  image: ResponsiveImageData | null
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

        <div class="space-y-2.5" :class="step.title ? '' : 'pt-1'">
          <p
            v-for="(paragraph, p) in step.paragraphs"
            :key="p"
            class="leading-relaxed text-slate-700 dark:text-white/80"
          >
            {{ paragraph }}
          </p>
        </div>

        <div v-if="step.tips.length" class="mt-3 space-y-2">
          <StepTip v-for="(tip, t) in step.tips" :key="t" :tip="tip" />
        </div>

        <ResponsiveImage
          v-if="step.image"
          :image="step.image"
          class="mt-3 aspect-[4/3] max-w-xs rounded-lg sm:max-w-sm"
          sizes="(min-width: 640px) 24rem, 20rem"
        />
      </div>
    </li>
  </ol>
</template>
