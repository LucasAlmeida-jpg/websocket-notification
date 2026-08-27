import { defineStore } from 'pinia'
import { ref } from 'vue'

export interface NotificationData {
  type: string
  message: string
  actor_id: number
  actor_name: string
  resource_type: string | null
  resource_id: number | null
}

export interface Notification {
  id: string
  type: string
  data: NotificationData
  read_at: string | null
  created_at: string
}

export const useNotificationStore = defineStore('notifications', () => {
  const items = ref<Notification[]>([])
  const unreadCount = ref(0)
  const loading = ref(false)
  const currentPage = ref(1)
  const lastPage = ref(1)

  function pushRealtime(notif: NotificationData) {
    const fake: Notification = {
      id: crypto.randomUUID(),
      type: 'App\\Notifications\\SocialNotification',
      data: notif,
      read_at: null,
      created_at: new Date().toISOString(),
    }
    items.value.unshift(fake)
    unreadCount.value++
  }

  function markRead(id: string) {
    const notif = items.value.find((n: Notification) => n.id === id)
    if (notif && !notif.read_at) {
      notif.read_at = new Date().toISOString()
      unreadCount.value = Math.max(0, unreadCount.value - 1)
    }
  }

  function markAllRead() {
    items.value.forEach((n: Notification) => {
      if (!n.read_at) n.read_at = new Date().toISOString()
    })
    unreadCount.value = 0
  }

  function remove(id: string) {
    const notif = items.value.find((n: Notification) => n.id === id)
    if (notif && !notif.read_at) unreadCount.value = Math.max(0, unreadCount.value - 1)
    items.value = items.value.filter((n: Notification) => n.id !== id)
  }

  function setItems(newItems: Notification[], page: number, last: number) {
    if (page === 1) {
      items.value = newItems
    } else {
      items.value.push(...newItems)
    }
    currentPage.value = page
    lastPage.value = last
  }

  return {
    items, unreadCount, loading, currentPage, lastPage,
    pushRealtime, markRead, markAllRead, remove, setItems,
  }
})
