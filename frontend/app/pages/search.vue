<template>
  <AppLayout @create-post="showCreateModal = true">
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

    <AppSpinner v-if="loading" padding="sm" />

    <AppEmptyState
      v-else-if="results.length === 0 && query.trim().length > 0"
      :icon="UserX"
      message="No users found"
      padding="sm"
    />

    <div v-else class="flex flex-col gap-2">
      <div
        v-for="user in results"
        :key="user.id"
        class="flex items-center gap-3 px-4 py-3 bg-[#181818] border border-neutral-800 rounded-2xl"
      >
        <NuxtLink :to="`/profile/${user.id}`" class="flex items-center gap-3 flex-1 min-w-0">
          <AppAvatar :user-id="user.id" :name="user.name" :avatar="user.avatar" />
          <div class="min-w-0">
            <p class="text-sm font-medium text-white truncate">{{ user.name }}</p>
            <p v-if="user.bio" class="text-xs text-neutral-500 truncate">{{ user.bio }}</p>
          </div>
        </NuxtLink>

        <AppFollowButton
          :following="user.is_following"
          :disabled="followingInProgress.has(user.id)"
          @toggle="toggleFollow(user)"
        />
      </div>
    </div>

    <CreatePostModal v-model="showCreateModal" @posted="() => {}" />
  </AppLayout>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { Search, UserX } from 'lucide-vue-next'
import { useApi } from '~/composables/useApi'
import type { User } from '~/types'

definePageMeta({ middleware: ['auth'] })

interface SearchUser extends User {
  is_following: boolean
}

const { get, post } = useApi()

const query = ref('')
const results = ref<SearchUser[]>([])
const loading = ref(false)
const showCreateModal = ref(false)
const followingInProgress = ref(new Set<number>())

let debounceTimer: ReturnType<typeof setTimeout> | null = null

function onInput() {
  if (debounceTimer) clearTimeout(debounceTimer)
  if (!query.value.trim()) {
    results.value = []
    return
  }
  debounceTimer = setTimeout(async () => {
    loading.value = true
    try {
      const res = await get<SearchUser[]>(`/api/users?q=${encodeURIComponent(query.value)}`)
      results.value = res
    } catch {
      results.value = []
    } finally {
      loading.value = false
    }
  }, 350)
}

async function toggleFollow(user: SearchUser) {
  if (followingInProgress.value.has(user.id)) return
  followingInProgress.value = new Set(followingInProgress.value).add(user.id)
  try {
    const res = await post<{ following: boolean }>(`/api/users/${user.id}/follow`, {})
    const found = results.value.find((u) => u.id === user.id)
    if (found) found.is_following = res.following
  } catch {
  } finally {
    const next = new Set(followingInProgress.value)
    next.delete(user.id)
    followingInProgress.value = next
  }
}
</script>
