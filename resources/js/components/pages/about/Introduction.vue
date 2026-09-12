<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import { ArrowRight } from 'lucide-vue-next'
import { useRoute } from '@/lib/route'

const route = useRoute()

defineProps<{
  /** Real counts, so the panel cannot drift from what is on the rig page. */
  parts: number
  years: number
}>()
</script>

<template>
  <div class="relative overflow-hidden">
    <div
      class="absolute left-0 top-0 h-full w-full bg-transparent bg-brand-radial-gradient opacity-15 transition-gradient-custom z-0"
    />

    <!--
      A grid rather than justify-between: the columns then hold their
      proportions whatever the copy does, instead of the text shrinking away
      from the photograph every time it is edited.
    -->
    <div class="container relative z-10 grid items-end gap-8 lg:grid-cols-[1.15fr_0.85fr]">
      <div class="flex flex-col justify-center gap-4 py-8 lg:py-16">
        <div class="mb-1 flex items-center">
          <span
            class="divider-horizontal-after text-2xl font-bold uppercase tracking-wide text-brand"
          >Hi! I'm Reno, aka Wildcard Overland</span>
        </div>

        <p class="text-lg leading-relaxed text-black dark:text-white/90">
          This started with one camping trip a couple of years back and got out of hand
          from there. Now everything I need lives in the back of the truck, and the plan
          is usually whichever road looks more interesting.
        </p>

        <p class="text-black dark:text-white/90">
          Maya comes with me. Setting up camp deep in the trees or just driving to the
          next spot, she is equally pleased about it, and that is a lot of pleased.
          Winter is the other half of this. I take the Tacoma up near the resorts, ride
          all day and come back to camp rather than to a room.
        </p>

        <p class="text-black dark:text-white/90">
          The truck is a 2021 Tacoma TRD Off Road and I built it out myself. Tune M1L
          camper on the back, solar on the roof, a galley in there I put together.
        </p>

        <p class="text-black dark:text-white/90">
          What is on here: write-ups of where the truck has been with the campsites
          listed, the food that actually got cooked out there, and the whole build
          broken down part by part. Exact coordinates for the quiet spots sit behind a
          login. Everything else is open.
        </p>

        <!-- Real numbers, pulled from the parts list rather than typed here. -->
        <dl class="mt-2 flex flex-wrap gap-x-10 gap-y-4">
          <div>
            <dt class="text-xs font-bold uppercase tracking-widest text-slate-500 dark:text-white/50">
              The truck
            </dt>
            <dd class="mt-1 font-bold text-black dark:text-white">2021 Tacoma TRD Off Road</dd>
          </div>
          <div>
            <dt class="text-xs font-bold uppercase tracking-widest text-slate-500 dark:text-white/50">
              The camper
            </dt>
            <dd class="mt-1 font-bold text-black dark:text-white">Tune Outdoor M1L</dd>
          </div>
          <div v-if="parts">
            <dt class="text-xs font-bold uppercase tracking-widest text-slate-500 dark:text-white/50">
              Bolted on
            </dt>
            <dd class="mt-1 font-bold text-black dark:text-white">
              {{ parts }} {{ parts === 1 ? 'part' : 'parts' }}<span v-if="years > 0">, {{ years }} {{ years === 1 ? 'year' : 'years' }}</span>
            </dd>
          </div>
        </dl>

        <Link
          :href="route('rig')"
          class="group mt-2 inline-flex w-fit items-center gap-1.5 text-sm font-bold uppercase tracking-wide text-brand hover:underline"
        >
          Take the rig apart
          <ArrowRight class="h-4 w-4 transition-transform group-hover:translate-x-0.5" />
        </Link>
      </div>

      <div class="flex justify-center lg:justify-end">
        <img
          src="@img/about-reno-photo.png"
          alt="Reno, who runs Wildcard Overland"
          width="520"
          height="640"
          class="h-auto w-full max-w-[320px] object-contain object-bottom lg:max-w-none"
        >
      </div>
    </div>
  </div>
</template>
