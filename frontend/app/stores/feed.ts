import { defineStore } from 'pinia'
import { ref } from 'vue'
import type { User } from '~/stores/auth'

export interface Post {
  id: number
  body: string
  likes_count: number
  replies_count: number
  reposts_count: number
  liked: boolean
  reposted: boolean
  created_at: string
  user: User
}

export const useFeedStore = defineStore('feed', () => {
  const items = ref<Post[]>([])
  const loading = ref(false)
  const currentPage = ref(1)
  const lastPage = ref(1)

  function setItems(newItems: Post[], page: number, last: number) {
    if (page === 1) {
      items.value = newItems
    } else {
      items.value.push(...newItems)
    }
    currentPage.value = page
    lastPage.value = last
  }

  function prependPost(post: Post) {
    items.value.unshift(post)
  }

  function removePost(id: number) {
    items.value = items.value.filter((p: Post) => p.id !== id)
  }

  function toggleLike(id: number, liked: boolean, likesCount: number) {
    const post = items.value.find((p: Post) => p.id === id)
    if (post) {
      post.liked = liked
      post.likes_count = likesCount
    }
  }

  function toggleRepost(id: number, reposted: boolean, repostsCount: number) {
    const post = items.value.find((p: Post) => p.id === id)
    if (post) {
      post.reposted = reposted
      post.reposts_count = repostsCount
    }
  }

  return { items, loading, currentPage, lastPage, setItems, prependPost, removePost, toggleLike, toggleRepost }
})
