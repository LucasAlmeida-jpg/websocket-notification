<template>
  <li
    class="flex items-start gap-3 px-5 py-4 transition border-b border-neutral-800 last:border-b-0"
    :class="notification.read_at ? 'bg-transparent' : 'bg-neutral-900/40'"
  >
    <button
      class="flex items-start gap-3 flex-1 min-w-0 text-left"
      @click="handleClick"
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
        <p class="text-xs text-neutral-500 mt-1">{{ timeAgo(notification.created_at, 'long') }}</p>
      </div>
    </button>

    <div class="flex items-center gap-1 shrink-0">
      <button
        v-if="!notification.read_at"
        class="p-1.5 rounded-lg text-neutral-500 hover:text-white hover:bg-neutral-800 transition"
        @click.stop="$emit('markRead', notification.id)"
      >
        <Check class="w-4 h-4" />
      </button>
      <button
        class="p-1.5 rounded-lg text-neutral-500 hover:text-red-400 hover:bg-neutral-800 transition"
        @click.stop="$emit('remove', notification.id)"
      >
        <Trash2 class="w-4 h-4" />
      </button>
    </div>
  </li>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { Check, Trash2, Heart, MessageCircle, UserPlus, AtSign, Bell, Repeat2, Send } from 'lucide-vue-next'
import { timeAgo } from '~/utils/timeAgo'
import { NotificationType } from '~/types'
import type { Notification } from '~/types'

const props = defineProps<{ notification: Notification }>()
const emit = defineEmits<{ markRead: [id: string]; remove: [id: string] }>()

const iconMap: Record<string, { component: unknown; bg: string; color: string }> = {
  [NotificationType.Like]:    { component: Heart,         bg: 'bg-red-500/20',    color: 'text-red-400' },
  [NotificationType.Comment]: { component: MessageCircle, bg: 'bg-blue-500/20',   color: 'text-blue-400' },
  [NotificationType.Follow]:  { component: UserPlus,      bg: 'bg-violet-500/20', color: 'text-violet-400' },
  [NotificationType.Mention]: { component: AtSign,        bg: 'bg-amber-500/20',  color: 'text-amber-400' },
  [NotificationType.Repost]:  { component: Repeat2,       bg: 'bg-green-500/20',  color: 'text-green-400' },
  [NotificationType.Share]:   { component: Send,          bg: 'bg-sky-500/20',    color: 'text-sky-400' },
}

const iconComponent = computed(() => iconMap[props.notification.data.type]?.component ?? Bell)
const iconBg        = computed(() => iconMap[props.notification.data.type]?.bg ?? 'bg-neutral-800')
const iconColor     = computed(() => iconMap[props.notification.data.type]?.color ?? 'text-neutral-400')

const bodyText = computed(() =>
  props.notification.data.message.replace(props.notification.data.actor_name, '').trim()
)

const destination = computed(() => {
  const { type, resource_type, resource_id, actor_id } = props.notification.data
  if (type === 'follow') return `/profile/${actor_id}`
  if (resource_type === 'post' && resource_id) return `/post/${resource_id}`
  return null
})

function handleClick() {
  if (!props.notification.read_at) emit('markRead', props.notification.id)
  if (destination.value) navigateTo(destination.value)
}
</script>
