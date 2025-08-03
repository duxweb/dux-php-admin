<script setup lang="ts">
import { useCustom } from '@duxweb/dvha-core'
import { DuxCard, DuxChart } from '@duxweb/dvha-pro'
import { NButton } from 'naive-ui'
import { computed } from 'vue'

// 获取操作统计数据
const { data: chartData, refetch } = useCustom({
  path: 'system/home/operate'
})

// 操作统计图表配置
const operateChartData = computed(() => chartData.value?.data)

// 刷新数据
const handleRefresh = () => {
  refetch()
}
</script>

<template>
  <DuxCard title="操作统计" content-class="!p-4">
    <template #headerExtra>
      <NButton size="small" text @click="handleRefresh">
        刷新
        <template #icon>
          <i class="i-tabler:refresh text-sm" />
        </template>
      </NButton>
    </template>
    <DuxChart 
      v-if="operateChartData"
      type="line"
      :option="operateChartData" 
      class="h-80"
      height="320px"
    />
    <div v-else class="h-80 flex items-center justify-center text-muted">
      加载中...
    </div>
  </DuxCard>
</template>