/**
 * Keeps the head in step with the page after a client side navigation.
 *
 * The tags are rendered by the Blade layout so they are in the very first
 * response, which is what a crawler or a link unfurler reads. Inertia then
 * swaps the page without touching them, so from the second page onwards the
 * head still described the first one.
 *
 * That is not only an SEO worry. Chrome's share sheet on a phone reads the
 * canonical rather than the address bar, so sharing a recipe handed someone
 * the homepage. The address bar was right the whole time, which is why
 * copying the URL by hand worked and the share button did not.
 *
 * Inertia already handles the title. Everything else is here.
 */
export interface SeoImage {
  url: string
  width: number
  height: number
  alt: string
}

export interface SeoMeta {
  title: string
  description: string
  canonical: string
  type: string
  index: boolean
  site: string
  image: SeoImage | null
  article?: {
    published?: string
    modified?: string
    section?: string
    tags?: string[]
  } | null
}

/** Upserts one tag, or takes it out when the new page has nothing to say. */
function set(selector: string, create: () => HTMLElement, attr: string, value: string | null) {
  const existing = document.head.querySelector(selector)

  if (value === null) {
    existing?.remove()

    return
  }

  const element = existing ?? document.head.appendChild(create())

  element.setAttribute(attr, value)
}

const meta = (name: string, value: string | null) =>
  set(
    `meta[name="${name}"]`,
    () => Object.assign(document.createElement('meta'), { name }),
    'content',
    value,
  )

const property = (name: string, value: string | null) =>
  set(
    `meta[property="${name}"]`,
    () => {
      const element = document.createElement('meta')
      element.setAttribute('property', name)

      return element
    },
    'content',
    value,
  )

export function syncHead(seo: SeoMeta | undefined) {
  if (!seo || typeof document === 'undefined') return

  meta('description', seo.description)
  meta('robots', seo.index ? 'index, follow, max-image-preview:large' : 'noindex, follow')

  set(
    'link[rel="canonical"]',
    () => Object.assign(document.createElement('link'), { rel: 'canonical' }),
    'href',
    seo.canonical,
  )

  property('og:site_name', seo.site)
  property('og:type', seo.type)
  property('og:title', seo.title)
  property('og:description', seo.description)
  property('og:url', seo.canonical)

  property('og:image', seo.image?.url ?? null)
  property('og:image:width', seo.image ? String(seo.image.width) : null)
  property('og:image:height', seo.image ? String(seo.image.height) : null)
  property('og:image:alt', seo.image?.alt ?? null)
  property('og:image:type', seo.image ? 'image/jpeg' : null)

  meta('twitter:card', seo.image ? 'summary_large_image' : 'summary')
  meta('twitter:title', seo.title)
  meta('twitter:description', seo.description)
  meta('twitter:image', seo.image?.url ?? null)
  meta('twitter:image:alt', seo.image?.alt ?? null)

  /*
   * Article properties belong to a trip or a recipe. Leaving the last
   * one's dates on a listing page would date the listing.
   */
  const article = seo.article ?? null

  property('article:published_time', article?.published ?? null)
  property('article:modified_time', article?.modified ?? null)
  property('article:section', article?.section ?? null)
  property('article:author', article ? 'Reno' : null)

  document.head.querySelectorAll('meta[property="article:tag"]').forEach((tag) => tag.remove())

  for (const tag of article?.tags ?? []) {
    const element = document.createElement('meta')
    element.setAttribute('property', 'article:tag')
    element.setAttribute('content', tag)
    document.head.appendChild(element)
  }

  /*
   * The JSON-LD is deliberately left alone. It is only ever read by
   * something that fetched the URL itself, where the server already put the
   * right graph in the response, and building a second copy of the graph
   * builder in JavaScript to fix what nothing reads would cost more than it
   * is worth.
   */
}
