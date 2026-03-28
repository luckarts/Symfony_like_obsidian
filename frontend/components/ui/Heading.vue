<script setup lang="ts">
const props = defineProps<{
  level?: 1 | 2 | 3 | 4 | 5 | 6
  size?: 'sm' | 'base' | 'lg' | 'xl' | '2xl'
}>()

type Size = 'sm' | 'base' | 'lg' | 'xl' | '2xl'

const tag = computed(() => `h${props.level ?? 2}`)

const sizeClasses: Record<Size, string> = {
  sm:   'text-sm',
  base: 'text-base',
  lg:   'text-lg',
  xl:   'text-xl',
  '2xl': 'text-2xl',
}

const defaultSize: Record<1 | 2 | 3 | 4 | 5 | 6, Size> = {
  1: '2xl',
  2: 'xl',
  3: 'lg',
  4: 'base',
  5: 'sm',
  6: 'sm',
}
</script>

<template>
  <component
    :is="tag"
    class="font-semibold leading-tight text-heading-color"
    :class="sizeClasses[size ?? defaultSize[level ?? 2]]"
  >
    <slot />
  </component>
</template>
