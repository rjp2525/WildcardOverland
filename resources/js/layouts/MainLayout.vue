<script setup lang="ts">
import { Footer, Navbar } from '@/components/navigation';
import { LogoSprite } from '@/components/icon';
</script>

<template>
  <!-- min-h-screen with the footer pushed down, so a short page still has it
       sitting at the bottom of the window rather than halfway up. -->
  <LogoSprite />

  <div class="flex min-h-[100svh] flex-col">
    <Navbar />
    <main class="flex-1">
      <slot />
    </main>
    <Footer />
  </div>
</template>
