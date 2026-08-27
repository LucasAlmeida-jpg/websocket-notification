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
          <img
            v-if="authStore.user?.avatar"
            :src="authStore.user.avatar"
            class="w-10 h-10 rounded-full object-cover shrink-0"
          />
          <div
            v-else
            class="w-10 h-10 rounded-full flex items-center justify-center text-sm font-bold text-white shrink-0"
            :class="avatarColor(authStore.user?.id ?? 0)"
          >
            {{ initials(authStore.user?.name ?? '') }}
          </div>
          <div class="flex-1 relative">
            <textarea
              ref="textareaRef"
              v-model="body"
              rows="4"
              maxlength="500"
              placeholder="What's on your mind?"
              class="w-full bg-transparent text-white text-sm placeholder-neutral-600 outline-none resize-none leading-relaxed"
              @input="onInput"
              @keydown="onKeydown"
              @keydown.ctrl.enter="submit"
              @keydown.meta.enter="submit"
            />

            <div
              v-if="mentionOpen && mentionUsers.length > 0"
              class="absolute left-0 z-10 mt-1 w-64 bg-[#222] border border-neutral-700 rounded-xl overflow-hidden shadow-xl"
            >
              <button
                v-for="(u, i) in mentionUsers"
                :key="u.id"
                class="flex items-center gap-2 w-full px-3 py-2 text-sm text-left transition"
                :class="i === mentionIndex ? 'bg-neutral-700 text-white' : 'text-neutral-300 hover:bg-neutral-700'"
                @mousedown.prevent="insertMention(u)"
              >
                <img
                  v-if="u.avatar"
                  :src="u.avatar"
                  class="w-7 h-7 rounded-full object-cover shrink-0"
                />
                <div
                  v-else
                  class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold text-white shrink-0"
                  :class="avatarColor(u.id)"
                >
                  {{ initials(u.name) }}
                </div>
                {{ u.name }}
              </button>
            </div>

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
import type { Post } from '~/types'

const props = defineProps<{
  modelValue: boolean
  replyTo?: Post | null
}>()

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
  posted: [post: Post]
}>()

const authStore = useAuthStore()
const { post: apiPost, get } = useApi()

const body = ref('')
const loading = ref(false)
const error = ref('')
const textareaRef = ref<HTMLTextAreaElement | null>(null)

import type { User } from '~/types'
type MentionUser = User
const mentionOpen = ref(false)
const mentionUsers = ref<MentionUser[]>([])
const mentionIndex = ref(0)
const mentionStart = ref(-1)

let mentionTimer: ReturnType<typeof setTimeout> | null = null

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

function getMentionQuery(): string | null {
  const el = textareaRef.value
  if (!el) return null
  const cursor = el.selectionStart
  const text = body.value.substring(0, cursor)
  const match = text.match(/@(\w*)$/)
  if (!match) return null
  mentionStart.value = cursor - match[0].length
  return match[1]
}

function onInput() {
  const q = getMentionQuery()
  if (q === null) {
    mentionOpen.value = false
    return
  }
  if (mentionTimer) clearTimeout(mentionTimer)
  mentionTimer = setTimeout(async () => {
    if (!q) {
      mentionUsers.value = []
      mentionOpen.value = false
      return
    }
    try {
      const res = await get<MentionUser[]>(`/api/users?q=${encodeURIComponent(q)}`)
      mentionUsers.value = res.slice(0, 5)
      mentionIndex.value = 0
      mentionOpen.value = mentionUsers.value.length > 0
    } catch {
      mentionOpen.value = false
    }
  }, 250)
}

function onKeydown(e: KeyboardEvent) {
  if (!mentionOpen.value) return
  if (e.key === 'ArrowDown') {
    e.preventDefault()
    mentionIndex.value = (mentionIndex.value + 1) % mentionUsers.value.length
  } else if (e.key === 'ArrowUp') {
    e.preventDefault()
    mentionIndex.value = (mentionIndex.value - 1 + mentionUsers.value.length) % mentionUsers.value.length
  } else if (e.key === 'Enter' || e.key === 'Tab') {
    e.preventDefault()
    insertMention(mentionUsers.value[mentionIndex.value])
  } else if (e.key === 'Escape') {
    mentionOpen.value = false
  }
}

function insertMention(user: MentionUser) {
  const el = textareaRef.value
  if (!el) return
  const cursor = el.selectionStart
  const firstName = user.name.split(' ')[0]
  const before = body.value.substring(0, mentionStart.value)
  const after = body.value.substring(cursor)
  body.value = `${before}@${firstName} ${after}`
  mentionOpen.value = false
  nextTick(() => {
    const pos = mentionStart.value + firstName.length + 2
    el.setSelectionRange(pos, pos)
    el.focus()
  })
}

function close() {
  body.value = ''
  error.value = ''
  mentionOpen.value = false
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
