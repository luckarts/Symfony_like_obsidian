import type { SignupPayload, SignupResponse } from '~/types/auth'

const API_ENDPOINTS = {
  SIGNUP: '/api/users',
} as const

export interface LoginResponse {
  token: string
}

interface OAuth2TokenResponse {
  access_token: string
  refresh_token: string
  token_type: string
  expires_in: number
}

export const loginService = async (email: string, password: string): Promise<LoginResponse> => {
  const config = useRuntimeConfig()

  const body = new URLSearchParams({
    grant_type: 'password',
    client_id: config.public.oauthClientId,
    client_secret: config.public.oauthClientSecret,
    username: email,
    password,
  })

  const res = await $fetch<OAuth2TokenResponse>('/oauth2/token', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body,
  })

  return { token: res.access_token }
}

export const signupService = (payload: SignupPayload): Promise<SignupResponse> => {
  return $fetch<SignupResponse>(API_ENDPOINTS.SIGNUP, {
    method: 'POST',
    headers: { 'Content-Type': 'application/ld+json' },
    body: { ...payload },
  })
}
