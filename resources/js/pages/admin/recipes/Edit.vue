<script setup lang="ts">
import { computed } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import { ArrowLeft } from 'lucide-vue-next'
import { AdminLayout } from '@/layouts'
import PageHeading from '@/components/admin/ui/PageHeading.vue'
import Button from '@/components/admin/ui/Button.vue'
import Card from '@/components/admin/ui/Card.vue'
import CheckboxGroup from '@/components/admin/ui/CheckboxGroup.vue'
import Field from '@/components/admin/ui/Field.vue'
import Input from '@/components/admin/ui/Input.vue'
import Repeater from '@/components/admin/ui/Repeater.vue'
import RichTextEditor from '@/components/admin/ui/RichTextEditor.vue'
import Select from '@/components/admin/ui/Select.vue'
import ImagePicker from '@/components/admin/ui/ImagePicker.vue'
import { useImageLibrary, type ImageOption } from '@/composables/useImageLibrary'
import Switch from '@/components/admin/ui/Switch.vue'
import Textarea from '@/components/admin/ui/Textarea.vue'
import { useRoute } from '@/lib/route'

const route = useRoute()

defineOptions({ layout: AdminLayout })

interface Ingredient {
  id?: number | null
  quantity: string | null
  unit: string | null
  item: string
  note: string | null
  in_shopping_list: boolean
}

interface Source {
  kind: string
  label: string
  url: string
  note: string
}

interface Step {
  id?: number | null
  body: string
  note: string
  image_id: number | null
}

interface RecipePayload {
  id: number
  name: string
  slug: string | null
  headline: string | null
  hero_image_id: number | null
  summary: string | null
  notes: string | null
  meal_type: string
  difficulty: string | null
  dietary: string[]
  cooking_methods: string[]
  prep_minutes: number | null
  cook_minutes: number | null
  servings: number | null
  is_draft: boolean
  published_at: string | null
  ingredients: Ingredient[]
  steps: Step[]
  sources: Source[]
}

const props = defineProps<{
  recipe: RecipePayload | null
  mealTypes: Array<{ value: string; label: string }>
  difficulties: Array<{ value: string; label: string }>
  dietaryTags: Array<{ value: string; label: string }>
  cookingMethods: Array<{ value: string; label: string }>
  sourceKinds: Array<{ value: string; label: string }>
  images: ImageOption[]
}>()

const isEdit = !!props.recipe

const { options: imageOptions, add: addImage } = useImageLibrary(props.images)

const form = useForm({
  name: props.recipe?.name ?? '',
  slug: props.recipe?.slug ?? '',
  headline: props.recipe?.headline ?? '',
  hero_image_id: props.recipe?.hero_image_id ?? null,
  summary: props.recipe?.summary ?? '',
  notes: props.recipe?.notes ?? null,
  meal_type: props.recipe?.meal_type ?? 'dinner',
  difficulty: props.recipe?.difficulty ?? null,
  dietary: props.recipe?.dietary ?? [],
  cooking_methods: props.recipe?.cooking_methods ?? [],
  prep_minutes: props.recipe?.prep_minutes ?? null,
  cook_minutes: props.recipe?.cook_minutes ?? null,
  servings: props.recipe?.servings ?? null,
  is_draft: props.recipe?.is_draft ?? true,
  published_at: props.recipe?.published_at ?? '',
  ingredients: (props.recipe?.ingredients ?? []) as Ingredient[],
  steps: (props.recipe?.steps ?? []) as Step[],
  sources: (props.recipe?.sources ?? []) as Source[],
})

const totalMinutes = computed(() => {
  const total = Number(form.prep_minutes ?? 0) + Number(form.cook_minutes ?? 0)
  return total > 0 ? `${total} min total` : undefined
})

function err(key: string): string | undefined {
  return (form.errors as Record<string, string>)[key]
}

function submit() {
  if (isEdit) {
    form.put(route('admin.recipes.update', props.recipe!.id), { preserveScroll: true })
  } else {
    form.post(route('admin.recipes.store'))
  }
}
</script>

