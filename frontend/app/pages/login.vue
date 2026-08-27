<template>
  <div class="relative min-h-screen bg-[#0d0d0d] flex flex-col overflow-hidden">
    <!-- Background sticker image -->
    <div
      class="absolute inset-0 bg-no-repeat opacity-80 pointer-events-none"
      style="background-position: center -80px;"
      :style="{ backgroundImage: `url('https://static.cdninstagram.com/rsrc.php/ym/r/gf40BP6SRYU.avif')`, backgroundSize: '100%' }"
    />

    <!-- Center content -->
    <div class="relative z-10 flex flex-1 flex-col items-center justify-center px-4 py-20">
      <p class="text-white font-semibold text-base mb-6">Entre com sua conta do Instagram</p>

      <form class="flex flex-col gap-3 w-full max-w-[340px]" @submit.prevent="submit">
        <input
          v-model="form.email"
          type="email"
          required
          placeholder="Nome de usuário, telefone ou email"
          class="w-full rounded-xl border border-neutral-700 bg-[#1a1a1a] text-white px-4 py-3 text-sm outline-none focus:border-neutral-500 transition placeholder-neutral-500"
        />

        <input
          v-model="form.password"
          type="password"
          required
          placeholder="Senha"
          class="w-full rounded-xl border border-neutral-700 bg-[#1a1a1a] text-white px-4 py-3 text-sm outline-none focus:border-neutral-500 transition placeholder-neutral-500"
        />

        <p v-if="error" class="text-xs text-red-400 px-1">{{ error }}</p>

        <button
          type="submit"
          :disabled="loading"
          class="w-full bg-white text-black rounded-xl py-3 text-sm font-semibold hover:bg-neutral-200 transition disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2 mt-1"
        >
          <Loader2 v-if="loading" class="w-4 h-4 animate-spin" />
          <span>{{ loading ? 'Entrando…' : 'Entrar' }}</span>
        </button>
      </form>

      <button class="text-neutral-400 text-sm mt-5 hover:text-white transition">
        Esqueceu a senha?
      </button>

      <!-- Divider -->
      <div class="flex items-center gap-3 w-full max-w-[340px] my-5">
        <div class="flex-1 h-px bg-neutral-700" />
        <span class="text-neutral-500 text-sm">ou</span>
        <div class="flex-1 h-px bg-neutral-700" />
      </div>

      <!-- Continue with Instagram -->
      <div class="w-full max-w-[340px]">
        <button
          class="w-full flex items-center justify-between px-4 py-4 rounded-xl border border-neutral-700 bg-transparent hover:bg-neutral-800/50 transition"
        >
          <div class="flex items-center gap-3">
            <!-- Instagram gradient icon -->
            <div class="w-9 h-9 rounded-lg flex items-center justify-center shrink-0" style="background: radial-gradient(circle at 30% 107%, #fdf497 0%, #fdf497 5%, #fd5949 45%, #d6249f 60%, #285AEB 90%)">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="2" y="2" width="20" height="20" rx="5" ry="5"/>
                <circle cx="12" cy="12" r="4"/>
                <circle cx="17.5" cy="6.5" r="1" fill="currentColor" stroke="none"/>
              </svg>
            </div>
            <span class="text-white font-semibold text-sm">Continuar com o Instagram</span>
          </div>
          <ChevronRight class="w-4 h-4 text-neutral-500" />
        </button>
      </div>
    </div>

    <!-- Footer -->
    <div class="relative z-10 pb-6 flex justify-center">
      <p class="text-neutral-600 text-xs flex flex-wrap justify-center gap-x-3 gap-y-1 px-4 text-center">
        <span>© 2026</span>
        <span class="hover:underline cursor-pointer">Termos do Threads</span>
        <span class="hover:underline cursor-pointer">Política de Privacidade</span>
        <span class="hover:underline cursor-pointer">Política de Cookies</span>
        <span class="hover:underline cursor-pointer">Relatar um problema</span>
      </p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive } from 'vue'
import { Loader2, ChevronRight } from 'lucide-vue-next'
import { useAuthStore } from '~/stores/auth'
import { useApi } from '~/composables/useApi'
import type { User } from '~/types'

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
    const data = await post<{ token: string; user: User }>('/api/login', form)
    authStore.setAuth(data.token, data.user)
    router.push('/')
  } catch (e: unknown) {
    error.value = e instanceof Error ? e.message : 'Falha ao entrar'
  } finally {
    loading.value = false
  }
}
</script>
