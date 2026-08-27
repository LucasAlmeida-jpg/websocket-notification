<template>
  <li
    class="flex items-start gap-3 px-5 py-4 transition border-b border-neutral-800 last:border-b-0"
    :class="notification.read_at ? 'bg-transparent' : 'bg-neutral-900/40'"
  >
    <div
      class="w-9 h-9 rounded-full flex items-center justify-center shrink-0"
      :class="iconBg"
    >
      <component :is="iconComponent" class="w-4 h-4" :class="iconColor" />
    </div>

    <div class="flex-1 min-w-0">
      <p class="text-sm text-white leading-snug">
        <span class="font-semibold">{{ notification.data.actor_name }}</span>
        {{ ' ' }}{{ bodyText }}
      </p>
      <p class="text-xs text-neutral-500 mt-1">{{ timeAgo }}</p>
    </div>

    <div class="flex items-center gap-1 shrink-0">
      <button
        v-if="!notification.read_at"
        class="p-1.5 rounded-lg text-neutral-500 hover:text-white hover:bg-neutral-800 transition"
        @click="$emit('markRead', notification.id)"
      >
        <Check class="w-4 h-4" />
      </button>
      <button
        class="p-1.5 rounded-lg text-neutral-500 hover:text-red-400 hover:bg-neutral-800 transition"
        @click="$emit('remove', notification.id)"
      >
        <Trash2 class="w-4 h-4" />
      </button>
    </div>
  </li>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { Check, Trash2, Heart, MessageCircle, UserPlus, AtSign, Bell } from 'lucide-vue-next'
import type { Notification } from '~/stores/notifications'

const props = defineProps<{ notification: Notification }>()
defineEmits<{ markRead: [id: string]; remove: [id: string] }>()

const iconMap: Record<string, { component: unknown; bg: string; color: string }> = {
  like: { component: Heart, bg: 'bg-red-500/20', color: 'text-red-400' },
  comment: { component: MessageCircle, bg: 'bg-blue-500/20', color: 'text-blue-400' },
  follow: { component: UserPlus, bg: 'bg-violet-500/20', color: 'text-violet-400' },
  mention: { component: AtSign, bg: 'bg-amber-500/20', color: 'text-amber-400' },
}

const iconComponent = computed(() => iconMap[props.notification.data.type]?.component ?? Bell)
const iconBg = computed(() => iconMap[props.notification.data.type]?.bg ?? 'bg-neutral-800')
const iconColor = computed(() => iconMap[props.notification.data.type]?.color ?? 'text-neutral-400')

const bodyText = computed(() => {
  const msg = props.notification.data.message
  return msg.replace(props.notification.data.actor_name, '').trim()
})

const timeAgo = computed(() => {
  const diff = Date.now() - new Date(props.notification.created_at).getTime()
  const mins = Math.floor(diff / 60000)
  if (mins < 1) return 'just now'
  if (mins < 60) return `${mins}m ago`
  const hours = Math.floor(mins / 60)
  if (hours < 24) return `${hours}h ago`
  return `${Math.floor(hours / 24)}d ago`
})
</script>
