<template>
  <div class="min-h-screen bg-[#0d0d0d]">
    <AppSidebar @create-post="$emit('createPost')" />
    <main class="md:ml-64 flex justify-center px-4 py-6 pb-24 md:pb-6">
      <div class="w-full max-w-xl">
        <slot />
      </div>
    </main>
  </div>
</template>

<script setup lang="ts">
defineEmits<{ createPost: [] }>()
</script>
