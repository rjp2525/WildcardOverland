<script setup lang="ts">
defineProps<{ title?: string; description?: string }>()
</script>

<template>
  <section
    class="rounded-lg border border-zinc-200 bg-white shadow-xs dark:border-zinc-800 dark:bg-zinc-900"
  >
    <header
      v-if="title || $slots.actions"
      class="flex items-center justify-between gap-4 border-b border-zinc-200 px-5 py-4 dark:border-zinc-800"
    >
      <div>
        <h2 v-if="title" class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">
          {{ title }}
        </h2>
        <p v-if="description" class="mt-0.5 text-sm text-zinc-500 dark:text-zinc-400">
          {{ description }}
        </p>
      </div>
      <slot name="actions" />
    </header>
    <div class="p-5">
      <slot />
    </div>
  </section>
</template>
