<script setup lang="ts">
import type { TableColumn } from '@duxweb/dvha-naiveui'
import { DuxDrawerPage, DuxTable, useTableColumn } from '@duxweb/dvha-pro'
import { computed, defineProps } from 'vue'

const props = defineProps({
  work: {
    type: String,
    required: true,
  },
})

const column = useTableColumn()
const filter = computed(() => ({
  work: props.work,
}))

const columns: TableColumn[] = [
  {
    title: '任务',
    key: 'job_class',
    minWidth: 260,
    render: column.renderMedia({
      title: 'job_class',
      desc: row => row?.job_method || '-',
    }),
  },
  {
    title: '任务ID/时间',
    key: 'job_id',
    minWidth: 220,
    render: column.renderMedia({
      title: 'job_id',
      desc: row => row?.created_at || '-',
    }),
  },
  {
    title: '状态',
    key: 'event',
    width: 100,
    render: column.renderStatus({
      key: 'event',
      maps: {
        info: { label: '入队', value: 'enqueue' },
        warning: { label: '执行', value: 'execute' },
        success: { label: '成功', value: 'done' },
        error: { label: '失败', value: 'failed' },
      },
    }),
  },
  { title: '耗时(ms)', key: 'duration_ms', width: 120 },
  { title: '错误', key: 'error_message', minWidth: 240 },
]
</script>

<template>
  <DuxDrawerPage :scrollbar="false">
    <div class="h-full p-4">
      <DuxTable class="h-full" flex-height path="system/queue/log" :filter="filter" :columns="columns" />
    </div>
  </DuxDrawerPage>
</template>

<style scoped></style>
