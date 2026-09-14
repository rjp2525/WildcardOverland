<script setup lang="ts">
import { Moon, SunMedium } from 'lucide-vue-next'
import { useTheme } from '@/composables/useTheme'

const { toggle } = useTheme()
</script>

<template>
  <div class="flex">
    <button id="light-dark-mode" type="button" class="nav-link p-2" @click="toggle">
      <span class="sr-only">Light/Dark Mode</span>
      <span class="flex h-6 w-full items-center justify-center">
        <Moon class="block dark:hidden" />
        <SunMedium class="hidden text-orange-400 dark:block" />
      </span>
    </button>
  </div>
</template>
