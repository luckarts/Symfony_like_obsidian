<script setup lang="ts">
const props = defineProps<{
  size?: 'sm' | 'md' | 'lg'
}>()

const sizePx = computed(() => ({ sm: 32, md: 48, lg: 64 }[props.size ?? 'md']))
</script>

<template>
  <svg
    :width="sizePx"
    :height="sizePx"
    viewBox="0 0 48 48"
    fill="none"
    xmlns="http://www.w3.org/2000/svg"
  >
    <circle cx="24" cy="24" r="24" fill="#1F1F1F" />
    <line x1="12" y1="18" x2="36" y2="18" stroke="white" stroke-width="2.5" stroke-linecap="round" />
    <line x1="12" y1="24" x2="36" y2="24" stroke="white" stroke-width="2.5" stroke-linecap="round" />
    <line x1="12" y1="30" x2="36" y2="30" stroke="white" stroke-width="2.5" stroke-linecap="round" />
  </svg>
</template>
