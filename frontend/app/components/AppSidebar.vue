<template>
  <aside class="hidden md:flex flex-col fixed left-0 top-0 h-full w-64 bg-[#0d0d0d] border-r border-neutral-800 px-4 py-6 z-20">
    <div class="mb-8 px-3">
      <span class="text-2xl font-bold text-white tracking-tight">Threads</span>
    </div>

    <nav class="flex flex-col gap-1 flex-1">
      <NuxtLink
        to="/"
        class="flex items-center gap-3 px-3 py-3 rounded-xl text-neutral-400 hover:text-white hover:bg-neutral-900 transition group"
        active-class="text-white bg-neutral-900"
      >
        <House class="w-5 h-5" />
        <span class="text-sm font-medium">Home</span>
      </NuxtLink>

      <NuxtLink
        to="/search"
        class="flex items-center gap-3 px-3 py-3 rounded-xl text-neutral-400 hover:text-white hover:bg-neutral-900 transition"
        active-class="text-white bg-neutral-900"
      >
        <Search class="w-5 h-5" />
        <span class="text-sm font-medium">Search</span>
      </NuxtLink>

      <NuxtLink
        to="/notifications"
        class="flex items-center gap-3 px-3 py-3 rounded-xl text-neutral-400 hover:text-white hover:bg-neutral-900 transition relative"
        active-class="text-white bg-neutral-900"
      >
        <div class="relative">
          <Bell class="w-5 h-5" />
          <span
            v-if="notifStore.unreadCount > 0"
            class="absolute -top-1.5 -right-1.5 bg-red-500 text-white text-[10px] font-bold rounded-full min-w-[16px] h-4 flex items-center justify-center px-0.5"
          >
            {{ notifStore.unreadCount > 9 ? '9+' : notifStore.unreadCount }}
          </span>
        </div>
        <span class="text-sm font-medium">Notifications</span>
      </NuxtLink>

      <ClientOnly>
        <NuxtLink
          v-if="authStore.user"
          :to="`/profile/${authStore.user.id}`"
          class="flex items-center gap-3 px-3 py-3 rounded-xl text-neutral-400 hover:text-white hover:bg-neutral-900 transition"
          active-class="text-white bg-neutral-900"
        >
          <User class="w-5 h-5" />
          <span class="text-sm font-medium">Profile</span>
        </NuxtLink>
      </ClientOnly>
    </nav>

    <div class="flex flex-col gap-2 mt-4">
      <button
        class="flex items-center gap-3 px-3 py-3 rounded-xl text-neutral-400 hover:text-white hover:bg-neutral-900 transition w-full text-left"
        @click="$emit('createPost')"
      >
        <Pencil class="w-5 h-5" />
        <span class="text-sm font-medium">New Thread</span>
      </button>

      <NuxtLink
        to="/settings"
        class="flex items-center gap-3 px-3 py-3 rounded-xl text-neutral-400 hover:text-white hover:bg-neutral-900 transition"
        active-class="text-white bg-neutral-900"
      >
        <Settings class="w-5 h-5" />
        <span class="text-sm font-medium">Settings</span>
      </NuxtLink>

      <button
        class="flex items-center gap-3 px-3 py-3 rounded-xl text-neutral-400 hover:text-red-400 hover:bg-neutral-900 transition w-full text-left"
        @click="doLogout"
      >
        <LogOut class="w-5 h-5" />
        <span class="text-sm font-medium">Logout</span>
      </button>
    </div>
  </aside>

  <nav class="md:hidden fixed bottom-0 left-0 right-0 bg-[#0d0d0d] border-t border-neutral-800 z-20 flex items-center justify-around px-2 py-2">
    <NuxtLink
      to="/"
      class="flex flex-col items-center gap-0.5 px-4 py-2 rounded-xl text-neutral-500 hover:text-white transition"
      active-class="text-white"
    >
      <House class="w-6 h-6" />
    </NuxtLink>

    <NuxtLink
      to="/search"
      class="flex flex-col items-center gap-0.5 px-4 py-2 rounded-xl text-neutral-500 hover:text-white transition"
      active-class="text-white"
    >
      <Search class="w-6 h-6" />
    </NuxtLink>

    <button
      class="flex flex-col items-center gap-0.5 px-4 py-2 rounded-xl text-neutral-500 hover:text-white transition"
      @click="$emit('createPost')"
    >
      <Pencil class="w-6 h-6" />
    </button>

    <NuxtLink
      to="/notifications"
      class="flex flex-col items-center gap-0.5 px-4 py-2 rounded-xl text-neutral-500 hover:text-white transition relative"
      active-class="text-white"
    >
      <div class="relative">
        <Bell class="w-6 h-6" />
        <span
          v-if="notifStore.unreadCount > 0"
          class="absolute -top-1.5 -right-1.5 bg-red-500 text-white text-[10px] font-bold rounded-full min-w-[16px] h-4 flex items-center justify-center px-0.5"
        >
          {{ notifStore.unreadCount > 9 ? '9+' : notifStore.unreadCount }}
        </span>
      </div>
    </NuxtLink>

    <ClientOnly>
      <NuxtLink
        v-if="authStore.user"
        :to="`/profile/${authStore.user.id}`"
        class="flex flex-col items-center gap-0.5 px-4 py-2 rounded-xl text-neutral-500 hover:text-white transition"
        active-class="text-white"
      >
        <User class="w-6 h-6" />
      </NuxtLink>
    </ClientOnly>
  </nav>
</template>

<script setup lang="ts">
import { House, Search, Bell, User, Pencil, LogOut, Settings } from 'lucide-vue-next'
import { useAuthStore } from '~/stores/auth'
import { useNotificationStore } from '~/stores/notifications'
import { useApi } from '~/composables/useApi'

defineEmits<{ createPost: [] }>()

const authStore = useAuthStore()
const notifStore = useNotificationStore()
const { get } = useApi()
const { $pusher } = useNuxtApp()
const router = useRouter()

async function doLogout() {
  await get('/api/logout').catch(() => {})
  $pusher.disconnect()
  authStore.logout()
  router.push('/login')
}
</script>
