<template>
  <div class="bg-[#181818] border border-neutral-800 rounded-2xl p-4 flex gap-3">
    <button
      class="shrink-0"
      @click="navigateTo(`/profile/${post.user.id}`)"
    >
      <div
        class="w-10 h-10 rounded-full flex items-center justify-center text-sm font-bold text-white shrink-0"
        :class="avatarColor(post.user.id)"
      >
        {{ initials(post.user.name) }}
      </div>
    </button>

    <div class="flex-1 min-w-0">
      <div class="flex items-center justify-between gap-2">
        <button
          class="font-semibold text-white text-sm hover:underline truncate"
          @click="navigateTo(`/profile/${post.user.id}`)"
        >
          {{ post.user.name }}
        </button>
        <div class="flex items-center gap-2 shrink-0">
          <span class="text-xs text-neutral-500">{{ timeAgo }}</span>
          <button
            v-if="isOwn"
            class="text-neutral-600 hover:text-red-500 transition p-1 rounded-lg hover:bg-neutral-800"
            @click="handleDelete"
          >
            <Trash2 class="w-4 h-4" />
          </button>
        </div>
      </div>

      <button
        class="text-neutral-200 text-sm mt-1 text-left w-full leading-relaxed hover:text-white transition"
        @click="navigateTo(`/post/${post.id}`)"
        v-html="renderedBody"
      />

      <div class="flex items-center gap-4 mt-3">
        <button
          class="flex items-center gap-1.5 text-neutral-500 hover:text-red-500 transition group"
          :class="{ 'text-red-500': post.liked }"
          @click="handleLike"
        >
          <Heart
            class="w-4 h-4 transition"
            :class="post.liked ? 'fill-red-500 text-red-500' : 'group-hover:text-red-500'"
          />
          <span class="text-xs">{{ post.likes_count }}</span>
        </button>

        <button
          class="flex items-center gap-1.5 text-neutral-500 hover:text-white transition"
          @click="navigateTo(`/post/${post.id}`)"
        >
          <MessageCircle class="w-4 h-4" />
          <span class="text-xs">{{ post.replies_count }}</span>
        </button>

        <button
          class="flex items-center gap-1.5 transition"
          :class="post.reposted ? 'text-green-500' : 'text-neutral-500 hover:text-green-500'"
          @click="handleRepost"
        >
          <Repeat2 class="w-4 h-4" />
          <span class="text-xs">{{ post.reposts_count || '' }}</span>
        </button>

        <button class="flex items-center gap-1.5 text-neutral-500 hover:text-white transition">
          <Send class="w-4 h-4" />
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'

function escapeHtml(text: string): string {
  return text.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
}
import { Heart, MessageCircle, Repeat2, Send, Trash2 } from 'lucide-vue-next'
import { useAuthStore } from '~/stores/auth'
import type { Post } from '~/stores/feed'

const props = defineProps<{ post: Post }>()
const emit = defineEmits<{ like: [id: number]; repost: [id: number]; delete: [id: number] }>()

const authStore = useAuthStore()

const isOwn = computed(() => authStore.user?.id === props.post.user.id)

const renderedBody = computed(() =>
  escapeHtml(props.post.body).replace(
    /@(\w+)/g,
    '<span class="text-blue-400 font-medium">@$1</span>',
  )
)

const timeAgo = computed(() => {
  const diff = Date.now() - new Date(props.post.created_at).getTime()
  const mins = Math.floor(diff / 60000)
  if (mins < 1) return 'now'
  if (mins < 60) return `${mins}m`
  const hours = Math.floor(mins / 60)
  if (hours < 24) return `${hours}h`
  return `${Math.floor(hours / 24)}d`
})

const avatarColors = [
  'bg-violet-600',
  'bg-blue-600',
  'bg-emerald-600',
  'bg-amber-600',
  'bg-rose-600',
  'bg-cyan-600',
]

function avatarColor(id: number): string {
  return avatarColors[id % 6]
}

function initials(name: string): string {
  return name
    .split(' ')
    .map((w) => w[0])
    .join('')
    .substring(0, 2)
    .toUpperCase()
}

function handleLike() {
  emit('like', props.post.id)
}

function handleRepost() {
  emit('repost', props.post.id)
}

function handleDelete() {
  emit('delete', props.post.id)
}
</script>
