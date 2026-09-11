<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { cn } from '@/lib/utils'

defineProps<{
  class?: HTMLAttributes['class']
  invalid?: boolean
  options: Array<{ value: string | number | null; label: string }>
  placeholder?: string
}>()

const model = defineModel<string | number | null>()
</script>

<template>
  <select
    v-model="model"
    :aria-invalid="invalid || undefined"
    :class="cn(
      'block w-full rounded-md border bg-white px-3 py-2 text-sm text-zinc-900 shadow-xs transition-colors focus:outline-hidden focus:ring-2 focus:ring-brand dark:bg-zinc-900 dark:text-zinc-100',
      invalid ? 'border-red-500' : 'border-zinc-300 dark:border-zinc-700',
      $props.class,
    )"
  >
    <option v-if="placeholder" :value="null">{{ placeholder }}</option>
    <option v-for="option in options" :key="String(option.value)" :value="option.value">
      {{ option.label }}
    </option>
  </select>
</template>
