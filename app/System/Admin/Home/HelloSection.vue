<script setup lang="ts">
import { useCustom } from '@duxweb/dvha-core'
import { DuxDashboardHelloBig } from '@duxweb/dvha-pro'
import { computed } from 'vue'

// 获取用户信息
const { data: userInfo } = useCustom({
  path: 'system/profile'
})

// 获取统计数据
const { data: statsData } = useCustom({
  path: 'system/home/stats'
})

// Hello组件顶部统计数据
const topStats = computed(() => [
  {
    label: '未读通知',
    icon: 'i-tabler:bell',
    value: statsData.value?.data?.notice?.unread || 0,
  },
  {
    label: '待办备忘',
    icon: 'i-tabler:clipboard-list',
    value: statsData.value?.data?.memo?.pending || 0,
  },
  {
    label: '系统公告',
    icon: 'i-tabler:speakerphone',
    value: statsData.value?.data?.bulletin?.total || 0,
  },
])
</script>

<template>
  <DuxDashboardHelloBig
    :title="`你好，${userInfo?.data?.nickname || '管理员'}！`"
    :data="topStats"
  />
</template>