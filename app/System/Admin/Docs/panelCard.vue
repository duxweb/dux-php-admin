<script setup>
import { DuxCard } from '@duxweb/dvha-pro'
import { ref } from 'vue'

defineProps({
  title: {
    type: String,
    required: true,
  },
  icon: {
    type: String,
    required: true,
  },
  color: {
    type: String,
    required: true,
  },
  count: {
    type: Number,
    required: true,
  },
})

const isOpen = ref(true)
</script>

<template>
  <DuxCard
    padding="0"
    size="none"
    header-size="none"
    :shadow="false"
    :class="`border border-${color}/20 rounded-lg overflow-hidden`"
    content-size="none"
  >
    <template #header>
      <div
        class="px-4 py-3 flex items-center justify-between cursor-pointer"
        :class="`bg-${color}/20`"
        @click="isOpen = !isOpen"
      >
        <div class="flex items-center gap-2">
          <div class="size-5" :class="[icon, `text-${color}`]" />
          <h4 class="font-medium" :class="`text-${color}`">
            {{ title }}
          </h4>
          <div
            class="flex items-center justify-center text-white/80 size-5 text-sm leading-none rounded-full bg-primary"
          >
            {{ count }}
          </div>
        </div>
        <div class="flex items-center gap-2">
          <div
            class="p-1 bg-white/50 rounded-full"
            :class="[`text-${color}`, isOpen ? 'rotate-180' : '']"
          >
            <div class="size-4 i-tabler:chevron-down" />
          </div>
        </div>
      </div>
    </template>

    <div v-if="isOpen">
      <slot />
    </div>
  </DuxCard>
</template>
