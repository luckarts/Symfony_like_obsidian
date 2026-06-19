const API_BASE = process.env.API_URL ?? 'http://localhost:8080'

export async function deleteTestUser(email: string): Promise<void> {
  await fetch(`${API_BASE}/api/test/users/${encodeURIComponent(email)}`, {
    method: 'DELETE',
  })
}
