<script setup lang="ts">
import { useCustomMutation } from '@duxweb/dvha-core'
import { DuxCardPage, useDrawer } from '@duxweb/dvha-pro'
import { NButton, NDropdown, useMessage } from 'naive-ui'

const path = 'system/queue'
const drawer = useDrawer()
const message = useMessage()
const { mutateAsync, isLoading } = useCustomMutation()

const showLogs = (name?: string) => {
  if (!name) {
    return
  }
  drawer.show({
    title: `队列记录 - ${name}`,
    width: 860,
    component: () => import('./logDrawer.vue'),
    componentProps: {
      work: name,
    },
  })
}

const testQueue = async (name?: string) => {
  if (!name) {
    return
  }
  try {
    await mutateAsync({
      path: 'system/queue/test',
      method: 'POST',
      payload: {
        work: name,
        priority: 'medium',
      },
    })
    message.success('已投递测试任务')
  }
  catch (error) {
    message.error((error as Error)?.message || '投递失败')
  }
}

const actionOptions = [
  {
    label: '测试任务',
    key: 'test',
  },
  {
    label: '队列记录',
    key: 'log',
  },
]

const onSelectAction = (key: string | number, row: Record<string, any>) => {
  if (key === 'test') {
    testQueue(row?.name)
    return
  }
  if (key === 'log') {
    showLogs(row?.name)
  }
}
</script>

<template>
  <DuxCardPage :path="path" :pagination="false">
    <template #default="{ item }">
      <div class="flex flex-col gap-4 rounded border p-4 overflow-hidden transition-all bg-container bg-gradient-to-l from-primary/5 via-transparent to-transparent border-muted hover:border-primary hover:shadow">
        <div class="flex items-start justify-between">
          <div class="min-w-0">
            <div class="text-base font-medium truncate">
              {{ item?.name || '-' }}
            </div>
            <div class="text-xs text-muted mt-1">
              并发 {{ item?.num ?? 0 }}
            </div>
          </div>
          <div class="flex items-center gap-2">
            <NDropdown
              trigger="click"
              placement="bottom-end"
              :options="actionOptions"
              @select="key => onSelectAction(key, item)"
            >
              <NButton size="small" quaternary circle :loading="isLoading">
                <template #icon>
                  <i class="i-tabler:dots-vertical" />
                </template>
              </NButton>
            </NDropdown>
          </div>
        </div>
        <div class="flex items-center gap-2 text-xs text-muted">
          <span class="px-2 py-0.5 rounded bg-primary/10 text-primary">高 {{ item?.weight_high ?? 0 }}</span>
          <span class="px-2 py-0.5 rounded bg-warning/10 text-warning">中 {{ item?.weight_medium ?? 0 }}</span>
          <span class="px-2 py-0.5 rounded bg-default/10 text-muted">低 {{ item?.weight_low ?? 0 }}</span>
        </div>
        <div class="grid grid-cols-2 gap-3 text-sm">
          <div class="flex items-center justify-between rounded bg-elevated px-3 py-2">
            <span class="text-muted">待处理</span>
            <span class="font-medium">{{ item?.pending ?? 0 }}</span>
          </div>
          <div class="flex items-center justify-between rounded bg-elevated px-3 py-2">
            <span class="text-muted">处理中</span>
            <span class="font-medium">{{ item?.running ?? 0 }}</span>
          </div>
          <div class="flex items-center justify-between rounded bg-elevated px-3 py-2">
            <span class="text-muted">已执行</span>
            <span class="font-medium">{{ item?.executed ?? 0 }}</span>
          </div>
          <div class="flex items-center justify-between rounded bg-elevated px-3 py-2">
            <span class="text-muted">失败</span>
            <span class="font-medium">{{ item?.failed ?? 0 }}</span>
          </div>
        </div>
      </div>
    </template>
  </DuxCardPage>
</template>

<style scoped></style>
