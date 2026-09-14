<script setup lang="ts">
import { computed } from 'vue'
import { Head } from '@inertiajs/vue3'
import { ExternalLink, Wrench } from 'lucide-vue-next'
import { PageHeader } from '@/components/page-header'
import { AnimatedContent } from '@/components/ui/motion'

interface Part {
  id: number
  name: string
  vendor: string | null
  description: string | null
  installed_label: string | null
  layer: string | null
  cost: number | null
  buyUrl: string | null
  isAffiliate: boolean
}

interface Layer {
  value: string
  label: string
  depth: number
}

const props = defineProps<{
  layers: Layer[]
  parts: Part[]
  stats: { parts: number; years: number; spend: number; priced: number }
}>()

/**
 * The build, grouped by where each part lives.
 *
 * Parts with no layer set are not dropped: they collect at the end, so
 * forgetting to file one costs it its heading rather than its place on the
 * page. An empty group is left out entirely.
 */
const groups = computed(() => {
  const filed = props.layers
    .map((layer) => ({
      key: layer.value,
      label: layer.label,
      parts: props.parts.filter((part) => part.layer === layer.value),
    }))
    .filter((group) => group.parts.length > 0)

  const unfiled = props.parts.filter(
    (part) => !props.layers.some((layer) => layer.value === part.layer),
  )

  return unfiled.length
    ? [...filed, { key: 'unfiled', label: 'Everything else', parts: unfiled }]
    : filed
})

const money = (amount: number) => `$${amount.toLocaleString('en-US')}`

/** What a group of parts came to, ignoring the ones with no price on them. */
function groupSpend(parts: Part[]): number {
  return parts.reduce((total, part) => total + (part.cost ?? 0), 0)
}
</script>

<template>
  <Head title="The Rig" />

  <PageHeader
    title="The Rig"
    subtitle="Every part on the truck, where it lives and what it cost."
  />

  <div class="container py-12">
    <p v-if="parts.length === 0" class="py-16 text-center text-slate-500 dark:text-white/60">
      Nothing bolted on yet. The build starts soon.
    </p>

    <template v-else>
      <!-- The whole build in three numbers. -->
      <dl class="mb-10 flex flex-wrap gap-x-10 gap-y-4 border-b border-slate-200 pb-6 dark:border-white/10">
        <div>
          <dt class="text-xs font-bold uppercase tracking-widest text-brand">
            <Wrench class="mr-1 inline h-3.5 w-3.5" aria-hidden="true" />Parts
          </dt>
          <dd class="mt-0.5 font-brand text-2xl font-extrabold text-slate-900 dark:text-white">
            {{ stats.parts }}
          </dd>
        </div>

        <div v-if="stats.years > 0">
          <dt class="text-xs font-bold uppercase tracking-widest text-brand">In the making</dt>
          <dd class="mt-0.5 font-brand text-2xl font-extrabold text-slate-900 dark:text-white">
            {{ stats.years }} {{ stats.years === 1 ? 'year' : 'years' }}
          </dd>
        </div>

        <div v-if="stats.spend > 0">
          <dt class="text-xs font-bold uppercase tracking-widest text-brand">Spent so far</dt>
          <dd class="mt-0.5 font-brand text-2xl font-extrabold text-slate-900 dark:text-white">
            {{ money(stats.spend) }}
          </dd>
          <!--
            Say what the number is. Counting a part with no price recorded as
            zero and then calling the result a total would be a lie.
          -->
          <dd
            v-if="stats.priced < stats.parts"
            class="mt-0.5 text-xs text-slate-500 dark:text-white/50"
          >
            across the {{ stats.priced }} I wrote down
          </dd>
        </div>
      </dl>

      <div class="space-y-12">
        <AnimatedContent v-for="group in groups" :key="group.key" as="section">
          <div
            class="mb-4 flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1 border-b border-slate-200 pb-2 dark:border-white/10"
          >
            <h2 class="font-brand text-xl font-extrabold uppercase text-brand">
              {{ group.label }}
            </h2>
            <p class="text-xs font-medium uppercase tracking-widest text-slate-400 dark:text-white/40">
              {{ group.parts.length }} {{ group.parts.length === 1 ? 'part' : 'parts' }}
              <template v-if="groupSpend(group.parts) > 0">
                · {{ money(groupSpend(group.parts)) }}
              </template>
            </p>
          </div>

          <ul class="divide-y divide-slate-200 dark:divide-white/10">
            <li
              v-for="part in group.parts"
              :key="part.id"
              class="flex flex-wrap items-baseline gap-x-6 gap-y-1 py-4 sm:flex-nowrap"
            >
              <div class="min-w-0 flex-1">
                <p
                  v-if="part.vendor"
                  class="text-xs font-bold uppercase tracking-widest text-slate-400 dark:text-white/40"
                >
                  {{ part.vendor }}
                </p>

                <h3 class="font-medium text-slate-900 dark:text-white">
                  <a
                    v-if="part.buyUrl"
                    :href="part.buyUrl"
                    target="_blank"
                    :rel="part.isAffiliate ? 'sponsored noopener noreferrer' : 'noopener noreferrer'"
                    class="inline-flex items-baseline gap-1 hover:text-brand"
                  >
                    {{ part.name }}
                    <ExternalLink class="h-3 w-3 shrink-0 self-center" aria-hidden="true" />
                  </a>
                  <template v-else>{{ part.name }}</template>
                </h3>

                <p v-if="part.description" class="mt-1 text-sm text-slate-600 dark:text-white/65">
                  {{ part.description }}
                </p>
              </div>

              <p
                v-if="part.installed_label"
                class="w-24 shrink-0 text-sm text-slate-500 tabular-nums dark:text-white/50"
              >
                {{ part.installed_label }}
              </p>

              <p
                v-if="part.cost"
                class="w-20 shrink-0 text-sm font-medium text-slate-700 tabular-nums dark:text-white/75 sm:text-right"
              >
                {{ money(part.cost) }}
              </p>
            </li>
          </ul>
        </AnimatedContent>
      </div>

      <p class="mt-10 text-xs text-slate-500 dark:text-white/45">
        Some of these are affiliate links. They cost you nothing extra and they help
        pay for the next thing that breaks.
      </p>
    </template>
  </div>
</template>
