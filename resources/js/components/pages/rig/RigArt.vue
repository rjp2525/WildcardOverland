<script setup lang="ts">
/**
 * The truck, drawn in four separate slices so they can be pulled apart.
 *
 * Every slice shares one viewBox, so a point in the artwork means the same
 * thing in each of them - which is what lets a hotspot be stored as a plain
 * percentage of the box regardless of which layer it belongs to.
 *
 * Proportions come from a double-cab Tacoma: 127" wheelbase, 70.6" roof,
 * 32" tyres, at roughly 3.9px to the inch with the ground at y=416. The frame
 * carries deliberate slack above and below so the exploded layers have
 * somewhere to travel.
 */
defineProps<{ layer: string }>()

const WHEELS = [
  { cx: 269, cy: 354 },
  { cx: 764, cy: 354 },
]

/**
 * A wheel arch: along the body's lower edge, up over the tyre, back down.
 * A cubic rather than an elliptical arc, so the tangents at both ends are
 * vertical - which is what makes it read as a fender rather than a bite.
 *
 * Both body outlines are drawn front to back, so the curve runs right to
 * left; reversing it would close a lens across the opening and fill it in.
 */
const arch = (cx: number) =>
  `L ${cx + 72} 368 C ${cx + 72} 249, ${cx - 72} 249, ${cx - 72} 368`
</script>

