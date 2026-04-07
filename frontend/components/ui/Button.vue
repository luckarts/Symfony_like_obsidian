<script setup lang="ts">
const props = defineProps<{
  variant?: 'primary' | 'ghost' | 'outline' | 'secondary' | 'danger'
  size?: 'sm' | 'md' | 'icon'
  type?: 'button' | 'submit'
  fullWidth?: boolean
  pill?: boolean
  loading?: boolean
  disabled?: boolean
}>()

const emit = defineEmits<{
  click: []
}>()

const variantClasses = {
  primary:
    'bg-btn-primary text-white border border-btn-primary-border hover:bg-btn-primary-hover border-btn-primary-hover-border active:bg-brand-700',
  ghost:
    'bg-transparent text-btn-text-secondary border hover:bg-btn-hover-bg border-ui-border active:bg-btn-active-bg',
  outline:
    'border border-ui-border bg-btn-outline-bg text-btn-text-secondary hover:bg-btn-hover-bg active:bg-btn-active-bg',
  secondary: 'bg-gray-300 text-gray-800 border border-ui-border hover:bg-gray-350',
  danger:
    'bg-transparent text-btn-danger-text border border-transparent hover:bg-btn-danger-hover-bg hover:border-btn-danger-hover-border active:bg-btn-danger-active-bg',
} satisfies Record<string, string>

const sizeClasses = {
  sm: 'h-8 px-3 text-xs',
  md: 'h-10 px-4 text-sm',
  icon: 'size-8 p-0 text-xs',
} satisfies Record<string, string>

const isDisabled = computed(() => props.disabled || props.loading)
</script>

<template>
  <button
    :type="type ?? 'button'"
    :disabled="isDisabled"
    :class="[
      'inline-flex items-center justify-center gap-2 font-medium',
      pill ? 'rounded-full' : 'rounded-xl',
      'transition-colors duration-150',
      'disabled:cursor-not-allowed disabled:opacity-40',
      variantClasses[variant ?? 'ghost'],
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
