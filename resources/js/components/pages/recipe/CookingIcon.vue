<script setup lang="ts">
/**
 * A drawing of the thing the food is cooked on.
 *
 * Line work rather than fills, at the same weight as the lucide icons used
 * everywhere else, so a row of these sits next to the clock and the serving
 * count without looking like it came from somewhere different. Everything
 * inherits currentColor, so it themes for free.
 */
defineProps<{ method: string }>()
</script>

<template>
  <svg
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    stroke-width="1.6"
    stroke-linecap="round"
    stroke-linejoin="round"
    aria-hidden="true"
    focusable="false"
  >
    <!-- Skottle: a shallow disc on a tripod, bottle underneath. -->
    <g v-if="method === 'skottle'">
      <path d="M3 9h18" />
      <path d="M4 9c0 3.3 3.6 5 8 5s8-1.7 8-5" />
      <path d="M7 14 5.5 21M17 14l1.5 7M12 14.9V21" />
      <path d="M10.5 6.5c.6-.8.2-1.6-.2-2.2M13.8 6.6c.6-.9.2-1.8-.3-2.4" />
    </g>

    <!-- Camper stove: two burners in a box, knobs on the front. -->
    <g v-else-if="method === 'stovetop'">
      <rect x="2.5" y="6" width="19" height="12" rx="2" />
      <circle cx="8" cy="10.5" r="2.4" />
      <circle cx="16" cy="10.5" r="2.4" />
      <path d="M6.5 15.5h.01M10 15.5h.01M14 15.5h.01M17.5 15.5h.01" />
    </g>

    <!-- Jetboil: tall cup on a burner, canister below. -->
    <g v-else-if="method === 'jetboil'">
      <path d="M8 3h8v9a2 2 0 0 1-2 2h-4a2 2 0 0 1-2-2z" />
      <path d="M10 14v1.5M14 14v1.5" />
      <rect x="8.5" y="16" width="7" height="5" rx="1.6" />
      <path d="M10.5 3.5v3M13.5 3.5v3" />
    </g>

    <!-- Cast iron skillet, seen from above with its long handle. -->
    <g v-else-if="method === 'skillet'">
      <circle cx="10" cy="12" r="6.5" />
      <circle cx="10" cy="12" r="3.6" />
      <path d="M16.4 12H22" />
      <path d="M20 10.4v3.2" />
    </g>

    <!-- Dutch oven: domed lid, bail handle, three legs. -->
    <g v-else-if="method === 'dutch-oven'">
      <path d="M4.5 11h15v4.5a3 3 0 0 1-3 3h-9a3 3 0 0 1-3-3z" />
      <path d="M3.5 11h17" />
      <path d="M6.5 11a5.5 5.5 0 0 1 11 0" />
      <path d="M12 5.5V4" />
      <path d="M7 18.5 6 21M17 18.5l1 2.5M12 18.6V21" />
    </g>

    <!-- Campfire: crossed logs and a flame. -->
    <g v-else-if="method === 'campfire'">
      <path d="M12 3c1.8 2.2 1 3.6.3 4.6-.8 1.1-1.6 2.2-1.6 3.6a3.3 3.3 0 0 0 6.6 0c0-2.6-1.7-4-2.6-5" />
      <path d="M9.6 8.4c-1.2.9-2 2.3-2 3.8a4.4 4.4 0 0 0 1.3 3.1" />
      <path d="M3.5 17.5 20.5 21M20.5 17.5 3.5 21" />
    </g>

    <!-- Charcoal: a kettle grill with the lid off, coals underneath. -->
    <g v-else-if="method === 'charcoal'">
      <path d="M3.5 10.5h17a8.5 8.5 0 0 1-17 0z" />
      <path d="M4.5 8.5h15" />
      <path d="M7 17.5 5 21M17 17.5l2 3.5" />
      <path d="M9 12.6h.01M12 13.4h.01M15 12.6h.01" />
    </g>

    <!-- Smoker: an offset barrel with its chimney. -->
    <g v-else-if="method === 'smoker'">
      <rect x="2.5" y="9" width="14" height="9" rx="4.5" />
      <path d="M19 9V6.5" />
      <path d="M17.6 6.5h2.8" />
      <path d="M16.5 11.5h2.5v6.5" />
      <path d="M6 18v2.5M13 18v2.5" />
      <path d="M6.5 13.5h6" />
      <path d="M19 4.6c.8-.7.4-1.4 0-2" />
    </g>
  </svg>
</template>
