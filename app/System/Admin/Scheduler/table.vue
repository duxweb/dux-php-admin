<script setup lang="ts">
import type { JsonSchemaNode } from '@duxweb/dvha-core'
import type { TableColumn } from '@duxweb/dvha-naiveui'
import type { UseActionItem } from '@duxweb/dvha-pro'
import { DuxTablePage, useAction, useTableColumn } from '@duxweb/dvha-pro'
import { ref } from 'vue'

const action = useAction()
const column = useTableColumn()

const columns: TableColumn[] = [
  { title: '#', key: 'id', width: 80 },
  {
    title: '任务',
    key: 'name',
    minWidth: 220,
    render: column.renderMedia({
      title: 'name',
      desc: row => row?.selected_task || '-',
    }),
  },
  { title: 'Cron', key: 'cron', minWidth: 160 },
  { title: '描述', key: 'desc', minWidth: 220 },
  { title: '排序', key: 'sort', width: 100 },
  {
    title: '状态',
    key: 'status',
    width: 120,
    render: column.renderSwitch({ key: 'status' }),
  },
  {
    title: '操作',
    key: 'action',
    width: 160,
    fixed: 'right',
    align: 'center',
    render: action.renderTable({
      align: 'center',
      type: 'button',
      text: true,
      items: [
        {
          label: '编辑',
          type: 'modal',
          component: () => import('./form.vue'),
          width: 720,
        },
        { label: '删除', type: 'delete', path: 'system/scheduler' },
      ],
    }),
  },
]

const actions: UseActionItem[] = [
  {
    label: '新增任务',
    color: 'primary',
    icon: 'i-tabler:plus',
    type: 'modal',
    component: () => import('./form.vue'),
    width: 720,
  },
]

const filter = ref<Record<string, any>>({})
const filterSchema: JsonSchemaNode[] = [
  {
    tag: 'n-input',
    name: 'keyword',
    attrs: {
      'placeholder': '任务名称/任务索引/描述',
      'clearable': true,
      'v-model:value': [filter.value, 'keyword'],
    },
  },
  {
    tag: 'n-select',
    name: 'status',
    attrs: {
      'placeholder': '状态',
      'options': [
        { label: '启用', value: 1 },
        { label: '停用', value: 2 },
      ],
      'clearable': true,
      'v-model:value': [filter.value, 'status'],
    },
  },
]
</script>

<template>
  <DuxTablePage path="system/scheduler" :filter="filter" :filter-schema="filterSchema" :columns="columns" :actions="actions" />
</template>

<style scoped></style>
