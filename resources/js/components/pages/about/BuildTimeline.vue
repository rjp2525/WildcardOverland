<script setup lang="ts">
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import { ArrowRight, ExternalLink } from 'lucide-vue-next'
import { AnimatedContent } from '@/components/ui/motion'
import { useRoute } from '@/lib/route'

const route = useRoute()

export interface TimelineEntry {
  id: number
  name: string
  description: string | null
  vendor: string | null
  url: string | null
  installed_on: string | null
  installed_label: string | null
}

const props = defineProps<{ timeline: TimelineEntry[] }>()

/**
 * Grouped by year, because that is how a build actually reads: a run of
 * things done one summer, then a quiet winter, then another run. A flat
 * list flattens that out and every entry ends up looking the same size.
 */
const years = computed(() => {
  const groups = new Map<string, TimelineEntry[]>()

  for (const entry of props.timeline) {
    const year = entry.installed_on?.slice(0, 4) ?? 'Undated'
    groups.set(year, [...(groups.get(year) ?? []), entry])
  }

  return [...groups.entries()].map(([year, entries]) => ({ year, entries }))
})
</script>

<template>
  <section v-if="timeline.length" class="relative overflow-hidden py-16">
    <div class="container">
      <div class="mb-12 text-center">
        <h2 class="text-3xl font-extrabold uppercase text-brand dark:drop-shadow">The build</h2>
        <p class="mt-2 text-slate-600 dark:text-white/60">
          Everything that has gone on, in the order it went on.
        </p>
      </div>

      <!--
        A route with waypoints on it rather than a list. The line runs down
        the middle on a wide screen and down the left on a narrow one, and
        the entries hang off it the way stops hang off a track.
      -->
      <div class="relative mx-auto max-w-4xl">
        <span class="build-trail" aria-hidden="true" />

        <div v-for="group in years" :key="group.year" class="relative">
          <div class="build-year" aria-hidden="true">{{ group.year }}</div>
          <h3 class="sr-only">{{ group.year }}</h3>

          <ol>
            <AnimatedContent
              v-for="(entry, index) in group.entries"
              :key="entry.id"
              as="li"
              :distance="20"
              class="build-stop"
              :class="index % 2 === 0 ? 'is-left' : 'is-right'"
            >
              <span class="build-pin" aria-hidden="true" />

              <div class="build-card">
                <div class="flex flex-wrap items-baseline justify-between gap-x-3">
                  <h3 class="font-bold text-black dark:text-white">
                    <component
                      :is="entry.url ? 'a' : 'span'"
                      :href="entry.url ?? undefined"
                      :target="entry.url ? '_blank' : undefined"
                      :rel="entry.url ? 'noopener noreferrer' : undefined"
                      :class="entry.url ? 'inline-flex items-center gap-1 hover:text-brand' : ''"
                    >
                      {{ entry.name }}
                      <ExternalLink v-if="entry.url" class="h-3.5 w-3.5" />
                    </component>
                  </h3>

                  <time
                    v-if="entry.installed_label"
                    :datetime="entry.installed_on ?? undefined"
                    class="shrink-0 text-xs uppercase tracking-widest text-slate-400 dark:text-white/40"
                  >
                    {{ entry.installed_label }}
                  </time>
                </div>

                <p v-if="entry.vendor" class="mt-0.5 text-sm font-bold uppercase tracking-wide text-brand/90">
                  {{ entry.vendor }}
                </p>
                <p v-if="entry.description" class="mt-2 text-sm leading-relaxed text-slate-600 dark:text-white/70">
                  {{ entry.description }}
                </p>
              </div>
            </AnimatedContent>
          </ol>
        </div>

        <span class="build-trailhead" aria-hidden="true" />
      </div>

      <div class="mt-12 text-center">
        <Link
          :href="route('rig')"
          class="group inline-flex items-center gap-2 rounded-md border border-brand/40 bg-brand/10 px-6 py-3 text-sm font-bold uppercase tracking-wide text-brand transition-colors hover:bg-brand/20"
        >
          See where every part sits
          <ArrowRight class="h-4 w-4 transition-transform group-hover:translate-x-0.5" />
        </Link>
      </div>
    </div>
  </section>
</template>

<style scoped>
/* The track. Dashed, like a trail on a map rather than a rule on a page. */
.build-trail {
  position: absolute;
  top: 0;
  bottom: 0;
  left: 0.4375rem;
  width: 2px;
  background-image: linear-gradient(
    to bottom,
    color-mix(in oklab, var(--color-brand) 45%, transparent) 0 8px,
    transparent 8px 16px
  );
  background-size: 2px 16px;
}

/* Where the track stops. */
.build-trailhead {
  position: absolute;
  bottom: -0.3rem;
  left: 0.0625rem;
  width: 0.8125rem;
  height: 0.8125rem;
  border-radius: 9999px;
  border: 2px solid color-mix(in oklab, var(--color-brand) 45%, transparent);
}

/*
 * The year sits behind the run of parts it belongs to rather than on the
 * line. Masking the line to make room for it would mean painting the page
 * background behind the text, which is a thing that has to be got right
 * twice, once per theme, and looks broken the moment it is not.
 */
.build-year {
  position: relative;
  padding: 2rem 0 0.25rem 2.5rem;
  font-family: var(--font-brand, inherit);
  font-size: 3rem;
  font-weight: 800;
  line-height: 1;
  letter-spacing: 0.02em;
  color: color-mix(in oklab, var(--color-brand) 26%, transparent);
}

.build-stop {
  position: relative;
  display: block;
  padding: 0 0 1.75rem 2.5rem;
}

/* Same pin as the campsite map, so a stop reads the same way sitewide. */
.build-pin {
  position: absolute;
  left: 0;
  top: 0.55rem;
  width: 0.9375rem;
  height: 0.9375rem;
  border-radius: 9999px;
  background: var(--color-brand);
  border: 3px solid var(--color-white, #fff);
  box-shadow: 0 0 0 3px color-mix(in oklab, var(--color-brand) 28%, transparent);
}

.dark .build-pin {
  border-color: #101013;
}

.build-card {
  border-radius: 0.5rem;
  background: rgb(0 0 0 / 0.03);
  padding: 1rem 1.15rem;
}

.dark .build-card {
  background: rgb(255 255 255 / 0.05);
}

/*
 * On a wide screen the track moves to the middle and the stops alternate
 * either side of it. Below that they all stay left of a single line, which
 * is the only thing that reads at phone width.
 */
@media (min-width: 64rem) {
  .build-trail,
  .build-trailhead {
    left: 50%;
    transform: translateX(-50%);
  }

  .build-year {
    /* Centred over the track, in normal flow so it cannot land on a card.
       The dashed line runs behind it, which is the point. */
    padding: 0 0 1.5rem;
    text-align: center;
    font-size: 4.5rem;
  }

  .build-stop {
    width: 50%;
    padding-bottom: 2rem;
  }

  .build-stop.is-left {
    padding-left: 0;
    padding-right: 2.75rem;
    text-align: right;
  }

  .build-stop.is-right {
    margin-left: 50%;
    padding-left: 2.75rem;
  }

  .build-stop.is-left .build-pin {
    left: auto;
    right: -0.47rem;
  }

  .build-stop.is-right .build-pin {
    left: -0.47rem;
  }

  .build-stop.is-left .build-card {
    text-align: right;
  }
}
</style>
