<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import {
  Boxes,
  CheckCircle2,
  FileImage,
  Files,
  LayoutDashboard,
  Link2,
  MessageSquare,
  LogOut,
  Map,
  ChefHat,
  Menu,
  Moon,
  SunMedium,
  Wrench,
  X,
  XCircle,
} from 'lucide-vue-next'
import Button from '@/components/admin/ui/Button.vue'
import type { PageProps } from '@/types/PageProps'
import { cn } from '@/lib/utils'
import { useRoute } from '@/lib/route'
import { useTheme } from '@/composables/useTheme'

const route = useRoute()

const { theme, toggle } = useTheme()

const page = usePage<PageProps>()

const user = computed(() => page.props.auth?.user ?? null)
const flash = computed(() => page.props.flash ?? { success: null, error: null })

const sidebarOpen = ref(false)
const toast = ref<{ type: 'success' | 'error'; message: string } | null>(null)
let toastTimer: ReturnType<typeof setTimeout> | undefined

watch(
  flash,
  (value) => {
    const message = value?.success ?? value?.error
    if (!message) return

    toast.value = { type: value.success ? 'success' : 'error', message }
    clearTimeout(toastTimer)
    toastTimer = setTimeout(() => (toast.value = null), 4000)
  },
  { deep: true, immediate: true },
)

const pending = computed(() => page.props.moderation?.pending ?? 0)

const nav = [
  { label: 'Dashboard', icon: LayoutDashboard, routeName: 'admin.dashboard' },
  { label: 'Trips', icon: Map, routeName: 'admin.trips.index' },
  { label: 'Recipes', icon: ChefHat, routeName: 'admin.recipes.index' },
  { label: 'Comments', icon: MessageSquare, routeName: 'admin.comments.index', badge: true },
  { label: 'Modifications', icon: Wrench, routeName: 'admin.vehicle-modifications.index' },
  { label: 'Brands', icon: Boxes, routeName: 'admin.brands.index' },
  { label: 'Images', icon: FileImage, routeName: 'admin.images.index' },
  { label: 'Files', icon: Files, routeName: 'admin.files.index' },
  { label: 'Navigation', icon: Link2, routeName: 'admin.navigation-links.index' },
]

function isCurrent(routeName: string): boolean {
  // `admin.dashboard` should not light up for every nested admin route.
  return routeName === 'admin.dashboard'
    ? route().current('admin.dashboard')
    : route().current(routeName.replace('.index', '.*'))
}

function logout() {
  router.post(route('admin.logout'))
}
</script>

<template>
  <div class="min-h-screen bg-zinc-50 dark:bg-zinc-950">
    <!-- Mobile backdrop -->
    <div
      v-if="sidebarOpen"
      class="fixed inset-0 z-30 bg-black/50 lg:hidden"
      @click="sidebarOpen = false"
    />

    <aside
      :class="
        cn(
          'fixed inset-y-0 left-0 z-40 flex w-64 flex-col border-r border-zinc-200 bg-white transition-transform dark:border-zinc-800 dark:bg-zinc-900 lg:translate-x-0',
          sidebarOpen ? 'translate-x-0' : '-translate-x-full',
        )
      "
    >
      <div
        class="flex h-16 items-center justify-between border-b border-zinc-200 px-5 dark:border-zinc-800"
      >
        <Link :href="route('admin.dashboard')" class="font-brand text-lg font-extrabold uppercase text-brand">
          Wildcard
        </Link>
        <Button variant="ghost" size="icon" class="lg:hidden" @click="sidebarOpen = false">
          <X class="h-5 w-5" />
        </Button>
      </div>

      <nav class="flex-1 space-y-1 overflow-y-auto p-3">
        <Link
          v-for="item in nav"
          :key="item.routeName"
          :href="route(item.routeName)"
          :class="
            cn(
              'flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition-colors',
              isCurrent(item.routeName)
                ? 'bg-brand/10 text-brand'
                : 'text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-100',
            )
          "
          @click="sidebarOpen = false"
        >
          <component :is="item.icon" class="h-4 w-4 shrink-0" />
          {{ item.label }}

          <!-- What is waiting to be read, so it does not sit for a week. -->
          <span
            v-if="item.badge && pending"
            class="ml-auto rounded-full bg-brand px-1.5 py-0.5 text-[0.65rem] font-bold leading-none text-white"
          >
            {{ pending }}
          </span>
        </Link>
      </nav>

      <div class="border-t border-zinc-200 p-3 dark:border-zinc-800">
        <div class="px-2 pb-2">
          <p class="truncate text-sm font-medium text-zinc-800 dark:text-zinc-200">
            {{ user?.name }}
          </p>
          <p class="truncate text-xs text-zinc-500 dark:text-zinc-400">{{ user?.email }}</p>
        </div>
        <Button variant="ghost" class="w-full justify-start" @click="toggle">
          <component :is="theme === 'dark' ? SunMedium : Moon" class="h-4 w-4" />
          {{ theme === 'dark' ? 'Light mode' : 'Dark mode' }}
        </Button>
        <Button variant="ghost" class="w-full justify-start" @click="logout">
          <LogOut class="h-4 w-4" />
          Sign out
        </Button>
      </div>
    </aside>

    <div class="lg:pl-64">
      <!--
        Inertia passes a page into the layout's DEFAULT slot only, so pages
        cannot fill named slots here. Page titles and actions are rendered by
        the PageHeading component inside each page instead.
      -->
      <header
        class="sticky top-0 z-20 flex h-16 items-center gap-3 border-b border-zinc-200 bg-white/80 px-4 backdrop-blur-sm dark:border-zinc-800 dark:bg-zinc-900/80 lg:hidden"
      >
        <Button variant="ghost" size="icon" @click="sidebarOpen = true">
          <Menu class="h-5 w-5" />
        </Button>
        <span class="font-brand text-base font-extrabold uppercase text-brand">Wildcard</span>
        <Button
          variant="ghost"
          size="icon"
          class="ml-auto"
          :aria-label="theme === 'dark' ? 'Switch to light mode' : 'Switch to dark mode'"
          @click="toggle"
        >
          <component :is="theme === 'dark' ? SunMedium : Moon" class="h-5 w-5" />
        </Button>
      </header>

      <main class="p-4 sm:p-6">
        <slot />
      </main>
    </div>

    <!-- Flash toast -->
    <Transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="translate-y-2 opacity-0"
      leave-active-class="transition duration-150 ease-in"
      leave-to-class="translate-y-2 opacity-0"
    >
      <div
        v-if="toast"
        role="status"
        aria-live="polite"
        :class="
          cn(
            'fixed bottom-4 right-4 z-50 flex items-center gap-2 rounded-lg px-4 py-3 text-sm text-white shadow-lg',
            toast.type === 'success' ? 'bg-emerald-600' : 'bg-red-600',
          )
        "
      >
        <component :is="toast.type === 'success' ? CheckCircle2 : XCircle" class="h-4 w-4" />
        {{ toast.message }}
      </div>
    </Transition>
  </div>
</template>
