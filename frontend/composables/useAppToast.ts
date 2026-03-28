export interface AppToast {
  id: number
  title?: string
  description: string
  variant?: 'info' | 'success' | 'warning' | 'error' | 'destructive'
}

export interface ToastOptions {
  title?: string
  description: string
  variant?: AppToast['variant']
  duration?: number
}

let _nextId = 0

export function useAppToast() {
  const toasts = useState<AppToast[]>('app-toasts', () => [])

  function toast(options: ToastOptions) {
    const { title, description, variant, duration = 6000 } = options
    const id = ++_nextId
    toasts.value.push({ id, title, description, variant })
    if (duration > 0) {
      setTimeout(() => remove(id), duration)
    }
  }

  function remove(id: number) {
    const idx = toasts.value.findIndex((t) => t.id === id)
    if (idx !== -1) toasts.value.splice(idx, 1)
  }

  return { toasts: readonly(toasts), toast, remove }
}
