<script setup lang="ts">
/**
 * The rig, drawn in five slices so they can be pulled apart.
 *
 * Every slice shares one viewBox, so a point in the artwork means the same
 * thing in each of them. That is what lets a hotspot be stored as a plain
 * percentage of the box no matter which layer it belongs to.
 *
 * Set out from the truck rather than sketched. A 2021 double cab short bed
 * on 33s with a small lift, at 2.779 units to the inch with the ground at
 * y=452 and the tyre 104 across:
 *
 *   wheelbase 127in   bed 60.5in   front overhang 33in   rear overhang 52in
 *
 * Heights are taken as multiples of the tyre off a side-on photograph,
 * because the brochure figures do not know about the lift and a truck drawn
 * to its unladen numbers comes out looking flattened:
 *
 *   bed rail 1.77   cab roof 2.37   camper box 2.54   popped 3.64
 *
 * The cab is the other thing worth measuring rather than guessing. On a
 * double cab the roof is long: it runs about three tenths of the truck's
 * whole length, and the screen only a further eighth. Drawn shorter than
 * that it stops being a Tacoma and starts being a Defender.
 *
 * The camper is a Tune M1L. Two things drive its shape: the hard box sits on
 * the bed and barely clears the cab, and the canvas above it is far longer
 * than the box, reaching forward over the cab roof but stopping short of the
 * screen. Drawing the top as though it ended at the bulkhead is what made
 * the old one read as a topper.
 */
withDefaults(defineProps<{ layer: string; popped?: boolean }>(), { popped: true })

const GROUND = 452
const AXLE = 400
const REAR_AXLE = 344
const FRONT_AXLE = 698

const WHEELS = [
  { cx: REAR_AXLE, cy: AXLE },
  { cx: FRONT_AXLE, cy: AXLE },
]

/**
 * A wheel arch: along the lower edge, up over the tyre, back down. A cubic
 * rather than an elliptical arc, so the tangents at both ends are vertical,
 * which is what makes it read as a fender rather than a bite. It peaks about
 * four inches above the tyre, which is the gap a small lift leaves.
 *
 * Both outlines are drawn front to back, so the curve runs right to left;
 * reversing it closes a lens across the opening and fills it in.
 */
const arch = (cx: number) => `L ${cx + 58} 391 C ${cx + 58} 318, ${cx - 58} 318, ${cx - 58} 391`

/**
 * The body, in one outline.
 *
 * Clockwise from the back of the bed: forward along the rail, up the back of
 * the cab, over the long roof, down the screen, along the hood to the blunt
 * nose, then back underneath through both arches.
 */
