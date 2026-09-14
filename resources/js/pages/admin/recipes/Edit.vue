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
import IngredientFields, {
  type IngredientRow,
} from '@/components/admin/recipe/IngredientFields.vue'
import { useImageLibrary, type ImageOption } from '@/composables/useImageLibrary'
import Switch from '@/components/admin/ui/Switch.vue'
import Textarea from '@/components/admin/ui/Textarea.vue'
import { useRoute } from '@/lib/route'

const route = useRoute()

defineOptions({ layout: AdminLayout })

interface IngredientGroupRow {
  name: string
  note: string | null
  ingredients: IngredientRow[]
}

interface Tip {
  kind: string
  title: string | null
  body: string
}

interface SectionRow {
  kind: string
  placement: string
  title: string
  intro: string | null
  body: string | null
}

interface Source {
  kind: string
  label: string
  url: string
  note: string
}

interface Step {
  id?: number | null
  title: string | null
  body: string
  image_id: number | null
  tips: Tip[]
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
  yield: string | null
  method_title: string | null
  method_intro: string | null
  is_draft: boolean
  published_at: string | null
  ingredients: IngredientRow[]
  ingredient_groups: IngredientGroupRow[]
  steps: Step[]
  sections: SectionRow[]
  sources: Source[]
}

const props = defineProps<{
  recipe: RecipePayload | null
  mealTypes: Array<{ value: string; label: string }>
  difficulties: Array<{ value: string; label: string }>
  dietaryTags: Array<{ value: string; label: string }>
  cookingMethods: Array<{ value: string; label: string }>
  sourceKinds: Array<{ value: string; label: string }>
  sectionKinds: Array<{ value: string; label: string }>
  sectionPlacements: Array<{ value: string; label: string }>
  tipKinds: Array<{ value: string; label: string }>
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
  yield: props.recipe?.yield ?? '',
  method_title: props.recipe?.method_title ?? '',
  method_intro: props.recipe?.method_intro ?? '',
  is_draft: props.recipe?.is_draft ?? true,
  published_at: props.recipe?.published_at ?? '',
  ingredients: (props.recipe?.ingredients ?? []) as IngredientRow[],
  ingredient_groups: (props.recipe?.ingredient_groups ?? []) as IngredientGroupRow[],
  steps: (props.recipe?.steps ?? []) as Step[],
  sections: (props.recipe?.sections ?? []) as SectionRow[],
  sources: (props.recipe?.sources ?? []) as Source[],
})

