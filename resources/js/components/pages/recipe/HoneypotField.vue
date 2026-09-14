<script setup lang="ts">
defineProps<{ name: string }>()

const value = defineModel<string>({ required: true })
</script>

<template>
  <!--
    The trap. Off the side of the page rather than display:none, because
    the things worth catching skip anything that is obviously hidden and
    fill in anything that looks like a form field. Taken out of the tab
    order and off the accessibility tree so nobody using a keyboard or a
    screen reader ever meets it.
  -->
  <div aria-hidden="true" class="pointer-events-none absolute -left-[9999px] h-0 w-0 overflow-hidden">
    <label :for="`trap-${name}`">Website</label>
    <input
      :id="`trap-${name}`"
      v-model="value"
      :name="name"
      type="text"
      tabindex="-1"
      autocomplete="off"
    >
  </div>
</template>
