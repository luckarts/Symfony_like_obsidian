import { type Ref, ref } from 'vue'
import type { SimpleRule } from '~/utils/validators'
import { validate as runValidation } from '~/utils/validators'

export function useField<T extends string = string>(
  initialValue: T = '' as T,
  rules: SimpleRule[] = []
) {
  const value = ref<T>(initialValue)
  const errorMessage = ref<string | undefined>(undefined)

  const validate = (): boolean => {
    errorMessage.value = runValidation(value.value, rules)
    return errorMessage.value === undefined
  }

  const handleBlur = () => {
    validate()
  }

  return {
    value: value as Ref<T>,
    errorMessage: errorMessage as Ref<string | undefined>,
    handleBlur,
    validate,
  }
}
