<template>
  <div class="min-h-screen bg-gray-50">
    <header class="bg-white border-b border-gray-100 sticky top-0 z-10">
      <div class="max-w-2xl mx-auto px-4 h-14 flex items-center justify-between">
        <div class="flex items-center gap-2.5">
          <Bell class="w-5 h-5 text-gray-900" />
          <span class="font-semibold text-gray-900">Notificações</span>
          <span
            v-if="notifStore.unreadCount > 0"
            class="bg-gray-900 text-white text-xs font-bold rounded-full px-2 py-0.5 min-w-[20px] text-center"
          >
            {{ notifStore.unreadCount }}
          </span>
        </div>

        <div class="flex items-center gap-2">
          <button
            v-if="notifStore.unreadCount > 0"
            class="flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-900 transition px-3 py-1.5 rounded-lg hover:bg-gray-50"
            @click="markAllRead"
          >
            <CheckCheck class="w-4 h-4" />
            Marcar todas
          </button>

          <button
            class="flex items-center gap-1.5 text-sm text-gray-500 hover:text-red-600 transition px-3 py-1.5 rounded-lg hover:bg-red-50"
            @click="doLogout"
          >
            <LogOut class="w-4 h-4" />
          </button>
        </div>
      </div>
    </header>

    <main class="max-w-2xl mx-auto px-4 py-6 flex flex-col gap-4">
      <SendNotificationCard />

      <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-50 flex items-center justify-between">
          <span class="text-sm font-medium text-gray-900">Feed</span>
          <button
            class="text-gray-400 hover:text-gray-700 transition"
            :class="{ 'animate-spin': notifStore.loading }"
            @click="fetchNotifications(1)"
          >
            <RefreshCw class="w-4 h-4" />
          </button>
        </div>

        <div v-if="notifStore.loading && notifStore.items.length === 0" class="flex items-center justify-center py-16">
          <Loader2 class="w-5 h-5 animate-spin text-gray-400" />
        </div>

        <div v-else-if="notifStore.items.length === 0" class="flex flex-col items-center justify-center py-16 gap-2 text-gray-400">
          <BellOff class="w-8 h-8" />
          <p class="text-sm">Nenhuma notificação ainda</p>
        </div>

        <ul v-else class="divide-y divide-gray-50">
          <NotificationItem
            v-for="notif in notifStore.items"
            :key="notif.id"
            :notification="notif"
            @mark-read="markRead"
            @remove="remove"
          />
        </ul>

        <div v-if="notifStore.currentPage < notifStore.lastPage" class="px-5 py-4 border-t border-gray-50">
          <button
            class="w-full text-sm text-gray-500 hover:text-gray-900 transition flex items-center justify-center gap-2"
            :disabled="notifStore.loading"
            @click="fetchNotifications(notifStore.currentPage + 1)"
          >
            <Loader2 v-if="notifStore.loading" class="w-4 h-4 animate-spin" />
            <span>Carregar mais</span>
          </button>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup lang="ts">
import { onMounted, onUnmounted } from 'vue'
import { Bell, BellOff, CheckCheck, LogOut, RefreshCw, Loader2 } from 'lucide-vue-next'
import { useAuthStore } from '~/stores/auth'
import { useNotificationStore } from '~/stores/notifications'
import type { Notification } from '~/stores/notifications'
import { useApi } from '~/composables/useApi'

definePageMeta({ middleware: ['auth'] })

const authStore = useAuthStore()
const notifStore = useNotificationStore()
const { get, patch, del } = useApi()
const router = useRouter()
const { $pusher } = useNuxtApp()

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

async function pollUnread() {
  try {
    const { count } = await get<{ count: number }>('/api/notifications/unread-count')
    if (count !== notifStore.unreadCount) {
      await fetchNotifications(1)
    }
  } catch {}
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

async function doLogout() {
  await get('/api/logout').catch(() => {})
  $pusher.disconnect()
  authStore.logout()
  router.push('/login')
}

let pollInterval: ReturnType<typeof setInterval> | null = null

onMounted(async () => {
  authStore.restoreFromStorage()
  if (!authStore.isAuthenticated) return
  await fetchNotifications(1)
  if (authStore.user) $pusher.connect(authStore.user.id)
  pollInterval = setInterval(pollUnread, 5000)
})

onUnmounted(() => {
  if (pollInterval) clearInterval(pollInterval)
})
</script>
