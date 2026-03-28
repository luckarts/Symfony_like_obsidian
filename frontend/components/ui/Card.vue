<script setup lang="ts">
const props = defineProps<{
  variant?: 'flat' | 'bordered' | 'shadow'
}>()

const variantClasses = {
  flat:     '',
  bordered: 'rounded-2xl border border-ui-border',
  shadow:   'rounded-2xl shadow-sm',
} satisfies Record<string, string>
</script>

<template>
  <div
    class="ui-bg p-8 space-y-5"
    :class="variantClasses[props.variant ?? 'flat']"                                                                            
    v-bind="$attrs"
  >
    <slot />
  </div>
</template>
