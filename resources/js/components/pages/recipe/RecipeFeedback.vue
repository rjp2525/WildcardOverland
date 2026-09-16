<script setup lang="ts">
import { computed } from 'vue'
import RatingSummary, { type RatingSummaryData } from './RatingSummary.vue'
import RatingStars from './RatingStars.vue'
import ReviewForm, { type Honeypot, type ReviewState } from './ReviewForm.vue'
import StepPhoto, { type StepImage } from './StepPhoto.vue'

export interface Review {
  name: string
  body: string
  posted: string | null
  stars: number | null
  photo: StepImage
}

export interface Feedback {
  rating: RatingSummaryData
  /** What this browser already sent in, if it has been here before. */
  yours: { stars: number | null; state: ReviewState } | null
  comments: Review[]
  /** Handed out per render, and good for one submission. */
  stamp: string
  trap: string
  stampField: string
  photos: boolean
  moderated: boolean
  confirms: boolean
  maxLength: number
  photoMaxKb: number
}

const props = defineProps<{
  feedback: Feedback
  reviewUrl: string
}>()

const honeypot = computed<Honeypot>(() => ({
  stamp: props.feedback.stamp,
  trap: props.feedback.trap,
  stampField: props.feedback.stampField,
}))

/*
 * A circle of colour with their initial in it. Nobody has an account, so
 * there is no picture to show and a row of identical grey blanks just makes
 * the list harder to read. The hue comes from the name so the same person
 * comes back the same colour.
 */
function hue(name: string): number {
  let total = 0

  for (let i = 0; i < name.length; i++) {
    total = (total * 31 + name.charCodeAt(i)) % 360
  }

  return total
}
</script>

<template>
  <section
    id="notes"
    class="print-hide border-t border-slate-200 py-12 dark:border-white/10"
    aria-labelledby="notes-heading"
  >
    <div class="container max-w-3xl">
      <h2 id="notes-heading" class="text-2xl font-extrabold uppercase text-brand">
        Cook's notes
      </h2>

      <div class="mt-6 rounded-lg border border-slate-200 p-5 dark:border-white/10 sm:p-6">
        <RatingSummary :rating="feedback.rating" />
      </div>

      <ol v-if="feedback.comments.length" class="mt-10 space-y-8">
        <li
          v-for="(comment, i) in feedback.comments"
          :key="i"
          class="flex gap-4"
        >
          <span
            class="mt-0.5 flex h-10 w-10 shrink-0 items-center justify-center rounded-full font-brand text-base font-extrabold text-white"
            :style="{ backgroundColor: `hsl(${hue(comment.name)} 55% 42%)` }"
            aria-hidden="true"
          >
            {{ comment.name.trim().charAt(0).toUpperCase() }}
          </span>

          <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
              <p class="font-bold text-slate-900 dark:text-white">{{ comment.name }}</p>

              <RatingStars v-if="comment.stars" :value="comment.stars" size="sm" />

              <time
                v-if="comment.posted"
                :datetime="comment.posted"
                class="text-sm text-slate-400 dark:text-white/40"
              >
                {{ new Date(`${comment.posted}T00:00:00`).toLocaleDateString(undefined, {
                  day: 'numeric', month: 'long', year: 'numeric',
                }) }}
              </time>
            </div>

            <!-- Their words, exactly as typed. Never markup: it is a stranger's. -->
            <p class="mt-1.5 whitespace-pre-line text-slate-700 dark:text-white/80">
              {{ comment.body }}
            </p>

            <StepPhoto
              v-if="comment.photo.crop"
              :image="comment.photo"
              :label="`${comment.name}'s photo`"
            />
          </div>
        </li>
      </ol>

      <div class="mt-10 border-t border-slate-200 pt-8 dark:border-white/10">
        <h3 class="font-brand text-sm font-extrabold uppercase tracking-widest text-slate-900 dark:text-white">
          {{ feedback.comments.length ? 'Add yours' : 'Be the first' }}
        </h3>
        <p class="mt-1 text-sm text-slate-500 dark:text-white/55">
          Made it? Give it a rating, say how it went, and put up a photo of yours if
          you took one.
        </p>

        <div class="mt-5">
          <ReviewForm
            :url="reviewUrl"
            :honeypot="honeypot"
            :photos="feedback.photos"
            :moderated="feedback.moderated"
            :confirms="feedback.confirms"
            :max-length="feedback.maxLength"
            :photo-max-kb="feedback.photoMaxKb"
            :yours="feedback.yours"
          />
        </div>
      </div>
    </div>
  </section>
</template>
