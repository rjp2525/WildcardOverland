<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref, shallowRef } from 'vue'
import type { Map as LeafletMap } from 'leaflet'
import { Lock } from 'lucide-vue-next'

export interface CampsitePoint {
  name: string
  nights: number | null
  lat: number
  lng: number
  /** False when the position has been fuzzed for a public viewer. */
  precise: boolean
  state: string | null
  trip: string | null
  url: string | null
}

export interface MapFocus {
  south: number
  west: number
  north: number
  east: number
}

const props = defineProps<{
  campsites: CampsitePoint[]
  /** Where to open: the densest cluster, not every pin at once. */
  focus?: MapFocus | null
}>()

const container = ref<HTMLElement | null>(null)
// shallowRef: Leaflet instances are large and mutate internally, so they must
// not be made deeply reactive.
const map = shallowRef<LeafletMap | null>(null)

/*
 * OpenStreetMap's own tiles: no account, no key, and the same project behind
 * the reverse geocoding. There is one set of them rather than a light and a
 * dark pair - the theme is a CSS filter over the tile pane, which also means
 * the map follows the site's toggle without a listener and without a flash.
 */
const TILES = 'https://tile.openstreetmap.org/{z}/{x}/{y}.png'

onMounted(async () => {
  if (!container.value || props.campsites.length === 0) return

  /*
   * Leaflet reaches for `window` as soon as it is imported, and this page is
   * server-rendered - so it is loaded here rather than at module scope. That
   * also keeps it out of every other page's bundle.
   */
  const [L] = await Promise.all([
    import('leaflet'),
    import('leaflet/dist/leaflet.css'),
  ])

  const instance = L.map(container.value, {
    scrollWheelZoom: false,
    zoomControl: true,
  })

  L.tileLayer(TILES, {
    attribution:
      '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
    maxZoom: 17,
  }).addTo(instance)

  /*
   * A divIcon rather than Leaflet's default marker: the stock icons are PNGs
   * resolved relative to the stylesheet, which breaks under a bundler, and
   * this lets the pins carry the brand colour.
   */
  const icon = L.divIcon({
    className: '',
    html: '<span class="campsite-pin"></span>',
    iconSize: [16, 16],
    iconAnchor: [8, 8],
  })

  props.campsites.forEach((camp) => {
    const marker = L.marker([camp.lat, camp.lng], { icon, title: camp.name }).addTo(instance)

    const nights = camp.nights
      ? `<p class="text-xs opacity-70">${camp.nights} ${camp.nights === 1 ? 'night' : 'nights'}</p>`
      : ''
    const where = camp.state ? `<p class="text-xs opacity-70">${camp.state}</p>` : ''
    const trip =
      camp.url && camp.trip
        ? `<a href="${camp.url}" class="text-xs font-semibold underline" style="color:#e85a2f">${camp.trip}</a>`
        : ''
    const approx = camp.precise
      ? ''
      : '<p class="mt-1 text-[0.65rem] uppercase tracking-wide opacity-60">Approximate location</p>'

    marker.bindPopup(
      `<div class="space-y-0.5"><p class="font-bold">${camp.name}</p>${where}${nights}${trip}${approx}</div>`,
    )
  })

  // Open on the focus box when we have one; otherwise frame everything.
  if (props.focus) {
    instance.fitBounds(
      [
        [props.focus.south, props.focus.west],
        [props.focus.north, props.focus.east],
      ],
      { padding: [24, 24] },
    )
  } else {
    const bounds = props.campsites.map((c) => [c.lat, c.lng]) as [number, number][]
    bounds.length === 1
      ? instance.setView(bounds[0], 8)
      : instance.fitBounds(bounds, { padding: [40, 40] })
  }

  map.value = instance
})

onBeforeUnmount(() => {
  map.value?.remove()
  map.value = null
})

const anyApproximate = () => props.campsites.some((c) => !c.precise)
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
        class="campsite-map h-[28rem] w-full overflow-hidden rounded-lg border border-slate-200 bg-slate-100 shadow-md dark:border-white/10 dark:bg-zinc-800"
        role="application"
        aria-label="Map of campsites"
      />

      <p
        v-if="anyApproximate()"
        class="mt-3 flex items-center justify-center gap-1.5 text-xs text-slate-500 dark:text-white/50"
      >
        <Lock class="h-3 w-3" />
        Pins are approximate. Exact coordinates are for members.
      </p>
    </div>
  </section>
</template>

<style>
/* Brand pin, with a soft halo so it reads on both basemaps. */
.campsite-pin {
  display: block;
  width: 0.875rem;
  height: 0.875rem;
  border-radius: 9999px;
  background: var(--color-brand);
  border: 2px solid #fff;
  box-shadow: 0 0 0 3px color-mix(in oklab, var(--color-brand) 30%, transparent);
}

/*
 * One tileset, two themes. Desaturating in light mode keeps the map from
 * shouting over the page; inverting it in dark mode turns the same tiles
 * into a dark basemap, which is the whole reason there is only one set.
 */
.campsite-map .leaflet-tile-pane {
  filter: grayscale(0.55) brightness(1.02);
}
.dark .campsite-map .leaflet-tile-pane {
  filter: grayscale(1) invert(1) brightness(0.88) contrast(1.05);
}

/* Leaflet's chrome, nudged toward the site's look. */
.campsite-map .leaflet-container {
  font-family: inherit;
  background: transparent;
}
.campsite-map .leaflet-popup-content-wrapper,
.campsite-map .leaflet-popup-tip {
  border-radius: 0.5rem;
  background: #fff;
  color: var(--color-black);
}
.dark .campsite-map .leaflet-popup-content-wrapper,
.dark .campsite-map .leaflet-popup-tip {
  background: var(--color-dark);
  color: #fff;
}
.campsite-map .leaflet-bar a {
  background: #fff;
  color: var(--color-black);
  border-color: rgb(0 0 0 / 0.1);
}
.dark .campsite-map .leaflet-bar a {
  background: var(--color-dark);
  color: #fff;
  border-color: rgb(255 255 255 / 0.15);
}
.campsite-map .leaflet-control-attribution {
  background: rgb(255 255 255 / 0.75);
  font-size: 0.65rem;
}
.dark .campsite-map .leaflet-control-attribution {
  background: rgb(0 0 0 / 0.5);
  color: rgb(255 255 255 / 0.6);
}
.dark .campsite-map .leaflet-control-attribution a {
  color: rgb(255 255 255 / 0.8);
}
</style>
