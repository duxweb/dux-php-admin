<script setup lang="ts">
import { useCustom } from '@duxweb/dvha-core'
import { DuxBlockEmpty, DuxCard } from '@duxweb/dvha-pro'
import { NButton, NTabs, NTab } from 'naive-ui'
import { computed, ref } from 'vue'

// 获取系统公告
const { data: bulletinsData } = useCustom({
  path: 'system/bulletin'
})

// 通知公告Tab值
const notificationTabValue = ref('')

// 获取公告类型图标和样式
function getBulletinTypeIcon(type: number) {
  const types = {
    1: { // 通知
      icon: 'i-tabler:bell',
      bgColor: 'bg-blue-100 dark:bg-blue-900/30',
      iconColor: 'text-blue-600 dark:text-blue-400'
    },
    2: { // 公告
      icon: 'i-tabler:speakerphone',
      bgColor: 'bg-green-100 dark:bg-green-900/30',
      iconColor: 'text-green-600 dark:text-green-400'
    },
    3: { // 活动
      icon: 'i-tabler:calendar-event',
      bgColor: 'bg-orange-100 dark:bg-orange-900/30',
      iconColor: 'text-orange-600 dark:text-orange-400'
    }
  }
  return types[type] || types[1]
}

// 根据tab过滤公告数据
const filteredBulletins = computed(() => {
  const bulletins = bulletinsData.value?.data || []
  
  switch (notificationTabValue.value) {
    case '1': // 通知
      return bulletins.filter((bulletin) => bulletin.type === 1)
    case '2': // 公告
      return bulletins.filter((bulletin) => bulletin.type === 2)
    case '3': // 活动
      return bulletins.filter((bulletin) => bulletin.type === 3)
    default: // 全部
      return bulletins
  }
})

</script>

<template>
  <DuxCard content-class="!p-4">
    <template #header>
      <div class="flex justify-between items-center">
        <div>
          <NTabs 
            v-model:value="notificationTabValue" 
            type="segment" 
            size="small"
            tab-class="px-4!"
          >
            <NTab name="" tab="全部"></NTab>
            <NTab name="1" tab="通知"></NTab>
            <NTab name="2" tab="公告"></NTab>
            <NTab name="3" tab="活动"></NTab>
          </NTabs>
        </div>
        <div>
          <NButton size="small" text to="/system/bulletin">
            更多
            <template #icon>
              <i class="i-tabler:arrow-right text-sm" />
            </template>
          </NButton>
        </div>
      </div>
    </template>
    
    <div v-if="!filteredBulletins.length" class="p-4 border border-muted rounded">
      <DuxBlockEmpty title="暂无公告" desc="暂无发布更多公告" />
    </div>
    
    <div v-else class="space-y-3">
      <div 
        v-for="bulletin in filteredBulletins.slice(0, 4)" 
        :key="bulletin.id"
        class="group bg-muted border border-muted rounded p-4 hover:border-accented hover:shadow transition-all cursor-pointer"
      >
        <div class="flex items-start gap-3">
          <div 
            class="w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0"
            :class="[getBulletinTypeIcon(bulletin.type).bgColor]"
          >
            <i 
              class="text-lg"
              :class="[getBulletinTypeIcon(bulletin.type).icon, getBulletinTypeIcon(bulletin.type).iconColor]" 
            />
          </div>
          <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2 mb-2">
              <span class="text-xs text-muted">
                {{ bulletin.created_at }}
              </span>
            </div>
            <h3 class="text-sm font-medium line-clamp-2 mb-1">
              {{ bulletin.title }}
            </h3>
            <p v-if="bulletin.content" class="text-sm text-muted line-clamp-2">
              {{ bulletin.content }}
            </p>
          </div>
          <i class="i-tabler:chevron-right text-muted transition-colors" />
        </div>
      </div>
    </div>
  </DuxCard>
</template>