import { useAuthStore } from '~/stores/auth'

export function useApi() {
  const authStore = useAuthStore()

  async function request<T>(path: string, options: RequestInit = {}): Promise<T> {
    const headers: HeadersInit = {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      ...(authStore.token ? { Authorization: `Bearer ${authStore.token}` } : {}),
      ...((options.headers as Record<string, string>) ?? {}),
    }

    const res = await fetch(path, {
      ...options,
      headers,
    })

    if (!res.ok) {
      const err = await res.json().catch(() => ({ message: res.statusText }))
      throw new Error(err.message ?? 'Request failed')
    }

    return res.json() as Promise<T>
  }

  const get = <T>(path: string) => request<T>(path)
  const post = <T>(path: string, body: unknown) =>
    request<T>(path, { method: 'POST', body: JSON.stringify(body) })
  const patch = <T>(path: string) => request<T>(path, { method: 'PATCH' })
  const del = <T>(path: string, body?: unknown) =>
    request<T>(path, { method: 'DELETE', ...(body ? { body: JSON.stringify(body) } : {}) })

  return { get, post, patch, del }
}