const BODY = [
  'M 200 268',
  'L 368 268',
  'L 372 218',
  'Q 374 198, 392 198',
  'L 566 198',
  'Q 580 198, 588 206',
  'L 646 246',
  'L 668 248',
  'L 748 260',
  'Q 764 262, 770 274',
  'L 778 300',
  'L 790 306',
  'L 790 391',
  arch(FRONT_AXLE),
  'L 402 391',
  arch(REAR_AXLE),
  'L 200 391',
  'Z',
].join(' ')
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
      <path d="M286 391 C286 318, 402 318, 402 391 Z" class="rig-well" />
      <path d="M640 391 C640 318, 756 318, 756 391 Z" class="rig-well" />
      <path :d="`M150 ${GROUND} L850 ${GROUND}`" class="rig-ground" />

      <!-- Live axle and leaf packs at the back, coilovers up front. -->
      <rect x="272" y="392" width="144" height="12" rx="6" class="rig-metal" />
      <path d="M278 385 Q344 372 410 385" class="rig-leaf" />
      <path d="M632 384 L764 384 L754 410 L642 410 Z" class="rig-metal" />
      <path d="M656 352 L656 390 M740 352 L740 390" class="rig-leaf" />

      <!-- Exhaust, out to the back of the bed. -->
      <path d="M624 410 L258 410 L228 416" class="rig-pipe" />
      <rect x="210" y="410" width="20" height="12" rx="4" class="rig-metal" />

      <!-- Rock sliders between the arches. -->
      <rect x="406" y="393" width="234" height="16" rx="8" class="rig-slider" />
      <rect x="436" y="381" width="12" height="16" rx="3" class="rig-slider" />
      <rect x="598" y="381" width="12" height="16" rx="3" class="rig-slider" />

      <!-- Skid plate under the nose. -->
      <path d="M758 394 L794 394 L790 412 L762 412 Z" class="rig-metal" />

      <!-- KO2s on bronze wheels. -->
      <g v-for="wheel in WHEELS" :key="wheel.cx">
        <circle :cx="wheel.cx" :cy="wheel.cy" r="52" class="rig-tyre" />
        <circle :cx="wheel.cx" :cy="wheel.cy" r="43" class="rig-tread" />
        <circle :cx="wheel.cx" :cy="wheel.cy" r="28" class="rig-rim" />
        <circle :cx="wheel.cx" :cy="wheel.cy" r="10" class="rig-hub" />
        <g class="rig-spoke">
          <line
            v-for="n in 6"
            :key="n"
            :x1="wheel.cx"
            :y1="wheel.cy"
            :x2="wheel.cx + 23 * Math.cos(((n - 1) * 60 - 90) * (Math.PI / 180))"
            :y2="wheel.cy + 23 * Math.sin(((n - 1) * 60 - 90) * (Math.PI / 180))"
          />
        </g>
      </g>
    </g>

    <!-- Interior: the galley, the seats and the bed up in the top. -->
    <g v-if="layer === 'interior'">
      <!--
        The bed is up in the canvas, which reaches over the cab, so it runs
        much further forward than the galley underneath it.
      -->
      <g class="rig-bed-deck" :class="{ 'is-stowed': !popped }">
        <rect x="188" y="150" width="342" height="9" rx="3" class="rig-cabinet" />
        <rect x="192" y="133" width="334" height="18" rx="7" class="rig-bedding" />
        <path d="M224 135 L224 150 M270 133 L270 150 M316 135 L316 150 M362 133 L362 150" class="rig-plaid" />
      </g>

      <!-- Butcher block counter with the sink and the two burner. -->
      <rect x="184" y="222" width="188" height="8" rx="2" class="rig-counter" />
      <rect x="200" y="214" width="36" height="9" rx="2" class="rig-sink" />
      <rect x="252" y="212" width="50" height="11" rx="2" class="rig-hob" />
      <g class="rig-burner">
        <circle cx="264" cy="218" r="3.5" />
        <circle cx="290" cy="218" r="3.5" />
      </g>

      <!-- Black cabinets under it, with the blue lit knobs. -->
      <rect x="186" y="230" width="76" height="34" rx="4" class="rig-cabinet" />
      <rect x="191" y="235" width="66" height="12" rx="2" class="rig-cabinet-face" />
      <rect x="191" y="249" width="66" height="11" rx="2" class="rig-cabinet-face" />
      <g class="rig-knob">
        <circle cx="268" cy="236" r="3" />
        <circle cx="268" cy="245" r="3" />
      </g>

      <rect x="276" y="230" width="48" height="34" rx="4" class="rig-cabinet" />
      <rect x="281" y="235" width="38" height="24" rx="2" class="rig-fridge" />
      <rect x="287" y="244" width="26" height="5" rx="2.5" class="rig-accent" />

      <!-- Bench seat. The cushion is the only warm thing down here. -->
      <rect x="330" y="230" width="42" height="13" rx="5" class="rig-cushion" />
      <rect x="330" y="243" width="42" height="21" rx="4" class="rig-cabinet" />
      <path d="M336 251 L360 251 M336 258 L366 258" class="rig-wire" />

      <!-- Strip lighting down the floor and along the roof line. -->
      <path d="M186 266 L372 266" class="rig-led" />
      <path
        class="rig-led rig-led-high"
        :class="{ 'is-stowed': !popped }"
        d="M192 162 L526 162"
      />
    </g>

    <!-- Body: cement grey, double cab, steel up front. -->
    <g v-if="layer === 'body'">
      <path :d="BODY" class="rig-body" />

      <!-- Bed side, a shade down from the cab so the two read apart. -->
      <rect x="200" y="268" width="168" height="44" class="rig-body rig-bed" />
      <rect x="198" y="262" width="174" height="9" rx="4" class="rig-body-edge" />

      <!-- Glass. The rear door is squarer, the front one leans with the pillar. -->
      <path d="M384 206 L496 206 L496 246 L384 246 Z" class="rig-glass" />
      <path d="M506 206 L608 206 L634 246 L506 246 Z" class="rig-glass" />
      <path d="M592 212 L642 244 L618 244 L580 218 Z" class="rig-glass" />

      <!-- Door shuts, beltline and the crease down the flank. -->
      <path d="M500 203 L500 368" class="rig-crease" />
      <path d="M372 222 L372 352" class="rig-crease" />
      <path d="M378 252 L646 252" class="rig-crease" />
      <path d="M208 292 L362 292" class="rig-crease" />

      <rect x="448" y="262" width="30" height="7" rx="3.5" class="rig-body-edge" />
      <rect x="558" y="260" width="30" height="7" rx="3.5" class="rig-body-edge" />

      <!-- The bulge down the middle of the hood. -->
      <path d="M676 258 Q714 250 752 262" class="rig-crease" />

      <!-- Mirror, on its stalk off the base of the A pillar. -->
      <path d="M638 238 L648 236" class="rig-crease" />
      <rect x="646" y="229" width="13" height="17" rx="4" class="rig-steel" />

      <!-- Flares, which is most of what you notice from the side. -->
      <path d="M286 391 C286 318, 402 318, 402 391" class="rig-flare" />
      <path d="M640 391 C640 318, 756 318, 756 391" class="rig-flare" />

      <!-- Ditch light on the A pillar. -->
      <rect x="596" y="203" width="15" height="9" rx="3" class="rig-steel" />
      <circle cx="603.5" cy="207.5" r="3" class="rig-amber" />

      <!-- Steel front bumper: winch, shackle, pods and the amber bar. -->
      <path d="M764 298 L818 300 L822 352 L812 391 L764 391 Z" class="rig-steel" />
      <rect x="772" y="310" width="42" height="9" rx="4" class="rig-amber" />
      <circle cx="790" cy="344" r="10" class="rig-winch" />
      <circle cx="790" cy="344" r="3.5" class="rig-steel" />
      <path d="M812 364 a6 6 0 1 0 0.1 0" class="rig-shackle" />

      <!-- Headlight, and the steel and lamp at the back. -->
      <path d="M748 268 L774 272 L776 288 L749 284 Z" class="rig-lamp" />
      <rect x="178" y="306" width="26" height="56" rx="6" class="rig-steel" />
      <rect x="202" y="286" width="8" height="20" rx="2" class="rig-lamp" />
    </g>

    <!-- Camper: black hard base, canvas top that goes up and down. -->
    <g v-if="layer === 'camper'">
      <!-- The hard box, sat on the bed and barely clearing the cab. -->
      <path d="M176 268 L176 190 Q176 182 184 182 L368 182 Q376 182 376 190 L376 268 Z" class="rig-shell" />
      <rect x="172" y="179" width="208" height="9" rx="2" class="rig-shell-edge" />
      <rect x="174" y="260" width="204" height="10" rx="3" class="rig-shell-edge" />

      <g class="rig-seam">
        <path d="M232 192 L232 258" />
        <path d="M306 192 L306 258" />
      </g>

      <!-- Rear hatch, and the bottle on the back corner. -->
      <path d="M182 196 L228 196 L228 254 L182 254" class="rig-seam" />
      <rect x="154" y="206" width="21" height="46" rx="9" class="rig-bottle" />
      <rect x="158" y="194" width="13" height="8" rx="3" class="rig-shell-edge" />

      <text x="276" y="250" class="rig-decal">WILDCARD OVERLAND</text>

      <!--
        Canvas. It is longer than the box and reaches out over the cab, but
        stops short of the screen, which is the whole shape of this camper.
        Collapses onto the box when it is not up.
      -->
      <g class="rig-canvas" :class="{ 'is-stowed': !popped }">
        <path class="rig-canvas-wall" d="M180 182 L176 84 L560 80 L556 182 Z" />
        <rect class="rig-canvas-pane" x="196" y="104" width="74" height="46" rx="3" />
        <rect class="rig-canvas-pane" x="280" y="103" width="74" height="46" rx="3" />
        <rect class="rig-canvas-pane" x="364" y="102" width="74" height="46" rx="3" />
        <rect class="rig-canvas-pane" x="448" y="101" width="74" height="46" rx="3" />
        <path class="rig-strut" d="M186 182 L196 88 M550 182 L542 86" />
      </g>

      <!-- The awning, bagged along the canvas rail, out over the cab. -->
      <rect x="254" y="164" width="286" height="17" rx="8" class="rig-awning" />
      <rect x="254" y="164" width="13" height="17" rx="7" class="rig-accent" />

      <!-- The hard lid the canvas hangs from. -->
      <rect
        class="rig-lid"
        :class="{ 'is-stowed': !popped }"
        x="170"
        y="68"
        width="398"
        height="15"
        rx="3"
      />
    </g>

    <!-- Roof: the rack on the lid, and the bar that sits on the cab. -->
    <g v-if="layer === 'roof'">
      <g>
        <rect x="182" y="54" width="374" height="12" rx="2" class="rig-rack" />
        <g class="rig-rack-slat">
          <line v-for="n in 17" :key="n" :x1="192 + n * 20" y1="55" :x2="192 + n * 20" y2="65" />
        </g>

        <rect x="240" y="42" width="190" height="12" rx="2" class="rig-solar" />
        <g class="rig-solar-cell">
          <line v-for="n in 6" :key="n" :x1="240 + n * 27" y1="43" :x2="240 + n * 27" y2="53" />
        </g>

        <!-- Recovery boards on the rack. -->
        <rect x="196" y="36" width="36" height="18" rx="3" class="rig-rack" />
        <rect x="201" y="32" width="26" height="5" rx="2" class="rig-shell-edge" />

        <path d="M554 54 L546 22" class="rig-whip" />
        <circle cx="545" cy="20" r="4.5" class="rig-accent" />
      </g>

      <!--
        The amber bar is bolted to the cab, not to the rack, so it has to
        stay put while the rest of this layer drops. The layer is what moves,
        so the only way to hold something still inside it is to move it back.
      -->
      <g class="rig-roof-fixed" :class="{ 'is-stowed': !popped }">
        <path d="M418 198 L418 191 M518 198 L518 191" class="rig-whip" />
        <rect x="410" y="179" width="116" height="12" rx="3" class="rig-steel" />
        <rect x="416" y="182" width="104" height="6" rx="3" class="rig-amber" />
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
.rig-awning { fill: #2c3036; }
.rig-bottle { fill: #b9bec5; }
.rig-lid { fill: #1b1f25; transition: y 700ms cubic-bezier(0.22, 1, 0.36, 1); }

.rig-canvas-wall { fill: #e08e33; }
.rig-canvas-pane { fill: #2a2016; fill-opacity: 0.88; transition: height 700ms cubic-bezier(0.22, 1, 0.36, 1); }
.rig-strut { stroke: #8a5a22; stroke-width: 2.5; fill: none; }
.rig-canvas {
  transform-box: fill-box;
  transform-origin: 50% 100%;
  transition: transform 700ms cubic-bezier(0.22, 1, 0.36, 1);
}
.rig-canvas.is-stowed { transform: scaleY(0.09); }
.rig-canvas.is-stowed .rig-canvas-pane { height: 0; }
.rig-lid.is-stowed { y: 166px; }

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

/* Cancels the layer's own drop, for the parts bolted to the truck. */
.rig-roof-fixed { transition: transform 700ms cubic-bezier(0.22, 1, 0.36, 1); }
.rig-roof-fixed.is-stowed { transform: translateY(-98px); }

.rig-rack { fill: #1b1f25; }
.rig-rack-slat { stroke: #2f353d; stroke-width: 3; }
.rig-solar { fill: #16223a; }
.rig-solar-cell { stroke: #2c4670; stroke-width: 2; }

.rig-accent { fill: var(--color-brand); }

@media (prefers-reduced-motion: reduce) {
  .rig-canvas,
  .rig-lid,
  .rig-roof-fixed,
  .rig-bed-deck,
  .rig-led-high,
  .rig-canvas-pane {
    transition-duration: 1ms;
  }
}
</style>
