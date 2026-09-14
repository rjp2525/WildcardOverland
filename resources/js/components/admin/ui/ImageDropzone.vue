<script setup lang="ts">
import { computed, ref } from 'vue'
import { ImagePlus, Loader2 } from 'lucide-vue-next'
import { cn } from '@/lib/utils'
import { useImageUpload, type ImageOption } from '@/composables/useImageLibrary'

const props = withDefaults(
  defineProps<{
    /** What the uploaded images get typed as. */
    imageType?: string
    /** Accept a whole folder of them at once. */
    multiple?: boolean
    /** Shorter, borderless version for sitting next to a preview tile. */
    compact?: boolean
    label?: string
    class?: string
  }>(),
  { imageType: 'photo', multiple: false, compact: false },
)

const emit = defineEmits<{
  /** One per file, in the order they finish. */
  uploaded: [option: ImageOption]
  /** Everything in this drop, once the last one lands. */
  done: [options: ImageOption[]]
}>()

const { upload, uploading, progress, error } = useImageUpload()

const input = ref<HTMLInputElement | null>(null)
const dragging = ref(false)
const queued = ref(0)
const finished = ref(0)

const text = computed(() => {
  if (uploading.value && queued.value > 1) return `Uploading ${finished.value + 1} of ${queued.value}…`
  if (uploading.value) return `Uploading… ${progress.value}%`

  return props.label ?? (props.multiple ? 'Drop images here, or' : 'Drop an image here, or')
})

async function take(files: FileList | null | undefined) {
  const chosen = Array.from(files ?? []).filter((file) => file.type.startsWith('image/'))

  if (!chosen.length) return

  queued.value = chosen.length
  finished.value = 0

  const landed: ImageOption[] = []

  // One at a time. A dozen parallel uploads of camera-sized files will
  // simply time each other out.
  for (const file of chosen) {
    const option = await upload(file, props.imageType)

    if (option) {
      landed.push(option)
      emit('uploaded', option)
    }

    finished.value += 1
  }

  queued.value = 0
  if (input.value) input.value.value = ''
  if (landed.length) emit('done', landed)
}

function onDrop(event: DragEvent) {
  dragging.value = false
  take(event.dataTransfer?.files)
}
</script>

<template>
  <div>
    <div
      :class="cn(
        'flex items-center justify-center gap-2 rounded-lg border-2 border-dashed text-center transition-colors',
        compact ? 'px-3 py-3' : 'flex-col px-4 py-8',
        dragging ? 'border-brand bg-brand/5' : 'border-zinc-300 dark:border-zinc-700',
        uploading && 'opacity-70',
        props.class,
      )"
      @dragover.prevent="dragging = true"
      @dragleave.prevent="dragging = false"
      @drop.prevent="onDrop"
    >
      <component
        :is="uploading ? Loader2 : ImagePlus"
        :class="cn('shrink-0 text-zinc-400', compact ? 'h-4 w-4' : 'h-6 w-6', uploading && 'animate-spin')"
      />

      <p :class="cn('text-zinc-600 dark:text-zinc-400', compact ? 'text-xs' : 'text-sm')">
        {{ text }}
        <button
          v-if="!uploading"
          type="button"
          class="font-medium text-brand hover:underline"
          @click="input?.click()"
        >
          browse
        </button>
      </p>

      <input
        ref="input"
        type="file"
        accept="image/*"
        class="hidden"
        :multiple="multiple"
        @change="take(($event.target as HTMLInputElement).files)"
      >
    </div>

    <p v-if="error" class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ error }}</p>
  </div>
</template>
