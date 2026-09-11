<script setup lang="ts">
import { Button } from '@/components/ui/button'
</script>

<template>
  <div class="h-screen w-screen relative bg-cover bg-landing">
    <div class="backdrop-blur-sm relative h-full w-full bg-black/30">
      <div class="container flex flex-col justify-center items-center h-full space-y-4">
        <span class="text-white text-2xl font-bold uppercase tracking-widest">Welcome to</span>
        <h1 class="text-8xl uppercase font-brand font-extrabold text-brand text-center drop-shadow">
          Wildcard Overland
        </h1>
        <div class="flex flex-col md:flex-row justify-center md:justify-even space-y-4 md:space-y-0 md:space-x-4 relative w-full px-4 md:px-0">
          <Button variant="outline" class="w-full md:w-auto">
            Browse The Camp Recipes
          </Button>
          <Button class="w-full md:w-fit" as-child>
            <Link href="#test">
              Read about My Latest Trip
            </Link>
          </Button>
        </div>
      </div>
    </div>
  </div>
  <div id="test">
    <div class="py-80"></div>
  </div>
</template>
