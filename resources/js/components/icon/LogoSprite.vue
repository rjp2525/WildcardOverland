<script setup lang="ts">
import { defineAsyncComponent } from 'vue'

/**
 * The logo's geometry, once per document.
 *
 * The mark is a traced illustration, so its path data is heavy. It appears
 * in the navbar and again in the footer, and inlining it twice put the whole
 * thing in the HTML twice for no benefit. It lives here as a symbol instead,
 * and both places point at it.
 */
const logo = defineAsyncComponent(() => import('../../../svg/logo_full_horizontal.svg'))
</script>

<template>
  <svg class="hidden" aria-hidden="true" focusable="false">
    <symbol id="wildcard-logo" viewBox="0 0 961.729 471.4">
      <component :is="logo" />
    </symbol>
  </svg>
</template>
