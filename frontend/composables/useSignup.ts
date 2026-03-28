import { signupService } from '~/services/auth'
import type { SignupPayload } from '~/types/auth'

export function useSignup() {
  const store = useAuthStore()
  const { toast } = useAppToast()
  const loading = ref(false)

  async function signup(payload: SignupPayload) {
    loading.value = true
    try {
      const data = await signupService(payload)
      store.setToken(data.token)
      store.setUser(data.user)
      toast({
        title: 'Bienvenue !',
        description: 'Connexion réussie',
        variant: 'success',
      })
      await navigateTo('/')
    } catch (err: unknown) {
      const e = err as { statusCode?: number; data?: { message?: string }; message?: string }
      let description = 'Une erreur est survenue, veuillez réessayer'
      if (e.statusCode === 422) {
        description = 'Email déjà utilisé ou données invalides'
      } else if (e.statusCode === 500) {
        description = 'Erreur serveur. Veuillez réessayer plus tard'
      } else {
        description = e.data?.message || e.message || description
      }
      toast({ title: 'Erreur de connexion', description, variant: 'destructive' })
    } finally {
      loading.value = false
    }
  }

  return {
    signup,
    loading: readonly(loading),
  }
}
