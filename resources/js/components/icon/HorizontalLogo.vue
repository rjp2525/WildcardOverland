<template>
  <component :is="icon" v-bind="props" :class="cn('stroke-2 text-white', props.class)" />
</template>

<script lang="ts" setup>
import { defineAsyncComponent, computed } from 'vue';
import { cn } from '@/lib/utils';
import type { PropType } from 'vue';

/*
 * The mark is a dark plate with the artwork drawn on top of it in
 * currentColor. The plate is a fixed dark, so the artwork has to stay light
 * or the two merge and the logo turns into a solid blob. White by default
 * for that reason, on a light page as much as a dark one; pass a class to
 * override it only if the plate colour changes too.
 */

// Define props
const props = defineProps({
  class: {
    type: String as PropType<string>,
    required: false
  }
});

const icon = computed(() => defineAsyncComponent(() => import(`../../../svg/logo_full_horizontal.svg`)));
</script>
