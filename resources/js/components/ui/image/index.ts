import { cva, type VariantProps } from 'class-variance-authority'

export { default as ResponsiveImage } from './ResponsiveImage.vue'

export const imageVariants = cva('block max-w-full', {
  variants: {
    fit: {
      cover: 'h-full w-full object-cover',
      contain: 'h-full w-full object-contain',
      none: 'h-auto w-full',
    },
    rounded: {
      none: '',
      sm: 'rounded-sm',
      md: 'rounded-md',
      lg: 'rounded-lg',
      full: 'rounded-full',
    },
  },
  defaultVariants: {
    fit: 'cover',
    rounded: 'none',
  },
})

export type ImageVariants = VariantProps<typeof imageVariants>

/**
 * What ImagePresenter emits for one image: a full set of candidates rather
 * than a single rendering, so the browser fetches the size it will actually
 * display.
 */
export interface ResponsiveImageData {
  src: string
  srcset: string
  sizes: string
  width: number | null
  height: number | null
  alt: string
  caption: string | null
  /** Average colour of the source, painted while the real bytes arrive. */
  color: string | null
}
