<template>
  <AppLayout @create-post="showCreateModal = true">
    <button
      class="flex items-center gap-2 text-neutral-400 hover:text-white transition mb-5 text-sm"
      @click="router.back()"
    >
      <ArrowLeft class="w-4 h-4" />
      Back
    </button>

    <AppSpinner v-if="loading && !profile" />

    <AppEmptyState
      v-else-if="!profile"
      :icon="UserIcon"
      message="User not found"
    />

    <template v-else>
      <div class="bg-[#181818] border border-neutral-800 rounded-2xl p-6 mb-4">
        <div class="flex items-start justify-between gap-4">
          <div class="flex items-start gap-4">
            <AppAvatar :user-id="profile.id" :name="profile.name" :avatar="profile.avatar" size="lg" />
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

          <AppFollowButton
            v-if="authStore.user?.id !== profile.id"
            :following="profile.is_following"
            size="md"
            @toggle="handleFollow"
          />
        </div>
      </div>

      <AppEmptyState
        v-if="posts.length === 0 && !postsLoading"
        :icon="MessageCircle"
        message="No threads yet"
        padding="sm"
      />

      <div class="flex flex-col gap-3">
        <PostCard
          v-for="p in posts"
          :key="p.id"
          :post="p"
          @like="handleLike"
          @repost="handleRepost"
          @delete="handlePostDelete"
        />
        <AppLoadMore
          :loading="postsLoading"
          :show="postsCurrentPage < postsLastPage"
          @load="loadMorePosts"
        />
      </div>
    </template>

    <CreatePostModal v-model="showCreateModal" @posted="onPosted" />
  </AppLayout>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { ArrowLeft, User as UserIcon, MessageCircle } from 'lucide-vue-next'
import { useAuthStore } from '~/stores/auth'
import { useApi } from '~/composables/useApi'
import { useErrorToast } from '~/composables/useErrorToast'
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
const { showError } = useErrorToast()

const profile = ref<UserProfile | null>(null)
const posts = ref<Post[]>([])
const loading = ref(false)
const postsLoading = ref(false)
const postsCurrentPage = ref(1)
const postsLastPage = ref(1)
const showCreateModal = ref(false)

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
  const snapshot = { is_following: profile.value.is_following, followers_count: profile.value.followers_count }
  profile.value.is_following = !snapshot.is_following
  profile.value.followers_count = snapshot.is_following
    ? Math.max(0, snapshot.followers_count - 1)
    : snapshot.followers_count + 1
  try {
    const res = await apiPost<{ following: boolean }>(`/api/users/${profile.value.id}/follow`, {})
    profile.value.is_following = res.following
    profile.value.followers_count = res.following
      ? snapshot.followers_count + 1
      : Math.max(0, snapshot.followers_count - 1)
  } catch {
    profile.value.is_following = snapshot.is_following
    profile.value.followers_count = snapshot.followers_count
    showError('Não foi possível seguir o usuário. Tente novamente.')
  }
}

async function handleLike(id: number) {
  const p = posts.value.find((x) => x.id === id)
  if (!p) return
  const snapshot = { liked: p.liked, likes_count: p.likes_count }
  p.liked = !snapshot.liked
  p.likes_count = snapshot.liked ? snapshot.likes_count - 1 : snapshot.likes_count + 1
  try {
    const res = await apiPost<{ liked: boolean; likes_count: number }>(`/api/posts/${id}/like`, {})
    p.liked = res.liked
    p.likes_count = res.likes_count
  } catch {
    p.liked = snapshot.liked
    p.likes_count = snapshot.likes_count
    showError('Não foi possível curtir o post. Tente novamente.')
  }
}

async function handleRepost(id: number) {
  const p = posts.value.find((x) => x.id === id)
  if (!p) return
  const snapshot = { reposted: p.reposted, reposts_count: p.reposts_count }
  p.reposted = !snapshot.reposted
  p.reposts_count = snapshot.reposted ? snapshot.reposts_count - 1 : snapshot.reposts_count + 1
  try {
    const res = await apiPost<{ reposted: boolean; reposts_count: number }>(`/api/posts/${id}/repost`, {})
    p.reposted = res.reposted
    p.reposts_count = res.reposts_count
  } catch {
    p.reposted = snapshot.reposted
    p.reposts_count = snapshot.reposts_count
    showError('Não foi possível repostar. Tente novamente.')
  }
}

async function handlePostDelete(id: number) {
  try {
    await del(`/api/posts/${id}`)
    posts.value = posts.value.filter((p) => p.id !== id)
  } catch {
    showError('Não foi possível deletar o post. Tente novamente.')
  }
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
