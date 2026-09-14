import { onMounted, ref, toValue, watch, type MaybeRefOrGetter } from 'vue'

/** Everything this stores lives under one prefix, so it can be swept. */
const PREFIX = 'wildcard:checklist:'

/** A list older than this is from a shop you have long since done. */
const KEEP_DAYS = 45

interface Stored {
  ticked: string[]
  at: number
}

function read(key: string): Stored | null {
  try {
    const raw = localStorage.getItem(PREFIX + key)

    if (!raw) return null

    const parsed = JSON.parse(raw) as Stored

    return Array.isArray(parsed?.ticked) ? parsed : null
  } catch {
    // Private browsing, blocked storage, or something else wrote here.
    return null
  }
}

/**
 * Drops lists nobody is coming back to.
 *
 * Without this every recipe anyone ever opened keeps a row forever, and the
 * one thing worse than losing a checklist is finding a stale one already
 * half ticked.
 */
function sweep() {
  try {
    const cutoff = Date.now() - KEEP_DAYS * 86_400_000

    for (const key of Object.keys(localStorage)) {
      if (!key.startsWith(PREFIX)) continue

      const stored = read(key.slice(PREFIX.length))

      if (!stored || stored.at < cutoff) localStorage.removeItem(key)
    }
  } catch {
    // Nothing to sweep if we cannot read it.
  }
}

/**
 * Ticking ingredients off, remembered between visits.
 *
 * Kept in the browser rather than sent anywhere: it is one person's progress
 * through one shop, it is nobody else's business, and it should not cost a
 * cookie header on every request for the whole site.
 *
 * Ids are the caller's problem and must be stable across edits. Position
 * will not do: add an ingredient to the top of a part and every tick below
 * it would slide onto the wrong line, which is worse than losing them.
 */
export function useChecklist(source: MaybeRefOrGetter<string>) {
  const ticked = ref<Set<string>>(new Set())
  const ready = ref(false)

  function load() {
    const stored = read(toValue(source))

    ticked.value = new Set(stored?.ticked ?? [])
  }

  /*
   * After mount, never during setup. The server has no localStorage, so
   * reading it earlier renders one thing on the server and another on the
   * client, and Vue throws away the markup it was given.
   */
  onMounted(() => {
    sweep()
    load()
    ready.value = true
  })

  /*
   * Inertia keeps the component alive between two recipes, so the key can
   * change underneath us. Without this you would carry the last recipe's
   * ticks onto the next one.
   */
  watch(() => toValue(source), () => {
    if (ready.value) load()
  })

  function save() {
    const key = PREFIX + toValue(source)

    try {
      if (ticked.value.size === 0) {
        localStorage.removeItem(key)

        return
      }

      localStorage.setItem(
        key,
        JSON.stringify({ ticked: [...ticked.value], at: Date.now() } satisfies Stored),
      )
    } catch {
      // Storage refused it. The page still works for this visit.
    }
  }

  function toggle(id: string) {
    const next = new Set(ticked.value)
    next.has(id) ? next.delete(id) : next.add(id)
    ticked.value = next
    save()
  }

  function clear() {
    ticked.value = new Set()
    save()
  }

  return { ticked, ready, toggle, clear }
}
