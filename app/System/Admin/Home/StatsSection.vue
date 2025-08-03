<script setup lang="ts">
import { useCustom } from '@duxweb/dvha-core'
import { computed } from 'vue'

// 获取统计数据
const { data: statsData } = useCustom({
  path: 'system/home/stats'
})

// 统计卡片数据
const statsCards = computed(() => [
  {
    title: '总备忘录',
    value: statsData.value?.data?.memo?.total || 0,
    icon: 'i-tabler:notes',
    bgColor: 'bg-blue/10',
    iconColor: 'text-blue',
  },
  {
    title: '今日操作',
    value: statsData.value?.data?.operate?.today || 0,
    icon: 'i-tabler:activity',
    bgColor: 'bg-green/10',
    iconColor: 'text-green',
  },
  {
    title: '登录记录',
    value: statsData.value?.data?.login?.total || 0,
    icon: 'i-tabler:login',
    bgColor: 'bg-purple/10',
    iconColor: 'text-purple',
  },
  {
    title: '最近操作',
    value: statsData.value?.data?.operate?.recent || 0,
    icon: 'i-tabler:clock',
    bgColor: 'bg-orange/10',
    iconColor: 'text-orange',
  }
])
</script>

<template>
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
    <DuxCard 
      v-for="card in statsCards" 
      :key="card.title"
      class="p-4 relative"
    >
      <div class="flex items-center justify-between">
        <div class="flex-1">
          <p class="text-sm text-muted mb-1">
            {{ card.title }}
          </p>
          <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">
            {{ card.value }}
          </p>
        </div>
        <div 
          class="flex items-center justify-center w-12 h-12 rounded-lg transition-transform group-hover:scale-110"
          :class="[card.bgColor]"
        >
          <i :class="[card.icon, card.iconColor, 'text-xl']" />
        </div>
      </div>
    </DuxCard>
  </div>
</template>