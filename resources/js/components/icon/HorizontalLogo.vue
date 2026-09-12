<script setup lang="ts">
import type { PropType } from 'vue'
import { cn } from '@/lib/utils'

/*
 * Points at the symbol LogoSprite puts in the document, so the path data is
 * downloaded and parsed once however many times the mark appears.
 *
 * The mark is a dark plate with the artwork drawn on top of it in
 * currentColor. The plate is a fixed dark, so the artwork has to stay light
 * or the two merge and the logo turns into a solid blob. White by default
 * for that reason, on a light page as much as a dark one; pass a class to
 * override it only if the plate colour changes too.
 */
const props = defineProps({
  class: {
    type: String as PropType<string>,
    required: false,
  },
})
</script>

<template>
  <svg
    viewBox="0 0 961.729 471.4"
    :class="cn('text-white', props.class)"
    role="img"
    aria-label="Wildcard Overland"
  >
    <use href="#wildcard-logo" />
  </svg>
</template>
