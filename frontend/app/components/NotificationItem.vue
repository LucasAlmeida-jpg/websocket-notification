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
        <p class="text-xs text-neutral-500 mt-1">{{ timeAgo }}</p>
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
import type { Notification } from '~/stores/notifications'

const props = defineProps<{ notification: Notification }>()
const emit = defineEmits<{ markRead: [id: string]; remove: [id: string] }>()

const iconMap: Record<string, { component: unknown; bg: string; color: string }> = {
  like:    { component: Heart,          bg: 'bg-red-500/20',    color: 'text-red-400' },
  comment: { component: MessageCircle,  bg: 'bg-blue-500/20',   color: 'text-blue-400' },
  follow:  { component: UserPlus,       bg: 'bg-violet-500/20', color: 'text-violet-400' },
  mention: { component: AtSign,         bg: 'bg-amber-500/20',  color: 'text-amber-400' },
  repost:  { component: Repeat2,        bg: 'bg-green-500/20',  color: 'text-green-400' },
  share:   { component: Send,           bg: 'bg-sky-500/20',    color: 'text-sky-400' },
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

const timeAgo = computed(() => {
  const diff = Date.now() - new Date(props.notification.created_at).getTime()
  const mins = Math.floor(diff / 60000)
  if (mins < 1) return 'just now'
  if (mins < 60) return `${mins}m ago`
  const hours = Math.floor(mins / 60)
  if (hours < 24) return `${hours}h ago`
  return `${Math.floor(hours / 24)}d ago`
})

function handleClick() {
  if (!props.notification.read_at) emit('markRead', props.notification.id)
  if (destination.value) navigateTo(destination.value)
}
</script>
