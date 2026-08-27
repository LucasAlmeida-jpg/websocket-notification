<template>
  <div class="min-h-screen bg-[#0d0d0d]">
    <AppSidebar @create-post="showCreateModal = true" />

    <main class="md:ml-64 flex justify-center px-4 py-6 pb-24 md:pb-6">
      <div class="w-full max-w-xl">
        <button
          class="flex items-center gap-2 text-neutral-400 hover:text-white transition mb-5 text-sm"
          @click="router.back()"
        >
          <ArrowLeft class="w-4 h-4" />
          Back
        </button>

        <div v-if="loading && !post" class="flex justify-center py-16">
          <Loader2 class="w-6 h-6 animate-spin text-neutral-500" />
        </div>

        <div v-else-if="!post" class="flex flex-col items-center justify-center py-16 gap-3 text-neutral-500">
          <MessageCircle class="w-10 h-10" />
          <p class="text-sm">Thread not found</p>
        </div>

        <template v-else>
          <div class="bg-[#181818] border border-neutral-800 rounded-2xl p-4 mb-3">
            <div class="flex gap-3">
              <button @click="navigateTo(`/profile/${post.user.id}`)">
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
                    class="font-semibold text-white text-sm hover:underline"
                    @click="navigateTo(`/profile/${post.user.id}`)"
                  >
                    {{ post.user.name }}
                  </button>
                  <div class="flex items-center gap-2 shrink-0">
                    <span class="text-xs text-neutral-500">{{ timeAgo(post.created_at) }}</span>
                    <button
                      v-if="authStore.user?.id === post.user.id"
                      class="text-neutral-600 hover:text-red-500 transition p-1 rounded-lg hover:bg-neutral-800"
                      @click="handleDelete(post.id)"
                    >
                      <Trash2 class="w-4 h-4" />
                    </button>
                  </div>
                </div>
                <p class="text-neutral-200 text-sm mt-1 leading-relaxed">{{ post.body }}</p>
                <div class="flex items-center gap-4 mt-3">
                  <button
                    class="flex items-center gap-1.5 transition group"
                    :class="post.liked ? 'text-red-500' : 'text-neutral-500 hover:text-red-500'"
                    @click="handleLike(post.id)"
                  >
                    <Heart
                      class="w-4 h-4"
                      :class="post.liked ? 'fill-red-500' : ''"
                    />
                    <span class="text-xs">{{ post.likes_count }}</span>
                  </button>
                  <button
                    class="flex items-center gap-1.5 text-neutral-500 hover:text-white transition"
                    @click="showReplyModal = true"
                  >
                    <MessageCircle class="w-4 h-4" />
                    <span class="text-xs">{{ post.replies_count }}</span>
                  </button>
                  <button class="flex items-center gap-1.5 text-neutral-500 hover:text-white transition">
                    <Repeat2 class="w-4 h-4" />
                  </button>
                  <button class="flex items-center gap-1.5 text-neutral-500 hover:text-white transition">
                    <Send class="w-4 h-4" />
                  </button>
                </div>
              </div>
            </div>
          </div>

          <div v-if="replies.length > 0" class="flex flex-col gap-3">
            <div class="text-xs text-neutral-500 font-medium uppercase tracking-wider px-1">
              Replies
            </div>
            <PostCard
              v-for="reply in replies"
              :key="reply.id"
              :post="reply"
              @like="handleReplyLike"
              @delete="handleReplyDelete"
            />
          </div>

          <div v-else class="flex flex-col items-center justify-center py-10 gap-2 text-neutral-600">
            <MessageCircle class="w-8 h-8" />
            <p class="text-sm">No replies yet</p>
          </div>

          <button
            class="fixed bottom-24 md:bottom-6 right-4 md:right-6 bg-white text-black px-5 py-3 rounded-full text-sm font-semibold hover:bg-neutral-200 transition shadow-lg flex items-center gap-2 z-10"
            @click="showReplyModal = true"
          >
            <MessageCircle class="w-4 h-4" />
            Reply
          </button>
        </template>
      </div>
    </main>

    <CreatePostModal
      v-model="showCreateModal"
      @posted="onPosted"
    />

    <CreatePostModal
      v-model="showReplyModal"
      :reply-to="post ?? undefined"
      @posted="onReplied"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { ArrowLeft, Heart, MessageCircle, Repeat2, Send, Trash2, Loader2 } from 'lucide-vue-next'
import { useAuthStore } from '~/stores/auth'
import { useApi } from '~/composables/useApi'
import type { Post } from '~/stores/feed'

definePageMeta({ middleware: ['auth'] })

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const { get, post: apiPost, del } = useApi()

const post = ref<Post | null>(null)
const replies = ref<Post[]>([])
const loading = ref(false)
const showCreateModal = ref(false)
const showReplyModal = ref(false)

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
  return name.split(' ').map((w) => w[0]).join('').substring(0, 2).toUpperCase()
}

function timeAgo(date: string): string {
  const diff = Date.now() - new Date(date).getTime()
  const mins = Math.floor(diff / 60000)
  if (mins < 1) return 'now'
  if (mins < 60) return `${mins}m`
  const hours = Math.floor(mins / 60)
  if (hours < 24) return `${hours}h`
  return `${Math.floor(hours / 24)}d`
}

async function fetchPost() {
  loading.value = true
  try {
    const res = await get<{ post: Post; replies: Post[] }>(`/api/posts/${route.params.id}`)
    post.value = res.post
    replies.value = res.replies
  } catch {
    post.value = null
  } finally {
    loading.value = false
  }
}

async function handleLike(id: number) {
  try {
    const res = await apiPost<{ liked: boolean; likes_count: number }>(`/api/posts/${id}/like`, {})
    if (post.value && post.value.id === id) {
      post.value.liked = res.liked
      post.value.likes_count = res.likes_count
    }
  } catch {}
}

async function handleDelete(id: number) {
  try {
    await del(`/api/posts/${id}`)
    router.back()
  } catch {}
}

async function handleReplyLike(id: number) {
  try {
    const res = await apiPost<{ liked: boolean; likes_count: number }>(`/api/posts/${id}/like`, {})
    const reply = replies.value.find((r) => r.id === id)
    if (reply) {
      reply.liked = res.liked
      reply.likes_count = res.likes_count
    }
  } catch {}
}

async function handleReplyDelete(id: number) {
  try {
    await del(`/api/posts/${id}`)
    replies.value = replies.value.filter((r) => r.id !== id)
    if (post.value) post.value.replies_count = Math.max(0, post.value.replies_count - 1)
  } catch {}
}

function onPosted(newPost: Post) {
  replies.value.unshift(newPost)
  if (post.value) post.value.replies_count++
}

function onReplied(newPost: Post) {
  replies.value.unshift(newPost)
  if (post.value) post.value.replies_count++
}

onMounted(() => {
  fetchPost()
})
</script>
