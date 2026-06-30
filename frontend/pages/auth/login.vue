<script setup lang="ts">
import { useLogin } from '~/composables/useLogin'

definePageMeta({ layout: 'auth' })

const { login, loading } = useLogin()

function handleSubmit(payload: { email: string; password: string }) {
  login(payload.email, payload.password)
}
</script>

<template>
  <LoginCard :loading="loading" @submit="handleSubmit" />
</template>
