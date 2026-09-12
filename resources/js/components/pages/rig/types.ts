export interface BuildLayer {
  value: string
  label: string
  depth: number
}

export interface BuildPart {
  id: number
  name: string
  vendor: string | null
  description: string | null
  installed_label: string | null
  layer: string | null
  /** Percentages of the illustration box; null when the part is not placed. */
  hotspot: { x: number; y: number } | null
  buyUrl: string | null
  isAffiliate: boolean
}
