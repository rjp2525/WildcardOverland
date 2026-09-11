<script setup lang="ts">
import { computed } from 'vue'
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

interface LinkPayload {
  id: number
  name: string
  aria_label: string | null
  route_name: string
  parent_id: number | null
  order: number
  enabled: boolean
}

const props = defineProps<{
  link: LinkPayload | null
  routeNames: string[]
  parents: Array<{ id: number; name: string }>
}>()

const isEdit = !!props.link

const form = useForm({
  name: props.link?.name ?? '',
  aria_label: props.link?.aria_label ?? '',
  route_name: props.link?.route_name ?? '',
  parent_id: props.link?.parent_id ?? null,
  order: props.link?.order ?? 0,
  enabled: props.link?.enabled ?? true,
})

const routeOptions = computed(() => props.routeNames.map((name) => ({ value: name, label: name })))
const parentOptions = computed(() => props.parents.map((p) => ({ value: p.id, label: p.name })))

function submit() {
  if (isEdit) {
    form.put(route('admin.navigation-links.update', props.link!.id))
  } else {
    form.post(route('admin.navigation-links.store'))
  }
}
</script>

<template>
  <Head :title="isEdit ? `Edit ${link!.name}` : 'New link'" />

  <PageHeading :title="isEdit ? link!.name : 'New link'">
    <template #before>
      <Button :as="Link" :href="route('admin.navigation-links.index')" variant="ghost" size="icon">
        <ArrowLeft class="h-4 w-4" />
      </Button>
    </template>
  </PageHeading>

  <form class="max-w-2xl space-y-6" @submit.prevent="submit">
    <Card title="Link">
      <div class="space-y-5">
        <Field label="Name" for="name" :error="form.errors.name" required>
          <Input id="name" v-model="form.name" required :invalid="!!form.errors.name" />
        </Field>

        <Field
          label="Aria label"
          for="aria_label"
          :error="form.errors.aria_label"
          hint="Optional accessible label, when the visible name isn't descriptive."
        >
          <Input id="aria_label" v-model="form.aria_label" :invalid="!!form.errors.aria_label" />
        </Field>

        <Field label="Route" for="route_name" :error="form.errors.route_name" required>
          <Select
            id="route_name"
            v-model="form.route_name"
            :options="routeOptions"
            placeholder="Choose a route…"
            :invalid="!!form.errors.route_name"
          />
        </Field>

        <div class="grid gap-5 sm:grid-cols-2">
          <Field label="Parent" for="parent_id" :error="form.errors.parent_id">
            <Select
              id="parent_id"
              v-model="form.parent_id"
              :options="parentOptions"
              placeholder="No parent (top level)"
              :invalid="!!form.errors.parent_id"
            />
          </Field>

          <Field label="Order" for="order" :error="form.errors.order">
            <Input id="order" v-model="form.order" type="number" min="0" :invalid="!!form.errors.order" />
          </Field>
        </div>

        <Switch v-model="form.enabled" label="Enabled" description="Hidden from the site when off." />
      </div>
    </Card>

    <div class="flex items-center gap-3">
      <Button type="submit" :disabled="form.processing">
        {{ form.processing ? 'Saving…' : isEdit ? 'Save changes' : 'Create' }}
      </Button>
      <Button :as="Link" :href="route('admin.navigation-links.index')" variant="secondary">Cancel</Button>
    </div>
  </form>
</template>
