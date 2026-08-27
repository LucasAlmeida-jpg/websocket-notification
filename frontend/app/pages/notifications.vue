<template>
  <AppLayout @create-post="showCreateModal = true">
    <div class="flex items-center justify-between mb-6">
      <div class="flex items-center gap-3">
        <h1 class="text-lg font-semibold text-white">Notifications</h1>
        <span
          v-if="notifStore.unreadCount > 0"
          class="bg-red-500 text-white text-xs font-bold rounded-full px-2 py-0.5 min-w-[20px] text-center"
        >
          {{ notifStore.unreadCount }}
        </span>
      </div>

      <button
        v-if="notifStore.unreadCount > 0"
        class="flex items-center gap-1.5 text-sm text-neutral-400 hover:text-white transition px-3 py-1.5 rounded-lg hover:bg-neutral-800"
        @click="markAllRead"
      >
        <CheckCheck class="w-4 h-4" />
        Mark all read
      </button>
    </div>

    <div class="bg-[#181818] border border-neutral-800 rounded-2xl overflow-hidden">
      <AppSpinner v-if="notifStore.loading && notifStore.items.length === 0" />

      <AppEmptyState
        v-else-if="notifStore.items.length === 0"
        :icon="BellOff"
        message="No notifications yet"
      />

      <ul v-else>
        <NotificationItem
          v-for="notif in notifStore.items"
          :key="notif.id"
          :notification="notif"
          @mark-read="markRead"
          @remove="remove"
        />
      </ul>

      <AppLoadMore
        :loading="notifStore.loading"
        :show="notifStore.currentPage < notifStore.lastPage"
        variant="flat"
        @load="fetchNotifications(notifStore.currentPage + 1)"
      />
    </div>

    <CreatePostModal v-model="showCreateModal" @posted="() => {}" />
  </AppLayout>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import { BellOff, CheckCheck } from 'lucide-vue-next'
import { useAuthStore } from '~/stores/auth'
import { useNotificationStore } from '~/stores/notifications'
import type { Notification } from '~/types'
import { useApi } from '~/composables/useApi'

definePageMeta({ middleware: ['auth'] })

const authStore = useAuthStore()
const notifStore = useNotificationStore()
const { get, patch, del } = useApi()

const showCreateModal = ref(false)

interface PaginatedResponse {
  data: Notification[]
  current_page: number
  last_page: number
}

async function fetchNotifications(page = 1) {
  notifStore.loading = true
  try {
    const res = await get<PaginatedResponse>(`/api/notifications?page=${page}`)
    notifStore.setItems(res.data, res.current_page, res.last_page)
    if (page === 1) {
      const counts = await get<{ count: number }>('/api/notifications/unread-count')
      notifStore.unreadCount = counts.count
    }
  } finally {
    notifStore.loading = false
  }
}

async function markRead(id: string) {
  notifStore.markRead(id)
  await patch(`/api/notifications/${id}/read`).catch(() => {})
}

async function markAllRead() {
  notifStore.markAllRead()
  await patch('/api/notifications/read-all').catch(() => {})
}

async function remove(id: string) {
  notifStore.remove(id)
  await del(`/api/notifications/${id}`).catch(() => {})
}

let pollInterval: ReturnType<typeof setInterval> | null = null

onMounted(async () => {
  authStore.restoreFromStorage()
  if (!authStore.isAuthenticated) return
  await fetchNotifications(1)
  pollInterval = setInterval(async () => {
    try {
      const { count } = await get<{ count: number }>('/api/notifications/unread-count')
      notifStore.unreadCount = count
    } catch {}
  }, 5000)
})

onUnmounted(() => {
  if (pollInterval) clearInterval(pollInterval)
})
</script>
