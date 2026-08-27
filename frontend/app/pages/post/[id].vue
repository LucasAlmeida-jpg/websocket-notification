<template>
  <AppLayout @create-post="showCreateModal = true">
    <button
      class="flex items-center gap-2 text-neutral-400 hover:text-white transition mb-5 text-sm"
      @click="router.back()"
    >
      <ArrowLeft class="w-4 h-4" />
      Back
    </button>

    <AppSpinner v-if="loading && !post" />

    <AppEmptyState
      v-else-if="!post"
      :icon="MessageCircle"
      message="Thread not found"
    />

    <template v-else>
      <div class="bg-[#181818] border border-neutral-800 rounded-2xl p-4 mb-3">
        <div class="flex gap-3">
          <button @click="navigateTo(`/profile/${post.user.id}`)">
            <AppAvatar :user-id="post.user.id" :name="post.user.name" :avatar="post.user.avatar" />
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
                <Heart class="w-4 h-4" :class="post.liked ? 'fill-red-500' : ''" />
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

      <AppEmptyState
        v-else
        :icon="MessageCircle"
        message="No replies yet"
        padding="sm"
      />

      <button
        class="fixed bottom-24 md:bottom-6 right-4 md:right-6 bg-white text-black px-5 py-3 rounded-full text-sm font-semibold hover:bg-neutral-200 transition shadow-lg flex items-center gap-2 z-10"
        @click="showReplyModal = true"
      >
        <MessageCircle class="w-4 h-4" />
        Reply
      </button>
    </template>

    <CreatePostModal v-model="showCreateModal" @posted="onPosted" />
    <CreatePostModal v-model="showReplyModal" :reply-to="post ?? undefined" @posted="onReplied" />
  </AppLayout>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { ArrowLeft, Heart, MessageCircle, Repeat2, Send, Trash2 } from 'lucide-vue-next'
import { useAuthStore } from '~/stores/auth'
import { useApi } from '~/composables/useApi'
import { timeAgo } from '~/utils/timeAgo'
import type { Post } from '~/types'

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
    if (reply) { reply.liked = res.liked; reply.likes_count = res.likes_count }
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