<template>
  <svg
    class="rig-art"
    viewBox="0 10 1000 480"
    fill="none"
    xmlns="http://www.w3.org/2000/svg"
    aria-hidden="true"
    focusable="false"
  >
    <!-- Underside: wheels, axles, sliders, skid plate, exhaust. -->
    <g v-if="layer === 'underside'">
      <path d="M197 368 C197 249, 341 249, 341 368 Z" class="rig-well" />
      <path d="M692 368 C692 249, 836 249, 836 368 Z" class="rig-well" />
      <path d="M70 418 L930 418" class="rig-ground" />

      <rect x="188" y="348" width="172" height="13" rx="6" class="rig-metal" />
      <path d="M196 341 Q269 328 344 341" class="rig-leaf" />
      <path d="M700 372 L832 372 L822 396 L712 396 Z" class="rig-metal" />
      <path d="M340 396 L124 396 L106 406" class="rig-pipe" />
      <rect x="96" y="400" width="18" height="12" rx="4" class="rig-metal" />

      <rect x="346" y="366" width="342" height="16" rx="8" class="rig-slider" />
      <rect x="392" y="354" width="12" height="16" rx="3" class="rig-slider" />
      <rect x="630" y="354" width="12" height="16" rx="3" class="rig-slider" />

      <g v-for="wheel in WHEELS" :key="wheel.cx">
        <circle :cx="wheel.cx" :cy="wheel.cy" r="62" class="rig-tyre" />
        <circle :cx="wheel.cx" :cy="wheel.cy" r="52" class="rig-tread" />
        <circle :cx="wheel.cx" :cy="wheel.cy" r="33" class="rig-rim" />
        <circle :cx="wheel.cx" :cy="wheel.cy" r="12" class="rig-hub" />
        <g class="rig-spoke">
          <line
            v-for="n in 5"
            :key="n"
            :x1="wheel.cx"
            :y1="wheel.cy"
            :x2="wheel.cx + 27 * Math.cos(((n - 1) * 72 - 90) * (Math.PI / 180))"
            :y2="wheel.cy + 27 * Math.sin(((n - 1) * 72 - 90) * (Math.PI / 180))"
          />
        </g>
      </g>
    </g>

    <!-- Interior: what lives in the bed. -->
    <g v-if="layer === 'interior'">
      <rect x="120" y="248" width="248" height="98" rx="6" class="rig-kit" />
      <rect x="130" y="257" width="228" height="39" rx="3" class="rig-kit-face" />
      <rect x="130" y="300" width="228" height="39" rx="3" class="rig-kit-face" />
      <rect x="214" y="272" width="60" height="9" rx="4" class="rig-accent" />
      <rect x="214" y="315" width="60" height="9" rx="4" class="rig-accent" />

      <rect x="382" y="240" width="92" height="106" rx="6" class="rig-kit" />
      <rect x="382" y="240" width="92" height="19" rx="6" class="rig-kit-face" />
      <rect x="396" y="280" width="64" height="10" rx="5" class="rig-accent" />
      <rect x="396" y="302" width="42" height="7" rx="3" class="rig-kit-face" />

      <rect x="84" y="278" width="34" height="68" rx="4" class="rig-kit" />
      <rect x="90" y="287" width="22" height="7" rx="3" class="rig-accent" />
      <rect x="90" y="300" width="22" height="7" rx="3" class="rig-kit-face" />
    </g>

    <!-- Body: the cab and the bed, the bed side ghosted so the gear inside
         still reads before the layers are pulled apart. -->
    <g v-if="layer === 'body'">
      <path
        :d="`M498 143 L672 143 L755 240 L897 250 L902 368
             ${arch(764)} L498 368 Z`"
        class="rig-body"
      />
      <path
        :d="`M74 210 L486 210 L486 368 ${arch(269)} L74 368 Z`"
        class="rig-body rig-bed"
      />

      <rect x="72" y="204" width="416" height="10" rx="4" class="rig-body-edge" />
      <path d="M602 244 L602 364" class="rig-crease" />
      <path d="M508 244 L508 364" class="rig-crease" />

      <path d="M512 158 L594 158 L594 232 L512 232 Z" class="rig-glass" />
      <path d="M606 158 L668 158 L678 232 L606 232 Z" class="rig-glass" />
      <path d="M678 148 L700 148 L757 238 L724 238 Z" class="rig-glass" />
      <path d="M676 240 L654 247 L657 259 L679 252 Z" class="rig-body-edge" />

      <rect x="548" y="248" width="30" height="8" rx="4" class="rig-body-edge" />
      <rect x="626" y="248" width="30" height="8" rx="4" class="rig-body-edge" />

      <path d="M197 368 C197 249, 341 249, 341 368" class="rig-flare" />
      <path d="M692 368 C692 249, 836 249, 836 368" class="rig-flare" />

      <rect x="884" y="306" width="42" height="66" rx="7" class="rig-bumper" />
      <rect x="876" y="292" width="12" height="66" rx="5" class="rig-bumper" />
      <rect x="52" y="312" width="34" height="58" rx="6" class="rig-bumper" />

      <path d="M768 252 L886 261" class="rig-crease" />
      <path d="M864 256 L894 251 L896 278 L864 274 Z" class="rig-lamp" />
      <rect x="76" y="222" width="13" height="42" rx="3" class="rig-accent" />
    </g>

    <!-- Roof: rack and everything bolted to it. -->
    <g v-if="layer === 'roof'">
      <rect x="502" y="128" width="168" height="13" rx="3" class="rig-rack" />
      <g class="rig-rack-slat">
        <line v-for="n in 8" :key="n" :x1="512 + n * 18" y1="129" :x2="512 + n * 18" y2="140" />
      </g>
      <rect x="510" y="141" width="9" height="6" class="rig-rack" />
      <rect x="654" y="141" width="9" height="6" class="rig-rack" />

      <rect x="506" y="108" width="126" height="20" rx="10" class="rig-rack" />
      <rect x="506" y="110" width="14" height="16" rx="5" class="rig-accent" />
      <path d="M534 118 L620 118" class="rig-rack-slat" />

      <rect x="638" y="112" width="42" height="16" rx="4" class="rig-rack" />
      <rect x="643" y="116" width="32" height="8" rx="4" class="rig-lamp" />

      <path d="M502 130 L490 82" class="rig-whip" />
      <circle cx="489" cy="80" r="5" class="rig-accent" />
    </g>
  </svg>
</template>

<style scoped>
.rig-art {
  width: 100%;
  height: 100%;
  display: block;
  overflow: visible;
  /* Separates the slices from one another once they are pulled apart. */
  filter: drop-shadow(0 10px 18px rgb(0 0 0 / 0.45));
}

.rig-body { fill: #e7ecf2; }
.rig-bed { fill-opacity: 0.55; }
.rig-body-edge { fill: #aab4c2; }
.rig-crease { stroke: #b6bfcc; stroke-width: 2; }
.rig-glass { fill: #4e5c72; fill-opacity: 0.92; }
.rig-flare { stroke: #333b47; stroke-width: 13; fill: none; stroke-linecap: round; }
.rig-bumper { fill: #333b47; }
.rig-lamp { fill: #ffd88a; }

.rig-tyre { fill: #181d25; stroke: #333b47; stroke-width: 3; }
.rig-tread { fill: none; stroke: #2f3742; stroke-width: 9; stroke-dasharray: 9 10; }
.rig-rim { fill: #8d97a6; }
.rig-hub { fill: #59626f; }
.rig-spoke { stroke: #59626f; stroke-width: 7; stroke-linecap: round; }
.rig-metal { fill: #4a5361; }
.rig-well { fill: #12161d; }
.rig-ground { stroke: #ffffff; stroke-opacity: 0.08; stroke-width: 3; }
.rig-slider { fill: #333b47; }
.rig-pipe { stroke: #4a5361; stroke-width: 7; fill: none; stroke-linecap: round; }
.rig-leaf { stroke: #4a5361; stroke-width: 7; fill: none; stroke-linecap: round; }
.rig-whip { stroke: #38414f; stroke-width: 5; fill: none; stroke-linecap: round; }

.rig-kit { fill: #39424f; }
.rig-kit-face { fill: #515c6d; }

.rig-rack { fill: #222933; }
.rig-rack-slat { stroke: #39424f; stroke-width: 3; fill: none; }

.rig-accent { fill: var(--color-brand); }
</style>
