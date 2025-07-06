<script setup>
import { DuxCard } from '@duxweb/dvha-pro'
import { NBadge } from 'naive-ui'
import { computed, ref, nextTick, watch } from 'vue'
import Params from './params'

const props = defineProps({
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
const contentRef = ref(null)
const contentHeight = ref('auto')
const isUpdating = ref(false)

const updateHeight = async () => {
  if (!contentRef.value || isUpdating.value) return

  isUpdating.value = true

  try {
    if (isOpen.value) {
      contentHeight.value = '0px'
      await nextTick()
      const height = contentRef.value.scrollHeight
      requestAnimationFrame(() => {
        contentHeight.value = `${height}px`

        // 动画完成后设置为 auto
        setTimeout(() => {
          if (isOpen.value) {
            contentHeight.value = 'auto'
          }
        }, 300)
      })
    } else {
      const height = contentRef.value.scrollHeight
      contentHeight.value = `${height}px`
      await nextTick()
      requestAnimationFrame(() => {
        contentHeight.value = '0px'
      })
    }
  } finally {
    setTimeout(() => {
      isUpdating.value = false
    }, 300)
  }
}

// 防抖处理子组件高度变化
let heightChangeTimer = null
function handleHeightChange() {
  if (!isOpen.value) return

  clearTimeout(heightChangeTimer)
  heightChangeTimer = setTimeout(() => {
    if (contentRef.value && isOpen.value) {
      const height = contentRef.value.scrollHeight
      contentHeight.value = `${height}px`

      // 短暂延迟后设置为 auto
      setTimeout(() => {
        if (isOpen.value) {
          contentHeight.value = 'auto'
        }
      }, 100)
    }
  }, 100)
}

watch(isOpen, updateHeight, { immediate: true })

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
          <div class="flex items-center justify-center text-white/80 size-5 text-sm leading-none rounded-full bg-primary">
            {{ count }}
          </div>
        </div>
        <div class="flex items-center gap-2">
          <div
            class="p-1 bg-white/50 rounded-full transition-all duration-300"
            :class="[`text-${color}`, isOpen ? 'rotate-180' : '']"
          >
            <div class="size-4 i-tabler:chevron-down" />
          </div>
        </div>
      </div>
    </template>

    <div
      class="overflow-hidden transition-all duration-300 ease-in-out"
      :style="{ height: contentHeight }"
    >
      <div ref="contentRef" @height-change="handleHeightChange">
        <slot />
      </div>
    </div>
  </DuxCard>
</template>
