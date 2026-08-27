<template>
  <button
    class="shrink-0 rounded-full font-semibold transition"
    :class="[
      size === 'md' ? 'px-5 py-2 text-sm' : 'px-4 py-1.5 text-xs',
      following
        ? 'border border-neutral-600 text-white hover:bg-neutral-800'
        : 'bg-white text-black hover:bg-neutral-200',
    ]"
    :disabled="disabled"
    @click="$emit('toggle')"
  >
    {{ following ? 'Following' : 'Follow' }}
  </button>
</template>

<script setup lang="ts">
defineProps<{
  following: boolean
  disabled?: boolean
  size?: 'sm' | 'md'
}>()

defineEmits<{ toggle: [] }>()
</script>
