<script setup lang="ts">
import { computed, ref } from 'vue'
import { cn } from '@/lib/utils'
import { imageVariants, type ImageVariants, type ResponsiveImageData } from '.'

const props = withDefaults(
  defineProps<{
    image: ResponsiveImageData | null | undefined
    /** Overrides the image's own alt - use for decorative or relabelled images. */
    alt?: string
    /**
     * How wide the image actually renders. The variant ships a sensible
     * default; override it where the layout says otherwise, because a wrong
     * `sizes` makes the browser pick the wrong candidate.
     */
    sizes?: string
    /** Above the fold: load it eagerly and tell the browser it matters. */
    priority?: boolean
    fit?: ImageVariants['fit']
    rounded?: ImageVariants['rounded']
    class?: string
  }>(),
  { priority: false },
)

const loaded = ref(false)

const classes = computed(() =>
  cn(imageVariants({ fit: props.fit, rounded: props.rounded }), props.class),
)

/*
 * The average colour stands in until the real bytes paint, so a page loads
 * into roughly its final colours instead of into blank holes. It is dropped
 * on load: a logo's transparency would otherwise stay tinted by it.
 */
const style = computed(() =>
  !loaded.value && props.image?.color ? { backgroundColor: props.image.color } : undefined,
)
</script>

<template>
  <img
    v-if="image"
    :src="image.src"
    :srcset="image.srcset || undefined"
    :sizes="image.srcset ? (sizes ?? image.sizes) : undefined"
    :width="image.width ?? undefined"
    :height="image.height ?? undefined"
    :alt="alt ?? image.alt"
    :loading="priority ? 'eager' : 'lazy'"
    :fetchpriority="priority ? 'high' : undefined"
    decoding="async"
    :class="classes"
    :style="style"
    @load="loaded = true"
  >
</template>
