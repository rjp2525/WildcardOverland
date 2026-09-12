<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import { ArrowRight } from 'lucide-vue-next'
import { AnimatedContent, BlurText } from '@/components/ui/motion'
import { useRoute } from '@/lib/route'

const route = useRoute()

defineProps<{
  status: {
    code: number
    sign: string
    title: string
    body: string
    hint: string | null
  }
}>()

const routes = [
  { label: 'Trips', name: 'trips.index' },
  { label: 'Recipes', name: 'recipes.index' },
  { label: 'The rig', name: 'rig' },
]
</script>

<template>
  <Head :title="status.sign" />

  <section class="relative isolate flex min-h-[calc(100vh-6rem)] items-center overflow-hidden bg-dark">
    <div class="absolute inset-0 bg-brand-radial-gradient opacity-25" aria-hidden="true" />

    <!-- A road that stops. The bar across it is the tree. -->
    <svg
      class="pointer-events-none absolute inset-x-0 bottom-0 h-64 w-full opacity-30"
      viewBox="0 0 1200 260"
      preserveAspectRatio="none"
      aria-hidden="true"
    >
      <path d="M0 260 L470 40 L730 40 L1200 260 Z" fill="rgb(255 255 255 / 0.05)" />
      <g stroke="rgb(255 255 255 / 0.22)" stroke-width="4" stroke-linecap="round">
        <path d="M598 60 L598 96" />
        <path d="M596 126 L596 168" />
        <path d="M594 202 L594 258" />
      </g>
      <g class="error-tree">
        <rect x="250" y="150" width="700" height="26" rx="13" fill="var(--color-brand)" opacity="0.85" />
        <path d="M300 150 L286 128 M420 150 L404 122 M640 176 L664 200 M820 150 L840 126"
              stroke="var(--color-brand)" stroke-width="9" stroke-linecap="round" opacity="0.7" fill="none" />
      </g>
    </svg>

    <div class="container relative py-20">
      <AnimatedContent class="max-w-2xl">
        <p class="mb-3 inline-flex items-center gap-3 text-sm font-bold uppercase tracking-[0.2em] text-brand">
          <span class="inline-block h-px w-8 bg-brand" aria-hidden="true" />
          {{ status.sign }}
        </p>

        <BlurText
          as="h1"
          :text="status.title"
          class="font-brand text-4xl font-extrabold uppercase leading-tight text-white sm:text-6xl"
        />

        <p class="mt-5 text-lg leading-relaxed text-white/70">{{ status.body }}</p>
        <p v-if="status.hint" class="mt-2 text-white/50">{{ status.hint }}</p>

        <div class="mt-9 flex flex-wrap items-center gap-3">
          <Link
            :href="route('homepage')"
            class="group inline-flex items-center gap-2 rounded-md bg-brand px-6 py-3 text-sm font-bold uppercase tracking-wide text-white transition-opacity hover:opacity-90"
          >
            Back to the trailhead
            <ArrowRight class="h-4 w-4 transition-transform group-hover:translate-x-0.5" />
          </Link>

          <Link
            v-for="link in routes"
            :key="link.name"
            :href="route(link.name)"
            class="rounded-md border border-white/20 px-5 py-3 text-sm font-bold uppercase tracking-wide text-white/80 transition-colors hover:border-brand hover:text-brand"
          >
            {{ link.label }}
          </Link>
        </div>

        <p class="mt-10 font-mono text-xs uppercase tracking-widest text-white/25">
          Error {{ status.code }}
        </p>
      </AnimatedContent>
    </div>
  </section>
</template>

<style scoped>
/* The tree settles into place, once. */
@keyframes error-tree-drop {
  from {
    transform: translateY(-28px) rotate(-2.5deg);
    opacity: 0;
  }
  to {
    transform: translateY(0) rotate(0);
    opacity: 1;
  }
}

.error-tree {
  transform-origin: 600px 163px;
  animation: error-tree-drop 900ms cubic-bezier(0.22, 1, 0.36, 1) both;
}

@media (prefers-reduced-motion: reduce) {
  .error-tree {
    animation: none;
  }
}
</style>
