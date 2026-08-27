<template>
  <div class="min-h-screen bg-[#0d0d0d]">
    <AppSidebar @create-post="showCreateModal = true" />

    <main class="md:ml-64 flex justify-center px-4 py-6 pb-24 md:pb-6">
      <div class="w-full max-w-xl">
        <h1 class="text-lg font-semibold text-white mb-5">Search</h1>

        <div class="relative mb-6">
          <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-neutral-500 pointer-events-none" />
          <input
            v-model="query"
            type="text"
            placeholder="Search users…"
            class="w-full bg-[#181818] border border-neutral-800 rounded-xl pl-10 pr-4 py-3 text-sm text-white placeholder-neutral-600 outline-none focus:ring-2 focus:ring-white/10 focus:border-neutral-600 transition"
            @input="onInput"
          />
        </div>

        <div v-if="loading" class="flex justify-center py-10">
          <Loader2 class="w-5 h-5 animate-spin text-neutral-500" />
        </div>

        <div v-else-if="results.length === 0 && query.trim().length > 0" class="flex flex-col items-center py-10 gap-2 text-neutral-600">
          <UserX class="w-8 h-8" />
          <p class="text-sm">No users found</p>
        </div>

        <div v-else class="flex flex-col gap-2">
          <div
            v-for="user in results"
            :key="user.id"
            class="flex items-center gap-3 px-4 py-3 bg-[#181818] border border-neutral-800 rounded-2xl"
          >
            <NuxtLink :to="`/profile/${user.id}`" class="flex items-center gap-3 flex-1 min-w-0">
              <div
                class="w-10 h-10 rounded-full flex items-center justify-center text-sm font-bold text-white shrink-0"
                :class="avatarColor(user.id)"
              >
                {{ initials(user.name) }}
              </div>
              <div class="min-w-0">
                <p class="text-sm font-medium text-white truncate">{{ user.name }}</p>
                <p v-if="user.bio" class="text-xs text-neutral-500 truncate">{{ user.bio }}</p>
              </div>
            </NuxtLink>

            <button
              class="shrink-0 px-4 py-1.5 rounded-full text-xs font-semibold transition"
              :class="user.is_following
                ? 'border border-neutral-600 text-white hover:bg-neutral-800'
                : 'bg-white text-black hover:bg-neutral-200'"
              :disabled="followingInProgress.has(user.id)"
              @click="toggleFollow(user)"
            >
              {{ user.is_following ? 'Following' : 'Follow' }}
            </button>
          </div>
        </div>
      </div>
    </main>

    <CreatePostModal
      v-model="showCreateModal"
      @posted="() => {}"
    />
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { Search, Loader2, UserX } from 'lucide-vue-next'
import { useApi } from '~/composables/useApi'

definePageMeta({ middleware: ['auth'] })

interface User {
  id: number
  name: string
  avatar: string | null
}

const { get } = useApi()

const query = ref('')
const results = ref<User[]>([])
const loading = ref(false)
const showCreateModal = ref(false)

let debounceTimer: ReturnType<typeof setTimeout> | null = null

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
  return name.split(' ').map((w) => w[0]).join('').substring(0, 2).toUpperCase()
}

function onInput() {
  if (debounceTimer) clearTimeout(debounceTimer)
  if (!query.value.trim()) {
    results.value = []
    return
  }
  debounceTimer = setTimeout(async () => {
    loading.value = true
    try {
      const res = await get<User[]>(`/api/users?q=${encodeURIComponent(query.value)}`)
      results.value = res
    } catch {
      results.value = []
    } finally {
      loading.value = false
    }
  }, 350)
}
</script>
