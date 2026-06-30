<script setup lang="ts">
const props = defineProps<{
  to?: string
  href?: string
  variant?: 'brand' | 'secondary' | 'subtle'
  size?: 'xs' | 'sm' | 'base'
  external?: boolean
}>()

const variantClasses = {
  brand: 'text-link-primary hover:text-link-primary-hover hover:underline focus-visible:underline',
  secondary: 'text-gray-450 hover:text-gray-600 hover:underline focus-visible:underline',
  subtle: 'text-text hover:underline focus-visible:underline',
} satisfies Record<string, string>

const sizeClasses = {
  xs: 'text-xs',
  sm: 'text-sm',
  base: 'text-base',
} satisfies Record<string, string>
</script>

<template>
  <NuxtLink
    v-if="to"
    :to="to"
    :class="[
      'font-medium rounded-sm',
      'outline-none focus-visible:ring-2 focus-visible:ring-brand-500/60',
      'transition-colors duration-150',
      variantClasses[variant ?? 'secondary'],
      sizeClasses[size ?? 'xs'],
    ]"
  >
    <slot />
  </NuxtLink>
  <a
    v-else-if="href"
    :href="href"
    :target="external ? '_blank' : undefined"
    :rel="external ? 'noopener noreferrer' : undefined"
    :class="[
      'font-medium rounded-sm',
      'outline-none focus-visible:ring-2 focus-visible:ring-brand-500/60',
      'transition-colors duration-150',
      variantClasses[variant ?? 'secondary'],
      sizeClasses[size ?? 'xs'],
    ]"
  >
    <slot />
  </a>
</template>
