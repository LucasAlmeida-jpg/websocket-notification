let timer: ReturnType<typeof setTimeout> | null = null

export function useErrorToast() {
  const errorMessage = useState<string | null>('error-toast', () => null)

  function showError(message: string) {
    if (timer) clearTimeout(timer)
    errorMessage.value = message
    timer = setTimeout(() => { errorMessage.value = null }, 3500)
  }

  return { errorMessage, showError }
}
