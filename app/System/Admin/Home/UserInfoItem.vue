<script setup lang="ts">
interface Props {
  label: string
}

defineProps<Props>()
</script>

<template>
  <div class="flex items-center justify-between">
    <span class="text-sm text-gray-600 dark:text-gray-400">{{ label }}</span>
    <slot></slot>
  </div>
</template>