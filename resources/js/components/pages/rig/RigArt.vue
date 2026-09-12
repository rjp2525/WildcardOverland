<script setup lang="ts">
/**
 * The rig, drawn in five slices so they can be pulled apart.
 *
 * Every slice shares one viewBox, so a point in the artwork means the same
 * thing in each of them. That is what lets a hotspot be stored as a plain
 * percentage of the box no matter which layer it belongs to.
 *
 * Proportions come off a 2021 double cab Tacoma: 127in wheelbase, 71in to
 * the cab roof, 32in tyres, at roughly 3.9px to the inch with the ground at
 * y=416. The camper adds about another foot above the cab. The frame carries
 * slack above and below so the exploded layers have somewhere to travel.
 */
defineProps<{ layer: string }>()

const WHEELS = [
  { cx: 269, cy: 354 },
  { cx: 764, cy: 354 },
]

/**
 * A wheel arch: along the lower edge, up over the tyre, back down. A cubic
 * rather than an elliptical arc, so the tangents at both ends are vertical,
 * which is what makes it read as a fender rather than a bite.
 *
 * Both outlines are drawn front to back, so the curve runs right to left;
 * reversing it closes a lens across the opening and fills it in.
 */
const BASE = 356

const arch = (cx: number) =>
  `L ${cx + 72} ${BASE} C ${cx + 72} 240, ${cx - 72} 240, ${cx - 72} ${BASE}`
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
      <path d="M197 356 C197 240, 341 240, 341 356 Z" class="rig-well" />
      <path d="M692 356 C692 240, 836 240, 836 356 Z" class="rig-well" />
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
            v-for="n in 6"
            :key="n"
            :x1="wheel.cx"
            :y1="wheel.cy"
            :x2="wheel.cx + 27 * Math.cos(((n - 1) * 60 - 90) * (Math.PI / 180))"
            :y2="wheel.cy + 27 * Math.sin(((n - 1) * 60 - 90) * (Math.PI / 180))"
          />
        </g>
      </g>
    </g>

    <!-- Interior: the build inside the camper, seen through the ghosted side. -->
    <g v-if="layer === 'interior'">
      <!-- Sleeping platform, up high, with the mattress on it. -->
      <rect x="86" y="140" width="222" height="15" rx="4" class="rig-kit" />
      <rect x="91" y="121" width="212" height="20" rx="9" class="rig-soft" />

      <!-- Galley below it: drawers, fridge, then water and power. -->
      <rect x="88" y="163" width="86" height="118" rx="5" class="rig-kit" />
      <rect x="94" y="171" width="74" height="33" rx="3" class="rig-kit-face" />
      <rect x="94" y="210" width="74" height="33" rx="3" class="rig-kit-face" />
      <rect x="94" y="249" width="74" height="25" rx="3" class="rig-kit-face" />
      <rect x="115" y="184" width="32" height="7" rx="3" class="rig-accent" />
      <rect x="115" y="223" width="32" height="7" rx="3" class="rig-accent" />

      <rect x="182" y="163" width="60" height="118" rx="5" class="rig-kit" />
      <rect x="182" y="163" width="60" height="18" rx="5" class="rig-kit-face" />
      <rect x="192" y="200" width="40" height="9" rx="4" class="rig-accent" />
      <rect x="192" y="220" width="26" height="6" rx="3" class="rig-kit-face" />

      <rect x="250" y="163" width="58" height="56" rx="5" class="rig-kit" />
      <rect x="257" y="174" width="44" height="8" rx="4" class="rig-accent" />
      <rect x="257" y="188" width="30" height="6" rx="3" class="rig-kit-face" />

      <rect x="250" y="227" width="58" height="54" rx="5" class="rig-kit" />
      <path d="M260 241 L284 241 M260 253 L298 253 M260 265 L276 265" class="rig-wire" />

      <!-- Counter run along the top of the galley. -->
      <rect x="86" y="156" width="224" height="7" rx="3" class="rig-counter" />
    </g>

    <!-- Body: the truck itself. Bed sides ghosted so the build reads. -->
    <g v-if="layer === 'body'">
      <!-- Cab, hood and front, drawn as one silhouette. -->
      <path
        :d="`M318 139 L340 131 L606 131 L620 139 L700 231 L722 236 L884 250 L900 286 L902 356
             ${arch(764)} L318 356 Z`"
        class="rig-body"
      />

      <!-- Bed sides. The camper sits on top of these. -->
      <path
        :d="`M74 209 L318 209 L318 356 ${arch(269)} L74 356 Z`"
        class="rig-body rig-bed"
      />
      <rect x="72" y="203" width="250" height="9" rx="4" class="rig-body-edge" />

      <!-- Hood scoop and cowl, the giveaway on a TRD. -->
      <path d="M760 243 Q812 230 862 246 L862 250 L760 248 Z" class="rig-body-edge" />
      <path d="M724 240 L878 252" class="rig-crease" />

      <!-- Double cab: two doors, and the beltline kick at the back. -->
      <path d="M472 232 L472 352" class="rig-crease" />
      <path d="M330 232 L330 352" class="rig-crease" />
      <path d="M330 228 L700 228" class="rig-crease" />

      <path d="M346 148 L466 148 L466 221 L346 221 Z" class="rig-glass" />
      <path d="M478 148 L598 148 L610 221 L478 221 Z" class="rig-glass" />
      <path d="M624 143 L646 143 L698 221 L666 221 Z" class="rig-glass" />
      <path d="M630 232 L606 240 L609 252 L633 244 Z" class="rig-body-edge" />

      <rect x="398" y="238" width="32" height="8" rx="4" class="rig-body-edge" />
      <rect x="530" y="238" width="32" height="8" rx="4" class="rig-body-edge" />

      <path d="M197 356 C197 240, 341 240, 341 356" class="rig-flare" />
      <path d="M692 356 C692 240, 836 240, 836 356" class="rig-flare" />

      <!-- Hybrid front bumper with a hoop, and the rear steel. -->
      <rect x="880" y="296" width="46" height="62" rx="7" class="rig-bumper" />
      <path d="M888 296 L888 258 Q888 250 898 250" class="rig-hoop" />
      <rect x="52" y="302" width="34" height="54" rx="6" class="rig-bumper" />

      <path d="M862 256 L892 252 L894 280 L862 276 Z" class="rig-lamp" />
      <rect x="76" y="218" width="12" height="40" rx="3" class="rig-accent" />
    </g>

    <!-- Camper: the hard side shell on the bed. -->
    <g v-if="layer === 'camper'">
      <path d="M74 209 L74 104 Q74 96 84 96 L308 96 Q318 96 318 106 L318 209 Z" class="rig-shell" />

      <!-- Roof cap and the lower rail, the two hard lines on it. -->
      <path d="M70 96 L322 96 L322 106 L70 106 Z" class="rig-shell-edge" />
      <rect x="72" y="200" width="250" height="10" rx="3" class="rig-shell-edge" />

      <!-- Riveted panel seams. -->
      <g class="rig-seam">
        <path d="M152 108 L152 199" />
        <path d="M232 108 L232 199" />
      </g>

      <!-- The big side window, and the door with its own. -->
      <rect x="164" y="122" width="60" height="62" rx="6" class="rig-shell-glass" />
      <rect x="240" y="116" width="70" height="82" rx="5" class="rig-shell-door" />
      <rect x="248" y="124" width="54" height="42" rx="4" class="rig-shell-glass" />
      <rect x="292" y="178" width="12" height="6" rx="3" class="rig-accent" />

      <!-- Rear hatch, hinged at the top. -->
      <path d="M78 112 L142 112 L142 196 L78 196" class="rig-seam" />
      <rect x="86" y="150" width="8" height="18" rx="4" class="rig-accent" />
    </g>

    <!-- Roof: rack and everything bolted to it. -->
    <g v-if="layer === 'roof'">
      <rect x="78" y="80" width="240" height="12" rx="3" class="rig-rack" />
      <g class="rig-rack-slat">
        <line v-for="n in 11" :key="n" :x1="86 + n * 20" y1="81" :x2="86 + n * 20" y2="91" />
      </g>

      <!-- Solar, flat on the rack. -->
      <rect x="126" y="70" width="140" height="11" rx="2" class="rig-solar" />
      <g class="rig-solar-cell">
        <line v-for="n in 5" :key="n" :x1="126 + n * 23" y1="71" :x2="126 + n * 23" y2="80" />
      </g>

      <!-- Awning down the side, roof fan at the back. -->
      <rect x="274" y="66" width="46" height="16" rx="8" class="rig-rack" />
      <rect x="274" y="66" width="12" height="16" rx="6" class="rig-accent" />
      <rect x="86" y="64" width="34" height="17" rx="3" class="rig-rack" />
      <rect x="91" y="60" width="24" height="5" rx="2" class="rig-shell-edge" />

      <!-- Light bar across the cab roof. -->
      <rect x="414" y="119" width="122" height="12" rx="3" class="rig-rack" />
      <rect x="421" y="122" width="108" height="6" rx="3" class="rig-lamp" />

      <path d="M314 96 L304 52" class="rig-whip" />
      <circle cx="303" cy="50" r="5" class="rig-accent" />
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
.rig-bed { fill-opacity: 0.4; }
.rig-body-edge { fill: #aab4c2; }
.rig-crease { stroke: #b6bfcc; stroke-width: 2; fill: none; }
.rig-glass { fill: #4e5c72; fill-opacity: 0.92; }
.rig-flare { stroke: #333b47; stroke-width: 13; fill: none; stroke-linecap: round; }
.rig-bumper { fill: #333b47; }
.rig-hoop { stroke: #333b47; stroke-width: 9; fill: none; stroke-linecap: round; }
.rig-lamp { fill: #ffd88a; }

/* The camper is aluminium, so it reads cooler and flatter than the truck. */
.rig-shell { fill: #cfd7e0; fill-opacity: 0.38; stroke: #9aa5b4; stroke-width: 2.5; }
.rig-shell-edge { fill: #9aa5b4; }
.rig-shell-glass { fill: #46536a; fill-opacity: 0.9; }
.rig-shell-door { fill: #b9c3d0; fill-opacity: 0.55; stroke: #8d97a6; stroke-width: 2; }
.rig-seam { stroke: #9aa5b4; stroke-width: 2; fill: none; }

.rig-tyre { fill: #181d25; stroke: #333b47; stroke-width: 3; }
.rig-tread { fill: none; stroke: #2f3742; stroke-width: 9; stroke-dasharray: 9 10; }
.rig-rim { fill: #8d97a6; }
.rig-hub { fill: #59626f; }
.rig-spoke { stroke: #59626f; stroke-width: 7; stroke-linecap: round; }
.rig-metal { fill: #4a5361; }
.rig-slider { fill: #333b47; }
.rig-pipe { stroke: #4a5361; stroke-width: 7; fill: none; stroke-linecap: round; }
.rig-leaf { stroke: #4a5361; stroke-width: 7; fill: none; stroke-linecap: round; }
.rig-whip { stroke: #38414f; stroke-width: 5; fill: none; stroke-linecap: round; }
.rig-well { fill: #12161d; }
.rig-ground { stroke: #ffffff; stroke-opacity: 0.08; stroke-width: 3; }

.rig-kit { fill: #262e3a; stroke: #47525f; stroke-width: 1.5; }
.rig-kit-face { fill: #3f4a5a; }
.rig-soft { fill: #7a8598; }
.rig-counter { fill: #97a2b1; }
.rig-wire { stroke: var(--color-brand); stroke-width: 3; stroke-linecap: round; fill: none; opacity: 0.8; }

.rig-rack { fill: #222933; }
.rig-rack-slat { stroke: #39424f; stroke-width: 3; }
.rig-solar { fill: #1b2a44; }
.rig-solar-cell { stroke: #33507d; stroke-width: 2; }

.rig-accent { fill: var(--color-brand); }
</style>
