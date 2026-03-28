<script setup lang="ts">
defineProps<{
  title?: string
  description: string
  variant?: 'info' | 'success' | 'warning' | 'error' | 'destructive'
}>()

const emit = defineEmits<{
  close: []
}>()
</script>

<template>
  <div
    role="alert"
    :class="[
      'inline-flex items-start gap-3 rounded-lg px-4 py-3 shadow-md text-sm',
      variant === 'success'     ? 'bg-green-50 text-green-800 dark:bg-green-900/30 dark:text-green-300' :
      variant === 'warning'     ? 'bg-amber-50 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300' :
      variant === 'error' || variant === 'destructive'
                                ? 'bg-red-50 text-red-800 dark:bg-red-900/30 dark:text-red-300' :
                                  'bg-blue-50 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
    ]"
  >
    <!-- Icon -->
    <span class="mt-0.5 shrink-0">
      <svg v-if="variant === 'success'" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
      <svg v-else-if="variant === 'warning'" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
      <svg v-else-if="variant === 'error' || variant === 'destructive'" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
      <svg v-else xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
    </span>

    <!-- Content -->
    <span class="flex-1">
      <span v-if="title" class="block font-medium">{{ title }}</span>
      <span :class="title ? 'text-xs opacity-80' : ''">{{ description }}</span>
    </span>

    <!-- Close button -->
    <button
      type="button"
      aria-label="Fermer"
      class="ml-1 shrink-0 rounded p-0.5 opacity-60 hover:opacity-100 transition-opacity"
      @click="emit('close')"
    >
      <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
    </button>
  </div>
</template>
