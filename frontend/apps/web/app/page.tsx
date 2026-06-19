import { redirect } from 'next/navigation'
import { auth } from '@/app/api/auth/[...nextauth]/route'

export default async function HomePage() {
  const session = await auth()
  if (!session) redirect('/login')
  redirect('/dashboard')
}
