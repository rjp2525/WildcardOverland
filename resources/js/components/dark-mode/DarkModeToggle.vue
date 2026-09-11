<script setup lang="ts">
import { ref } from 'vue';
import { SunMedium, Moon } from 'lucide-vue-next';

const darkMode = ref(false);
if (localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
  document.documentElement.classList.add('dark')
  localStorage.setItem("theme", 'dark');
} else {
  document.documentElement.classList.remove('dark')
  localStorage.setItem("theme", null);
}

const toggleDarkMode = () => {
  if (document.documentElement.classList.contains('dark')) {
    document.documentElement.classList.remove("dark");
    localStorage.setItem("theme", null);
  } else {
    document.documentElement.classList.add("dark");
    localStorage.setItem("theme", 'dark');
  }
}
</script>

<template>
  <div class="flex">
      <button id="light-dark-mode" @click="toggleDarkMode" type="button" class="nav-link p-2">
          <span class="sr-only">Light/Dark Mode</span>
          <span class="flex items-center justify-center h-6 w-full">
            <Moon class="block dark:hidden" />
            <SunMedium class="hidden dark:block text-orange-400" />
          </span>
      </button>
  </div>
</template>
