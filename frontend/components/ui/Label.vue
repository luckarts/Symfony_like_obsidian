<script setup lang="ts">
defineProps<{
  for?: string
  required?: boolean
}>()
</script>

<template>
  <label
    :for="$props.for"
    class="block text-sm font-medium text-base-color"
  >
    <slot />
    <span v-if="required" class="ml-0.5 text-red-500" aria-hidden="true">*</span>
  </label>
</template>
