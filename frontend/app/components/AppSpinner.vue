<template>
  <div class="flex justify-center" :class="py">
    <Loader2 class="animate-spin text-neutral-500" :class="iconSize" />
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { Loader2 } from 'lucide-vue-next'

const props = defineProps<{
  size?: 'sm' | 'md' | 'lg'
  padding?: 'sm' | 'md' | 'lg'
}>()

const iconSize = computed(() => ({
  sm: 'w-4 h-4',
  md: 'w-6 h-6',
  lg: 'w-8 h-8',
}[props.size ?? 'md']))

const py = computed(() => ({
  sm: 'py-4',
  md: 'py-10',
  lg: 'py-16',
}[props.padding ?? 'md']))
</script>
