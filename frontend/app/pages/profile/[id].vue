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

        <div v-if="loading && !profile" class="flex justify-center py-16">
          <Loader2 class="w-6 h-6 animate-spin text-neutral-500" />
        </div>

        <div v-else-if="!profile" class="flex flex-col items-center justify-center py-16 gap-3 text-neutral-500">
          <User class="w-10 h-10" />
          <p class="text-sm">User not found</p>
        </div>

        <template v-else>
          <div class="bg-[#181818] border border-neutral-800 rounded-2xl p-6 mb-4">
            <div class="flex items-start justify-between gap-4">
              <div class="flex items-start gap-4">
                <img
                  v-if="profile.avatar"
                  :src="profile.avatar"
                  class="w-16 h-16 rounded-full object-cover shrink-0"
                />
                <div
                  v-else
                  class="w-16 h-16 rounded-full flex items-center justify-center text-xl font-bold text-white shrink-0"
                  :class="avatarColor(profile.id)"
                >
                  {{ initials(profile.name) }}
                </div>
                <div>
                  <h1 class="text-lg font-semibold text-white">{{ profile.name }}</h1>
                  <p v-if="profile.bio" class="text-sm text-neutral-400 mt-1 leading-relaxed">
                    {{ profile.bio }}
                  </p>
                  <div class="flex items-center gap-4 mt-3">
                    <span class="text-sm text-neutral-300">
                      <span class="font-semibold text-white">{{ profile.followers_count }}</span>
                      <span class="text-neutral-500 ml-1">followers</span>
                    </span>
                    <span class="text-sm text-neutral-300">
                      <span class="font-semibold text-white">{{ profile.following_count }}</span>
                      <span class="text-neutral-500 ml-1">following</span>
                    </span>
                  </div>
                </div>
              </div>

              <button
                v-if="authStore.user?.id !== profile.id"
                class="shrink-0 px-5 py-2 rounded-full text-sm font-semibold transition"
                :class="profile.is_following
                  ? 'border border-neutral-600 text-white hover:bg-neutral-800'
                  : 'bg-white text-black hover:bg-neutral-200'"
                @click="handleFollow"
              >
                {{ profile.is_following ? 'Following' : 'Follow' }}
              </button>
            </div>
          </div>

          <div v-if="posts.length === 0 && !postsLoading" class="flex flex-col items-center justify-center py-12 gap-3 text-neutral-600">
            <MessageCircle class="w-8 h-8" />
            <p class="text-sm">No threads yet</p>
          </div>

          <div class="flex flex-col gap-3">
            <PostCard
              v-for="p in posts"
              :key="p.id"
              :post="p"
              @like="handleLike"
              @repost="handleRepost"
              @delete="handlePostDelete"
            />

            <div v-if="postsCurrentPage < postsLastPage" class="flex justify-center pt-2">
              <button
                :disabled="postsLoading"
                class="text-sm text-neutral-400 hover:text-white transition flex items-center gap-2 px-4 py-2 rounded-full border border-neutral-700 hover:border-neutral-500"
                @click="loadMorePosts"
              >
                <Loader2 v-if="postsLoading" class="w-4 h-4 animate-spin" />
                <span>Load more</span>
              </button>
            </div>
          </div>
        </template>
      </div>
    </main>

    <CreatePostModal
      v-model="showCreateModal"
      @posted="onPosted"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { ArrowLeft, User, Loader2, MessageCircle } from 'lucide-vue-next'
import { useAuthStore } from '~/stores/auth'
import { useApi } from '~/composables/useApi'
import type { Post } from '~/types'

definePageMeta({ middleware: ['auth'] })

interface UserProfile {
  id: number
  name: string
  avatar: string | null
  bio: string | null
  followers_count: number
  following_count: number
  is_following: boolean
}

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const { get, post: apiPost, del } = useApi()

const profile = ref<UserProfile | null>(null)
const posts = ref<Post[]>([])
const loading = ref(false)
const postsLoading = ref(false)
const postsCurrentPage = ref(1)
const postsLastPage = ref(1)
const showCreateModal = ref(false)

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

interface ProfileResponse {
  user: UserProfile
  posts: {
    data: Post[]
    current_page: number
    last_page: number
  }
}

async function fetchProfile() {
  loading.value = true
  try {
    const res = await get<ProfileResponse>(`/api/users/${route.params.id}`)
    profile.value = res.user
    posts.value = res.posts.data
    postsCurrentPage.value = res.posts.current_page
    postsLastPage.value = res.posts.last_page
  } catch {
    profile.value = null
  } finally {
    loading.value = false
  }
}

async function loadMorePosts() {
  if (postsCurrentPage.value >= postsLastPage.value) return
  postsLoading.value = true
  try {
    const res = await get<ProfileResponse>(`/api/users/${route.params.id}?page=${postsCurrentPage.value + 1}`)
    posts.value.push(...res.posts.data)
    postsCurrentPage.value = res.posts.current_page
    postsLastPage.value = res.posts.last_page
  } finally {
    postsLoading.value = false
  }
}

async function handleFollow() {
  if (!profile.value) return
  try {
    const res = await apiPost<{ following: boolean }>(`/api/users/${profile.value.id}/follow`, {})
    profile.value.is_following = res.following
    if (res.following) {
      profile.value.followers_count++
    } else {
      profile.value.followers_count = Math.max(0, profile.value.followers_count - 1)
    }
  } catch {}
}

async function handleLike(id: number) {
  try {
    const res = await apiPost<{ liked: boolean; likes_count: number }>(`/api/posts/${id}/like`, {})
    const p = posts.value.find((x) => x.id === id)
    if (p) {
      p.liked = res.liked
      p.likes_count = res.likes_count
    }
  } catch {}
}

async function handleRepost(id: number) {
  try {
    const res = await apiPost<{ reposted: boolean; reposts_count: number }>(`/api/posts/${id}/repost`, {})
    const p = posts.value.find((x) => x.id === id)
    if (p) {
      p.reposted = res.reposted
      p.reposts_count = res.reposts_count
    }
  } catch {}
}

async function handlePostDelete(id: number) {
  try {
    await del(`/api/posts/${id}`)
    posts.value = posts.value.filter((p) => p.id !== id)
  } catch {}
}

function onPosted(post: Post) {
  if (authStore.user && post.user.id === Number(route.params.id)) {
    posts.value.unshift(post)
  }
}

onMounted(() => {
  fetchProfile()
})
</script>
