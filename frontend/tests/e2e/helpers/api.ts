const API_BASE = process.env.NUXT_PUBLIC_API_BASE || 'http://localhost:8000'

export async function deleteTestUser(email: string): Promise<void> {
  await fetch(`${API_BASE}/api/test/users/${encodeURIComponent(email)}`, {
    method: 'DELETE',
  })
}
