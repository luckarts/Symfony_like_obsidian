<script setup lang="ts">
const { toasts, remove } = useAppToast()
</script>

<template>
  <Teleport to="body">
    <div
      aria-live="polite"
      aria-atomic="false"
      class="fixed top-4 right-4 z-50 flex flex-col gap-2 items-end"
    >
      <TransitionGroup
        tag="div"
        class="flex flex-col gap-2 items-end"
        enter-active-class="transition-all duration-300 ease-out"
        leave-active-class="transition-all duration-200 ease-in"
        enter-from-class="opacity-0 translate-x-4"
        enter-to-class="opacity-100 translate-x-0"
        leave-from-class="opacity-100 translate-x-0"
        leave-to-class="opacity-0 translate-x-4"
      >
        <Toast
          v-for="t in toasts"
          :key="t.id"
          :title="t.title"
          :description="t.description"
          :variant="t.variant"
          class="min-w-64 max-w-sm"
          @close="remove(t.id)"
        />
      </TransitionGroup>
    </div>
  </Teleport>
</template>
