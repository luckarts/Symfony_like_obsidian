<script setup lang="ts">
defineProps<{
  modelValue?: string
  type?: 'text' | 'email' | 'password'
  label?: string
  placeholder?: string
  name?: string
  error?: string
  disabled?: boolean
}>()

const emit = defineEmits<{
  'update:modelValue': [value: string]
}>()
</script>

<template>
  <div class="flex flex-col gap-1">
    <Label v-if="label" :for="name">{{ label }}</Label>

    <div
      :class="[
        'flex h-12 w-full items-center gap-2 rounded-xl border bg-white/0 px-3',
        'text-sm text-base-color',
        'transition-colors duration-150',
        error
          ? 'border-red-400 focus-within:border-red-500'
          : 'border-ui-border focus-within:border-brand-500',
        disabled && 'cursor-not-allowed opacity-40',
      ]"
    >
      <slot name="prefix" />
      <input
        :id="name"
        :type="type ?? 'text'"
        :value="modelValue"
        :placeholder="placeholder"
        :name="name"
        :disabled="disabled"
        class="min-w-0 flex-1 bg-transparent outline-none placeholder:text-gray-400"
        @input="emit('update:modelValue', ($event.target as HTMLInputElement).value)"
      />
      <slot name="suffix" />
    </div>

    <Text v-if="error" as="span" size="xs" class="text-red-500">{{ error }}</Text>
  </div>
</template>
