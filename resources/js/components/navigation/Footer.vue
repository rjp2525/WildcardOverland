<script setup lang="ts">
import { computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import { ArrowUp } from 'lucide-vue-next'
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
  { label: 'Trips', to: 'trips.index' },
  { label: 'Camp recipes', to: 'recipes.index' },
  { label: 'The rig', to: 'rig' },
  { label: 'About', to: 'about' },
]

/**
 * Whatever handles are configured, as text links. Icons would mean finding
 * a mark for each platform, and there is no TikTok one in the icon set.
 */
const elsewhere = computed(() => {
  const s = site.value?.social ?? {}

  return [
    s.instagram && { label: 'Instagram', href: s.instagram },
    s.youtube && { label: 'YouTube', href: s.youtube },
    s.tiktok && { label: 'TikTok', href: s.tiktok },
    site.value?.email && { label: 'Email me', href: `mailto:${site.value.email}` },
  ].filter(Boolean) as Array<{ label: string; href: string }>
})

function toTop() {
  window.scrollTo({ top: 0, behavior: 'smooth' })
}
</script>

<template>
  <footer class="relative isolate mt-auto overflow-hidden bg-dark">
    <!-- The same night the hero is sitting on, cropped to a strip. -->
    <div class="absolute inset-0 bg-page-header bg-cover bg-center opacity-25" aria-hidden="true" />
    <div class="absolute inset-0 bg-linear-to-b from-black/70 via-black/85 to-black" aria-hidden="true" />
    <div class="absolute inset-0 bg-brand-radial-gradient opacity-15" aria-hidden="true" />

    <div class="container relative py-14">
      <div class="grid gap-10 lg:grid-cols-[1.4fr_1fr_1fr_auto]">
        <div>
          <Link :href="route('homepage')" class="inline-block">
            <HorizontalLogo class="h-14 w-auto" />
          </Link>

          <p class="mt-4 max-w-sm text-sm leading-relaxed text-white/60">
            A Toyota Tacoma, a camper on the back and whichever road looks more
            interesting. Trip write-ups, camp cooking and the whole build.
          </p>

        </div>

        <div>
          <h2 class="mb-4 text-xs font-bold uppercase tracking-[0.2em] text-brand">Explore</h2>
          <ul class="space-y-2.5">
            <li v-for="link in explore" :key="link.label">
              <Link
                :href="route(link.to)"
                class="text-sm text-white/65 transition-colors hover:text-white"
              >
                {{ link.label }}
              </Link>
            </li>
          </ul>
        </div>

        <div v-if="elsewhere.length">
          <h2 class="mb-4 text-xs font-bold uppercase tracking-[0.2em] text-brand">Elsewhere</h2>
          <ul class="space-y-2.5">
            <li v-for="link in elsewhere" :key="link.label">
              <a
                :href="link.href"
                :target="link.href.startsWith('mailto:') ? undefined : '_blank'"
                :rel="link.href.startsWith('mailto:') ? undefined : 'noopener noreferrer'"
                class="text-sm text-white/65 transition-colors hover:text-white"
              >
                {{ link.label }}
              </a>
            </li>
          </ul>
        </div>

        <div class="lg:text-right">
          <button
            type="button"
            class="group inline-flex items-center gap-2 rounded-lg border border-white/12 px-4 py-2.5 text-xs font-bold uppercase tracking-widest text-white/70 transition-colors hover:border-brand hover:text-brand"
            @click="toTop"
          >
            Back to the top
            <ArrowUp class="h-4 w-4 transition-transform group-hover:-translate-y-0.5" />
          </button>
        </div>
      </div>

      <div class="mt-12 border-t border-white/10 pt-6">
        <p class="text-xs leading-relaxed text-white/40">
          Some links on this site are affiliate links. They cost you nothing extra.
          Everything listed earned its place on the truck first.
        </p>
        <p class="mt-3 text-xs text-white/40">
          &copy; {{ copyright }} Wildcard Overland. Exact campsite coordinates are
          kept back on purpose.
        </p>
      </div>
    </div>
  </footer>
</template>
