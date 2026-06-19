import { auth } from '@/auth'

export default async function DashboardPage() {
  const session = await auth()

  return (
    <div>
      <h1 className="text-2xl font-bold mb-6">
        Bonjour, {session?.user?.name} 👋
      </h1>
      <p className="text-muted-foreground">Aucun projet pour le moment.</p>
    </div>
  )
}
