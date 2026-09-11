<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3'
import { ArrowLeft } from 'lucide-vue-next'
import { AdminLayout } from '@/layouts'
import PageHeading from '@/components/admin/ui/PageHeading.vue'
import Button from '@/components/admin/ui/Button.vue'
import Card from '@/components/admin/ui/Card.vue'
import Field from '@/components/admin/ui/Field.vue'
import Input from '@/components/admin/ui/Input.vue'
import Switch from '@/components/admin/ui/Switch.vue'
import { useRoute } from '@/lib/route'

const route = useRoute()

defineOptions({ layout: AdminLayout })

interface ModPayload {
  id: number
  name: string
  vendor: string | null
  purchased_from: string | null
  description: string | null
  purchase_date: string | null
  install_date: string | null
  cost: string | null
  url: string | null
  shown_on_timeline: boolean
}

const props = defineProps<{ modification: ModPayload | null }>()

const isEdit = !!props.modification

const form = useForm({
  name: props.modification?.name ?? '',
  vendor: props.modification?.vendor ?? '',
  purchased_from: props.modification?.purchased_from ?? '',
  description: props.modification?.description ?? '',
  purchase_date: props.modification?.purchase_date ?? '',
  install_date: props.modification?.install_date ?? '',
  cost: props.modification?.cost ?? '',
  url: props.modification?.url ?? '',
  shown_on_timeline: props.modification?.shown_on_timeline ?? false,
})

function submit() {
  if (isEdit) {
    form.put(route('admin.vehicle-modifications.update', props.modification!.id))
  } else {
    form.post(route('admin.vehicle-modifications.store'))
  }
}
</script>

<template>
  <Head :title="isEdit ? `Edit ${modification!.name}` : 'New modification'" />

  <PageHeading :title="isEdit ? modification!.name : 'New modification'">
    <template #before>
      <Button :as="Link" :href="route('admin.vehicle-modifications.index')" variant="ghost" size="icon">
        <ArrowLeft class="h-4 w-4" />
      </Button>
    </template>
  </PageHeading>

  <form class="max-w-3xl space-y-6" @submit.prevent="submit">
    <Card title="Details">
      <div class="space-y-5">
        <Field label="Name" for="name" :error="form.errors.name" required>
          <Input id="name" v-model="form.name" required :invalid="!!form.errors.name" />
        </Field>

        <Field label="Description" for="description" :error="form.errors.description">
          <Input id="description" v-model="form.description" :invalid="!!form.errors.description" />
        </Field>

        <div class="grid gap-5 sm:grid-cols-2">
          <Field label="Vendor" for="vendor" :error="form.errors.vendor">
            <Input id="vendor" v-model="form.vendor" :invalid="!!form.errors.vendor" />
          </Field>
          <Field label="Purchased from" for="purchased_from" :error="form.errors.purchased_from">
            <Input id="purchased_from" v-model="form.purchased_from" :invalid="!!form.errors.purchased_from" />
          </Field>
        </div>

        <div class="grid gap-5 sm:grid-cols-2">
          <Field label="Purchase date" for="purchase_date" :error="form.errors.purchase_date">
            <Input id="purchase_date" v-model="form.purchase_date" type="date" :invalid="!!form.errors.purchase_date" />
          </Field>
          <Field label="Install date" for="install_date" :error="form.errors.install_date">
            <Input id="install_date" v-model="form.install_date" type="date" :invalid="!!form.errors.install_date" />
          </Field>
        </div>

        <div class="grid gap-5 sm:grid-cols-2">
          <Field label="Cost" for="cost" :error="form.errors.cost" hint="In dollars; stored as cents.">
            <Input id="cost" v-model="form.cost" type="number" step="0.01" min="0" :invalid="!!form.errors.cost" />
          </Field>
          <Field label="URL" for="url" :error="form.errors.url">
            <Input id="url" v-model="form.url" type="url" :invalid="!!form.errors.url" />
          </Field>
        </div>

        <Switch
          v-model="form.shown_on_timeline"
          label="Show on build timeline"
          description="Appears in the public build timeline on the About page."
        />
      </div>
    </Card>

    <div class="flex items-center gap-3">
      <Button type="submit" :disabled="form.processing">
        {{ form.processing ? 'Saving…' : isEdit ? 'Save changes' : 'Create' }}
      </Button>
      <Button :as="Link" :href="route('admin.vehicle-modifications.index')" variant="secondary">
        Cancel
      </Button>
    </div>
  </form>
</template>
