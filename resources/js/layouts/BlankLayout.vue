<script setup lang="ts">
/**
 * Renders a page with no chrome. Used by screens that must not inherit the
 * public MainLayout (the admin login, for example).
 */
</script>

<template>
  <slot />
</template>
