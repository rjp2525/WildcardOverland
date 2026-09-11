<script setup lang="ts">
import CountUp from 'vue-countup-v3';
import { Infinity } from 'lucide-vue-next';
</script>

<template>
  <div class="bg-brand dark:shadow">
    <div class="container py-6">
      <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 text-center text-white/80 divide-y lg:divide-y-0 divide-x divide-white/60">
        <div class="flex flex-col justify-center p-4 space-y-1">
          <span class="font-extrabold text-4xl">
            <count-up :start-val="0" :end-val="23567" :options="{ enableScrollSpy: true, scrollSpyDelay: 100 }" />
          </span>
          <span class="text-sm font-bold">miles off road</span>
        </div>
        <div class="flex flex-col justify-center p-4 space-y-1 !border-t-0">
          <span class="font-extrabold text-4xl">
            <count-up :start-val="0" :end-val="46" :options="{ enableScrollSpy: true, scrollSpyDelay: 200 }" />
          </span>
          <span class="text-sm font-bold">trips</span>
        </div>
        <div class="flex flex-col justify-center p-4 space-y-1 !border-l-0 sm:!border-l sm:!border-t-0">
          <span class="font-extrabold text-4xl">
            <count-up :start-val="0" :end-val="71" :options="{ enableScrollSpy: true, scrollSpyDelay: 300 }" />
          </span>
          <span class="text-sm font-bold">nights camped</span>
        </div>
        <div class="flex flex-col justify-center p-4 space-y-1 sm:!border-l-0 lg:!border-l">
          <span class="font-extrabold text-4xl justify-center flex">
            <Infinity stroke-width="3" class="h-10 w-auto" />
          </span>
          <span class="text-sm font-bold">friends made</span>
        </div>
        <div class="flex flex-col justify-center p-4 space-y-1 !border-l-0 sm:!border-l">
          <span class="font-extrabold text-4xl">
            <count-up :start-val="0" :end-val="3115" :options="{ enableScrollSpy: true, scrollSpyDelay: 400 }" />
          </span>
          <span class="text-sm font-bold">photos</span>
        </div>
        <div class="flex flex-col justify-center p-4 space-y-1">
          <span class="font-extrabold text-4xl">
            <count-up :start-val="0" :end-val="16" :options="{ enableScrollSpy: true, scrollSpyDelay: 500 }" />
          </span>
          <span class="text-sm font-bold">states</span>
        </div>
      </div>
    </div>
  </div>
</template>
