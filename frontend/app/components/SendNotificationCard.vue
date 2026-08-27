<template>
  <div class="bg-[#181818] border border-neutral-800 rounded-2xl p-5">
    <div class="flex items-center gap-2 mb-4">
      <Send class="w-4 h-4 text-neutral-400" />
      <h2 class="text-sm font-medium text-white">Send test notification</h2>
    </div>

    <form class="flex flex-col gap-3" @submit.prevent="submit">
      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="block text-xs font-medium text-neutral-400 mb-1">Recipient (user ID)</label>
          <input
            v-model.number="form.recipient_id"
            type="number"
            min="1"
            required
            placeholder="e.g. 2"
            class="w-full rounded-lg border border-neutral-700 bg-neutral-900 text-white px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-white/20 focus:border-neutral-600 transition placeholder-neutral-600"
          />
        </div>

        <div>
          <label class="block text-xs font-medium text-neutral-400 mb-1">Type</label>
          <select
            v-model="form.type"
            class="w-full rounded-lg border border-neutral-700 bg-neutral-900 text-white px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-white/20 focus:border-neutral-600 transition"
          >
            <option value="like">like</option>
            <option value="comment">comment</option>
            <option value="follow">follow</option>
            <option value="mention">mention</option>
          </select>
        </div>
      </div>

      <div>
        <label class="block text-xs font-medium text-neutral-400 mb-1">Message</label>
        <input
          v-model="form.message"
          type="text"
          required
          placeholder="e.g. liked your post"
          class="w-full rounded-lg border border-neutral-700 bg-neutral-900 text-white px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-white/20 focus:border-neutral-600 transition placeholder-neutral-600"
        />
      </div>

      <div class="flex items-center gap-3">
        <button
          type="submit"
          :disabled="loading"
          class="flex items-center gap-2 bg-white text-black rounded-lg px-4 py-2 text-sm font-semibold hover:bg-neutral-200 transition disabled:opacity-40 disabled:cursor-not-allowed"
        >
          <Loader2 v-if="loading" class="w-4 h-4 animate-spin" />
          <Send v-else class="w-4 h-4" />
          Send
        </button>

        <p v-if="success" class="flex items-center gap-1.5 text-sm text-emerald-400">
          <CircleCheck class="w-4 h-4" />
          Sent!
        </p>
        <p v-if="error" class="flex items-center gap-1.5 text-sm text-red-400">
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
    error.value = e instanceof Error ? e.message : 'Failed to send'
  } finally {
    loading.value = false
  }
}
</script>