<template>
  <Head :title="isEdit ? `Edit ${recipe!.name}` : 'New recipe'" />

  <PageHeading :title="isEdit ? recipe!.name : 'New recipe'">
    <template #before>
      <Button :as="Link" :href="route('admin.recipes.index')" variant="ghost" size="icon">
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
          <Field label="Slug" for="slug" :error="form.errors.slug" hint="Left blank, derived from the name.">
            <Input id="slug" v-model="form.slug" :invalid="!!form.errors.slug" />
          </Field>
        </div>

        <Field label="Headline" for="headline" :error="form.errors.headline">
          <Input id="headline" v-model="form.headline" :invalid="!!form.errors.headline" />
        </Field>

        <Field label="Summary" for="summary" :error="form.errors.summary" hint="Shown on recipe cards.">
          <Textarea id="summary" v-model="form.summary" :rows="3" :invalid="!!form.errors.summary" />
        </Field>

        <Field
          label="Hero image"
          for="hero_image_id"
          :error="form.errors.hero_image_id"
          hint="Upload under Files first; images appear here automatically."
        >
          <ImagePicker
            id="hero_image_id"
            v-model="form.hero_image_id"
            :options="imageOptions"
            placeholder="No image"
            :invalid="!!form.errors.hero_image_id"
            @uploaded="addImage"
          />
        </Field>
      </div>
    </Card>

    <Card title="At a glance" description="These become the badges on a recipe card.">
      <div class="space-y-5">
        <div class="grid gap-5 sm:grid-cols-2">
          <Field label="Meal type" for="meal_type" :error="form.errors.meal_type" required>
            <Select id="meal_type" v-model="form.meal_type" :options="mealTypes" :invalid="!!form.errors.meal_type" />
          </Field>
          <Field label="Difficulty" for="difficulty" :error="form.errors.difficulty">
            <Select
              id="difficulty"
              v-model="form.difficulty"
              :options="difficulties"
              placeholder="Unspecified"
              :invalid="!!form.errors.difficulty"
            />
          </Field>
        </div>

        <div class="grid gap-5 sm:grid-cols-3">
          <Field label="Prep minutes" for="prep_minutes" :error="form.errors.prep_minutes" :hint="totalMinutes">
            <Input id="prep_minutes" v-model="form.prep_minutes" type="number" min="0" :invalid="!!form.errors.prep_minutes" />
          </Field>
          <Field label="Cook minutes" for="cook_minutes" :error="form.errors.cook_minutes">
            <Input id="cook_minutes" v-model="form.cook_minutes" type="number" min="0" :invalid="!!form.errors.cook_minutes" />
          </Field>
          <Field label="Servings" for="servings" :error="form.errors.servings">
            <Input id="servings" v-model="form.servings" type="number" min="1" :invalid="!!form.errors.servings" />
          </Field>
        </div>

        <Field label="Dietary" :error="form.errors.dietary">
          <CheckboxGroup v-model="form.dietary" :options="dietaryTags" />
        </Field>

        <Field label="Cooked on" :error="form.errors.cooking_methods" hint="Pick every bit of kit the recipe needs.">
          <CheckboxGroup v-model="form.cooking_methods" :options="cookingMethods" />
        </Field>
      </div>
    </Card>

    <Card title="Ingredients" description="Quantity and unit are optional — “salt, to taste” works.">
      <Repeater
        v-model="form.ingredients"
        item-label="Ingredient"
        :new-row="(): Ingredient => ({ id: null, quantity: null, unit: null, item: '', note: null, in_shopping_list: true })"
        empty-message="No ingredients yet."
      >
        <template #row="{ row, index }">
          <div class="grid gap-4 sm:grid-cols-4">
            <Field label="Qty" :error="err(`ingredients.${index}.quantity`)">
              <Input v-model="row.quantity" placeholder="1 1/2" :invalid="!!err(`ingredients.${index}.quantity`)" />
            </Field>
            <Field label="Unit" :error="err(`ingredients.${index}.unit`)">
              <Input v-model="row.unit" placeholder="cups" :invalid="!!err(`ingredients.${index}.unit`)" />
            </Field>
            <Field label="Item" :error="err(`ingredients.${index}.item`)" required>
              <Input v-model="row.item" placeholder="rolled oats" :invalid="!!err(`ingredients.${index}.item`)" />
            </Field>
            <Field label="Note" :error="err(`ingredients.${index}.note`)">
              <Input v-model="row.note" placeholder="drained" :invalid="!!err(`ingredients.${index}.note`)" />
            </Field>
            <Field label="Shopping list" :error="err(`ingredients.${index}.in_shopping_list`)">
              <Switch
                v-model="row.in_shopping_list"
                label="Include"
                description="Off for things you already carry."
              />
            </Field>
          </div>
        </template>
      </Repeater>
    </Card>

    <Card title="Method" description="One instruction per step; they render numbered.">
      <Repeater
        v-model="form.steps"
        item-label="Step"
        numbered
        :new-row="(): Step => ({ id: null, body: '', note: '', image_id: null })"
        empty-message="No steps yet."
      >
        <template #row="{ row, index }">
          <div class="space-y-4">
            <Field :error="err(`steps.${index}.body`)" required>
              <Textarea v-model="row.body" :rows="2" :invalid="!!err(`steps.${index}.body`)" />
            </Field>
            <div class="grid gap-4 sm:grid-cols-2">
              <Field label="Aside" :error="err(`steps.${index}.note`)" hint="Shown beside the step, not as part of it.">
                <Input v-model="row.note" placeholder="Watch it, this catches fast" :invalid="!!err(`steps.${index}.note`)" />
              </Field>
              <Field label="Photo" :error="err(`steps.${index}.image_id`)" hint="What the pan should look like here.">
                <ImagePicker
                  v-model="row.image_id"
                  :options="imageOptions"
                  placeholder="No photo"
                  @uploaded="addImage"
                />
              </Field>
            </div>
          </div>
        </template>
      </Repeater>
    </Card>

    <Card title="Where it came from" description="Credit the source, and say what set you off.">
      <Repeater
        v-model="form.sources"
        item-label="Source"
        :new-row="(): Source => ({ kind: 'found', label: '', url: '', note: '' })"
        empty-message="No sources listed."
      >
        <template #row="{ row, index }">
          <div class="grid gap-4 sm:grid-cols-2">
            <Field label="Kind" :error="err(`sources.${index}.kind`)" required>
              <Select v-model="row.kind" :options="sourceKinds" />
            </Field>
            <Field label="Name" :error="err(`sources.${index}.label`)" required>
              <Input v-model="row.label" placeholder="Serious Eats" :invalid="!!err(`sources.${index}.label`)" />
            </Field>
            <Field label="Link" :error="err(`sources.${index}.url`)">
              <Input v-model="row.url" type="url" :invalid="!!err(`sources.${index}.url`)" />
            </Field>
            <Field label="Note" :error="err(`sources.${index}.note`)">
              <Input v-model="row.note" placeholder="Swapped the beans" :invalid="!!err(`sources.${index}.note`)" />
            </Field>
          </div>
        </template>
      </Repeater>
    </Card>

    <Card title="Notes" description="Tips, substitutions, the story behind it.">
      <RichTextEditor v-model="form.notes" placeholder="Anything worth knowing…" />
    </Card>

    <Card title="Publishing">
      <div class="space-y-5">
        <Switch v-model="form.is_draft" label="Draft" description="Drafts are hidden from the public site." />
        <Field label="Publish date" for="published_at" :error="form.errors.published_at">
          <Input id="published_at" v-model="form.published_at" type="datetime-local" :invalid="!!form.errors.published_at" />
        </Field>
      </div>
    </Card>

    <div class="flex items-center gap-3">
      <Button type="submit" :disabled="form.processing">
        {{ form.processing ? 'Saving…' : isEdit ? 'Save changes' : 'Create recipe' }}
      </Button>
      <Button :as="Link" :href="route('admin.recipes.index')" variant="secondary">Cancel</Button>
    </div>
  </form>
</template>
