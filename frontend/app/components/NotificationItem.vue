<template>
  <li
    class="flex items-start gap-3 px-5 py-4 transition"
    :class="notification.read_at ? 'bg-white' : 'bg-blue-50/40'"
  >
    <div class="w-9 h-9 rounded-full bg-gray-100 flex items-center justify-center shrink-0 text-base">
      {{ icon }}
    </div>

    <div class="flex-1 min-w-0">
      <p class="text-sm text-gray-900 leading-snug">
        <span class="font-medium">{{ notification.data.actor_name }}</span>
        {{ ' ' }}{{ notification.data.message.replace(notification.data.actor_name, '').trim() }}
      </p>
      <p class="text-xs text-gray-400 mt-1">{{ timeAgo }}</p>
    </div>

    <div class="flex items-center gap-1 shrink-0">
      <button
        v-if="!notification.read_at"
        class="p-1.5 rounded-lg text-gray-400 hover:text-blue-600 hover:bg-blue-50 transition"
        title="Marcar como lida"
        @click="$emit('markRead', notification.id)"
      >
        <Check class="w-4 h-4" />
      </button>
      <button
        class="p-1.5 rounded-lg text-gray-400 hover:text-red-500 hover:bg-red-50 transition"
        title="Remover"
        @click="$emit('remove', notification.id)"
      >
        <Trash2 class="w-4 h-4" />
      </button>
    </div>
  </li>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { Check, Trash2 } from 'lucide-vue-next'
import type { Notification } from '~/stores/notifications'

const props = defineProps<{ notification: Notification }>()
defineEmits<{ markRead: [id: string]; remove: [id: string] }>()

const icons: Record<string, string> = {
  like: '❤️', comment: '💬', follow: '👤', mention: '📣',
}
const icon = computed(() => icons[props.notification.data.type] ?? '🔔')

const timeAgo = computed(() => {
  const diff = Date.now() - new Date(props.notification.created_at).getTime()
  const mins = Math.floor(diff / 60000)
  if (mins < 1) return 'agora mesmo'
  if (mins < 60) return `há ${mins}min`
  const hours = Math.floor(mins / 60)
  if (hours < 24) return `há ${hours}h`
  return `há ${Math.floor(hours / 24)}d`
})
</script>
