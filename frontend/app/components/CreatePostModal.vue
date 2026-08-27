<template>
  <Teleport to="body">
    <div
      v-if="modelValue"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 px-4"
      @click.self="close"
    >
      <div class="bg-[#181818] border border-neutral-800 rounded-2xl w-full max-w-lg p-5">
        <div class="flex items-center justify-between mb-4">
          <span class="font-semibold text-white">
            {{ replyTo ? 'Reply to thread' : 'New Thread' }}
          </span>
          <button
            class="text-neutral-500 hover:text-white transition p-1 rounded-lg hover:bg-neutral-800"
            @click="close"
          >
            <X class="w-5 h-5" />
          </button>
        </div>

        <div
          v-if="replyTo"
          class="mb-4 p-3 rounded-xl bg-neutral-900 border border-neutral-700"
        >
          <p class="text-xs text-neutral-400 mb-1">Replying to {{ replyTo.user.name }}</p>
          <p class="text-sm text-neutral-300 line-clamp-2">{{ replyTo.body }}</p>
        </div>

        <div class="flex gap-3">
          <div
            class="w-10 h-10 rounded-full flex items-center justify-center text-sm font-bold text-white shrink-0"
            :class="avatarColor(authStore.user?.id ?? 0)"
          >
            {{ initials(authStore.user?.name ?? '') }}
          </div>
          <div class="flex-1">
            <textarea
              ref="textareaRef"
              v-model="body"
              rows="4"
              maxlength="500"
              placeholder="What's on your mind?"
              class="w-full bg-transparent text-white text-sm placeholder-neutral-600 outline-none resize-none leading-relaxed"
              @keydown.ctrl.enter="submit"
              @keydown.meta.enter="submit"
            />
            <div class="flex items-center justify-between mt-3 pt-3 border-t border-neutral-800">
              <span
                class="text-xs"
                :class="body.length >= 480 ? 'text-red-400' : 'text-neutral-500'"
              >
                {{ body.length }} / 500
              </span>
              <button
                :disabled="!body.trim() || loading"
                class="bg-white text-black text-sm font-semibold px-5 py-2 rounded-full hover:bg-neutral-200 transition disabled:opacity-40 disabled:cursor-not-allowed flex items-center gap-2"
                @click="submit"
              >
                <Loader2 v-if="loading" class="w-4 h-4 animate-spin" />
                <span>Post</span>
              </button>
            </div>
          </div>
        </div>

        <p v-if="error" class="text-sm text-red-400 mt-3 flex items-center gap-1.5">
          <CircleX class="w-4 h-4 shrink-0" />
          {{ error }}
        </p>
      </div>
    </div>
  </Teleport>
</template>

<script setup lang="ts">
import { ref, watch, nextTick } from 'vue'
import { X, Loader2, CircleX } from 'lucide-vue-next'
import { useAuthStore } from '~/stores/auth'
import { useApi } from '~/composables/useApi'
import type { Post } from '~/stores/feed'

const props = defineProps<{
  modelValue: boolean
  replyTo?: Post | null
}>()

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
  posted: [post: Post]
}>()

const authStore = useAuthStore()
const { post: apiPost } = useApi()

const body = ref('')
const loading = ref(false)
const error = ref('')
const textareaRef = ref<HTMLTextAreaElement | null>(null)

const avatarColors = [
  'bg-violet-600',
  'bg-blue-600',
  'bg-emerald-600',
  'bg-amber-600',
  'bg-rose-600',
  'bg-cyan-600',
]

function avatarColor(id: number): string {
  return avatarColors[id % 6]
}

function initials(name: string): string {
  return name
    .split(' ')
    .map((w) => w[0])
    .join('')
    .substring(0, 2)
    .toUpperCase()
}

function close() {
  body.value = ''
  error.value = ''
  emit('update:modelValue', false)
}

async function submit() {
  if (!body.value.trim() || loading.value) return
  error.value = ''
  loading.value = true
  try {
    const payload: { body: string; parent_id?: number } = { body: body.value.trim() }
    if (props.replyTo) payload.parent_id = props.replyTo.id
    const created = await apiPost<Post>('/api/posts', payload)
    emit('posted', created)
    close()
  } catch (e: unknown) {
    error.value = e instanceof Error ? e.message : 'Failed to post'
  } finally {
    loading.value = false
  }
}

watch(
  () => props.modelValue,
  async (val) => {
    if (val) {
      await nextTick()
      textareaRef.value?.focus()
    }
  },
)
</script>
