<template>
  <div class="min-h-screen bg-[#0d0d0d] font-sans text-white">
    <NuxtRouteAnnouncer />
    <NuxtPage />

    <Transition name="toast">
      <div
        v-if="toast"
        class="fixed bottom-6 right-6 z-50 flex items-start gap-3 rounded-xl bg-[#181818] border border-neutral-700 px-5 py-4 text-white shadow-2xl max-w-sm w-full"
      >
        <component :is="toastIcon" class="w-5 h-5 mt-0.5 shrink-0 text-neutral-400" />
        <div class="flex-1 min-w-0">
          <p class="font-semibold text-sm">{{ toast.actor_name }}</p>
          <p class="text-neutral-400 text-sm truncate">{{ toast.message }}</p>
        </div>
        <button
          class="text-neutral-500 hover:text-white transition shrink-0"
          @click="toast = null"
        >
          <X class="w-4 h-4" />
        </button>
      </div>
    </Transition>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { X, Heart, MessageCircle, UserPlus, AtSign, Bell } from 'lucide-vue-next'
import { useNotificationStore } from '~/stores/notifications'
import type { NotificationData } from '~/types'

const notifStore = useNotificationStore()
const toast = ref<NotificationData | null>(null)

const iconMap: Record<string, unknown> = {
  like: Heart,
  comment: MessageCircle,
  follow: UserPlus,
  mention: AtSign,
}

const toastIcon = computed(() => toast.value ? (iconMap[toast.value.type] ?? Bell) : Bell)

notifStore.$onAction(({ name, args }: { name: string; args: unknown[] }) => {
  if (name === 'pushRealtime') {
    toast.value = args[0] as NotificationData
    setTimeout(() => { toast.value = null }, 4000)
  }
})
</script>

<style>
body { margin: 0; background: #0d0d0d; }
.toast-enter-active, .toast-leave-active { transition: all .3s ease; }
.toast-enter-from, .toast-leave-to { opacity: 0; transform: translateY(1rem); }
</style>
