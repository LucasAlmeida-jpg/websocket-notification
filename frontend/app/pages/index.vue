<template>
  <AppLayout @create-post="showCreateModal = true">
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

    <AppSpinner v-if="feedStore.loading && feedStore.items.length === 0" />

    <AppEmptyState
      v-else-if="feedStore.items.length === 0"
      :icon="MessageCircle"
      message="No threads yet. Start the conversation!"
    />

    <div v-else class="flex flex-col gap-3">
      <PostCard
        v-for="post in feedStore.items"
        :key="post.id"
        :post="post"
        @like="handleLike"
        @repost="handleRepost"
        @delete="handleDelete"
      />
      <AppLoadMore
        :loading="feedStore.loading"
        :show="feedStore.currentPage < feedStore.lastPage"
        @load="loadMore"
      />
    </div>

    <button
      class="md:hidden fixed bottom-20 right-4 w-14 h-14 bg-white text-black rounded-full flex items-center justify-center shadow-lg hover:bg-neutral-200 transition z-10"
      @click="showCreateModal = true"
    >
      <Pencil class="w-6 h-6" />
    </button>

    <CreatePostModal v-model="showCreateModal" @posted="onPosted" />
  </AppLayout>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import { Pencil, MessageCircle } from 'lucide-vue-next'
import { useAuthStore } from '~/stores/auth'
import { useNotificationStore } from '~/stores/notifications'
import { useFeedStore } from '~/stores/feed'
import type { Post } from '~/types'
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

async function handleRepost(id: number) {
  try {
    const res = await apiPost<{ reposted: boolean; reposts_count: number }>(`/api/posts/${id}/repost`, {})
    feedStore.toggleRepost(id, res.reposted, res.reposts_count)
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
