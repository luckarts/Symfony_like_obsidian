import { describe, expect, it } from 'vitest'
import {
  email,
  max,
  min,
  phoneFR,
  required,
  validate,
} from './useFieldValidation'

describe('required', () => {
  it('returns true for non-empty value', () => {
    expect(required()('foo')).toBe(true)
  })

  it('returns message for empty string', () => {
    expect(required('Le prénom est requis')('')).toBe('Le prénom est requis')
  })

  it('returns default message when none provided', () => {
    expect(required()('')).toBe('Ce champ est requis')
  })
})

describe('email', () => {
  it('accepts valid email', () => {
    expect(email()('foo@bar.com')).toBe(true)
  })

  it('rejects missing @', () => {
    expect(email('Adresse email invalide')('pas-un-email')).toBe('Adresse email invalide')
  })

  it('rejects missing domain', () => {
    expect(email()('foo@')).not.toBe(true)
  })

  it('rejects empty string', () => {
    expect(email()('')).not.toBe(true)
  })
})

describe('min', () => {
  it('accepts value meeting length', () => {
    expect(min(8)('motdepasse')).toBe(true)
  })

  it('accepts value exactly at length', () => {
    expect(min(8)('12345678')).toBe(true)
  })

  it('rejects value below length', () => {
    expect(min(8, 'au moins 8 caractères')('court')).toBe('au moins 8 caractères')
  })

  it('uses default message when none provided', () => {
    expect(min(8)('court')).toBe('Minimum 8 caractères')
  })
})

describe('max', () => {
  it('accepts value within limit', () => {
    expect(max(10)('hello')).toBe(true)
  })

  it('rejects value exceeding limit', () => {
    expect(max(3, 'trop long')('hello')).toBe('trop long')
  })
})

describe('phoneFR', () => {
  it('accepts valid French mobile', () => {
    expect(phoneFR()('+33612345678')).toBe(true)
  })

  it('accepts valid French landline', () => {
    expect(phoneFR()('0123456789')).toBe(true)
  })

  it('accepts empty string (optional field)', () => {
    expect(phoneFR()('')).toBe(true)
  })

  it('rejects non-French format', () => {
    expect(phoneFR('Téléphone invalide')('123')).toBe('Téléphone invalide')
  })
})

describe('validate', () => {
  it('returns undefined when all rules pass', () => {
    expect(validate('motdepasse', [required(), min(8)])).toBeUndefined()
  })

  it('returns first failing rule message', () => {
    expect(validate('', [required('requis'), min(8, 'trop court')])).toBe('requis')
  })

  it('returns second rule message when first passes', () => {
    expect(validate('ab', [required(), min(8, 'trop court')])).toBe('trop court')
  })
})
