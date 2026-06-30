import { signupService } from '~/services/auth'
import type { SignupPayload } from '~/types/auth'

export function useSignup() {
  const store = useAuthStore()
  const loading = ref(false)
  const error = ref<string | null>(null)

  async function signup(payload: SignupPayload) {
    loading.value = true
    error.value = null
    try {
      const data = await signupService(payload)
      store.setToken(data.token)
      store.setUser(data.user)
      await navigateTo('/')
    } catch (err: unknown) {
      const status = (err as { statusCode?: number })?.statusCode
      if (status === 422) {
        error.value = 'Ces informations sont invalides ou déjà utilisées'
      } else {
        error.value = 'Une erreur est survenue, veuillez réessayer'
      }
    } finally {
      loading.value = false
    }
  }

  return {
    signup,
    loading: readonly(loading),
    error: readonly(error),
  }
}
