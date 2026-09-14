<script setup lang="ts">
import { computed } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { Check, ExternalLink, ShieldAlert, Trash2 } from 'lucide-vue-next'
import { AdminLayout } from '@/layouts'
import PageHeading from '@/components/admin/ui/PageHeading.vue'
import Button from '@/components/admin/ui/Button.vue'
import Pagination from '@/components/ui/Pagination.vue'
import RatingStars from '@/components/pages/recipe/RatingStars.vue'
import type { Paginated } from '@/components/admin/ui/table'
import { useRoute } from '@/lib/route'
import { cn } from '@/lib/utils'

const route = useRoute()

defineOptions({ layout: AdminLayout })

interface RatingRow {
  id: number
  stars: number
  status: string
  posted: string | null
  recipe: string | null
  recipeUrl: string | null
  published: { average: number | null; count: number }
  alsoFromAddress: number
}

const props = defineProps<{
  ratings: Paginated<RatingRow>
  filters: { search: string | null; sort: string; direction: string; trashed: string | null }
  status: string
  statuses: Array<{ value: string; label: string }>
  counts: Record<string, number>
}>()

function move(rating: RatingRow, status: string): void {
  router.put(route('admin.ratings.update', rating.id), { status }, { preserveScroll: true })
}

const empty = computed(
  () =>
    ({
      held: 'Nothing held back. Every rating that came in counted.',
      counted: 'No ratings yet.',
      discounted: 'Nothing thrown out.',
    })[props.status] ?? 'Nothing here.',
)
</script>

<template>
  <Head title="Ratings" />

  <PageHeading title="Ratings" />

  <!--
    Why this screen exists, said once and plainly, because a held rating
    looks like a bug until you know it is a decision.
  -->
  <p class="mb-6 max-w-2xl text-sm text-zinc-500 dark:text-zinc-400">
    Anyone can rate without an account, so a rating that cannot be told apart from an
    attempt to move the number is kept but not counted. It is not in the average on the
    page and not in what search engines are told, until you say so here.
  </p>

  <div class="mb-6 flex gap-1 rounded-lg bg-zinc-100 p-1 dark:bg-zinc-800 sm:w-fit">
    <Link
      v-for="option in statuses"
      :key="option.value"
      :href="route('admin.ratings.index', { status: option.value })"
      preserve-scroll
      :class="cn(
        'rounded-md px-3 py-1.5 text-sm font-medium transition-colors',
        status === option.value
          ? 'bg-white text-zinc-900 shadow-sm dark:bg-zinc-900 dark:text-zinc-100'
          : 'text-zinc-500 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-zinc-200',
      )"
    >
      {{ option.label }}
      <span v-if="counts[option.value]" class="ml-1 text-xs opacity-60">
        {{ counts[option.value] }}
      </span>
    </Link>
  </div>

  <p
    v-if="!ratings.data.length"
    class="rounded-lg border border-dashed border-zinc-300 p-10 text-center text-sm text-zinc-500 dark:border-zinc-700 dark:text-zinc-400"
  >
    {{ empty }}
  </p>

  <ul v-else class="space-y-3">
    <li
      v-for="rating in ratings.data"
      :key="rating.id"
      class="flex flex-wrap items-center gap-x-5 gap-y-3 rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900"
    >
      <RatingStars :value="rating.stars" />

      <div class="min-w-0 flex-1">
        <a
          v-if="rating.recipeUrl"
          :href="rating.recipeUrl"
          target="_blank"
          rel="noopener noreferrer"
          class="inline-flex items-center gap-1 font-medium text-zinc-900 hover:text-brand dark:text-zinc-100"
        >
          {{ rating.recipe }} <ExternalLink class="h-3 w-3" />
        </a>
        <span v-else class="font-medium text-zinc-400">Recipe deleted</span>

        <p class="text-xs text-zinc-500 dark:text-zinc-400">
          {{ rating.posted }}
          <!-- What the recipe is publishing as things stand. -->
          <span v-if="rating.published.count">
            · publishing {{ rating.published.average?.toFixed(1) }}
            from {{ rating.published.count }}
          </span>
          <span v-else>· publishing nothing yet</span>
        </p>
      </div>

      <span
        v-if="rating.alsoFromAddress"
        class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800 dark:bg-amber-900/40 dark:text-amber-300"
      >
        <ShieldAlert class="h-3 w-3" />
        {{ rating.alsoFromAddress }} more from this address
      </span>

      <div class="flex gap-2">
        <Button
          v-if="rating.status !== 'counted'"
          size="sm"
          @click="move(rating, 'counted')"
        >
          <Check class="h-3.5 w-3.5" />
          Count it
        </Button>

        <Button
          v-if="rating.status !== 'discounted'"
          size="sm"
          variant="secondary"
          @click="move(rating, 'discounted')"
        >
          <Trash2 class="h-3.5 w-3.5" />
          Throw it out
        </Button>
      </div>
    </li>
  </ul>

  <Pagination v-if="ratings.data.length" :links="ratings.links" class="mt-6" />
</template>
