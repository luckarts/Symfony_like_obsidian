'use client'

import { useRouter } from 'next/navigation'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { useToast } from '@/components/ui/use-toast'
import { useLogin } from '@/hooks/useLogin'
import {
  useField,
  required,
  email as emailRule,
} from '@repo/shared/hooks/useFieldValidation'

export function LoginCard() {
  const router = useRouter()
  const { toast } = useToast()
  const { login, loading } = useLogin()

  const email = useField('', [required(), emailRule('Adresse email invalide')])
  const password = useField('', [required('Le mot de passe est requis')])

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault()
    const valid = [email.validate(), password.validate()].every(Boolean)
    if (!valid) return

    const result = await login({ email: email.value, password: password.value })

    if (result.ok) {
      router.push('/dashboard')
    } else {
      toast({
        title: 'Erreur de connexion',
        description: 'Email ou mot de passe incorrect',
        variant: 'destructive',
      })
    }
  }

  return (
    <Card className="w-full max-w-sm">
      <CardHeader className="items-center gap-3">
        <CardTitle>Connexion</CardTitle>
      </CardHeader>
      <CardContent>
        <form className="space-y-4" noValidate onSubmit={handleSubmit}>
          <Input
            label="Email"
            type="email"
            name="email"
            placeholder="jean@exemple.com"
            value={email.value}
            onChange={(e) => email.setValue(e.target.value)}
            onBlur={email.touch}
            error={email.error}
            disabled={loading}
          />
          <Input
            label="Mot de passe"
            type="password"
            name="password"
            placeholder="Votre mot de passe"
            value={password.value}
            onChange={(e) => password.setValue(e.target.value)}
            onBlur={password.touch}
            error={password.error}
            disabled={loading}
          />
          <Button type="submit" className="w-full" disabled={loading}>
            {loading ? 'Connexion...' : 'Se connecter'}
          </Button>
        </form>

        <p className="mt-4 text-center text-sm text-muted-foreground">
          Pas de compte ?{' '}
          <a href="/signup" className="text-primary hover:underline">
            Créer un compte
          </a>
        </p>
      </CardContent>
    </Card>
  )
}
