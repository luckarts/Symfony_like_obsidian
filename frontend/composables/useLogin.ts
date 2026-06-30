import { loginService } from '~/services/auth'
import { useAppToast } from './useAppToast'

export function useLogin() {
  const { toast } = useAppToast()
  const authStore = useAuthStore()
  const route = useRoute()
  const loading = ref(false)

  async function login(email: string, password: string) {
    loading.value = true
    try {
      const res = await loginService(email, password)
      authStore.setToken(res.token)
      toast({
        title: 'Content de vous revoir !',
        description: 'Connexion réussie',
        variant: 'success',
      })
      const redirect = (route.query.redirect as string) || '/'
      await navigateTo(redirect)
    } catch (err: unknown) {
      const e = err as {
        status?: number
        data?: { message?: string; violations?: unknown[] }
        message?: string
      }
      let description = 'Une erreur est survenue, veuillez réessayer'
      if (e.status === 401) {
        description = 'Identifiants invalides'
      } else if (e.status === 422) {
        description = 'Données invalides. Vérifiez vos informations.'
      } else if (e.status === 500) {
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
    login,
    loading: readonly(loading),
  }
}
