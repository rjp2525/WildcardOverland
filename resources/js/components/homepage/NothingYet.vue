<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import { Compass } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { useRoute } from '@/lib/route'

const route = useRoute()
</script>

<template>
  <section class="py-20">
    <div class="container">
      <div
        class="mx-auto flex max-w-xl flex-col items-center gap-4 rounded-2xl border border-dashed border-slate-300 px-6 py-12 text-center dark:border-white/15"
      >
        <Compass class="h-8 w-8 text-brand" aria-hidden="true" />

        <h2 class="font-brand text-2xl font-extrabold uppercase text-slate-900 dark:text-white">
          Nothing written up yet
        </h2>

        <p class="text-slate-600 dark:text-white/70">
          The trips and the recipes are still being put together. The truck is done though, so go
          have a look at that while you wait.
        </p>

        <Button as-child class="mt-2">
          <Link :href="route('rig')">See the rig</Link>
        </Button>
      </div>
    </div>
  </section>
</template>
