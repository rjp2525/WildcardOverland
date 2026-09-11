import { inject } from 'vue'
import { route as ziggyRoute } from '../../../vendor/tightenco/ziggy'

/**
 * Ziggy's Vue plugin exposes `route` to templates via globalProperties and to
 * components via provide/inject - but not to `<script setup>` bodies. Calling
 * the bare global there works in the browser (window.Ziggy is set by the
 * @routes directive) yet throws under SSR, where no such global exists.
 *
 * Injecting the plugin-bound function keeps the configured routes in both
 * environments.
 */
export function useRoute(): typeof ziggyRoute {
    return inject<typeof ziggyRoute>('route', ziggyRoute)
}
