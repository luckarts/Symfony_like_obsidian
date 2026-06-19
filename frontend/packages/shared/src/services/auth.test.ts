import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { signupService } from './auth'

const VALID_PAYLOAD = {
  firstName: 'Jean',
  lastName: 'Dupont',
  email: 'jean@example.com',
  password: 'T3st!P@ss',
}

const VALID_RESPONSE = {
  id: 'uuid-123',
  email: 'jean@example.com',
  firstName: 'Jean',
  lastName: 'Dupont',
  roles: ['ROLE_USER'],
  createdAt: '2024-01-01T00:00:00Z',
  updatedAt: '2024-01-01T00:00:00Z',
}

function mockFetch(status: number, body: unknown) {
  return vi.fn().mockResolvedValue({
    ok: status >= 200 && status < 300,
    status,
    statusText: String(status),
    json: async () => body,
  })
}

beforeEach(() => {
  vi.stubGlobal('fetch', vi.fn())
})

afterEach(() => {
  vi.unstubAllGlobals()
})

describe('signupService', () => {
  it('returns parsed response on 201', async () => {
    vi.stubGlobal('fetch', mockFetch(201, VALID_RESPONSE))
    const result = await signupService(VALID_PAYLOAD)
    expect(result).toMatchObject({ email: 'jean@example.com', firstName: 'Jean' })
  })

  it('sends correct headers and body', async () => {
    const fetchSpy = mockFetch(201, VALID_RESPONSE)
    vi.stubGlobal('fetch', fetchSpy)
    await signupService(VALID_PAYLOAD)
    expect(fetchSpy).toHaveBeenCalledWith(
      '/api/users',
      expect.objectContaining({
        method: 'POST',
        headers: { 'Content-Type': 'application/ld+json' },
        body: JSON.stringify(VALID_PAYLOAD),
      }),
    )
  })

  it('throws with status 422 on unprocessable', async () => {
    vi.stubGlobal('fetch', mockFetch(422, { message: 'Unprocessable', violations: [] }))
    await expect(signupService(VALID_PAYLOAD)).rejects.toMatchObject({
      status: 422,
      data: { violations: [] },
    })
  })

  it('throws with status 500 on server error', async () => {
    vi.stubGlobal('fetch', mockFetch(500, { message: 'Internal Server Error' }))
    await expect(signupService(VALID_PAYLOAD)).rejects.toMatchObject({ status: 500 })
  })

  it('uses custom apiBase when provided', async () => {
    const fetchSpy = mockFetch(201, VALID_RESPONSE)
    vi.stubGlobal('fetch', fetchSpy)
    await signupService(VALID_PAYLOAD, 'http://localhost:8080')
    expect(fetchSpy).toHaveBeenCalledWith(
      'http://localhost:8080/api/users',
      expect.any(Object),
    )
  })
})
