<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { CollapsibleContent, type CollapsibleContentProps } from 'reka-ui'
import { cn } from '@/lib/utils'

const props = defineProps<CollapsibleContentProps & { class?: HTMLAttributes['class'] }>()
</script>

<template>
  <!--
    The height it animates to comes from reka as a CSS variable, because the
    real height is only known once the content is laid out. Hence keyframes
    rather than a transition: there is nothing to transition from.
  -->
  <CollapsibleContent
    v-bind="props"
    :class="cn('collapsible-content', props.class)"
  >
    <slot />
  </CollapsibleContent>
</template>
