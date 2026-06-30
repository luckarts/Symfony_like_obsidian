<script setup lang="ts">
const props = defineProps<{
  title?: string
  description: string
  variant?: 'info' | 'success' | 'warning' | 'error' | 'destructive'
}>()

const emit = defineEmits<{
  close: []
}>()

const variantClasses: Record<string, string> = {
  success: 'toast-success',
  warning: 'toast-warning',
  error: 'toast-error',
  destructive: 'toast-error',
}

const variantClass = computed(() => variantClasses[props.variant ?? ''] ?? 'toast-info')
</script>

<template>
  <div
    role="alert"
    :class="['inline-flex items-start gap-3 rounded-lg px-4 py-3 shadow-md text-sm cursor-pointer', variantClass]"
    @click="emit('close')"
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
  </div>
</template>

<style scoped>
.toast-success { background-color: #f0fdf4; color: #166534; }
.toast-warning { background-color: #fffbeb; color: #92400e; }
.toast-error   { background-color: #fef2f2; color: #991b1b; }
.toast-info    { background-color: #eff6ff; color: #1e40af; }

[data-theme='dark'] .toast-success { background-color: rgba(20, 83, 45, 0.3);   color: #86efac; }
[data-theme='dark'] .toast-warning { background-color: rgba(120, 53, 15, 0.3);  color: #fcd34d; }
[data-theme='dark'] .toast-error   { background-color: rgba(127, 29, 29, 0.3);  color: #fca5a5; }
[data-theme='dark'] .toast-info    { background-color: rgba(30, 58, 138, 0.3);  color: #93c5fd; }
</style>
