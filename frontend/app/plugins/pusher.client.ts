import Pusher from 'pusher-js'
import { useAuthStore } from '~/stores/auth'
import { useNotificationStore } from '~/stores/notifications'
import type { NotificationData } from '~/stores/notifications'

export default defineNuxtPlugin(() => {
  const config = useRuntimeConfig()
  const authStore = useAuthStore()
  const notifStore = useNotificationStore()

  let pusher: Pusher | null = null
  let channel: ReturnType<Pusher['subscribe']> | null = null

  function connect(userId: number) {
    if (pusher) return

    pusher = new Pusher(config.public.pusherKey, {
      wsHost: config.public.pusherHost,
      wsPort: Number(config.public.pusherPort),
      wssPort: Number(config.public.pusherPort),
      forceTLS: false,
      enabledTransports: ['ws'],
      cluster: config.public.pusherCluster,
      authEndpoint: `${config.public.apiBase}/broadcasting/auth`,
      auth: {
        headers: {
          Authorization: `Bearer ${authStore.token}`,
          Accept: 'application/json',
        },
      },
    })

    channel = pusher.subscribe(`private-users.${userId}`)

    channel.bind('notification.created', (data: { notification: NotificationData }) => {
      notifStore.pushRealtime(data.notification)
    })
  }

  function disconnect() {
    channel?.unbind_all()
    pusher?.disconnect()
    pusher = null
    channel = null
  }

  return {
    provide: {
      pusher: { connect, disconnect },
    },
  }
})
