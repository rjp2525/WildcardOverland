<script setup lang="ts">
import { computed, ref } from 'vue'
import { useForm, usePage } from '@inertiajs/vue3'
import { ImagePlus, Loader2, X } from 'lucide-vue-next'
import HoneypotField from './HoneypotField.vue'
import StarPicker from './StarPicker.vue'
import { Button } from '@/components/ui/button'
import { useHoneypot } from '@/composables/useHoneypot'

export interface Honeypot {
  stamp: string
  trap: string
  stampField: string
}

/** Where somebody's own review has got to. */
export type ReviewState = 'unconfirmed' | 'waiting' | 'published'

const props = defineProps<{
  url: string
  honeypot: Honeypot
  /** Whether a photograph may come with it. */
  photos: boolean
  /** Whether it waits to be read before it goes up. */
  moderated: boolean
  maxLength: number
  photoMaxKb: number
  /** Whether the address given has to be answered before anything happens. */
  confirms: boolean
  /** What this browser already sent in, if it has been here before. */
  yours: { stars: number | null; state: ReviewState } | null
}>()

const page = usePage()

/** Whatever a form filler put in the field nobody can see. */
const trap = ref('')

const form = useForm({
  name: '',
  email: '',
  stars: props.yours?.stars ?? 0,
  body: '',
  photo: null as File | null,
})

/*
 * The bot checks ride along on the way out. Their names are decided by the
 * server, so they cannot be part of a typed form shape.
 */
form.transform((data) => ({
  ...data,
  [props.honeypot.stampField]: props.honeypot.stamp,
  [props.honeypot.trap]: trap.value,
}))

const errors = computed(() => form.errors as Record<string, string | undefined>)

const { whenSettled } = useHoneypot()

const file = ref<HTMLInputElement>()

/** Their own photograph, shown back to them before it is sent anywhere. */
const preview = ref<string | null>(null)

const sent = ref(false)

const remaining = computed(() => props.maxLength - form.body.length)

/*
 * Said by the server, which is the only thing that knows whether the email
 * went out. The fallback is only for a response that carried no message.
 */
const outcome = computed(() => {
  const flash = page.props.flash as { success?: string | null; error?: string | null }

  return {
    failed: !!flash?.error,
    message:
      flash?.error ??
      flash?.success ??
      (props.moderated ? 'Thanks. It will show up once I have read it.' : 'Thanks, that is up.'),
  }
})

/** Where the review this browser already left has got to. */
const standing = computed(() => {
  const states: Record<ReviewState, string> = {
    unconfirmed:
      'You have already written one for this. Follow the link in the email to confirm it is you, and it joins the queue. Sending it again emails you a new link.',
    waiting:
      'You have already sent one in for this. It will show up once I have read it. Sending another from the same address replaces it.',
    published:
      'Your review is on this page. Sending another from the same address replaces it.',
  }

  return states[props.yours?.state ?? 'waiting']
})

function choose(event: Event): void {
  const chosen = (event.target as HTMLInputElement).files?.[0] ?? null

  if (preview.value) {
    URL.revokeObjectURL(preview.value)
  }

  form.photo = chosen
  preview.value = chosen ? URL.createObjectURL(chosen) : null
}

function drop(): void {
  if (preview.value) {
    URL.revokeObjectURL(preview.value)
  }

  form.photo = null
  preview.value = null

  if (file.value) {
    file.value.value = ''
  }
}

function submit(): void {
  sent.value = false

  whenSettled(() =>
    form.post(props.url, {
      preserveScroll: true,
      preserveState: true,
      forceFormData: true,
      onSuccess: () => {
        sent.value = true
        form.reset('name', 'email', 'body', 'photo')
        drop()
      },
    }),
  )
}

const field =
  'w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-slate-900 placeholder:text-slate-400 focus:border-brand focus:outline-hidden focus:ring-2 focus:ring-brand/30 dark:border-white/15 dark:bg-white/5 dark:text-white dark:placeholder:text-white/35'

const wrong = 'border-red-500 dark:border-red-500/70'
</script>

