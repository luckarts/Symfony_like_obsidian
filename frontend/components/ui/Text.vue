<script setup lang="ts">
const props = defineProps<{
  as?: 'span' | 'p' | 'div' | 'label' | 'strong' | 'em'
  size?: 'xs' | 'sm' | 'base' | 'lg' | 'xl'
  color?: 'default' | 'secondary' | 'error' | 'success'
}>()

const tag = computed(() => props.as ?? 'span')

const sizeClasses = {
  xs: 'text-xs',
  sm: 'text-sm',
  base: 'text-base',
  lg: 'text-lg',
  xl: 'text-xl',
} satisfies Record<string, string>

const colorClasses = {
  default: 'text-base-color',
  secondary: 'text-gray-500',
  error: 'text-red-500',
  success: 'text-green-500',
} satisfies Record<string, string>
</script>

<template>
  <component
    :is="tag"
    :class="[
      sizeClasses[size ?? 'sm'],
      colorClasses[color ?? (tag === 'p' ? 'secondary' : 'default')],
    ]"
  >
    <slot />
  </component>
</template>
