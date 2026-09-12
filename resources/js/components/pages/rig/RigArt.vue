<script setup lang="ts">
/**
 * The rig, drawn in five slices so they can be pulled apart.
 *
 * Every slice shares one viewBox, so a point in the artwork means the same
 * thing in each of them. That is what lets a hotspot be stored as a plain
 * percentage of the box no matter which layer it belongs to.
 *
 * Proportions come off a 2021 double cab Tacoma: 127in wheelbase, 71in to
 * the cab roof, 33in tyres, at roughly 3.9px to the inch with the ground at
 * y=416. The frame carries slack above and below so the exploded layers have
 * somewhere to travel.
 *
 * The camper is a Tune M1L: a black hard sided base sitting a little proud
 * of the cab roof, with a canvas top that pops up above it. Those are two
 * different silhouettes, which is why the top is a state rather than a fixed
 * shape.
 */
withDefaults(defineProps<{ layer: string; popped?: boolean }>(), { popped: true })

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
const arch = (cx: number) => `L ${cx + 72} 356 C ${cx + 72} 240, ${cx - 72} 240, ${cx - 72} 356`
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

      <!-- KO2s on bronze wheels. -->
      <g v-for="wheel in WHEELS" :key="wheel.cx">
        <circle :cx="wheel.cx" :cy="wheel.cy" r="64" class="rig-tyre" />
        <circle :cx="wheel.cx" :cy="wheel.cy" r="54" class="rig-tread" />
        <circle :cx="wheel.cx" :cy="wheel.cy" r="34" class="rig-rim" />
        <circle :cx="wheel.cx" :cy="wheel.cy" r="13" class="rig-hub" />
        <g class="rig-spoke">
          <line
            v-for="n in 6"
            :key="n"
            :x1="wheel.cx"
            :y1="wheel.cy"
            :x2="wheel.cx + 28 * Math.cos(((n - 1) * 60 - 90) * (Math.PI / 180))"
            :y2="wheel.cy + 28 * Math.sin(((n - 1) * 60 - 90) * (Math.PI / 180))"
          />
        </g>
      </g>
    </g>

    <!-- Interior: the galley, the seats and the bed up in the top. -->
    <g v-if="layer === 'interior'">
      <!-- Bed platform. It only exists once the top is up. -->
      <g class="rig-bed-deck" :class="{ 'is-stowed': !popped }">
        <rect x="90" y="92" width="216" height="10" rx="3" class="rig-cabinet" />
        <rect x="94" y="74" width="208" height="19" rx="8" class="rig-bedding" />
        <path d="M118 76 L118 92 M158 74 L158 92 M198 76 L198 92 M238 74 L238 92" class="rig-plaid" />
      </g>

      <!-- Butcher block counter with the sink and the two burner. -->
      <rect x="86" y="156" width="224" height="9" rx="2" class="rig-counter" />
      <rect x="110" y="147" width="42" height="10" rx="2" class="rig-sink" />
      <rect x="170" y="145" width="60" height="12" rx="2" class="rig-hob" />
      <g class="rig-burner">
        <circle cx="186" cy="151" r="4" />
        <circle cx="214" cy="151" r="4" />
      </g>

      <!-- Black cabinets under it, with the blue lit knobs. -->
      <rect x="88" y="165" width="90" height="116" rx="4" class="rig-cabinet" />
      <rect x="94" y="172" width="78" height="32" rx="2" class="rig-cabinet-face" />
      <rect x="94" y="209" width="78" height="32" rx="2" class="rig-cabinet-face" />
      <rect x="94" y="246" width="78" height="29" rx="2" class="rig-cabinet-face" />
      <g class="rig-knob">
        <circle cx="184" cy="172" r="3.5" />
        <circle cx="184" cy="184" r="3.5" />
      </g>

      <rect x="186" y="165" width="56" height="116" rx="4" class="rig-cabinet" />
      <rect x="192" y="172" width="44" height="14" rx="2" class="rig-cabinet-face" />
      <rect x="192" y="196" width="44" height="40" rx="2" class="rig-fridge" />
      <rect x="199" y="212" width="30" height="6" rx="3" class="rig-accent" />

      <!-- Bench seats. The cushions are the only warm thing down here. -->
      <rect x="250" y="165" width="58" height="18" rx="6" class="rig-cushion" />
      <rect x="250" y="183" width="58" height="46" rx="4" class="rig-cabinet" />
      <rect x="250" y="235" width="58" height="46" rx="4" class="rig-cabinet" />
      <path d="M258 250 L286 250 M258 260 L300 260 M258 270 L276 270" class="rig-wire" />

      <!-- Strip lighting down the floor and along the roof line. -->
      <path d="M88 285 L308 285" class="rig-led" />
      <path
        class="rig-led rig-led-high"
        :class="{ 'is-stowed': !popped }"
        d="M92 108 L304 108"
      />
    </g>

    <!-- Body: cement grey, double cab, steel up front. -->
    <g v-if="layer === 'body'">
      <path
        :d="`M318 139 L340 131 L606 131 L620 139 L700 231 L722 236 L884 250 L900 286 L902 356
             ${arch(764)} L318 356 Z`"
        class="rig-body"
      />
      <path :d="`M74 209 L318 209 L318 356 ${arch(269)} L74 356 Z`" class="rig-body rig-bed" />
      <rect x="72" y="203" width="250" height="9" rx="4" class="rig-body-edge" />

      <path d="M760 243 Q812 230 862 246 L862 250 L760 248 Z" class="rig-body-edge" />
      <path d="M724 240 L878 252" class="rig-crease" />

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

      <!-- Ditch light on the A pillar. -->
      <rect x="612" y="136" width="16" height="10" rx="3" class="rig-steel" />
      <circle cx="620" cy="141" r="3.5" class="rig-amber" />

      <!-- Steel front bumper: winch, shackle, pods and the amber bar. -->
      <path d="M876 290 L930 290 L934 336 L924 356 L876 356 Z" class="rig-steel" />
      <rect x="884" y="300" width="40" height="9" rx="4" class="rig-amber" />
      <circle cx="900" cy="326" r="11" class="rig-winch" />
      <circle cx="900" cy="326" r="4" class="rig-steel" />
      <path d="M924 340 a7 7 0 1 0 0.1 0" class="rig-shackle" />
      <rect x="866" y="336" width="14" height="12" rx="3" class="rig-amber" />

      <!-- Rear steel and the tail light. -->
      <rect x="52" y="302" width="34" height="54" rx="6" class="rig-steel" />
      <path d="M862 256 L892 252 L894 280 L862 276 Z" class="rig-lamp" />
      <rect x="76" y="218" width="12" height="40" rx="3" class="rig-accent" />
    </g>

    <!-- Camper: black hard base, canvas top that goes up and down. -->
    <g v-if="layer === 'camper'">
      <!-- The hard sided base, standing a little proud of the cab. -->
      <path d="M74 209 L74 134 Q74 128 80 128 L312 128 Q318 128 318 134 L318 209 Z" class="rig-shell" />
      <rect x="70" y="126" width="252" height="9" rx="2" class="rig-shell-edge" />
      <rect x="72" y="200" width="250" height="10" rx="3" class="rig-shell-edge" />

      <g class="rig-seam">
        <path d="M150 136 L150 199" />
        <path d="M236 136 L236 199" />
      </g>

      <!-- Rear hatch, and the bottle on the back corner. -->
      <path d="M80 140 L142 140 L142 196 L80 196" class="rig-seam" />
      <rect x="52" y="146" width="20" height="44" rx="8" class="rig-bottle" />
      <rect x="56" y="140" width="12" height="8" rx="3" class="rig-shell-edge" />

      <!-- The awning, bagged along the side. -->
      <rect x="150" y="158" width="164" height="17" rx="8" class="rig-awning" />
      <rect x="150" y="158" width="13" height="17" rx="6" class="rig-accent" />

      <text x="196" y="192" class="rig-decal">WILDCARD OVERLAND</text>

      <!-- Canvas top. Collapses onto the base when it is not up. -->
      <g class="rig-canvas" :class="{ 'is-stowed': !popped }">
        <path class="rig-canvas-wall" d="M78 128 L78 72 L314 66 L314 128 Z" />
        <rect class="rig-canvas-pane" x="96" y="82" width="58" height="34" rx="3" />
        <rect class="rig-canvas-pane" x="166" y="80" width="58" height="34" rx="3" />
        <rect class="rig-canvas-pane" x="236" y="78" width="58" height="34" rx="3" />
        <path class="rig-strut" d="M88 128 L100 74 M304 128 L292 70" />
      </g>

      <!-- The hard lid the canvas hangs from. -->
      <rect
        class="rig-lid"
        :class="{ 'is-stowed': !popped }"
        x="70"
        y="58"
        width="252"
        height="14"
        rx="3"
      />
    </g>

    <!-- Roof: the rack on the lid, and the bar that overhangs the cab. -->
    <g v-if="layer === 'roof'">
      <g>
        <rect x="78" y="46" width="248" height="11" rx="2" class="rig-rack" />
        <g class="rig-rack-slat">
          <line v-for="n in 11" :key="n" :x1="88 + n * 20" y1="47" :x2="88 + n * 20" y2="56" />
        </g>

        <rect x="120" y="36" width="150" height="10" rx="2" class="rig-solar" />
        <g class="rig-solar-cell">
          <line v-for="n in 5" :key="n" :x1="120 + n * 25" y1="37" :x2="120 + n * 25" y2="45" />
        </g>

        <!-- Amber bar on the front edge, reaching out over the windscreen. -->
        <rect x="326" y="44" width="132" height="13" rx="3" class="rig-steel" />
        <rect x="332" y="47" width="120" height="7" rx="3" class="rig-amber" />

        <rect x="86" y="30" width="34" height="16" rx="3" class="rig-rack" />
        <rect x="91" y="26" width="24" height="5" rx="2" class="rig-shell-edge" />

        <path d="M322 46 L314 12" class="rig-whip" />
        <circle cx="313" cy="10" r="5" class="rig-accent" />
      </g>
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

/* Cement grey, which is most of what you see of the truck itself. */
.rig-body { fill: #878f9a; }
.rig-bed { fill-opacity: 0.42; }
.rig-body-edge { fill: #656e79; }
.rig-crease { stroke: #7d8792; stroke-width: 2; fill: none; }
.rig-glass { fill: #2f3946; fill-opacity: 0.95; }
.rig-flare { stroke: #1d2127; stroke-width: 13; fill: none; stroke-linecap: round; }
.rig-steel { fill: #23272d; }
.rig-winch { fill: #3a4149; }
.rig-shackle { stroke: var(--color-brand); stroke-width: 4; fill: none; }
.rig-lamp { fill: #ffd88a; }
.rig-amber { fill: #f0a12e; }

/* The camper base is flat black; the top is the orange everyone recognises. */
.rig-shell { fill: #16191e; fill-opacity: 0.9; }
.rig-shell-edge { fill: #2b3037; }
.rig-seam { stroke: #363c44; stroke-width: 2; fill: none; }
.rig-awning { fill: #3f453c; }
.rig-bottle { fill: #b9bec5; }
.rig-lid { fill: #1b1f25; transition: y 700ms cubic-bezier(0.22, 1, 0.36, 1); }

.rig-canvas-wall { fill: #d98f3c; fill-opacity: 0.58; }
.rig-canvas-pane { fill: #4a2f16; fill-opacity: 0.85; transition: height 700ms cubic-bezier(0.22, 1, 0.36, 1); }
.rig-strut { stroke: #2b3037; stroke-width: 2.5; fill: none; }
.rig-canvas {
  transform-box: fill-box;
  transform-origin: 50% 100%;
  transition: transform 700ms cubic-bezier(0.22, 1, 0.36, 1);
}
.rig-canvas.is-stowed { transform: scaleY(0.09); }
.rig-canvas.is-stowed .rig-canvas-pane { height: 0; }
.rig-lid.is-stowed { y: 116px; }

.rig-decal {
  fill: var(--color-brand);
  font-family: inherit;
  font-size: 11px;
  font-weight: 800;
  letter-spacing: 0.09em;
  text-anchor: middle;
}

.rig-tyre { fill: #16191e; stroke: #2b3037; stroke-width: 3; }
.rig-tread { fill: none; stroke: #262c34; stroke-width: 9; stroke-dasharray: 9 10; }
.rig-rim { fill: #a5843f; }
.rig-hub { fill: #6d5828; }
.rig-spoke { stroke: #6d5828; stroke-width: 7; stroke-linecap: round; }
.rig-metal { fill: #3f464f; }
.rig-slider { fill: #23272d; }
.rig-pipe { stroke: #3f464f; stroke-width: 7; fill: none; stroke-linecap: round; }
.rig-leaf { stroke: #3f464f; stroke-width: 7; fill: none; stroke-linecap: round; }
.rig-whip { stroke: #2b3037; stroke-width: 5; fill: none; stroke-linecap: round; }
.rig-well { fill: #101318; }
.rig-ground { stroke: #ffffff; stroke-opacity: 0.08; stroke-width: 3; }

/* Inside: black cabinets, a wood counter, orange cushions. */
.rig-cabinet { fill: #1e2229; stroke: #383f48; stroke-width: 1.5; }
.rig-cabinet-face { fill: #2a3038; }
.rig-counter { fill: #ab7740; }
.rig-sink { fill: #11141a; stroke: #6f7883; stroke-width: 1.5; }
.rig-hob { fill: #0e1116; stroke: #383f48; stroke-width: 1.5; }
.rig-burner { fill: #2a3038; }
.rig-knob { fill: #3d7fd6; }
.rig-fridge { fill: #2a3038; }
.rig-cushion { fill: #e0762e; }
.rig-bedding { fill: #59606c; }
.rig-plaid { stroke: #7d8792; stroke-width: 2; fill: none; }
.rig-wire { stroke: var(--color-brand); stroke-width: 3; stroke-linecap: round; fill: none; opacity: 0.8; }
.rig-led { stroke: #f0a12e; stroke-width: 3; stroke-linecap: round; fill: none; opacity: 0.85; }

/* Anything that lives up in the top goes with it. */
.rig-bed-deck,
.rig-led-high {
  transition: transform 700ms cubic-bezier(0.22, 1, 0.36, 1);
}
.rig-bed-deck.is-stowed,
.rig-led-high.is-stowed {
  transform: translateY(52px);
  opacity: 0;
}

.rig-rack { fill: #1b1f25; }
.rig-rack-slat { stroke: #2f353d; stroke-width: 3; }
.rig-solar { fill: #16223a; }
.rig-solar-cell { stroke: #2c4670; stroke-width: 2; }

.rig-accent { fill: var(--color-brand); }

@media (prefers-reduced-motion: reduce) {
  .rig-canvas,
  .rig-lid,
  .rig-bed-deck,
  .rig-led-high,
  .rig-canvas-pane {
    transition-duration: 1ms;
  }
}
</style>
