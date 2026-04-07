import { defineStore } from 'pinia'
import type { AuthUser } from '~/types/auth'

const TOKEN_KEY = 'auth_token'

export const useAuthStore = defineStore('auth', () => {
  const token = ref<string | null>(null)
  const user = ref<AuthUser | null>(null)

  const isAuthenticated = computed(() => !!token.value)

  function setToken(value: string) {
    token.value = value
    if (import.meta.client) {
      localStorage.setItem(TOKEN_KEY, value)
    }
  }

  function setUser(value: AuthUser) {
    user.value = value
  }

  function hydrate() {
    if (import.meta.client && !token.value) {
      const stored = localStorage.getItem(TOKEN_KEY)
      if (stored) token.value = stored
    }
  }

  function logout() {
    token.value = null
    user.value = null
    if (import.meta.client) {
      localStorage.removeItem(TOKEN_KEY)
    }
  }

  return { token, user, isAuthenticated, setToken, setUser, hydrate, logout }
})
