<script setup lang="ts">
defineProps<{
  label?: string
  error?: string
  hint?: string
  for?: string
  required?: boolean
}>()
</script>

<template>
  <div class="space-y-1.5">
    <label
      v-if="label"
      :for="$props.for"
      class="block text-sm font-medium text-zinc-700 dark:text-zinc-300"
    >
      {{ label }}
      <span v-if="required" class="text-red-600" aria-hidden="true">*</span>
    </label>

    <slot />

    <p v-if="error" class="text-sm text-red-600 dark:text-red-400">{{ error }}</p>
    <p v-else-if="hint" class="text-sm text-zinc-500 dark:text-zinc-400">{{ hint }}</p>
  </div>
</template>
