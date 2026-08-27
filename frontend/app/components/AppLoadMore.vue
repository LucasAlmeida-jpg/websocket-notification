<template>
  <div
    v-if="show"
    :class="variant === 'flat'
      ? 'px-5 py-4 border-t border-neutral-800'
      : 'flex justify-center pt-2'"
  >
    <button
      :disabled="loading"
      :class="variant === 'flat'
        ? 'w-full text-sm text-neutral-400 hover:text-white transition flex items-center justify-center gap-2'
        : 'text-sm text-neutral-400 hover:text-white transition flex items-center gap-2 px-4 py-2 rounded-full border border-neutral-700 hover:border-neutral-500'"
      @click="$emit('load')"
    >
      <Loader2 v-if="loading" class="w-4 h-4 animate-spin" />
      <span>Load more</span>
    </button>
  </div>
</template>

<script setup lang="ts">
import { Loader2 } from 'lucide-vue-next'

defineProps<{
  loading: boolean
  show: boolean
  variant?: 'default' | 'flat'
}>()

defineEmits<{ load: [] }>()
</script>
