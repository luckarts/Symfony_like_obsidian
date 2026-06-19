import { useState, useCallback } from 'react'

export type SimpleRule = (val: string) => true | string

export const required =
  (msg = 'Ce champ est requis'): SimpleRule =>
  (val) =>
    !!val || msg

export const email = (msg = 'Email invalide'): SimpleRule => {
  const EMAIL_REGEX = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
  return (val) => EMAIL_REGEX.test(val) || msg
}

export const min =
  (length: number, msg?: string): SimpleRule =>
  (val) =>
    val.length >= length || msg || `Minimum ${length} caractères`

export const max =
  (length: number, msg?: string): SimpleRule =>
  (val) =>
    val.length <= length || msg || `Maximum ${length} caractères`

export const phoneFR = (msg = 'Téléphone invalide'): SimpleRule => {
  const PHONE_REGEX = /^(?:(?:\+|00)33|0)\s*[1-9](?:[\s.-]*\d{2}){4}$/
  return (val) => !val || PHONE_REGEX.test(val) || msg
}

export const sameAs =
  (getValue: () => string, msg = 'Les valeurs ne correspondent pas'): SimpleRule =>
  (val) =>
    val === getValue() || msg

export const regex =
  (pattern: RegExp, msg = 'Format invalide'): SimpleRule =>
  (val) =>
    pattern.test(val) || msg

export const validate = (value: string, rules: SimpleRule[]): string | undefined => {
  for (const rule of rules) {
    const result = rule(value)
    if (result !== true) return result
  }
  return undefined
}

export const useField = (initialValue = '', rules: SimpleRule[] = []) => {
  const [value, setValue] = useState(initialValue)
  const [error, setError] = useState<string | undefined>(undefined)
  const [touched, setTouched] = useState(false)

  const validateField = useCallback((): boolean => {
    const err = validate(value, rules)
    setError(err)
    return err === undefined
  }, [value, rules])

  const touch = useCallback(() => {
    setTouched(true)
    const err = validate(value, rules)
    setError(err)
  }, [value, rules])

  const reset = useCallback(() => {
    setValue(initialValue)
    setError(undefined)
    setTouched(false)
  }, [initialValue])

  return {
    value,
    setValue,
    error,
    touched,
    hasError: error !== undefined,
    isValid: error === undefined,
    validate: validateField,
    touch,
    reset,
  }
}
