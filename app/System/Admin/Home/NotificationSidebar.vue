<script setup lang="ts">
import { useCustom, useManage } from '@duxweb/dvha-core'
import { DuxCard, DuxWidgetConnect } from '@duxweb/dvha-pro'
import { NButton, NEmpty } from 'naive-ui'
import { useRouter } from 'vue-router'

const { data: noticesData } = useCustom({
  path: 'system/notice'
})

// 获取系统信息
const { data: statsData } = useCustom({
  path: 'system/home/stats'
})

const router = useRouter()
const manage = useManage()

function handleNoticeClick(notice: any) {
  if (notice.url?.startsWith('http')) {
    window.open(notice.url, '_blank')
    return
  }
  router.push(manage.getRoutePath(notice.url || 'system/notice'))
}

// 打开官网
function openWebsite() {
  const website = statsData.value?.data?.info?.website
  if (website) {
    window.open(website, '_blank')
  }
}
</script>

<template>
  <!-- 我的通知 -->
  <DuxCard title="我的通知" content-class="!p-4">
    <template #headerExtra>
      <NButton size="small" text :to="manage.getRoutePath('system/notice')">
        更多
        <template #icon>
          <i class="i-tabler-arrow-right text-sm" />
        </template>
      </NButton>
    </template>

    <div v-if="!noticesData?.data?.length" class="py-8">
      <NEmpty description="暂无通知" />
    </div>
    
    <div v-else class="space-y-3">
      <div 
        v-for="notice in noticesData?.data?.slice(0, 5)" 
        :key="notice.id"
        class="group bg-default dark:bg-muted border border-muted rounded-lg p-3 transition-all duration-200 hover:shadow-md cursor-pointer"
        @click="handleNoticeClick(notice)"
      >
        <div class="flex items-start gap-3">
          <div class="flex-1 min-w-0">
            <!-- 标题和状态 -->
            <div class="flex items-center gap-2 mb-2">
              <span class="text-xs text-muted">
                {{ notice.created_at_formatted }}
              </span>
              <div v-if="!notice.read" class="w-2 h-2 bg-primary rounded-full" />
            </div>
            
            <!-- 标题 -->
            <h4 
              class="text-sm font-medium line-clamp-2 mb-1 group-hover:text-primary transition-colors"
              :class="{
                'text-muted': notice.read
              }"
            >
              {{ notice.title }}
            </h4>
            
            <!-- 内容 -->
            <p 
              v-if="notice.content" 
              class="text-xs line-clamp-2"
              :class="{
                'text-toned': !notice.read,
                'text-muted': notice.read
              }"
            >
              {{ notice.content }}
            </p>
          </div>
          
          <!-- 右侧箭头图标 -->
          <i 
            v-if="notice.url?.startsWith('http')" 
            class="i-tabler-external-link text-muted text-sm flex-shrink-0 group-hover:text-primary transition-colors" 
          />
          <i 
            v-else
            class="i-tabler-chevron-right text-muted text-sm flex-shrink-0 group-hover:text-primary transition-colors" 
          />
        </div>
      </div>
    </div>
  </DuxCard>

  <!-- DuxWidgetConnect 广告 -->
  <DuxWidgetConnect title="系统升级服务" desc="获取更多功能和技术支持">
    <NButton type="primary" size="small" @click="openWebsite">
      了解更多
    </NButton>
  </DuxWidgetConnect>
</template>