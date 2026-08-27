<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50 px-4">
    <div class="w-full max-w-sm bg-white rounded-2xl shadow-sm border border-gray-100 p-8">
      <div class="flex flex-col items-center mb-8">
        <div class="w-12 h-12 bg-gray-900 rounded-xl flex items-center justify-center mb-4">
          <Bell class="w-6 h-6 text-white" />
        </div>
        <h1 class="text-xl font-semibold text-gray-900">Notification System</h1>
        <p class="text-sm text-gray-500 mt-1">Faça login para continuar</p>
      </div>

      <form class="flex flex-col gap-4" @submit.prevent="submit">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1.5">Email</label>
          <input
            v-model="form.email"
            type="email"
            required
            placeholder="alice@example.com"
            class="w-full rounded-lg border border-gray-200 px-3 py-2.5 text-sm outline-none focus:ring-2 focus:ring-gray-900 focus:border-transparent transition"
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1.5">Senha</label>
          <input
            v-model="form.password"
            type="password"
            required
            placeholder="••••••••"
            class="w-full rounded-lg border border-gray-200 px-3 py-2.5 text-sm outline-none focus:ring-2 focus:ring-gray-900 focus:border-transparent transition"
          />
        </div>

        <p v-if="error" class="text-sm text-red-500 flex items-center gap-1.5">
          <CircleX class="w-4 h-4 shrink-0" />
          {{ error }}
        </p>

        <button
          type="submit"
          :disabled="loading"
          class="mt-1 w-full bg-gray-900 text-white rounded-lg py-2.5 text-sm font-medium hover:bg-gray-700 transition disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
        >
          <Loader2 v-if="loading" class="w-4 h-4 animate-spin" />
          <span>{{ loading ? 'Entrando…' : 'Entrar' }}</span>
        </button>
      </form>

      <p class="text-xs text-gray-400 text-center mt-6">
        Usuários: alice / bob / carol@example.com · senha: password
      </p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Bell, CircleX, Loader2 } from 'lucide-vue-next'
import { useAuthStore } from '~/stores/auth'
import { useApi } from '~/composables/useApi'

definePageMeta({ middleware: [] })

const authStore = useAuthStore()
const { post } = useApi()
const router = useRouter()

const form = reactive({ email: '', password: '' })
const loading = ref(false)
const error = ref('')

async function submit() {
  error.value = ''
  loading.value = true
  try {
    const data = await post<{ token: string; user: { id: number; name: string; email: string } }>(
      '/api/login',
      form,
    )
    authStore.setAuth(data.token, data.user)
    router.push('/')
  } catch (e: unknown) {
    error.value = e instanceof Error ? e.message : 'Erro ao fazer login'
  } finally {
    loading.value = false
  }
}
</script>
