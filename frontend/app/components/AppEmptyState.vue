<template>
  <div class="flex flex-col items-center justify-center gap-3 text-neutral-500" :class="paddingClass">
    <component :is="icon" class="w-10 h-10" />
    <p class="text-sm">{{ message }}</p>
  </div>
</template>

<script setup lang="ts">
import { computed, type Component } from 'vue'

const props = defineProps<{
  icon: Component
  message: string
  padding?: 'sm' | 'md'
}>()

const paddingClass = computed(() => props.padding === 'sm' ? 'py-10' : 'py-16')
</script>
