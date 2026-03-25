<script setup lang="ts">
defineProps<{
  loading: boolean
  error: string | null
}>()

const emit = defineEmits<{
  submit: [{ name: string; email: string; password: string }]
}>()

const name = ref('')
const email = ref('')
const password = ref('')

const errors = reactive({ name: '', email: '', password: '' })

function validate(): boolean {
  errors.name = ''
  errors.email = ''
  errors.password = ''

  let valid = true

  if (!name.value.trim()) {
    errors.name = 'Le nom est requis'
    valid = false
  }

  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value)) {
    errors.email = 'Adresse email invalide'
    valid = false
  }

  if (password.value.length < 8) {
    errors.password = 'Le mot de passe doit contenir au moins 8 caractères'
    valid = false
  }

  return valid
}

function handleSubmit() {
  if (validate()) {
    emit('submit', { name: name.value, email: email.value, password: password.value })
  }
}
</script>

<template>
  <div class="bg-white rounded-2xl p-8 w-full max-w-sm shadow-sm space-y-5">
    <div class="flex flex-col items-center gap-3">
      <AppLogo size="md" />
      <h1 class="text-lg font-semibold text-gray-900 dark:text-white">
        Créer un compte
      </h1>
    </div>

    <form class="space-y-4" @submit.prevent="handleSubmit">
      <Input
        v-model="name"
        type="text"
        name="name"
        label="Nom complet"
        placeholder="Jean Dupont"
        :error="errors.name"
        :disabled="loading"
      />

      <Input
        v-model="email"
        type="email"
        name="email"
        label="Email"
        placeholder="jean@exemple.com"
        :error="errors.email"
        :disabled="loading"
      />

      <Input
        v-model="password"
        type="password"
        name="password"
        label="Mot de passe"
        placeholder="8 caractères minimum"
        :error="errors.password"
        :disabled="loading"
      />

      <p v-if="error" class="text-sm text-red-500">{{ error }}</p>

      <Button
        type="submit"
        variant="primary"
        full-width
        :loading="loading"
      >
        Créer mon compte
      </Button>
    </form>

    <p class="text-center text-sm text-gray-500">
      Déjà un compte ?
      <NuxtLink to="/auth/login" class="font-medium text-brand-500 hover:underline">
        Se connecter
      </NuxtLink>
    </p>
  </div>
</template>
