<script setup lang="ts">
definePageMeta({ layout: 'auth' })

const { signup, loading, error } = useSignup()
</script>

<template>
  <SignupCard
    :loading="loading"
    :error="error"
    @submit="signup"
  />
</template>