<template>
  <form class="relative space-y-5" @submit.prevent="submit">
    <HoneypotField v-model="trap" :name="honeypot.trap" />

    <!--
      What the server actually did, in its own words. It knows things the
      form cannot: whether the email went out, whether this address had
      already been answered, whether the mailer fell over.
    -->
    <p
      v-if="sent"
      :class="[
        'rounded-md px-4 py-3 text-sm font-medium',
        outcome.failed
          ? 'bg-red-500/10 text-red-600 dark:text-red-400'
          : 'bg-brand/10 text-brand',
      ]"
      role="status"
    >
      {{ outcome.message }}
    </p>

    <!--
      Somebody who has already written one, coming back to the page. Said
      before the form rather than after it, so they are not halfway through
      writing a second before they find out.
    -->
    <p
      v-else-if="yours"
      class="rounded-md bg-slate-100 px-4 py-3 text-sm text-slate-600 dark:bg-white/5 dark:text-white/70"
    >
      {{ standing }}
    </p>

    <!-- The stars are the review, not a thing you do instead of one. -->
    <div>
      <StarPicker v-model="form.stars" :invalid="!!form.errors.stars" />
      <p v-if="form.errors.stars" class="mt-1 text-sm text-red-600 dark:text-red-400">
        {{ form.errors.stars }}
      </p>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
      <div>
        <label for="review-name" class="mb-1 block text-sm font-medium text-slate-700 dark:text-white/75">
          Your name
        </label>
        <input
          id="review-name"
          v-model="form.name"
          type="text"
          maxlength="60"
          autocomplete="name"
          placeholder="However you want to be known"
          :class="[field, form.errors.name && wrong]"
        >
        <p v-if="form.errors.name" class="mt-1 text-sm text-red-600 dark:text-red-400">
          {{ form.errors.name }}
        </p>
      </div>

      <div>
        <label for="review-email" class="mb-1 block text-sm font-medium text-slate-700 dark:text-white/75">
          Your email
        </label>
        <input
          id="review-email"
          v-model="form.email"
          type="email"
          maxlength="255"
          autocomplete="email"
          placeholder="you@example.com"
          aria-describedby="review-email-note"
          :class="[field, form.errors.email && wrong]"
        >
        <p v-if="form.errors.email" class="mt-1 text-sm text-red-600 dark:text-red-400">
          {{ form.errors.email }}
        </p>
        <p v-else id="review-email-note" class="mt-1 text-xs text-slate-400 dark:text-white/40">
          {{ confirms
            ? 'You will get one email with a link to confirm it is you. Never shown on the page, and never passed on.'
            : 'Never shown on the page, and never passed on.' }}
        </p>
      </div>
    </div>

    <div>
      <label for="review-body" class="mb-1 block text-sm font-medium text-slate-700 dark:text-white/75">
        How did it go?
      </label>
      <textarea
        id="review-body"
        v-model="form.body"
        rows="4"
        :maxlength="maxLength"
        placeholder="What you changed, what you would do differently, how it came out."
        :class="[field, form.errors.body && wrong]"
      />
      <div class="mt-1 flex items-baseline justify-between gap-4">
        <p v-if="form.errors.body" class="text-sm text-red-600 dark:text-red-400">
          {{ form.errors.body }}
        </p>
        <span v-else />
        <!-- Only once it is close enough to matter. -->
        <span
          v-if="remaining < 200"
          class="shrink-0 text-xs tabular-nums text-slate-400 dark:text-white/40"
        >
          {{ remaining }} left
        </span>
      </div>
    </div>

    <div v-if="photos">
      <div v-if="preview" class="flex items-start gap-3">
        <img :src="preview" alt="" class="h-24 w-24 rounded-md object-cover">
        <div class="min-w-0">
          <p class="truncate text-sm text-slate-700 dark:text-white/75">
            {{ form.photo?.name }}
          </p>
          <button
            type="button"
            class="mt-1 inline-flex items-center gap-1 text-sm font-medium text-brand hover:underline"
            @click="drop"
          >
            <X class="h-3.5 w-3.5" /> Remove
          </button>
        </div>
      </div>

      <label
        v-else
        class="inline-flex cursor-pointer items-center gap-2 rounded-md border border-dashed border-slate-300 px-4 py-3 text-sm font-medium text-slate-600 transition-colors hover:border-brand hover:text-brand focus-within:border-brand dark:border-white/20 dark:text-white/70"
      >
        <ImagePlus class="h-4 w-4" />
        Add a photo of yours
        <input
          ref="file"
          type="file"
          accept="image/jpeg,image/png,image/webp,image/heic,image/heif"
          class="sr-only"
          @change="choose"
        >
      </label>

      <p v-if="form.errors.photo" class="mt-1 text-sm text-red-600 dark:text-red-400">
        {{ form.errors.photo }}
      </p>
      <p v-else class="mt-1 text-xs text-slate-400 dark:text-white/40">
        JPEG, PNG, WebP or HEIC, up to {{ Math.floor(photoMaxKb / 1024) }}MB.
      </p>
    </div>

    <p v-if="errors[honeypot.stampField]" class="text-sm text-red-600 dark:text-red-400">
      {{ errors[honeypot.stampField] }}
    </p>

    <div class="flex flex-wrap items-center gap-4">
      <Button type="submit" size="sm" :disabled="form.processing">
        <Loader2 v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
        {{ form.processing ? 'Sending' : 'Post it' }}
      </Button>

      <p v-if="moderated" class="text-xs text-slate-400 dark:text-white/40">
        No account needed. I read everything before it goes up.
      </p>
    </div>

    <!-- The upload is the slow part, so say how far along it is. -->
    <div
      v-if="form.progress"
      class="h-1 w-full overflow-hidden rounded-full bg-slate-200 dark:bg-white/10"
    >
      <div
        class="h-full bg-brand transition-[width]"
        :style="{ width: `${form.progress.percentage}%` }"
      />
    </div>
  </form>
</template>
