<template>
  <div>
    <NuxtLayout>
      <NuxtPage />
    </NuxtLayout>
    <ToastContainer/>
  </div>
</template>

<script setup lang="ts">
// Configuration globale de l'app
useHead({
  titleTemplate: '%s - Task Manager',
})
</script>
