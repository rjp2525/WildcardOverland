<script setup lang="ts">
import { computed, ref } from 'vue'
import { Head } from '@inertiajs/vue3'
import { ExternalLink, Wrench, X } from 'lucide-vue-next'
import { PageHeader } from '@/components/page-header'
import ExplodedRig from '@/components/pages/rig/ExplodedRig.vue'
import type { BuildLayer, BuildPart } from '@/components/pages/rig/types'

const props = defineProps<{
  layers: BuildLayer[]
  parts: BuildPart[]
  stats: { parts: number; years: number }
}>()

const selectedId = ref<number | null>(null)

const selected = computed(
  () => props.parts.find((part) => part.id === selectedId.value) ?? null,
)

/**
 * Grouped for the list below the illustration. Parts without a layer are
 * collected at the end rather than dropped, so the list is always the whole
 * build even when the artwork is not.
 */
const groups = computed(() => {
  const placed = props.layers
    .map((layer) => ({
      key: layer.value,
      label: layer.label,
      parts: props.parts.filter((part) => part.layer === layer.value),
    }))
    .filter((group) => group.parts.length > 0)

  const rest = props.parts.filter(
    (part) => !props.layers.some((layer) => layer.value === part.layer),
  )

  return rest.length > 0
    ? [...placed, { key: 'other', label: 'Everything else', parts: rest }]
    : placed
})

const anyAffiliate = computed(() => props.parts.some((part) => part.isAffiliate))
</script>

<template>
  <Head title="The Rig" />

  <PageHeader
    title="The Rig"
    subtitle="Every part on the truck, and where it lives."
  />

  <div class="container py-12">
    <div v-if="parts.length === 0" class="py-16 text-center text-slate-500 dark:text-white/60">
      Nothing bolted on yet — the build starts soon.
    </div>

    <template v-else>
      <div class="mb-8 flex flex-wrap items-baseline gap-x-8 gap-y-2">
        <p class="inline-flex items-center gap-2 text-sm font-bold uppercase tracking-widest text-brand">
          <Wrench class="h-4 w-4" /> {{ stats.parts }} {{ stats.parts === 1 ? 'part' : 'parts' }}
        </p>
        <p v-if="stats.years > 0" class="text-sm text-slate-500 dark:text-white/60">
          {{ stats.years }} {{ stats.years === 1 ? 'year' : 'years' }} in the making
        </p>
      </div>

      <ExplodedRig
        :layers="layers"
        :parts="parts"
        :selected-id="selectedId"
        @select="selectedId = $event"
      />

      <!-- The detail for whichever hotspot is open. -->
      <div
        v-if="selected"
        class="mt-6 rounded-xl border border-brand/30 bg-brand/5 p-6"
      >
        <div class="flex items-start justify-between gap-4">
          <div>
            <p v-if="selected.vendor" class="text-xs font-bold uppercase tracking-widest text-brand">
              {{ selected.vendor }}
            </p>
            <h2 class="text-2xl font-extrabold uppercase text-black dark:text-white">
              {{ selected.name }}
            </h2>
          </div>
          <button
            type="button"
            class="rounded-md p-1.5 text-slate-500 transition-colors hover:bg-black/5 dark:text-white/60 dark:hover:bg-white/10"
            aria-label="Close part details"
            @click="selectedId = null"
          >
            <X class="h-4 w-4" />
          </button>
        </div>

        <p v-if="selected.description" class="mt-3 max-w-2xl text-slate-700 dark:text-white/80">
          {{ selected.description }}
        </p>

        <div class="mt-4 flex flex-wrap items-center gap-5">
          <a
            v-if="selected.buyUrl"
            :href="selected.buyUrl"
            target="_blank"
            rel="noopener noreferrer nofollow sponsored"
            class="inline-flex items-center gap-1.5 rounded-md bg-brand px-4 py-2 text-sm font-bold uppercase tracking-wide text-white transition-opacity hover:opacity-90"
          >
            Get one <ExternalLink class="h-3.5 w-3.5" />
          </a>
          <span v-if="selected.installed_label" class="text-sm text-slate-500 dark:text-white/60">
            Fitted {{ selected.installed_label }}
          </span>
        </div>
      </div>

      <!-- The full parts list, so nothing depends on finding a hotspot. -->
      <section v-for="group in groups" :key="group.key" class="pt-12">
        <h2 class="mb-5 text-xl font-extrabold uppercase text-brand">{{ group.label }}</h2>
        <ul class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          <li
            v-for="part in group.parts"
            :key="part.id"
            class="flex flex-col rounded-lg bg-slate-50 p-5 transition-colors dark:bg-white/5"
            :class="{ 'ring-2 ring-brand': selectedId === part.id }"
          >
            <p v-if="part.vendor" class="text-xs font-bold uppercase tracking-widest text-brand">
              {{ part.vendor }}
            </p>
            <h3 class="mt-0.5 font-bold text-black dark:text-white">{{ part.name }}</h3>
            <p v-if="part.description" class="mt-2 grow text-sm text-slate-600 dark:text-white/70">
              {{ part.description }}
            </p>

            <div class="mt-4 flex flex-wrap items-center gap-4 text-xs">
              <a
                v-if="part.buyUrl"
                :href="part.buyUrl"
                target="_blank"
                rel="noopener noreferrer nofollow sponsored"
                class="inline-flex items-center gap-1 font-bold uppercase tracking-wide text-brand hover:underline"
              >
                Buy it <ExternalLink class="h-3 w-3" />
              </a>
              <button
                v-if="part.hotspot"
                type="button"
                class="font-medium uppercase tracking-wide text-slate-500 hover:text-brand dark:text-white/50"
                @click="selectedId = selectedId === part.id ? null : part.id"
              >
                Show on the truck
              </button>
              <span v-if="part.installed_label" class="ml-auto text-slate-400 dark:text-white/40">
                {{ part.installed_label }}
              </span>
            </div>
          </li>
        </ul>
      </section>

      <p v-if="anyAffiliate" class="pt-12 text-xs text-slate-500 dark:text-white/50">
        Some links here are affiliate links. They cost you nothing extra and they
        help pay for fuel — but everything on this truck was chosen because it
        earned its place, not because of a commission.
      </p>
    </template>
  </div>
</template>
