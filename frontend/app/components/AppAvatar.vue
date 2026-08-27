<template>
  <img
    v-if="avatar"
    :src="avatar"
    :class="sizeClass"
    class="rounded-full object-cover shrink-0"
  />
  <div
    v-else
    :class="[sizeClass, textClass, avatarColor(userId), 'rounded-full flex items-center justify-center font-bold text-white shrink-0']"
  >
    {{ initials(name) }}
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useAvatar } from '~/composables/useAvatar'

const props = defineProps<{
  userId: number
  name: string
  avatar: string | null
  size?: 'sm' | 'md' | 'lg' | 'xl'
}>()

const { avatarColor, initials } = useAvatar()

const sizeClass = computed(() => ({
  sm: 'w-7 h-7',
  md: 'w-10 h-10',
  lg: 'w-16 h-16',
  xl: 'w-20 h-20',
}[props.size ?? 'md']))

const textClass = computed(() => ({
  sm: 'text-xs',
  md: 'text-sm',
  lg: 'text-xl',
  xl: 'text-2xl',
}[props.size ?? 'md']))
</script>
