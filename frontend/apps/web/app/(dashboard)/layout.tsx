import { auth } from '@/auth'
import { redirect } from 'next/navigation'
import { AppHeader } from '@/components/layout/AppHeader'

export default async function DashboardLayout({ children }: { children: React.ReactNode }) {
  const session = await auth()
  if (!session) redirect('/login')

  return (
    <div className="min-h-screen flex flex-col">
      <AppHeader user={session.user} />
      <main className="flex-1 container mx-auto px-4 py-8">{children}</main>
    </div>
  )
}
