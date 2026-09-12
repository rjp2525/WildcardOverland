<script setup lang="ts">
import { computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import { ArrowUpRight, ChefHat, Map, Wrench } from 'lucide-vue-next'
import { HorizontalLogo } from '@/components/icon'
import { useRoute } from '@/lib/route'
import type { PageProps } from '@/types/PageProps'

const route = useRoute()
const page = usePage<PageProps>()

const site = computed(() => page.props.site)
const year = new Date().getFullYear()

/** "2024" on its own until there is a second year to span. */
const copyright = computed(() => {
  const since = site.value?.since ?? year

  return since >= year ? `${year}` : `${since}–${year}`
})

/** Only real destinations. A footer full of dead ends is worse than a short one. */
const explore = [
  { label: 'Trips', to: 'trips.index', icon: Map, blurb: 'Where the truck has been' },
  { label: 'Camp recipes', to: 'recipes.index', icon: ChefHat, blurb: 'Food worth the effort' },
  { label: 'The rig', to: 'rig', icon: Wrench, blurb: 'Every part, and why' },
]

/**
 * Whatever handles are configured, as text. Icons would mean finding a mark
 * for each platform, and there is no TikTok one in the icon set.
 */
const elsewhere = computed(() => {
  const s = site.value?.social ?? {}

  return [
    s.instagram && { label: 'Instagram', href: s.instagram },
    s.youtube && { label: 'YouTube', href: s.youtube },
    s.tiktok && { label: 'TikTok', href: s.tiktok },
    site.value?.email && { label: 'Email', href: `mailto:${site.value.email}` },
  ].filter(Boolean) as Array<{ label: string; href: string }>
})
</script>

<template>
  <footer class="relative isolate mt-auto overflow-hidden bg-dark">
    <div class="absolute inset-0 bg-page-header bg-cover bg-center opacity-20" aria-hidden="true" />
    <div class="absolute inset-0 bg-linear-to-b from-black/60 via-black/85 to-black" aria-hidden="true" />
    <div class="absolute inset-0 bg-brand-radial-gradient opacity-12" aria-hidden="true" />

    <div class="container relative">
      <!--
        Three cards across on a wide screen, stacked on a phone. Cards rather
        than a column of bare links, so the footer has something to look at on
        a narrow screen instead of a long ladder of text.
      -->
      <div class="grid gap-3 pt-14 sm:grid-cols-3">
        <Link
          v-for="link in explore"
          :key="link.label"
          :href="route(link.to)"
          class="group flex items-center gap-4 rounded-xl border border-white/10 bg-white/[0.04] p-4 transition-colors hover:border-brand/50 hover:bg-white/[0.07] sm:flex-col sm:items-start sm:gap-3 sm:p-5"
        >
          <span
            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-brand/15 text-brand transition-colors group-hover:bg-brand group-hover:text-white"
          >
            <component :is="link.icon" class="h-5 w-5" />
          </span>

          <span class="min-w-0 flex-1">
            <span class="flex items-center gap-1.5 font-bold uppercase tracking-wide text-white">
              {{ link.label }}
              <ArrowUpRight
                class="h-4 w-4 text-white/30 transition-all group-hover:translate-x-0.5 group-hover:-translate-y-0.5 group-hover:text-brand"
              />
            </span>
            <span class="mt-0.5 block text-sm text-white/50">{{ link.blurb }}</span>
          </span>
        </Link>
      </div>

      <div class="mt-12 grid gap-10 sm:grid-cols-2 lg:grid-cols-[1.6fr_1fr_1fr]">
        <div>
          <Link :href="route('homepage')" class="group inline-block">
            <HorizontalLogo class="h-16 w-auto text-white/90 transition-colors group-hover:text-white" />
          </Link>

          <p class="mt-4 max-w-sm text-sm leading-relaxed text-white/55">
            A Toyota Tacoma, a camper on the back and whichever road looks more
            interesting.
          </p>
        </div>

        <div>
          <h2 class="mb-4 text-xs font-bold uppercase tracking-[0.18em] text-brand">The site</h2>
          <ul class="space-y-2.5">
            <li v-for="link in [...explore, { label: 'About', to: 'about' }]" :key="`nav-${link.label}`">
              <Link
                :href="route(link.to)"
                class="text-sm text-white/60 transition-colors hover:text-white"
              >
                {{ link.label }}
              </Link>
            </li>
          </ul>
        </div>

        <div v-if="elsewhere.length">
          <h2 class="mb-4 text-xs font-bold uppercase tracking-[0.18em] text-brand">Elsewhere</h2>
          <ul class="space-y-2.5">
            <li v-for="link in elsewhere" :key="link.label">
              <a
                :href="link.href"
                :target="link.href.startsWith('mailto:') ? undefined : '_blank'"
                :rel="link.href.startsWith('mailto:') ? undefined : 'noopener noreferrer'"
                class="group inline-flex items-center gap-1.5 text-sm text-white/60 transition-colors hover:text-white"
              >
                {{ link.label }}
                <ArrowUpRight class="h-3.5 w-3.5 text-white/25 transition-colors group-hover:text-brand" />
              </a>
            </li>
          </ul>
        </div>
      </div>

      <div
        class="mt-12 flex flex-col gap-4 border-t border-white/10 py-7 sm:flex-row sm:items-center sm:justify-between"
      >
        <p class="text-xs text-white/40">
          &copy; {{ copyright }} Wildcard Overland. Exact campsite coordinates are kept back on purpose.
        </p>
        <p class="max-w-md text-xs leading-relaxed text-white/35 sm:text-right">
          Some links here are affiliate links. They cost you nothing extra, and
          everything listed earned its place on the truck first.
        </p>
      </div>
    </div>
  </footer>
</template>
