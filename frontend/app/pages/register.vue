<template>
  <div class="min-h-screen flex items-center justify-center bg-[#0d0d0d] px-4">
    <div class="w-full max-w-sm bg-[#181818] rounded-2xl border border-neutral-800 p-8">
      <div class="flex flex-col items-center mb-8">
        <div class="w-12 h-12 bg-white rounded-xl flex items-center justify-center mb-4">
          <span class="text-black font-bold text-xl">T</span>
        </div>
        <h1 class="text-xl font-semibold text-white">Join Threads</h1>
        <p class="text-sm text-neutral-500 mt-1">Create your account</p>
      </div>

      <form class="flex flex-col gap-4" @submit.prevent="submit">
        <div>
          <label class="block text-sm font-medium text-neutral-300 mb-1.5">Name</label>
          <input
            v-model="form.name"
            type="text"
            required
            placeholder="Your name"
            class="w-full rounded-lg border border-neutral-700 bg-neutral-900 text-white px-3 py-2.5 text-sm outline-none focus:ring-2 focus:ring-white/20 focus:border-neutral-500 transition placeholder-neutral-600"
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-neutral-300 mb-1.5">Email</label>
          <input
            v-model="form.email"
            type="email"
            required
            placeholder="you@example.com"
            class="w-full rounded-lg border border-neutral-700 bg-neutral-900 text-white px-3 py-2.5 text-sm outline-none focus:ring-2 focus:ring-white/20 focus:border-neutral-500 transition placeholder-neutral-600"
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-neutral-300 mb-1.5">Password</label>
          <input
            v-model="form.password"
            type="password"
            required
            placeholder="••••••••"
            class="w-full rounded-lg border border-neutral-700 bg-neutral-900 text-white px-3 py-2.5 text-sm outline-none focus:ring-2 focus:ring-white/20 focus:border-neutral-500 transition placeholder-neutral-600"
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-neutral-300 mb-1.5">Confirm password</label>
          <input
            v-model="form.password_confirmation"
            type="password"
            required
            placeholder="••••••••"
            class="w-full rounded-lg border border-neutral-700 bg-neutral-900 text-white px-3 py-2.5 text-sm outline-none focus:ring-2 focus:ring-white/20 focus:border-neutral-500 transition placeholder-neutral-600"
          />
        </div>

        <p v-if="error" class="text-sm text-red-400 flex items-center gap-1.5">
          <CircleX class="w-4 h-4 shrink-0" />
          {{ error }}
        </p>

        <button
          type="submit"
          :disabled="loading"
          class="mt-1 w-full bg-white text-black rounded-lg py-2.5 text-sm font-semibold hover:bg-neutral-200 transition disabled:opacity-40 disabled:cursor-not-allowed flex items-center justify-center gap-2"
        >
          <Loader2 v-if="loading" class="w-4 h-4 animate-spin" />
          <span>{{ loading ? 'Creating account…' : 'Create account' }}</span>
        </button>
      </form>

      <p class="text-sm text-neutral-500 text-center mt-6">
        Already have an account?
        <NuxtLink to="/login" class="text-white hover:underline">Sign in</NuxtLink>
      </p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive } from 'vue'
import { CircleX, Loader2 } from 'lucide-vue-next'
import { useAuthStore } from '~/stores/auth'
import { useApi } from '~/composables/useApi'
import type { User } from '~/types'

definePageMeta({ middleware: [] })

const authStore = useAuthStore()
const { post } = useApi()
const router = useRouter()

const form = reactive({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
})
const loading = ref(false)
const error = ref('')

async function submit() {
  error.value = ''
  if (form.password !== form.password_confirmation) {
    error.value = 'Passwords do not match'
    return
  }
  loading.value = true
  try {
    const data = await post<{ token: string; user: User }>('/api/register', form)
    authStore.setAuth(data.token, data.user)
    router.push('/')
  } catch (e: unknown) {
    error.value = e instanceof Error ? e.message : 'Registration failed'
  } finally {
    loading.value = false
  }
}
</script>
