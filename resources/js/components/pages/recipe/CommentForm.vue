<script setup lang="ts">
import { computed, ref } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { ImagePlus, Loader2, X } from 'lucide-vue-next'
import HoneypotField from './HoneypotField.vue'
import { Button } from '@/components/ui/button'
import { useHoneypot } from '@/composables/useHoneypot'
import type { Honeypot } from './RateThis.vue'

const props = defineProps<{
  url: string
  honeypot: Honeypot
  /** Whether a photograph may come with it. */
  photos: boolean
  /** Whether it waits to be read before it goes up. */
  moderated: boolean
  maxLength: number
  photoMaxKb: number
}>()

/** Whatever a form filler put in the field nobody can see. */
const trap = ref('')

const form = useForm({
  name: '',
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
        form.reset('name', 'body', 'photo')
        drop()
      },
    }),
  )
}

const field =
  'w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-slate-900 placeholder:text-slate-400 focus:border-brand focus:outline-hidden focus:ring-2 focus:ring-brand/30 dark:border-white/15 dark:bg-white/5 dark:text-white dark:placeholder:text-white/35'
</script>

<template>
  <form class="relative space-y-4" @submit.prevent="submit">
    <HoneypotField v-model="trap" :name="honeypot.trap" />

    <p
      v-if="sent"
      class="rounded-md bg-brand/10 px-4 py-3 text-sm font-medium text-brand"
      role="status"
    >
      {{ moderated
        ? 'Thanks. It will show up here once I have read it.'
        : 'Thanks, that is up.' }}
    </p>

    <div>
      <label for="comment-name" class="mb-1 block text-sm font-medium text-slate-700 dark:text-white/75">
        Your name
      </label>
      <input
        id="comment-name"
        v-model="form.name"
        type="text"
        maxlength="60"
        autocomplete="name"
        placeholder="However you want to be known"
        :class="field"
      >
      <p v-if="form.errors.name" class="mt-1 text-sm text-red-600 dark:text-red-400">
        {{ form.errors.name }}
      </p>
    </div>

    <div>
      <label for="comment-body" class="mb-1 block text-sm font-medium text-slate-700 dark:text-white/75">
        How did it go?
      </label>
      <textarea
        id="comment-body"
        v-model="form.body"
        rows="4"
        :maxlength="maxLength"
        placeholder="What you changed, what you would do differently, how it came out."
        :class="field"
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
