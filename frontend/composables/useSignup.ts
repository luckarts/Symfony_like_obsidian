export function useSignup() {
  const loading = ref(false)
  const error = ref<string | null>(null)

  async function signup(payload: { name: string; email: string; password: string }) {
    loading.value = true
    error.value = null
    try {
      await $fetch('/api/register', {
        method: 'POST',
        body: payload,
      })
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
