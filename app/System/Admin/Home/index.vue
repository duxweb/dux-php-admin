<script setup lang="ts">
import { DuxPage } from '@duxweb/dvha-pro'
import BulletinSection from './BulletinSection.vue'
import CalendarSection from './CalendarSection.vue'
import ChartSection from './ChartSection.vue'
import HelloSection from './HelloSection.vue'
import NotificationSidebar from './NotificationSidebar.vue'
import StatsSection from './StatsSection.vue'
import UserInfoSidebar from './UserInfoSidebar.vue'
</script>

<template>
  <DuxPage :card="false">
    <div class="flex gap-2 flex-col lg:flex-row">
      <!-- 主栏 -->
      <div class="flex-1 flex flex-col gap-2 lg:w-1">
        <!-- Hello组件 -->
        <HelloSection />

        <!-- 统计卡片 -->
        <StatsSection />

        <!-- 图表 -->
        <ChartSection />

        <!-- 第三行：分2列 -->
        <div class="grid grid-cols-1 xl:grid-cols-2 gap-2">
          <!-- 备忘录日历 -->
          <CalendarSection />

          <!-- 系统公告 -->
          <BulletinSection />
        </div>
      </div>

      <!-- 侧边栏 -->
      <div class="flex-none flex flex-col gap-2 lg:w-80">
        <!-- 用户信息 -->
        <UserInfoSidebar />

        <!-- 通知中心和广告 -->
        <NotificationSidebar />
      </div>
    </div>
  </DuxPage>
</template>
