<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3'
import Button from '@/components/admin/ui/Button.vue'
import Field from '@/components/admin/ui/Field.vue'
import Input from '@/components/admin/ui/Input.vue'
import Switch from '@/components/admin/ui/Switch.vue'
import { BlankLayout } from '@/layouts'
import { useRoute } from '@/lib/route'

const route = useRoute()

// Without this the page would inherit the public marketing navbar.
defineOptions({ layout: BlankLayout })

const form = useForm({
  email: '',
  password: '',
  remember: false,
})

function submit() {
  form.post(route('admin.login.store'), {
    onFinish: () => form.reset('password'),
  })
}
</script>

<template>
  <Head title="Sign in" />

  <div class="flex min-h-screen items-center justify-center bg-zinc-50 px-4 dark:bg-zinc-950">
    <div class="w-full max-w-sm">
      <div class="mb-8 text-center">
        <p class="font-brand text-3xl font-extrabold uppercase text-brand">Wildcard</p>
        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Sign in to manage the site</p>
      </div>

      <form
        class="space-y-5 rounded-lg border border-zinc-200 bg-white p-6 shadow-xs dark:border-zinc-800 dark:bg-zinc-900"
        @submit.prevent="submit"
      >
        <Field label="Email" for="email" :error="form.errors.email" required>
          <Input
            id="email"
            v-model="form.email"
            type="email"
            autocomplete="username"
            autofocus
            required
            :invalid="!!form.errors.email"
          />
        </Field>

        <Field label="Password" for="password" :error="form.errors.password" required>
          <Input
            id="password"
            v-model="form.password"
            type="password"
            autocomplete="current-password"
            required
            :invalid="!!form.errors.password"
          />
        </Field>

        <Switch v-model="form.remember" label="Remember me" />

        <Button type="submit" class="w-full" :disabled="form.processing">
          {{ form.processing ? 'Signing in…' : 'Sign in' }}
        </Button>
      </form>
    </div>
  </div>
</template>
