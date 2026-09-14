import { ref } from 'vue'

type Theme = 'light' | 'dark'

/**
 * Light or dark, remembered per browser.
 *
 * The class is put on <html> by an inline script in the layout so the page
 * paints in the right theme immediately. This only has to keep the toggle in
 * step with it, and there is one shared ref so every toggle on the page
 * agrees.
 */
const current = ref<Theme>(read())

function read(): Theme {
  if (typeof document === 'undefined') return 'light'

  return document.documentElement.classList.contains('dark') ? 'dark' : 'light'
}

function apply(theme: Theme) {
  current.value = theme
  document.documentElement.classList.toggle('dark', theme === 'dark')
  document.documentElement.style.colorScheme = theme

  try {
    localStorage.setItem('theme', theme)
  } catch {
    // Private browsing refuses to store it. The page still switches.
  }
}

export function useTheme() {
  function toggle() {
    apply(current.value === 'dark' ? 'light' : 'dark')
  }

  return { theme: current, toggle, isDark: current }
}
