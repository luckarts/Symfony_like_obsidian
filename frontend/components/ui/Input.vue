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
    <label
      v-if="label"
      :for="name"
      class="text-sm font-medium text-gray-700 dark:text-gray-300"
    >
      {{ label }}
    </label>

    <div
      :class="[
        'flex h-12 w-full items-center gap-2 rounded-xl border bg-white/0 px-3',
        'text-sm text-gray-800 dark:text-white',
        'transition-colors duration-150',
        error
          ? 'border-red-400 focus-within:border-red-500 dark:border-red-500'
          : 'border-gray-200 focus-within:border-brand-500 dark:border-white/10 dark:focus-within:border-brand-400',
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
        class="min-w-0 flex-1 bg-transparent outline-none placeholder:text-gray-400 dark:placeholder:text-gray-500"
        @input="emit('update:modelValue', ($event.target as HTMLInputElement).value)"
      />
      <slot name="suffix" />
    </div>

    <p v-if="error" class="text-xs text-red-500">{{ error }}</p>
  </div>
</template>
