/**
 * Shared DataTable types. These live outside the SFC because `<script setup>`
 * only hoists type exports that precede every other statement.
 */
export interface Column {
    key: string
    label: string
    sortable?: boolean
    class?: string
}

export interface Paginated<T> {
    data: T[]
    links: Array<{ url: string | null; label: string; active: boolean }>
    from: number | null
    to: number | null
    total: number
}
