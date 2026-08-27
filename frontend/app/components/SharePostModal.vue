<template>
  <Teleport to="body">
    <div
      v-if="modelValue"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 px-4"
      @click.self="close"
    >
      <div class="bg-[#181818] border border-neutral-800 rounded-2xl w-full max-w-sm p-5">
        <div class="flex items-center justify-between mb-4">
          <span class="font-semibold text-white text-sm">Enviar para</span>
          <button
            class="text-neutral-500 hover:text-white transition p-1 rounded-lg hover:bg-neutral-800"
            @click="close"
          >
            <X class="w-5 h-5" />
          </button>
        </div>

        <div class="relative mb-3">
          <SearchIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-neutral-500 pointer-events-none" />
          <input
            v-model="query"
            type="text"
            placeholder="Buscar…"
            class="w-full bg-[#111] border border-neutral-800 rounded-xl pl-10 pr-4 py-2.5 text-sm text-white placeholder-neutral-600 outline-none focus:ring-2 focus:ring-white/10 transition"
          />
        </div>

        <div v-if="loading" class="flex justify-center py-8">
          <Loader2 class="w-5 h-5 animate-spin text-neutral-500" />
        </div>

        <div v-else-if="filtered.length === 0" class="flex flex-col items-center py-8 gap-2 text-neutral-600">
          <UserX class="w-7 h-7" />
          <p class="text-xs">Você não segue ninguém ainda</p>
        </div>

        <ul v-else class="flex flex-col gap-1 max-h-64 overflow-y-auto">
          <li
            v-for="u in filtered"
            :key="u.id"
          >
            <button
              class="flex items-center gap-3 w-full px-3 py-2.5 rounded-xl hover:bg-neutral-800 transition text-left"
              :disabled="sent.has(u.id) || sending.has(u.id)"
              @click="send(u)"
            >
              <img
                v-if="u.avatar"
                :src="u.avatar"
                class="w-8 h-8 rounded-full object-cover shrink-0"
              />
              <div
                v-else
                class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold text-white shrink-0"
                :class="avatarColor(u.id)"
              >
                {{ initials(u.name) }}
              </div>
              <span class="text-sm text-white flex-1 truncate">{{ u.name }}</span>
              <span v-if="sent.has(u.id)" class="text-xs text-neutral-500">Enviado</span>
              <Loader2 v-else-if="sending.has(u.id)" class="w-4 h-4 animate-spin text-neutral-500" />
              <Send v-else class="w-4 h-4 text-neutral-500" />
            </button>
          </li>
        </ul>
      </div>
    </div>
  </Teleport>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { X, Loader2, Send, Search as SearchIcon, UserX } from 'lucide-vue-next'
import { useApi } from '~/composables/useApi'
import type { User } from '~/types'

const props = defineProps<{ modelValue: boolean; postId: number | null }>()
const emit = defineEmits<{ 'update:modelValue': [value: boolean] }>()

type FollowingUser = User

const { get, post: apiPost } = useApi()

const users = ref<FollowingUser[]>([])
const loading = ref(false)
const query = ref('')
const sending = ref(new Set<number>())
const sent = ref(new Set<number>())

const avatarColors = [
  'bg-violet-600', 'bg-blue-600', 'bg-emerald-600',
  'bg-amber-600', 'bg-rose-600', 'bg-cyan-600',
]

function avatarColor(id: number) { return avatarColors[id % 6] }
function initials(name: string) {
  return name.split(' ').map((w) => w[0]).join('').substring(0, 2).toUpperCase()
}

const filtered = computed(() => {
  const q = query.value.toLowerCase()
  return q ? users.value.filter((u) => u.name.toLowerCase().includes(q)) : users.value
})

async function fetchFollowing() {
  loading.value = true
  try {
    users.value = await get<FollowingUser[]>('/api/following')
  } catch {
    users.value = []
  } finally {
    loading.value = false
  }
}

async function send(user: FollowingUser) {
  if (!props.postId || sending.value.has(user.id) || sent.value.has(user.id)) return
  sending.value = new Set(sending.value).add(user.id)
  try {
    await apiPost(`/api/posts/${props.postId}/send`, { user_id: user.id })
    const next = new Set(sent.value).add(user.id)
    sent.value = next
  } finally {
    const s = new Set(sending.value)
    s.delete(user.id)
    sending.value = s
  }
}

function close() {
  query.value = ''
  emit('update:modelValue', false)
}

watch(() => props.modelValue, (val) => {
  if (val) {
    sent.value = new Set()
    fetchFollowing()
  }
})
</script>
