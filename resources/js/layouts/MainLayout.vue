<script setup lang="ts">
import { Footer, Navbar } from '@/components/navigation';
</script>

<template>
  <!-- min-h-screen with the footer pushed down, so a short page still has it
       sitting at the bottom of the window rather than halfway up. -->
  <div class="flex min-h-[100svh] flex-col">
    <Navbar />
    <main class="flex-1">
      <slot />
    </main>
    <Footer />
  </div>
</template>
