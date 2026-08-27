<template>
  <div class="min-h-screen bg-[#0d0d0d]">
    <AppSidebar @create-post="showCreateModal = true" />

    <main class="md:ml-64 flex justify-center px-4 py-6 pb-24 md:pb-6">
      <div class="w-full max-w-xl">
        <div class="hidden md:flex items-center justify-between mb-6">
          <h1 class="text-lg font-semibold text-white">Feed</h1>
          <button
            class="flex items-center gap-2 bg-white text-black text-sm font-semibold px-4 py-2 rounded-full hover:bg-neutral-200 transition"
            @click="showCreateModal = true"
          >
            <Pencil class="w-4 h-4" />
            New Thread
          </button>
        </div>

        <div v-if="feedStore.loading && feedStore.items.length === 0" class="flex justify-center py-16">
          <Loader2 class="w-6 h-6 animate-spin text-neutral-500" />
        </div>

        <div
          v-else-if="feedStore.items.length === 0"
          class="flex flex-col items-center justify-center py-16 gap-3 text-neutral-500"
        >
          <MessageCircle class="w-10 h-10" />
          <p class="text-sm">No threads yet. Start the conversation!</p>
        </div>

        <div v-else class="flex flex-col gap-3">
          <PostCard
            v-for="post in feedStore.items"
            :key="post.id"
            :post="post"
            @like="handleLike"
            @delete="handleDelete"
          />

          <div v-if="feedStore.currentPage < feedStore.lastPage" class="flex justify-center pt-2">
            <button
              :disabled="feedStore.loading"
              class="text-sm text-neutral-400 hover:text-white transition flex items-center gap-2 px-4 py-2 rounded-full border border-neutral-700 hover:border-neutral-500"
              @click="loadMore"
            >
              <Loader2 v-if="feedStore.loading" class="w-4 h-4 animate-spin" />
              <span>Load more</span>
            </button>
          </div>
        </div>
      </div>
    </main>

    <button
      class="md:hidden fixed bottom-20 right-4 w-14 h-14 bg-white text-black rounded-full flex items-center justify-center shadow-lg hover:bg-neutral-200 transition z-10"
      @click="showCreateModal = true"
    >
      <Pencil class="w-6 h-6" />
    </button>

    <CreatePostModal
      v-model="showCreateModal"
      @posted="onPosted"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import { Pencil, Loader2, MessageCircle } from 'lucide-vue-next'
import { useAuthStore } from '~/stores/auth'
import { useNotificationStore } from '~/stores/notifications'
import { useFeedStore } from '~/stores/feed'
import type { Post } from '~/stores/feed'
import { useApi } from '~/composables/useApi'

definePageMeta({ middleware: ['auth'] })

const authStore = useAuthStore()
const notifStore = useNotificationStore()
const feedStore = useFeedStore()
const { get, post: apiPost, del } = useApi()
const { $pusher } = useNuxtApp()

const showCreateModal = ref(false)

interface FeedResponse {
  data: Post[]
  current_page: number
  last_page: number
}

async function fetchFeed(page = 1) {
  feedStore.loading = true
  try {
    const res = await get<FeedResponse>(`/api/feed?page=${page}`)
    feedStore.setItems(res.data, res.current_page, res.last_page)
  } finally {
    feedStore.loading = false
  }
}

async function loadMore() {
  if (feedStore.currentPage < feedStore.lastPage) {
    await fetchFeed(feedStore.currentPage + 1)
  }
}

async function handleLike(id: number) {
  try {
    const res = await apiPost<{ liked: boolean; likes_count: number }>(`/api/posts/${id}/like`, {})
    feedStore.toggleLike(id, res.liked, res.likes_count)
  } catch {}
}

async function handleDelete(id: number) {
  try {
    await del(`/api/posts/${id}`)
    feedStore.removePost(id)
  } catch {}
}

function onPosted(post: Post) {
  feedStore.prependPost(post)
}

async function pollUnread() {
  try {
    const { count } = await get<{ count: number }>('/api/notifications/unread-count')
    notifStore.unreadCount = count
  } catch {}
}

let pollInterval: ReturnType<typeof setInterval> | null = null

onMounted(async () => {
  authStore.restoreFromStorage()
  if (!authStore.isAuthenticated) return
  await fetchFeed(1)
  if (authStore.user) $pusher.connect(authStore.user.id)
  pollInterval = setInterval(pollUnread, 5000)
})

onUnmounted(() => {
  if (pollInterval) clearInterval(pollInterval)
})
</script>
