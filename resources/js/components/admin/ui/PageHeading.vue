<script setup lang="ts">
defineProps<{ title: string; description?: string }>()
</script>

<template>
  <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
    <div class="flex min-w-0 items-center gap-3">
      <slot name="before" />
      <div class="min-w-0">
        <h1 class="truncate text-xl font-semibold text-zinc-900 dark:text-zinc-100">{{ title }}</h1>
        <p v-if="description" class="mt-0.5 text-sm text-zinc-500 dark:text-zinc-400">
          {{ description }}
        </p>
      </div>
    </div>
    <div v-if="$slots.actions" class="flex shrink-0 items-center gap-2">
      <slot name="actions" />
    </div>
  </div>
</template>
