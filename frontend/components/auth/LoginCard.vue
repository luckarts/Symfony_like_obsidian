<script setup lang="ts">
import { useField } from '~/composables/useField'
import { email as emailRule, required } from '~/utils/validators'

defineProps<{
  loading: boolean
}>()

const emit = defineEmits<{
  submit: [{ email: string; password: string }]
}>()

const {
  value: email,
  errorMessage: emailError,
  handleBlur: handleBlurEmail,
  validate: validateEmail,
} = useField('', [required(), emailRule('Adresse email invalide')])

const {
  value: password,
  errorMessage: passwordError,
  handleBlur: handleBlurPassword,
  validate: validatePassword,
} = useField('', [required()])

function handleSubmit() {
  const valid = [validateEmail, validatePassword].every((fn) => fn())
  if (valid) {
    emit('submit', { email: email.value, password: password.value })
  }
}
</script>

<template>
  <Card variant="shadow" class="w-full max-w-sm">
    <div class="flex flex-col items-center gap-3">
      <AppLogo size="md" />
      <Heading :level="1" size="lg">Connexion</Heading>
    </div>

    <form class="space-y-4" novalidate @submit.prevent="handleSubmit">
      <Input
        v-model="email"
        type="email"
        name="email"
        label="Email"
        placeholder="jean@exemple.com"
        :error="emailError"
        :disabled="loading"
        @blur="handleBlurEmail()"
      />

      <Input
        v-model="password"
        type="password"
        name="password"
        label="Mot de passe"
        placeholder="Votre mot de passe"
        :error="passwordError"
        :disabled="loading"
        @blur="handleBlurPassword()"
      />

      <Button
        type="submit"
        variant="primary"
        full-width
        :loading="loading"
      >
        Se connecter
      </Button>
    </form>

    <div class="flex flex-col items-center gap-1 mt-2">
      <Text as="p" class="text-center">
        <AppLink variant="brand" to="/auth/forgot-password">Mot de passe oublié ?</AppLink>
      </Text>
      <Text as="p" class="text-center">
        Pas encore de compte ?
        <AppLink variant="brand" to="/auth/signup">S'inscrire</AppLink>
      </Text>
    </div>
  </Card>
</template>
