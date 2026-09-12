<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import { ArrowRight, Wrench } from 'lucide-vue-next'
import { useRoute } from '@/lib/route'

const route = useRoute()

export interface Modification {
  id: number
  name: string
  vendor: string | null
  installed_label: string | null
}

defineProps<{ modifications: Modification[] }>()
</script>

<template>
  <section v-if="modifications.length" class="relative overflow-hidden bg-dark py-16">
    <div class="absolute inset-0 bg-brand-radial-gradient opacity-20" aria-hidden="true" />

    <div class="container relative grid gap-10 lg:grid-cols-2 lg:items-center">
      <div>
        <p class="mb-2 inline-flex items-center gap-2 text-sm font-bold uppercase tracking-widest text-brand">
          <Wrench class="h-4 w-4" /> The rig
        </p>
        <h2 class="font-brand text-4xl font-extrabold uppercase text-white sm:text-5xl">
          A Tacoma, slowly
        </h2>
        <p class="mt-4 max-w-lg text-white/70">
          Every trip teaches the truck something. Here's what's gone on most recently — pull
          the whole thing apart on the rig page.
        </p>
        <Link
          :href="route('rig')"
          class="group mt-6 inline-flex items-center gap-1.5 text-sm font-bold uppercase tracking-wide text-brand hover:underline"
        >
          Explode the build
          <ArrowRight class="h-4 w-4 transition-transform group-hover:translate-x-0.5" />
        </Link>
      </div>

      <ul class="space-y-3">
        <li
          v-for="mod in modifications"
          :key="mod.id"
          class="flex items-baseline justify-between gap-4 rounded-lg bg-white/5 px-4 py-3 backdrop-blur-sm"
        >
          <div class="min-w-0">
            <p class="truncate font-bold text-white">{{ mod.name }}</p>
            <p v-if="mod.vendor" class="truncate text-sm text-brand/90">{{ mod.vendor }}</p>
          </div>
          <span v-if="mod.installed_label" class="shrink-0 text-xs text-white/50">
            {{ mod.installed_label }}
          </span>
        </li>
      </ul>
    </div>
  </section>
</template>
