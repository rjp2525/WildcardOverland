<script setup lang="ts">
import { CheckboxIndicator, CheckboxRoot } from 'reka-ui'
import { Check } from 'lucide-vue-next'

defineProps<{ options: Array<{ value: string; label: string }> }>()

const selected = defineModel<string[]>({ default: () => [] })

function toggle(value: string, checked: boolean) {
  const next = new Set(selected.value)
  checked ? next.add(value) : next.delete(value)
  // Preserve the option order rather than click order.
  selected.value = [...next]
}
</script>

<template>
  <div class="flex flex-wrap gap-x-5 gap-y-2.5">
    <label
      v-for="option in options"
      :key="option.value"
      class="flex cursor-pointer items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300"
    >
      <CheckboxRoot
        :model-value="selected.includes(option.value)"
        class="flex h-4 w-4 shrink-0 items-center justify-center rounded-sm border border-zinc-300 bg-white transition-colors focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-brand data-[state=checked]:border-brand data-[state=checked]:bg-brand dark:border-zinc-600 dark:bg-zinc-900"
        @update:model-value="(checked) => toggle(option.value, checked === true)"
      >
        <CheckboxIndicator>
          <Check class="h-3 w-3 text-white" />
        </CheckboxIndicator>
      </CheckboxRoot>
      {{ option.label }}
    </label>
  </div>
</template>
