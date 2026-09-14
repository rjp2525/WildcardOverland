<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3'
import { ArrowLeft, GripVertical, Plus, Trash2 } from 'lucide-vue-next'
import { AdminLayout } from '@/layouts'
import PageHeading from '@/components/admin/ui/PageHeading.vue'
import Button from '@/components/admin/ui/Button.vue'
import Card from '@/components/admin/ui/Card.vue'
import Field from '@/components/admin/ui/Field.vue'
import Input from '@/components/admin/ui/Input.vue'
import Repeater from '@/components/admin/ui/Repeater.vue'
import RichTextEditor, { type RichTextDoc } from '@/components/admin/ui/RichTextEditor.vue'
import Select from '@/components/admin/ui/Select.vue'
import ImagePicker from '@/components/admin/ui/ImagePicker.vue'
import ImageDropzone from '@/components/admin/ui/ImageDropzone.vue'
import { useImageLibrary } from '@/composables/useImageLibrary'
import Switch from '@/components/admin/ui/Switch.vue'
import { useRoute } from '@/lib/route'
import type { ImageOption } from '@/composables/useImageLibrary'

const route = useRoute()

defineOptions({ layout: AdminLayout })

interface CampsiteRow {
  id: number | null
  name: string
  latitude: string | number | null
  longitude: string | number | null
  nights: number | null
  notes: string | null
}

interface RecipeRow {
  id: number | null
}

interface GalleryRow {
  id: number | null
  label?: string
  caption: string | null
}

interface TripPayload {
  id: number
  name: string
  slug: string | null
  headline: string | null
  hero_image_id: number | null
  images: GalleryRow[]
  recipes: RecipeRow[]
  summary: string | null
  content: RichTextDoc
  start_date: string | null
  end_date: string | null
  miles: number | null
  is_draft: boolean
  published_at: string | null
  nights: number | null
  campsites: CampsiteRow[]
}

const props = defineProps<{
  trip: TripPayload | null
  images: ImageOption[]
  recipeOptions: Array<{ value: number; label: string }>
}>()

const isEdit = !!props.trip

const { options: imageOptions, add: addImage } = useImageLibrary(props.images)

const form = useForm({
  name: props.trip?.name ?? '',
  slug: props.trip?.slug ?? '',
  headline: props.trip?.headline ?? '',
  hero_image_id: props.trip?.hero_image_id ?? null,
  images: (props.trip?.images ?? []) as GalleryRow[],
  recipes: (props.trip?.recipes ?? []) as RecipeRow[],
  summary: props.trip?.summary ?? null,
  content: props.trip?.content ?? null,
  start_date: props.trip?.start_date ?? '',
  end_date: props.trip?.end_date ?? '',
  miles: props.trip?.miles ?? null,
  is_draft: props.trip?.is_draft ?? true,
  published_at: props.trip?.published_at ?? '',
  campsites: (props.trip?.campsites ?? []) as CampsiteRow[],
})

/** A photo dropped on the gallery joins the library and gets its own row. */
function attachPhoto(option: ImageOption) {
  addImage(option)
  form.images.push({ id: option.value, caption: null })
}

function addCampsite() {
  form.campsites.push({
    id: null,
    name: '',
    latitude: null,
    longitude: null,
    nights: null,
    notes: null,
  })
}

function removeCampsite(index: number) {
  form.campsites.splice(index, 1)
}

function move(index: number, delta: number) {
  const target = index + delta
  if (target < 0 || target >= form.campsites.length) return
  const [row] = form.campsites.splice(index, 1)
  form.campsites.splice(target, 0, row)
}

function submit() {
  if (isEdit) {
    form.put(route('admin.trips.update', props.trip!.id), { preserveScroll: true })
  } else {
    form.post(route('admin.trips.store'))
  }
}

/** Nested row errors arrive dot-keyed from Laravel. */
function err(path: string): string | undefined {
  return (form.errors as Record<string, string>)[path]
}

