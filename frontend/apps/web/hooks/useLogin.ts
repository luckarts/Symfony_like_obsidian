'use client'

import { useState } from 'react'
import { signIn } from 'next-auth/react'

type LoginPayload = { email: string; password: string }
type LoginError = { message: string }
export type LoginResult = { ok: true } | { ok: false; error: LoginError }

export function useLogin() {
  const [loading, setLoading] = useState(false)

  async function login(payload: LoginPayload): Promise<LoginResult> {
    setLoading(true)
    try {
      const result = await signIn('credentials', { ...payload, redirect: false })
      if (!result || result.error) {
        return { ok: false, error: { message: result?.error ?? 'Erreur inconnue' } }
      }
      return { ok: true }
    } finally {
      setLoading(false)
    }
  }

  return { login, loading }
}
