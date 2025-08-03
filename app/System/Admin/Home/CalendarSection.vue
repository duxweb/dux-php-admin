<script setup lang="ts">
import { useCustom, useManage } from '@duxweb/dvha-core'
import { DuxCard } from '@duxweb/dvha-pro'
import { NCalendar } from 'naive-ui'
import { computed, ref } from 'vue'
import { useRouter } from 'vue-router'

// 获取我的备忘录
const { data: memosData } = useCustom({
  path: 'system/memo'
})

const router = useRouter()
const manage = useManage()

const calendarValue = ref(Date.now())

const calendarData = computed(() => {
  const memos = memosData.value?.data || []
  const memosByDate = {}
  
  // 按日期分组备忘录
  memos.forEach(memo => {
    const date = new Date(memo.created_at).toDateString()
    if (!memosByDate[date]) {
      memosByDate[date] = []
    }
    memosByDate[date].push(memo)
  })
  
  return memosByDate
})

const getMemosOnDate = (year: number, month: number, date: number) => {
  const targetDate = new Date(year, month - 1, date)
  const dateStr = targetDate.toDateString()
  return calendarData.value[dateStr] || []
}

const handleDateClick = (timestamp: number) => {
  const date = new Date(timestamp)
  const dateStr = date.toDateString()
  const dayMemos = calendarData.value[dateStr] || []
  
  if (dayMemos.length > 0) {
    router.push(manage.getRoutePath('system/memo'))
  }
}
</script>

<template>
  <DuxCard class="p-4">
    <NCalendar 
      v-model:value="calendarValue" 
      @update:value="handleDateClick"
    >
      <template #header>
        <div class="text-base font-normal">备忘日历</div>
      </template>
      <template #default="{ year, month, date }">
        <div class="h-full p-1 overflow-hidden">
          <div
            v-for="memo in getMemosOnDate(year, month, date).slice(0, 3)"
            :key="memo.id"
            class="text-xs text-primary mb-1 truncate leading-tight"
            :title="memo.title"
          >
            {{ memo.title }}
          </div>
          <div 
            v-if="getMemosOnDate(year, month, date).length > 3"
            class="text-xs text-gray-400"
          >
            ...
          </div>
        </div>
      </template>
    </NCalendar>
  </DuxCard>
</template>