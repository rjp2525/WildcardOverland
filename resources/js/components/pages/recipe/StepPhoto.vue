<script setup lang="ts">
import { ref } from 'vue'
import {
  DialogClose,
  DialogContent,
  DialogDescription,
  DialogOverlay,
  DialogPortal,
  DialogRoot,
  DialogTitle,
  DialogTrigger,
} from 'reka-ui'
import { Maximize2, X } from 'lucide-vue-next'
import { ResponsiveImage, type ResponsiveImageData } from '@/components/ui/image'

export interface StepImage {
  /** Cropped to the column's shape. */
  crop: ResponsiveImageData | null
  /** The whole frame, for the dialog. */
  full: ResponsiveImageData | null
}

defineProps<{ image: StepImage; label: string }>()

const open = ref(false)
</script>

<template>
  <figure v-if="image.crop" class="step-photo mt-4">
    <DialogRoot v-model:open="open">
      <DialogTrigger
        class="group relative block w-full max-w-md overflow-hidden rounded-lg focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-brand"
        :aria-label="`See the whole photo for ${label}`"
      >
        <!--
          The aspect box is the wrapper, never the image. ResponsiveImage
          fills its parent by design, so an aspect ratio put on the image
          itself loses to the height it inherits, and a portrait phone shot
          then runs the length of the step and out the bottom of it.
        -->
        <div class="aspect-[4/3] w-full bg-slate-200 dark:bg-white/5">
          <ResponsiveImage :image="image.crop" class="h-full w-full" />
        </div>

        <span
          v-if="image.full"
          class="print-hide absolute bottom-2 right-2 flex h-8 w-8 items-center justify-center rounded-md bg-black/55 text-white opacity-0 transition-opacity group-hover:opacity-100 group-focus-visible:opacity-100"
          aria-hidden="true"
        >
          <Maximize2 class="h-4 w-4" />
        </span>
      </DialogTrigger>

      <DialogPortal>
        <DialogOverlay
          class="fixed inset-0 z-50 bg-black/85 data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0"
        />
        <DialogContent
          class="fixed inset-0 z-50 flex items-center justify-center p-4 focus:outline-hidden sm:p-8"
        >
          <DialogTitle class="sr-only">{{ label }}</DialogTitle>
          <DialogDescription class="sr-only">The whole photograph, uncropped.</DialogDescription>

          <ResponsiveImage
            v-if="image.full"
            :image="image.full"
            fit="none"
            class="max-h-[88svh] w-auto max-w-full rounded-lg object-contain"
          />

          <DialogClose
            class="absolute right-4 top-4 flex h-10 w-10 items-center justify-center rounded-full bg-white/10 text-white transition-colors hover:bg-white/20 focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-brand"
            aria-label="Close"
          >
            <X class="h-5 w-5" />
          </DialogClose>
        </DialogContent>
      </DialogPortal>
    </DialogRoot>

    <figcaption
      v-if="image.crop.caption"
      class="mt-1.5 text-sm text-slate-500 dark:text-white/55"
    >
      {{ image.crop.caption }}
    </figcaption>
  </figure>
</template>
