<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3'
import { ArrowLeft } from 'lucide-vue-next'
import { AdminLayout } from '@/layouts'
import PageHeading from '@/components/admin/ui/PageHeading.vue'
import Button from '@/components/admin/ui/Button.vue'
import Card from '@/components/admin/ui/Card.vue'
import Field from '@/components/admin/ui/Field.vue'
import Input from '@/components/admin/ui/Input.vue'
import Select from '@/components/admin/ui/Select.vue'
import Switch from '@/components/admin/ui/Switch.vue'
import { useRoute } from '@/lib/route'

const route = useRoute()

defineOptions({ layout: AdminLayout })

interface ImagePayload {
  id: number
  name: string | null
  type: string
  width: number | null
  height: number | null
  private: boolean
  featured: boolean
  sort_order: number
  caption: string | null
  file: { id: string; original_filename: string; mime: string; readable_size: string } | null
}

const props = defineProps<{
  image: ImagePayload
  imageTypes: Array<{ value: string; label: string }>
}>()

const form = useForm({
  name: props.image.name ?? '',
  type: props.image.type,
  caption: props.image.caption ?? '',
  private: props.image.private,
  featured: props.image.featured,
  sort_order: props.image.sort_order,
})

function submit() {
  form.put(route('admin.images.update', props.image.id))
}
</script>

<template>
  <Head :title="`Edit ${image.name ?? `Image #${image.id}`}`" />

  <PageHeading :title="image.name ?? `Image #${image.id}`">
    <template #before>
      <Button :as="Link" :href="route('admin.images.index')" variant="ghost" size="icon">
        <ArrowLeft class="h-4 w-4" />
      </Button>
    </template>
  </PageHeading>

  <form class="max-w-2xl space-y-6" @submit.prevent="submit">
    <Card title="Image">
      <div class="space-y-5">
        <Field label="Name" for="name" :error="form.errors.name">
          <Input id="name" v-model="form.name" :invalid="!!form.errors.name" />
        </Field>

        <Field
          label="Type"
          for="type"
          :error="form.errors.type"
          hint="Only photographs are counted in the site statistics."
          required
        >
          <Select id="type" v-model="form.type" :options="imageTypes" :invalid="!!form.errors.type" />
        </Field>

        <Field label="Caption" for="caption" :error="form.errors.caption" hint="Shown under the image in the homepage gallery.">
          <Input id="caption" v-model="form.caption" :invalid="!!form.errors.caption" />
        </Field>

        <Switch
          v-model="form.private"
          label="Private"
          description="Private images are excluded from public listings and never counted."
        />

        <Switch
          v-model="form.featured"
          label="Featured"
          description="Featured photographs appear in the homepage gallery."
        />

        <Field
          label="Sort order"
          for="sort_order"
          :error="form.errors.sort_order"
          hint="Lower numbers appear first in the gallery."
        >
          <Input id="sort_order" v-model="form.sort_order" type="number" min="0" class="max-w-32" :invalid="!!form.errors.sort_order" />
        </Field>
      </div>
    </Card>

    <Card title="Source file">
      <dl class="grid gap-3 text-sm sm:grid-cols-2">
        <div>
          <dt class="text-zinc-500 dark:text-zinc-400">Filename</dt>
          <dd class="text-zinc-800 dark:text-zinc-200">{{ image.file?.original_filename ?? '—' }}</dd>
        </div>
        <div>
          <dt class="text-zinc-500 dark:text-zinc-400">Type</dt>
          <dd class="text-zinc-800 dark:text-zinc-200">{{ image.file?.mime ?? '—' }}</dd>
        </div>
        <div>
          <dt class="text-zinc-500 dark:text-zinc-400">Size</dt>
          <dd class="text-zinc-800 dark:text-zinc-200">{{ image.file?.readable_size ?? '—' }}</dd>
        </div>
        <div>
          <dt class="text-zinc-500 dark:text-zinc-400">Dimensions</dt>
          <dd class="text-zinc-800 dark:text-zinc-200">
            {{ image.width && image.height ? `${image.width} × ${image.height}` : '—' }}
          </dd>
        </div>
      </dl>
    </Card>

    <div class="flex items-center gap-3">
      <Button type="submit" :disabled="form.processing">
        {{ form.processing ? 'Saving…' : 'Save changes' }}
      </Button>
      <Button :as="Link" :href="route('admin.images.index')" variant="secondary">Cancel</Button>
    </div>
  </form>
</template>
