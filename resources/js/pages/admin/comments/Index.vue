<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { Check, ExternalLink, Search, ShieldAlert, Undo2 } from 'lucide-vue-next'
import { AdminLayout } from '@/layouts'
import PageHeading from '@/components/admin/ui/PageHeading.vue'
import Button from '@/components/admin/ui/Button.vue'
import ConfirmDelete from '@/components/admin/ui/ConfirmDelete.vue'
import Pagination from '@/components/ui/Pagination.vue'
import { ResponsiveImage, type ResponsiveImageData } from '@/components/ui/image'
import type { Paginated } from '@/components/admin/ui/table'
import { useRoute } from '@/lib/route'
import { cn } from '@/lib/utils'

const route = useRoute()

defineOptions({ layout: AdminLayout })

interface CommentRow {
  id: number
  name: string
  body: string
  status: string
  posted: string | null
  recipe: string | null
  recipeUrl: string | null
  photo: ResponsiveImageData | null
  /** Other submissions from the same address. */
  alsoFrom: number
}

const props = defineProps<{
  comments: Paginated<CommentRow>
  filters: { search: string | null; sort: string; direction: string; trashed: string | null }
  status: string
  statuses: Array<{ value: string; label: string }>
  counts: Record<string, number>
}>()

const search = ref(props.filters.search ?? '')

/*
 * Waits for them to stop typing. Every keystroke is a request otherwise,
 * and the answers come back out of order.
 */
let timer: ReturnType<typeof setTimeout>

watch(search, (value) => {
  clearTimeout(timer)

  timer = setTimeout(() => {
    router.get(
      route('admin.comments.index'),
      { status: props.status, search: value || undefined },
      { preserveState: true, replace: true },
    )
  }, 300)
})

function move(comment: CommentRow, status: string): void {
  router.put(
    route('admin.comments.update', comment.id),
    { status },
    { preserveScroll: true },
  )
}

const empty = computed(() =>
  ({
    pending: 'Nothing waiting. Everything sent in has been read.',
    approved: 'Nothing approved yet.',
    spam: 'No spam. The honeypot is doing its job.',
  })[props.status] ?? 'Nothing here.',
)
</script>

<template>
  <Head title="Comments" />

  <PageHeading title="Comments" />

  <div class="mb-6 flex flex-wrap items-center gap-3">
    <div class="flex gap-1 rounded-lg bg-zinc-100 p-1 dark:bg-zinc-800">
      <Link
        v-for="option in statuses"
        :key="option.value"
        :href="route('admin.comments.index', { status: option.value })"
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

    <div class="relative ml-auto">
      <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-zinc-400" />
      <input
        v-model="search"
        type="search"
        placeholder="Search names and words…"
        class="h-9 w-64 rounded-md border border-zinc-300 bg-white pl-9 pr-3 text-sm text-zinc-900 placeholder:text-zinc-400 focus:border-brand focus:outline-hidden focus:ring-2 focus:ring-brand/30 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100"
      >
    </div>
  </div>

  <p
    v-if="!comments.data.length"
    class="rounded-lg border border-dashed border-zinc-300 p-10 text-center text-sm text-zinc-500 dark:border-zinc-700 dark:text-zinc-400"
  >
    {{ empty }}
  </p>

  <ul v-else class="space-y-4">
    <li
      v-for="comment in comments.data"
      :key="comment.id"
      class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900"
    >
      <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1">
        <p class="font-medium text-zinc-900 dark:text-zinc-100">{{ comment.name }}</p>

        <a
          v-if="comment.recipeUrl"
          :href="comment.recipeUrl"
          target="_blank"
          rel="noopener noreferrer"
          class="inline-flex items-center gap-1 text-sm text-brand hover:underline"
        >
          {{ comment.recipe }} <ExternalLink class="h-3 w-3" />
        </a>

        <span class="text-xs text-zinc-400">{{ comment.posted }}</span>

        <!--
          The same address turning up over and over. Two is somebody who
          liked two recipes; twenty in an hour is not a person.
        -->
        <span
          v-if="comment.alsoFrom >= 3"
          class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800 dark:bg-amber-900/40 dark:text-amber-300"
        >
          <ShieldAlert class="h-3 w-3" />
          {{ comment.alsoFrom }} others from this address
        </span>
      </div>

      <div class="mt-3 flex gap-4">
        <!--
          Uploaded private and still private: this is a signed URL, and it
          only becomes a public one on approval.
        -->
        <div v-if="comment.photo" class="aspect-[4/3] w-40 shrink-0 overflow-hidden rounded-md bg-zinc-100 dark:bg-zinc-800">
          <ResponsiveImage :image="comment.photo" class="h-full w-full" />
        </div>

        <p class="min-w-0 flex-1 whitespace-pre-line text-sm text-zinc-700 dark:text-zinc-300">
          {{ comment.body }}
        </p>
      </div>

      <div class="mt-4 flex flex-wrap items-center gap-2 border-t border-zinc-100 pt-3 dark:border-zinc-800">
        <Button
          v-if="comment.status !== 'approved'"
          size="sm"
          @click="move(comment, 'approved')"
        >
          <Check class="h-3.5 w-3.5" />
          Put it on the page
        </Button>

        <Button
          v-if="comment.status !== 'pending'"
          size="sm"
          variant="secondary"
          @click="move(comment, 'pending')"
        >
          <Undo2 class="h-3.5 w-3.5" />
          Back to the queue
        </Button>

        <Button
          v-if="comment.status !== 'spam'"
          size="sm"
          variant="secondary"
          @click="move(comment, 'spam')"
        >
          <ShieldAlert class="h-3.5 w-3.5" />
          Spam
        </Button>

        <ConfirmDelete
          class="ml-auto"
          :url="route('admin.comments.destroy', comment.id)"
          title="Delete this comment?"
          :description="`Everything ${comment.name} sent in goes with it, photograph included. This cannot be undone.`"
          confirm-label="Delete for good"
        />
      </div>
    </li>
  </ul>

  <Pagination v-if="comments.data.length" :links="comments.links" class="mt-6" />
</template>
