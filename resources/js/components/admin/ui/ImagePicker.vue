<script setup lang="ts">
import { computed } from 'vue'
import { ImageOff, X } from 'lucide-vue-next'
import Select from './Select.vue'
import ImageDropzone from './ImageDropzone.vue'
import type { ImageOption } from '@/composables/useImageLibrary'

const props = withDefaults(
  defineProps<{
    options: ImageOption[]
    placeholder?: string
    invalid?: boolean
    id?: string
    /** What a dropped file gets typed as. Logos keep their transparency. */
    imageType?: string
    /** Hide the dropzone and leave only the list. */
    pickOnly?: boolean
  }>(),
  { placeholder: 'No image', imageType: 'photo', pickOnly: false },
)

const emit = defineEmits<{ uploaded: [option: ImageOption] }>()

const model = defineModel<number | null>()

const chosen = computed(() => props.options.find((option) => option.value === model.value))

function onUploaded(option: ImageOption) {
  emit('uploaded', option)
  // Whatever you just dropped is what you meant to use.
  model.value = option.value
}
</script>

<template>
  <div class="flex items-start gap-3">
    <div
      class="flex h-20 w-20 shrink-0 items-center justify-center overflow-hidden rounded-md border border-zinc-200 bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-900"
    >
      <img
        v-if="chosen?.thumb"
        :src="chosen.thumb"
        :alt="chosen.label"
        class="h-full w-full object-contain"
        loading="lazy"
        decoding="async"
      >
      <ImageOff v-else class="h-5 w-5 text-zinc-300 dark:text-zinc-700" />
    </div>

    <div class="min-w-0 flex-1 space-y-2">
      <div class="flex items-center gap-2">
        <Select
          :id="id"
          v-model="model"
          :options="options"
          :placeholder="placeholder"
          :invalid="invalid"
        />
        <button
          v-if="model"
          type="button"
          class="shrink-0 rounded-md p-2 text-zinc-400 transition-colors hover:bg-zinc-100 hover:text-zinc-700 dark:hover:bg-zinc-800 dark:hover:text-zinc-200"
          :aria-label="`Clear ${chosen?.label ?? 'image'}`"
          @click="model = null"
        >
          <X class="h-4 w-4" />
        </button>
      </div>

      <ImageDropzone
        v-if="!pickOnly"
        compact
        :image-type="imageType"
        @uploaded="onUploaded"
      />
    </div>
  </div>
</template>
