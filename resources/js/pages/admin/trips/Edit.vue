<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3'
import { ArrowLeft, GripVertical, Plus, Trash2 } from 'lucide-vue-next'
import { AdminLayout } from '@/layouts'
import PageHeading from '@/components/admin/ui/PageHeading.vue'
import Button from '@/components/admin/ui/Button.vue'
import Card from '@/components/admin/ui/Card.vue'
import Field from '@/components/admin/ui/Field.vue'
import Input from '@/components/admin/ui/Input.vue'
import RichTextEditor from '@/components/admin/ui/RichTextEditor.vue'
import Switch from '@/components/admin/ui/Switch.vue'
import { useRoute } from '@/lib/route'

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

interface TripPayload {
  id: number
  name: string
  slug: string | null
  headline: string | null
  summary: string | null
  content: string | null
  start_date: string | null
  end_date: string | null
  is_draft: boolean
  published_at: string | null
  nights: number | null
  campsites: CampsiteRow[]
}

const props = defineProps<{ trip: TripPayload | null }>()

const isEdit = !!props.trip

const form = useForm({
  name: props.trip?.name ?? '',
  slug: props.trip?.slug ?? '',
  headline: props.trip?.headline ?? '',
  summary: props.trip?.summary ?? null,
  content: props.trip?.content ?? null,
  start_date: props.trip?.start_date ?? '',
  end_date: props.trip?.end_date ?? '',
  is_draft: props.trip?.is_draft ?? true,
  published_at: props.trip?.published_at ?? '',
  campsites: (props.trip?.campsites ?? []) as CampsiteRow[],
})

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

/** Errors for nested campsite rows arrive dot-keyed from Laravel. */
function campsiteError(index: number, field: string): string | undefined {
  return (form.errors as Record<string, string>)[`campsites.${index}.${field}`]
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
      </div>
    </Card>

    <Card title="Summary" description="Shown in trip listings.">
      <RichTextEditor v-model="form.summary" placeholder="A short teaser…" />
    </Card>

    <Card title="Content" description="The full trip write-up.">
      <RichTextEditor v-model="form.content" placeholder="Tell the story…" />
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
