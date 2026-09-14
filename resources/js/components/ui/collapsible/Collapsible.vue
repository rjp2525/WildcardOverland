<script setup lang="ts">
import { CollapsibleRoot, type CollapsibleRootEmits, type CollapsibleRootProps, useForwardPropsEmits } from 'reka-ui'

const props = withDefaults(defineProps<CollapsibleRootProps>(), {
  /*
   * Closed content stays in the DOM rather than being torn down. Two
   * reasons: the browser can reveal it for find-in-page, and print can
   * override the hidden attribute to put every section on the paper.
   */
  unmountOnHide: false,
})

const emits = defineEmits<CollapsibleRootEmits>()

const forwarded = useForwardPropsEmits(props, emits)
</script>

<template>
  <CollapsibleRoot v-bind="forwarded">
    <slot />
  </CollapsibleRoot>
</template>
