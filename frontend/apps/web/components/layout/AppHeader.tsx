'use client'

import { signOut } from 'next-auth/react'
import type { User } from 'next-auth'
import { Button } from '@/components/ui/button'

interface AppHeaderProps {
  user?: User | null
}

export function AppHeader({ user }: AppHeaderProps) {
  return (
    <header className="border-b bg-background">
      <div className="container mx-auto px-4 h-16 flex items-center justify-between">
        <a href="/dashboard" className="font-semibold text-lg">
          TaskManager
        </a>
        <div className="flex items-center gap-4">
          {user && (
            <span className="text-sm text-muted-foreground">{user.name}</span>
          )}
          <Button
            variant="ghost"
            size="sm"
            onClick={() => signOut({ callbackUrl: '/login' })}
          >
            Déconnexion
          </Button>
        </div>
      </div>
    </header>
  )
}
