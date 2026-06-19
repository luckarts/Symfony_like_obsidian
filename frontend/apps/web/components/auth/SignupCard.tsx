"use client";

import { useRouter } from "next/navigation";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { useToast } from "@/components/ui/use-toast";
import { useSignup } from "@repo/shared/hooks/useSignup";
import {
  useField,
  required,
  email as emailRule,
  min,
} from "@repo/shared/hooks/useFieldValidation";

export function SignupCard() {
  const router = useRouter();
  const { toast } = useToast();
  const { signup, loading } = useSignup();

  const firstName = useField("", [required("Le prénom est requis")]);
  const lastName = useField("", [required("Le nom est requis")]);
  const email = useField("", [required(), emailRule("Adresse email invalide")]);
  const password = useField("", [
    required(),
    min(8, "Le mot de passe doit contenir au moins 8 caractères"),
  ]);

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    const valid = [
      firstName.validate(),
      lastName.validate(),
      email.validate(),
      password.validate(),
    ].every(Boolean);
    if (!valid) return;

    const result = await signup({
      firstName: firstName.value,
      lastName: lastName.value,
      email: email.value,
      password: password.value,
    });

    if (result.ok) {
      toast({
        title: "Bienvenue !",
        description: "Compte créé avec succès",
        variant: "success",
      });
      router.push("/");
    } else {
      const err = result.error;
      let description = "Une erreur est survenue, veuillez réessayer";
      if (err.status === 422) {
        const hasViolations =
          Array.isArray(err.data?.violations) && err.data.violations.length > 0;
        description = hasViolations
          ? "Données invalides. Vérifiez vos informations."
          : "Email déjà utilisé";
      } else if (err.status === 500) {
        description = "Erreur serveur. Veuillez réessayer plus tard";
      } else {
        description = err.data?.message || err.message || description;
      }
      toast({
        title: "Erreur de connexion",
        description,
        variant: "destructive",
      });
    }
  }

  return (
    <Card className="w-full max-w-sm">
      <CardHeader className="items-center gap-3">
        <CardTitle>Créer un compte</CardTitle>
      </CardHeader>
      <CardContent>
        <form className="space-y-4" noValidate onSubmit={handleSubmit}>
          <Input
            label="Prenom"
            type="text"
            name="firstName"
            placeholder="Jean Dupont"
            value={firstName.value}
            onChange={(e) => firstName.setValue(e.target.value)}
            onBlur={firstName.touch}
            error={firstName.error}
            disabled={loading}
          />
          <Input
            label="Nom"
            type="text"
            name="lastName"
            placeholder="Jean Dupont"
            value={lastName.value}
            onChange={(e) => lastName.setValue(e.target.value)}
            onBlur={lastName.touch}
            error={lastName.error}
            disabled={loading}
          />
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
            placeholder="8 caractères minimum"
            value={password.value}
            onChange={(e) => password.setValue(e.target.value)}
            onBlur={password.touch}
            error={password.error}
            disabled={loading}
          />

          <Button type="submit" className="w-full" disabled={loading}>
            {loading ? "Création..." : "Créer mon compte"}
          </Button>
        </form>

        <p className="mt-4 text-center text-sm text-muted-foreground">
          Déjà un compte ?{" "}
          <a href="/login" className="text-primary hover:underline">
            Se connecter
          </a>
        </p>
      </CardContent>
    </Card>
  );
}