function campsiteError(index: number, field: string): string | undefined {
  return err(`campsites.${index}.${field}`)
}
</script>

<template>
  <Head :title="isEdit ? `Edit ${trip!.name}` : 'New trip'" />

  <PageHeading :title="isEdit ? trip!.name : 'New trip'">
    <template #before>
      <Button :as="Link" :href="route('admin.trips.index')" variant="ghost" size="icon">
        <ArrowLeft class="h-4 w-4" />
      </Button>
    </template>
  </PageHeading>

  <form class="max-w-4xl space-y-6" @submit.prevent="submit">
    <Card title="Details">
      <div class="space-y-5">
        <div class="grid gap-5 sm:grid-cols-2">
          <Field label="Name" for="name" :error="form.errors.name" required>
            <Input id="name" v-model="form.name" required :invalid="!!form.errors.name" />
          </Field>

          <Field
            label="Slug"
            for="slug"
            :error="form.errors.slug"
            hint="Left blank, this is derived from the name."
          >
            <Input id="slug" v-model="form.slug" :invalid="!!form.errors.slug" />
          </Field>
        </div>

        <Field label="Headline" for="headline" :error="form.errors.headline">
          <Input id="headline" v-model="form.headline" :invalid="!!form.errors.headline" />
        </Field>

        <Field
          label="Hero image"
          for="hero_image_id"
          :error="form.errors.hero_image_id"
          hint="Used on trip cards and the top of the trip page."
        >
          <ImagePicker
            id="hero_image_id"
            v-model="form.hero_image_id"
            :options="imageOptions"
            placeholder="No hero image"
            :invalid="!!form.errors.hero_image_id"
            @uploaded="addImage"
          />
        </Field>

        <div class="grid gap-5 sm:grid-cols-2">
          <Field label="Start date" for="start_date" :error="form.errors.start_date">
            <Input id="start_date" v-model="form.start_date" type="date" :invalid="!!form.errors.start_date" />
          </Field>

          <Field
            label="End date"
            for="end_date"
            :error="form.errors.end_date"
            :hint="trip?.nights != null ? `${trip.nights} nights calculated` : undefined"
          >
            <Input id="end_date" v-model="form.end_date" type="date" :invalid="!!form.errors.end_date" />
          </Field>
        </div>

        <Field
          label="Miles off road"
          for="miles"
          :error="form.errors.miles"
          hint="Counted into the site statistics."
        >
          <Input id="miles" v-model="form.miles" type="number" min="0" class="max-w-48" :invalid="!!form.errors.miles" />
        </Field>
      </div>
    </Card>

    <Card
      title="Summary"
      description="Plain text. It is the card teaser and the description search engines show."
    >
      <Field :error="form.errors.summary">
        <Textarea v-model="form.summary" :rows="3" :invalid="!!form.errors.summary" />
      </Field>
    </Card>

    <Card title="Content" description="The full trip write-up.">
      <RichTextEditor v-model="form.content" placeholder="Tell the story…" />
    </Card>

    <Card title="Gallery" description="Photos shown on the trip page, in order.">
      <ImageDropzone
        multiple
        class="mb-4"
        label="Drop the whole set here, or"
        @uploaded="attachPhoto"
      />

      <Repeater
        v-model="form.images"
        item-label="Photo"
        :new-row="(): GalleryRow => ({ id: null, caption: null })"
        empty-message="No photos attached to this trip."
      >
        <template #row="{ row, index }">
          <div class="grid gap-4 sm:grid-cols-2">
            <Field label="Image" :error="err(`images.${index}.id`)" required>
              <ImagePicker
                v-model="row.id"
                :options="imageOptions"
                placeholder="Choose an image…"
                @uploaded="addImage"
              />
            </Field>
            <Field label="Caption" :error="err(`images.${index}.caption`)">
              <Input v-model="row.caption" placeholder="Optional caption" />
            </Field>
          </div>
        </template>
      </Repeater>
    </Card>

    <Card title="Recipes" description="What you cooked out there; shown at the bottom of the trip page.">
      <Repeater
        v-model="form.recipes"
        item-label="Recipe"
        :new-row="(): RecipeRow => ({ id: null })"
        empty-message="No recipes linked to this trip."
      >
        <template #row="{ row, index }">
          <Field label="Recipe" :error="err(`recipes.${index}.id`)" required>
            <Select v-model="row.id" :options="recipeOptions" placeholder="Choose a recipe…" />
          </Field>
        </template>
      </Repeater>
    </Card>

    <Card title="Campsites" description="Stops on this trip, in order.">
      <template #actions>
        <Button type="button" variant="secondary" size="sm" @click="addCampsite">
          <Plus class="h-4 w-4" />
          Add
        </Button>
      </template>

      <div v-if="form.campsites.length" class="space-y-4">
        <div
          v-for="(campsite, index) in form.campsites"
          :key="campsite.id ?? `new-${index}`"
          class="rounded-md border border-zinc-200 p-4 dark:border-zinc-800"
        >
          <div class="mb-3 flex items-center justify-between">
            <div class="flex items-center gap-1 text-zinc-400">
              <GripVertical class="h-4 w-4" />
              <span class="text-xs font-medium">Stop {{ index + 1 }}</span>
            </div>
            <div class="flex items-center gap-1">
              <Button type="button" variant="ghost" size="sm" :disabled="index === 0" @click="move(index, -1)">
                Up
              </Button>
              <Button
                type="button"
                variant="ghost"
                size="sm"
                :disabled="index === form.campsites.length - 1"
                @click="move(index, 1)"
              >
                Down
              </Button>
              <Button type="button" variant="ghost" size="icon" aria-label="Remove campsite" @click="removeCampsite(index)">
                <Trash2 class="h-4 w-4 text-red-600" />
              </Button>
            </div>
          </div>

          <div class="grid gap-4 sm:grid-cols-2">
            <Field label="Name" :error="campsiteError(index, 'name')" required>
              <Input v-model="campsite.name" :invalid="!!campsiteError(index, 'name')" />
            </Field>
            <Field label="Nights" :error="campsiteError(index, 'nights')">
              <Input v-model="campsite.nights" type="number" min="0" :invalid="!!campsiteError(index, 'nights')" />
            </Field>
            <Field label="Latitude" :error="campsiteError(index, 'latitude')">
              <Input v-model="campsite.latitude" type="number" step="0.0000001" :invalid="!!campsiteError(index, 'latitude')" />
            </Field>
            <Field label="Longitude" :error="campsiteError(index, 'longitude')">
              <Input v-model="campsite.longitude" type="number" step="0.0000001" :invalid="!!campsiteError(index, 'longitude')" />
            </Field>
          </div>

          <Field label="Notes" class="mt-4" :error="campsiteError(index, 'notes')">
            <Input v-model="campsite.notes" :invalid="!!campsiteError(index, 'notes')" />
          </Field>
        </div>
      </div>
      <p v-else class="text-sm text-zinc-500 dark:text-zinc-400">
        No campsites recorded for this trip.
      </p>
    </Card>

    <Card title="Publishing">
      <div class="space-y-5">
        <Switch
          v-model="form.is_draft"
          label="Draft"
          description="Drafts are hidden from the public site."
        />

        <Field label="Publish date" for="published_at" :error="form.errors.published_at">
          <Input
            id="published_at"
            v-model="form.published_at"
            type="datetime-local"
            :invalid="!!form.errors.published_at"
          />
        </Field>
      </div>
    </Card>

    <div class="flex items-center gap-3">
      <Button type="submit" :disabled="form.processing">
        {{ form.processing ? 'Saving…' : isEdit ? 'Save changes' : 'Create trip' }}
      </Button>
      <Button :as="Link" :href="route('admin.trips.index')" variant="secondary">Cancel</Button>
    </div>
  </form>
</template>
