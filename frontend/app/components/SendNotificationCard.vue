<template>
  <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
    <div class="flex items-center gap-2 mb-4">
      <Send class="w-4 h-4 text-gray-500" />
      <h2 class="text-sm font-medium text-gray-900">Enviar notificação de teste</h2>
    </div>

    <form class="flex flex-col gap-3" @submit.prevent="submit">
      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">Destinatário (user ID)</label>
          <input
            v-model.number="form.recipient_id"
            type="number"
            min="1"
            required
            placeholder="ex: 2"
            class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-gray-900 focus:border-transparent transition"
          />
        </div>

        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">Tipo</label>
          <select
            v-model="form.type"
            class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-gray-900 focus:border-transparent transition bg-white"
          >
            <option value="like">❤️ like</option>
            <option value="comment">💬 comment</option>
            <option value="follow">👤 follow</option>
            <option value="mention">📣 mention</option>
          </select>
        </div>
      </div>

      <div>
        <label class="block text-xs font-medium text-gray-600 mb-1">Mensagem</label>
        <input
          v-model="form.message"
          type="text"
          required
          placeholder="ex: curtiu sua foto"
          class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-gray-900 focus:border-transparent transition"
        />
      </div>

      <div class="flex items-center gap-3">
        <button
          type="submit"
          :disabled="loading"
          class="flex items-center gap-2 bg-gray-900 text-white rounded-lg px-4 py-2 text-sm font-medium hover:bg-gray-700 transition disabled:opacity-50 disabled:cursor-not-allowed"
        >
          <Loader2 v-if="loading" class="w-4 h-4 animate-spin" />
          <Send v-else class="w-4 h-4" />
          Enviar
        </button>

        <p v-if="success" class="flex items-center gap-1.5 text-sm text-emerald-600">
          <CircleCheck class="w-4 h-4" />
          Enviado!
        </p>
        <p v-if="error" class="flex items-center gap-1.5 text-sm text-red-500">
          <CircleX class="w-4 h-4" />
          {{ error }}
        </p>
      </div>
    </form>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive } from 'vue'
import { Send, Loader2, CircleCheck, CircleX } from 'lucide-vue-next'
import { useApi } from '~/composables/useApi'

const { post } = useApi()

const form = reactive({
  recipient_id: null as number | null,
  type: 'like' as 'like' | 'comment' | 'follow' | 'mention',
  message: '',
})

const loading = ref(false)
const success = ref(false)
const error = ref('')

async function submit() {
  error.value = ''
  success.value = false
  loading.value = true
  try {
    await post('/api/notifications/send', form)
    success.value = true
    form.message = ''
    setTimeout(() => { success.value = false }, 3000)
  } catch (e: unknown) {
    error.value = e instanceof Error ? e.message : 'Erro ao enviar'
  } finally {
    loading.value = false
  }
}
</script>
