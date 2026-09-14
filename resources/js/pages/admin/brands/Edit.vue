<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3'
import { ArrowLeft } from 'lucide-vue-next'
import { AdminLayout } from '@/layouts'
import PageHeading from '@/components/admin/ui/PageHeading.vue'
import Button from '@/components/admin/ui/Button.vue'
import Card from '@/components/admin/ui/Card.vue'
import Field from '@/components/admin/ui/Field.vue'
import Input from '@/components/admin/ui/Input.vue'
import ImagePicker from '@/components/admin/ui/ImagePicker.vue'
import { useImageLibrary, type ImageOption } from '@/composables/useImageLibrary'
import Textarea from '@/components/admin/ui/Textarea.vue'
import { useRoute } from '@/lib/route'

const route = useRoute()

defineOptions({ layout: AdminLayout })

interface BrandPayload {
  id: number
  name: string
  logo_image_id: number | null
  website: string | null
  description: string | null
  primary_color: string | null
  secondary_color: string | null
  notes: string | null
}

const props = defineProps<{
  brand: BrandPayload | null
  images: ImageOption[]
}>()

const isEdit = !!props.brand

const { options: imageOptions, add: addImage } = useImageLibrary(props.images)

const form = useForm({
  name: props.brand?.name ?? '',
  logo_image_id: props.brand?.logo_image_id ?? null,
  website: props.brand?.website ?? '',
  description: props.brand?.description ?? '',
  primary_color: props.brand?.primary_color ?? '',
  secondary_color: props.brand?.secondary_color ?? '',
  notes: props.brand?.notes ?? '',
})

function submit() {
  if (isEdit) {
    form.put(route('admin.brands.update', props.brand!.id))
  } else {
    form.post(route('admin.brands.store'))
  }
}
</script>

<template>
  <Head :title="isEdit ? `Edit ${brand!.name}` : 'New brand'" />

  <PageHeading :title="isEdit ? brand!.name : 'New brand'">
    <template #before>
      <Button :as="Link" :href="route('admin.brands.index')" variant="ghost" size="icon">
        <ArrowLeft class="h-4 w-4" />
      </Button>
    </template>
  </PageHeading>

  <form class="max-w-3xl space-y-6" @submit.prevent="submit">
    <Card title="Brand">
      <div class="space-y-5">
        <Field label="Name" for="name" :error="form.errors.name" required>
          <Input id="name" v-model="form.name" required :invalid="!!form.errors.name" />
        </Field>

        <Field label="Website" for="website" :error="form.errors.website">
          <Input id="website" v-model="form.website" type="url" placeholder="https://" :invalid="!!form.errors.website" />
        </Field>

        <Field
          label="Logo"
          for="logo_image_id"
          :error="form.errors.logo_image_id"
          hint="Drop a logo here, or pick one already in the library. Transparent PNG or SVG works best."
        >
          <ImagePicker
            id="logo_image_id"
            v-model="form.logo_image_id"
            :options="imageOptions"
            placeholder="No logo"
            image-type="logo"
            :invalid="!!form.errors.logo_image_id"
            @uploaded="addImage"
          />
        </Field>

        <Field label="Description" for="description" :error="form.errors.description">
          <Textarea id="description" v-model="form.description" :invalid="!!form.errors.description" />
        </Field>

        <div class="grid gap-5 sm:grid-cols-2">
          <Field label="Primary color" for="primary_color" :error="form.errors.primary_color" hint="Hex, e.g. #e85a2f">
            <div class="flex items-center gap-2">
              <Input id="primary_color" v-model="form.primary_color" placeholder="#e85a2f" :invalid="!!form.errors.primary_color" />
              <span
                class="h-9 w-9 shrink-0 rounded-md border border-zinc-300 dark:border-zinc-700"
                :style="{ backgroundColor: form.primary_color || 'transparent' }"
              />
            </div>
          </Field>

          <Field label="Secondary color" for="secondary_color" :error="form.errors.secondary_color" hint="Hex, e.g. #3b3f42">
            <div class="flex items-center gap-2">
              <Input id="secondary_color" v-model="form.secondary_color" placeholder="#3b3f42" :invalid="!!form.errors.secondary_color" />
              <span
                class="h-9 w-9 shrink-0 rounded-md border border-zinc-300 dark:border-zinc-700"
                :style="{ backgroundColor: form.secondary_color || 'transparent' }"
              />
            </div>
          </Field>
        </div>

        <Field label="Notes" for="notes" :error="form.errors.notes">
          <Textarea id="notes" v-model="form.notes" :invalid="!!form.errors.notes" />
        </Field>
      </div>
    </Card>

    <div class="flex items-center gap-3">
      <Button type="submit" :disabled="form.processing">
        {{ form.processing ? 'Saving…' : isEdit ? 'Save changes' : 'Create' }}
      </Button>
      <Button :as="Link" :href="route('admin.brands.index')" variant="secondary">Cancel</Button>
    </div>
  </form>
</template>
