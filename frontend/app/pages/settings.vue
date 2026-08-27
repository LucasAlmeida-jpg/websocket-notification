<template>
  <AppLayout>
    <h1 class="text-lg font-semibold text-white mb-6">Edit Profile</h1>

    <div class="bg-[#181818] border border-neutral-800 rounded-2xl p-6 flex flex-col gap-6">

      <div class="flex flex-col items-center gap-3">
        <button class="relative group" @click="pickAvatar">
          <AppAvatar
            :user-id="authStore.user?.id ?? 0"
            :name="form.name"
            :avatar="preview"
            size="xl"
          />
          <div class="absolute inset-0 rounded-full bg-black/50 flex items-center justify-center opacity-0 group-hover:opacity-100 transition">
            <Camera class="w-6 h-6 text-white" />
          </div>
        </button>
        <span class="text-xs text-neutral-500">Clique para trocar a foto</span>
        <input ref="fileInput" type="file" accept="image/*" class="hidden" @change="onFileChange" />
      </div>

      <div class="flex flex-col gap-1">
        <label class="text-xs text-neutral-400">Nome</label>
        <input
          v-model="form.name"
          type="text"
          maxlength="100"
          class="bg-[#111] border border-neutral-800 rounded-xl px-4 py-3 text-sm text-white outline-none focus:ring-2 focus:ring-white/10 focus:border-neutral-600 transition"
        />
      </div>

      <div class="flex flex-col gap-1">
        <label class="text-xs text-neutral-400">Bio</label>
        <textarea
          v-model="form.bio"
          rows="3"
          maxlength="300"
          placeholder="Conte algo sobre você…"
          class="bg-[#111] border border-neutral-800 rounded-xl px-4 py-3 text-sm text-white placeholder-neutral-600 outline-none focus:ring-2 focus:ring-white/10 focus:border-neutral-600 transition resize-none"
        />
        <span class="text-xs text-neutral-600 text-right">{{ form.bio?.length ?? 0 }} / 300</span>
      </div>

      <p v-if="error" class="text-sm text-red-400 flex items-center gap-1.5">
        <CircleX class="w-4 h-4 shrink-0" />
        {{ error }}
      </p>

      <p v-if="success" class="text-sm text-emerald-400 flex items-center gap-1.5">
        <CheckCircle class="w-4 h-4 shrink-0" />
        Perfil atualizado!
      </p>

      <button
        :disabled="loading || !changed"
        class="bg-white text-black text-sm font-semibold px-6 py-3 rounded-full hover:bg-neutral-200 transition disabled:opacity-40 disabled:cursor-not-allowed flex items-center justify-center gap-2"
        @click="save"
      >
        <Loader2 v-if="loading" class="w-4 h-4 animate-spin" />
        <span>Salvar</span>
      </button>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { Camera, Loader2, CircleX, CheckCircle } from 'lucide-vue-next'
import { useAuthStore } from '~/stores/auth'
import { useApi } from '~/composables/useApi'
import type { User } from '~/types'

definePageMeta({ middleware: ['auth'] })

const authStore = useAuthStore()
const { patch } = useApi()

const fileInput = ref<HTMLInputElement | null>(null)
const loading = ref(false)
const error = ref('')
const success = ref(false)

const form = ref({ name: '', bio: '' })
const preview = ref<string | null>(null)
const newAvatar = ref<string | null | undefined>(undefined)

const changed = computed(() => {
  const u = authStore.user
  if (!u) return false
  return form.value.name !== u.name
    || (form.value.bio ?? '') !== (u.bio ?? '')
    || newAvatar.value !== undefined
})

function pickAvatar() {
  fileInput.value?.click()
}

function onFileChange(e: Event) {
  const file = (e.target as HTMLInputElement).files?.[0]
  if (!file) return
  const reader = new FileReader()
  reader.onload = () => {
    const result = reader.result as string
    preview.value = result
    newAvatar.value = result
  }
  reader.readAsDataURL(file)
}

async function save() {
  if (!changed.value || loading.value) return
  error.value = ''
  success.value = false
  loading.value = true

  const payload: Record<string, string | null> = {
    name: form.value.name,
    bio:  form.value.bio || null,
  }
  if (newAvatar.value !== undefined) payload.avatar = newAvatar.value ?? null

  try {
    const updated = await patch<User>('/api/me', payload)
    authStore.setAuth(authStore.token!, updated)
    newAvatar.value = undefined
    success.value = true
    setTimeout(() => { success.value = false }, 3000)
  } catch (e: unknown) {
    error.value = e instanceof Error ? e.message : 'Erro ao salvar'
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  authStore.restoreFromStorage()
  const u = authStore.user
  if (u) {
    form.value.name = u.name
    form.value.bio  = u.bio ?? ''
    if (u.avatar) preview.value = u.avatar
  }
})
</script>