function blankIngredient(): IngredientRow {
  return {
    quantity: null,
    unit: null,
    item: '',
    note: null,
    detail: null,
    optional: false,
    in_shopping_list: true,
  }
}

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

        <Field
          label="Yield"
          for="yield"
          :error="form.errors.yield"
          hint="Prose, when a number will not do. Shown instead of the servings count."
        >
          <Input id="yield" v-model="form.yield" placeholder="10 to 12 big servings" :invalid="!!form.errors.yield" />
        </Field>

        <Field label="Dietary" :error="form.errors.dietary">
          <CheckboxGroup v-model="form.dietary" :options="dietaryTags" />
        </Field>

        <Field label="Cooked on" :error="form.errors.cooking_methods" hint="Pick every bit of kit the recipe needs.">
          <CheckboxGroup v-model="form.cooking_methods" :options="cookingMethods" />
        </Field>
      </div>
    </Card>

    <Card
      title="Ingredients"
      description="Split a big cook into its parts. Anything not in a part goes in the loose list."
    >
      <div class="space-y-8">
        <div>
          <h3 class="mb-3 text-sm font-semibold text-zinc-700 dark:text-zinc-300">Parts</h3>

          <Repeater
            v-model="form.ingredient_groups"
            item-label="Part"
            add-label="Add a part"
            :new-row="(): IngredientGroupRow => ({ name: '', note: null, ingredients: [] })"
            empty-message="No parts yet. A short recipe does not need any."
          >
            <template #row="{ row: group, index: g }">
              <div class="space-y-4">
                <div class="grid gap-4 sm:grid-cols-2">
                  <Field label="Part name" :error="err(`ingredient_groups.${g}.name`)" required>
                    <Input
                      v-model="group.name"
                      placeholder="Steak"
                      :invalid="!!err(`ingredient_groups.${g}.name`)"
                    />
                  </Field>
                  <Field
                    label="Note"
                    :error="err(`ingredient_groups.${g}.note`)"
                    hint="The paragraph that follows this part."
                  >
                    <Textarea
                      v-model="group.note"
                      :rows="2"
                      placeholder="The cornstarch is worth bringing."
                      :invalid="!!err(`ingredient_groups.${g}.note`)"
                    />
                  </Field>
                </div>

                <Repeater
                  v-model="group.ingredients"
                  item-label="Ingredient"
                  :new-row="blankIngredient"
                  empty-message="Nothing in this part yet."
                >
                  <template #row="{ row, index: i }">
                    <IngredientFields
                      :row="row"
                      :path="`ingredient_groups.${g}.ingredients.${i}`"
                      :error="err"
                    />
                  </template>
                </Repeater>
              </div>
            </template>
          </Repeater>
        </div>

        <div>
          <h3 class="mb-3 text-sm font-semibold text-zinc-700 dark:text-zinc-300">
            Not in a part
          </h3>

          <Repeater
            v-model="form.ingredients"
            item-label="Ingredient"
            :new-row="blankIngredient"
            empty-message="Nothing loose."
          >
            <template #row="{ row, index }">
              <IngredientFields :row="row" :path="`ingredients.${index}`" :error="err" />
            </template>
          </Repeater>
        </div>
      </div>
    </Card>

    <Card title="Method" description="Give a step a title and it reads like a recipe rather than a list.">
      <div class="space-y-6">
        <div class="grid gap-5 sm:grid-cols-2">
          <Field
            label="Method heading"
            for="method_title"
            :error="form.errors.method_title"
            hint="Left blank it just says “Method”."
          >
            <Input
              id="method_title"
              v-model="form.method_title"
              placeholder="Cooking it on the Skottle"
              :invalid="!!form.errors.method_title"
            />
          </Field>
          <Field
            label="Before the first step"
            for="method_intro"
            :error="form.errors.method_intro"
            hint="What to understand before you start, like how the heat zones work."
          >
            <Textarea
              id="method_intro"
              v-model="form.method_intro"
              :rows="3"
              :invalid="!!form.errors.method_intro"
            />
          </Field>
        </div>

        <Repeater
          v-model="form.steps"
          item-label="Step"
          numbered
          :new-row="(): Step => ({ id: null, title: null, body: '', image_id: null, tips: [] })"
          empty-message="No steps yet."
        >
          <template #row="{ row, index }">
            <div class="space-y-4">
              <Field label="Title" :error="err(`steps.${index}.title`)" hint="Optional, e.g. “Sear the steak”.">
                <Input v-model="row.title" :invalid="!!err(`steps.${index}.title`)" />
              </Field>

              <Field
                label="Instructions"
                :error="err(`steps.${index}.body`)"
                hint="Leave a blank line between paragraphs and they render as separate beats."
                required
              >
                <Textarea v-model="row.body" :rows="4" :invalid="!!err(`steps.${index}.body`)" />
              </Field>

              <Field label="Photo" :error="err(`steps.${index}.image_id`)" hint="What the pan should look like here.">
                <ImagePicker
                  v-model="row.image_id"
                  :options="imageOptions"
                  placeholder="No photo"
                  @uploaded="addImage"
                />
              </Field>

              <div>
                <h4 class="mb-2 text-sm font-semibold text-zinc-700 dark:text-zinc-300">Tips</h4>

                <Repeater
                  v-model="row.tips"
                  item-label="Tip"
                  add-label="Add a tip"
                  :new-row="(): Tip => ({ kind: 'tip', title: null, body: '' })"
                  empty-message="No tips on this step."
                >
                  <template #row="{ row: tip, index: t }">
                    <div class="space-y-4">
                      <div class="grid gap-4 sm:grid-cols-2">
                        <Field label="Kind" :error="err(`steps.${index}.tips.${t}.kind`)" required>
                          <Select v-model="tip.kind" :options="tipKinds" />
                        </Field>
                        <Field label="Title" :error="err(`steps.${index}.tips.${t}.title`)" hint="Optional heading.">
                          <Input v-model="tip.title" placeholder="Do not constantly stir it" />
                        </Field>
                      </div>
                      <Field :error="err(`steps.${index}.tips.${t}.body`)" required>
                        <Textarea v-model="tip.body" :rows="2" :invalid="!!err(`steps.${index}.tips.${t}.body`)" />
                      </Field>
                    </div>
                  </template>
                </Repeater>
              </div>
            </div>
          </template>
        </Repeater>
      </div>
    </Card>

    <Card
      title="Sections"
      description="Prep done at home, a technique worth its own heading, packing lists, feeding a crowd."
    >
      <Repeater
        v-model="form.sections"
        item-label="Section"
        add-label="Add a section"
        :new-row="(): SectionRow => ({ kind: 'prep', placement: 'before_method', title: '', intro: null, body: null })"
        empty-message="No extra sections."
      >
        <template #row="{ row, index }">
          <div class="space-y-4">
            <div class="grid gap-4 sm:grid-cols-2">
              <Field label="Kind" :error="err(`sections.${index}.kind`)" required>
                <Select v-model="row.kind" :options="sectionKinds" />
              </Field>
              <Field
                label="Placement"
                :error="err(`sections.${index}.placement`)"
                hint="Prep has to be read first. A tip about crisping rice does not."
                required
              >
                <Select v-model="row.placement" :options="sectionPlacements" />
              </Field>
            </div>

            <Field label="Title" :error="err(`sections.${index}.title`)" required>
              <Input
                v-model="row.title"
                placeholder="Prep before you leave home"
                :invalid="!!err(`sections.${index}.title`)"
              />
            </Field>

            <Field label="Intro" :error="err(`sections.${index}.intro`)" hint="One or two plain sentences.">
              <Textarea v-model="row.intro" :rows="2" :invalid="!!err(`sections.${index}.intro`)" />
            </Field>

            <Field label="Body" :error="err(`sections.${index}.body`)" hint="Lists, bold, links.">
              <RichTextEditor v-model="row.body" placeholder="The detail…" />
            </Field>
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
