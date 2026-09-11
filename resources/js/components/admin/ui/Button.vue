<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { Primitive, type PrimitiveProps } from 'reka-ui'
import { cva, type VariantProps } from 'class-variance-authority'
import { cn } from '@/lib/utils'

const adminButton = cva(
  'inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium transition-colors focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 dark:focus-visible:ring-offset-zinc-900',
  {
    variants: {
      variant: {
        default: 'bg-brand text-white hover:bg-brand/90',
        secondary:
          'border border-zinc-300 bg-white text-zinc-800 hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100 dark:hover:bg-zinc-700',
        destructive: 'bg-red-600 text-white hover:bg-red-600/90',
        ghost:
          'text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-100',
      },
      size: {
        default: 'h-9 px-4',
        sm: 'h-8 px-3 text-xs',
        icon: 'h-9 w-9',
      },
    },
    defaultVariants: { variant: 'default', size: 'default' },
  },
)

type AdminButtonVariants = VariantProps<typeof adminButton>

interface Props extends PrimitiveProps {
  variant?: AdminButtonVariants['variant']
  size?: AdminButtonVariants['size']
  class?: HTMLAttributes['class']
}

const props = withDefaults(defineProps<Props>(), { as: 'button' })
</script>

<template>
  <Primitive
    :as="as"
    :as-child="asChild"
    :class="cn(adminButton({ variant, size }), props.class)"
  >
    <slot />
  </Primitive>
</template>
