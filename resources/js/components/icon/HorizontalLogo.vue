<template>
  <component :is="icon" v-bind="props" :class="cn('stroke-2', props.class)" />
</template>

<script lang="ts" setup>
import { defineAsyncComponent, computed } from 'vue';
import { cn } from '@/lib/utils';
import type { PropType } from 'vue';

// Define props
const props = defineProps({
  class: {
    type: String as PropType<string>,
    required: false
  }
});

const icon = computed(() => defineAsyncComponent(() => import(`../../../svg/logo_full_horizontal.svg`)));
</script>
