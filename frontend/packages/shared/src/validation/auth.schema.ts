import { z } from 'zod'

export const signupSchema = z.object({
  name: z.string().min(1, 'Le nom est requis'),
  email: z.string().email('Adresse email invalide'),
  password: z.string().min(8, 'Le mot de passe doit contenir au moins 8 caractères'),
})

export const loginSchema = z.object({
  email: z.string().email('Adresse email invalide'),
  password: z.string().min(1, 'Le mot de passe est requis'),
})

export type SignupSchema = z.infer<typeof signupSchema>
export type LoginSchema = z.infer<typeof loginSchema>
