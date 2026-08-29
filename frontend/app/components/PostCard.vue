<template>
  <div class="bg-[#181818] border border-neutral-800 rounded-2xl p-4 flex gap-3">
    <button
      class="shrink-0"
      @click="navigateTo(`/profile/${post.user.id}`)"
    >
      <AppAvatar :user-id="post.user.id" :name="post.user.name" :avatar="post.user.avatar" />
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
          <span class="text-xs text-neutral-500">{{ timeAgo(post.created_at) }}</span>
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
      >
        <template v-for="(seg, i) in bodySegments" :key="i">
          <span v-if="seg.type === 'mention'" class="text-blue-400 font-medium">@{{ seg.value }}</span>
          <template v-else>{{ seg.value }}</template>
        </template>
      </button>

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

        <button
          class="flex items-center gap-1.5 text-neutral-500 hover:text-white transition"
          @click.stop="showShare = true"
        >
          <Send class="w-4 h-4" />
        </button>
      </div>
    </div>
  </div>

  <SharePostModal v-model="showShare" :post-id="post.id" />
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { Heart, MessageCircle, Repeat2, Send, Trash2 } from 'lucide-vue-next'
import { useAuthStore } from '~/stores/auth'
import { timeAgo } from '~/utils/timeAgo'
import type { Post } from '~/types'

const props = defineProps<{ post: Post }>()
const emit = defineEmits<{ like: [id: number]; repost: [id: number]; delete: [id: number] }>()

const authStore = useAuthStore()
const showShare = ref(false)

const isOwn = computed(() => authStore.user?.id === props.post.user.id)

const bodySegments = computed(() => {
  const parts: Array<{ type: 'text' | 'mention'; value: string }> = []
  const regex = /@(\w+)/g
  let last = 0
  let match
  while ((match = regex.exec(props.post.body)) !== null) {
    if (match.index > last) parts.push({ type: 'text', value: props.post.body.slice(last, match.index) })
    parts.push({ type: 'mention', value: match[1] })
    last = regex.lastIndex
  }
  if (last < props.post.body.length) parts.push({ type: 'text', value: props.post.body.slice(last) })
  return parts
})

function handleLike() { emit('like', props.post.id) }
function handleRepost() { emit('repost', props.post.id) }
function handleDelete() { emit('delete', props.post.id) }
</script>
