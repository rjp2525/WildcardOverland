import { onMounted, ref } from 'vue'

/**
 * Mirrors Honeypot::MIN_SECONDS with a second in hand, because the server
 * measures from when it handed the form out and the clock here starts a
 * moment later.
 */
const SETTLE_MS = 4000

/**
 * Keeps an honest submission from tripping the server's own bot check.
 *
 * The form is refused if it comes back faster than a person could have
 * filled it in. Typing a comment takes longer than that on its own, but
 * clicking a star does not, and somebody who reads a recipe and taps five
 * should not be told they are a robot. So a quick one waits out the rest of
 * the interval instead of being sent and rejected.
 */
export function useHoneypot() {
  const shownAt = ref(Date.now())

  onMounted(() => {
    shownAt.value = Date.now()
  })

  function whenSettled(send: () => void): void {
    const waited = Date.now() - shownAt.value

    if (waited >= SETTLE_MS) {
      send()

      return
    }

    window.setTimeout(send, SETTLE_MS - waited)
  }

  return { whenSettled }
}
