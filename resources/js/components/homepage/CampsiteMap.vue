<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref, shallowRef } from 'vue'
import type { Map as LeafletMap } from 'leaflet'

export interface CampsitePoint {
  name: string
  nights: number | null
  lat: number
  lng: number
  trip: string | null
  url: string | null
}

const props = defineProps<{ campsites: CampsitePoint[] }>()

const container = ref<HTMLElement | null>(null)
// shallowRef: Leaflet instances are large and mutate internally, so they must
// not be made deeply reactive.
const map = shallowRef<LeafletMap | null>(null)

onMounted(async () => {
  if (!container.value || props.campsites.length === 0) return

  /*
   * Leaflet reaches for `window` as soon as it is imported, so it is loaded
   * here rather than at module scope - this component is server-rendered
   * along with the rest of the page. The stylesheet is pulled in the same way
   * so it is not shipped to pages without a map.
   */
  const [L] = await Promise.all([
    import('leaflet'),
    import('leaflet/dist/leaflet.css'),
  ])

  const instance = L.map(container.value, {
    scrollWheelZoom: false,
    attributionControl: true,
  })

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors',
    maxZoom: 17,
  }).addTo(instance)

  /*
   * A divIcon rather than Leaflet's default marker: the stock icons are PNGs
   * resolved relative to the stylesheet, which breaks under a bundler, and
   * this lets the pins carry the brand colour.
   */
  const icon = L.divIcon({
    className: '',
    html: '<span class="block h-3.5 w-3.5 rounded-full border-2 border-white bg-brand shadow-md"></span>',
    iconSize: [14, 14],
    iconAnchor: [7, 7],
  })

  const bounds: [number, number][] = []

  props.campsites.forEach((camp) => {
    const marker = L.marker([camp.lat, camp.lng], { icon, title: camp.name }).addTo(instance)
    bounds.push([camp.lat, camp.lng])

    const nights = camp.nights
      ? `<p class="text-xs text-slate-500">${camp.nights} ${camp.nights === 1 ? 'night' : 'nights'}</p>`
      : ''
    const trip = camp.url && camp.trip
      ? `<a href="${camp.url}" class="text-xs font-medium text-[#e85a2f] underline">${camp.trip}</a>`
      : ''

    marker.bindPopup(
      `<div class="space-y-0.5"><p class="font-bold">${camp.name}</p>${nights}${trip}</div>`,
    )
  })

  bounds.length === 1
    ? instance.setView(bounds[0], 8)
    : instance.fitBounds(bounds, { padding: [40, 40] })

  map.value = instance
})

onBeforeUnmount(() => {
  map.value?.remove()
  map.value = null
})
</script>

<template>
  <section v-if="campsites.length" class="border-t border-slate-200 py-16 dark:border-white/10">
    <div class="container">
      <h2 class="mb-2 text-center text-3xl font-extrabold uppercase text-brand">Where we've camped</h2>
      <p class="mb-8 text-center text-slate-600 dark:text-white/70">
        {{ campsites.length }} {{ campsites.length === 1 ? 'campsite' : 'campsites' }} across every published trip.
      </p>

      <div
        ref="container"
        class="h-[28rem] w-full overflow-hidden rounded-lg border border-slate-200 bg-slate-100 dark:border-white/10 dark:bg-zinc-800"
        role="application"
        aria-label="Map of campsites"
      />
    </div>
  </section>
</template>

<style>
/* Leaflet's popup chrome, nudged toward the site's look. */
.leaflet-popup-content-wrapper {
  border-radius: 0.5rem;
}
.leaflet-container {
  font-family: inherit;
  background: transparent;
}
</style>
