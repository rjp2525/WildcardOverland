<script setup lang="ts">
import Field from '@/components/admin/ui/Field.vue'
import Input from '@/components/admin/ui/Input.vue'
import Switch from '@/components/admin/ui/Switch.vue'
import Textarea from '@/components/admin/ui/Textarea.vue'

export interface IngredientRow {
  quantity: string | null
  unit: string | null
  item: string
  note: string | null
  /** Sub-bullets, one per line. */
  detail: string | null
  optional: boolean
  in_shopping_list: boolean
}

defineProps<{
  row: IngredientRow
  /** Error key prefix, e.g. "ingredient_groups.0.ingredients.2". */
  path: string
  error: (key: string) => string | undefined
}>()
</script>

<template>
  <div class="space-y-4">
    <div class="grid gap-4 sm:grid-cols-4">
      <Field label="Qty" :error="error(`${path}.quantity`)">
        <Input v-model="row.quantity" placeholder="1 1/2" :invalid="!!error(`${path}.quantity`)" />
      </Field>
      <Field label="Unit" :error="error(`${path}.unit`)">
        <Input v-model="row.unit" placeholder="cups" :invalid="!!error(`${path}.unit`)" />
      </Field>
      <Field label="Item" :error="error(`${path}.item`)" required>
        <Input v-model="row.item" placeholder="rolled oats" :invalid="!!error(`${path}.item`)" />
      </Field>
      <Field label="Note" :error="error(`${path}.note`)">
        <Input v-model="row.note" placeholder="drained" :invalid="!!error(`${path}.note`)" />
      </Field>
    </div>

    <Field
      label="Sub-bullets"
      :error="error(`${path}.detail`)"
      hint="One per line. Which cut to buy, what to swap in."
    >
      <Textarea
        v-model="row.detail"
        :rows="2"
        placeholder="Sirloin is the best balance of flavour and price"
        :invalid="!!error(`${path}.detail`)"
      />
    </Field>

    <div class="grid gap-4 sm:grid-cols-2">
      <Switch
        v-model="row.optional"
        label="Optional"
        description="Marked “optional but excellent” on the page."
      />
      <Switch
        v-model="row.in_shopping_list"
        label="Shopping list"
        description="Off for things you already carry."
      />
    </div>
  </div>
</template>
