<script setup lang="ts">
const props = defineProps<{
  variant?: 'primary' | 'ghost' | 'outline'
  size?: 'sm' | 'md'
  type?: 'button' | 'submit'
  fullWidth?: boolean
  loading?: boolean
  disabled?: boolean
}>()

const emit = defineEmits<{
  click: []
}>()

const variantClasses = {
  primary: 'bg-brand-500 text-white hover:bg-brand-600 active:bg-brand-700 dark:bg-brand-400 dark:hover:bg-brand-500',
  ghost:   'bg-transparent text-gray-600 hover:bg-gray-100 active:bg-gray-200 dark:text-white dark:hover:bg-white/10',
  outline: 'border border-gray-200 bg-white text-gray-600 hover:bg-gray-50 active:bg-gray-100 dark:border-white/10 dark:bg-transparent dark:text-white dark:hover:bg-white/10',
} satisfies Record<string, string>

const sizeClasses = {
  sm: 'h-8 px-3 text-xs',
  md: 'h-10 px-4 text-sm',
} satisfies Record<string, string>

const isDisabled = computed(() => props.disabled || props.loading)
</script>

<template>
  <button
    :type="type ?? 'button'"
    :disabled="isDisabled"
    :class="[
      'inline-flex items-center justify-center gap-2 rounded-xl font-medium',
      'transition-colors duration-150',
      'disabled:cursor-not-allowed disabled:opacity-40',
      variantClasses[variant ?? 'primary'],
      sizeClasses[size ?? 'md'],
      fullWidth && 'w-full',
    ]"
    @click="!isDisabled && emit('click')"
  >
    <svg
      v-if="loading"
      class="h-4 w-4 animate-spin"
      fill="none"
      viewBox="0 0 24 24"
    >
      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
    </svg>
    <slot v-if="!loading" name="leading" />
    <slot />
  </button>
</template>
