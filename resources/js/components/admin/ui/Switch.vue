<script setup lang="ts">
import { SwitchRoot, SwitchThumb } from 'reka-ui'

defineProps<{ label?: string; description?: string }>()
const model = defineModel<boolean>({ default: false })
</script>

<template>
  <div class="flex items-start gap-3">
    <SwitchRoot
      v-model="model"
      class="relative mt-0.5 inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2 data-[state=checked]:bg-brand data-[state=unchecked]:bg-zinc-300 dark:data-[state=unchecked]:bg-zinc-600"
    >
      <SwitchThumb
        class="pointer-events-none block h-5 w-5 rounded-full bg-white shadow-lg ring-0 transition-transform data-[state=checked]:translate-x-5 data-[state=unchecked]:translate-x-0"
      />
    </SwitchRoot>
    <div v-if="label || description" class="text-sm">
      <p v-if="label" class="font-medium text-zinc-700 dark:text-zinc-300">{{ label }}</p>
      <p v-if="description" class="text-zinc-500 dark:text-zinc-400">{{ description }}</p>
    </div>
  </div>
</template>
