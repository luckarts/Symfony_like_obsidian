<script setup lang="ts">
definePageMeta({ layout: 'auth' })

const { signup, loading } = useSignup()
</script>

<template>
  <SignupCard
    :loading="loading"
    @submit="signup"
  />
</template>
