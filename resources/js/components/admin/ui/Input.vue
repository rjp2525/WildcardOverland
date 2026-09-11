<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { cn } from '@/lib/utils'

defineProps<{ class?: HTMLAttributes['class']; invalid?: boolean }>()
const model = defineModel<string | number | null>()
</script>

<template>
  <input
    v-model="model"
    :aria-invalid="invalid || undefined"
    :class="cn(
      'block w-full rounded-md border bg-white px-3 py-2 text-sm text-zinc-900 shadow-xs transition-colors placeholder:text-zinc-400 focus:outline-hidden focus:ring-2 focus:ring-brand disabled:cursor-not-allowed disabled:opacity-50 dark:bg-zinc-900 dark:text-zinc-100',
      invalid ? 'border-red-500' : 'border-zinc-300 dark:border-zinc-700',
      $props.class,
    )"
  >
</template>
