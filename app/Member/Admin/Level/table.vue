<script setup lang="ts">
import type { JsonSchemaNode } from '@duxweb/dvha-core'
import type { TableColumn } from '@duxweb/dvha-naiveui'
import type { UseActionItem } from '@duxweb/dvha-pro'
import { DuxTablePage, useAction, useTableColumn } from '@duxweb/dvha-pro'
import { ref } from 'vue'

const action = useAction()
const column = useTableColumn()

const columns: TableColumn[] = [
  {
    title: '#',
    key: 'id',
    width: 100,
  },
  {
    title: '等级名称',
    key: 'name',
    minWidth: 200,
  },
  {
    title: '成长值',
    key: 'growth',
    minWidth: 150,
  },
  {
    title: '类型',
    key: 'type',
    minWidth: 100,
    render: column.renderStatus({
      key: 'type',
      maps: {
        success: {
          label: '招募',
          value: 1,
        },
        warning: {
          label: '普通',
          value: 0,
        },
      },
    }),
  },
  {
    title: '默认等级',
    key: 'default',
    minWidth: 100,
    render: column.renderStatus({
      key: 'default',
      maps: {
        success: {
          label: '默认',
          value: 1,
        },
        warning: {
          label: '否',
          value: 0,
        },
      },
    }),
  },
  {
    title: '操作',
    key: 'action',
    align: 'center',
    width: 120,
    fixed: 'right',
    render: action.renderTable({
      align: 'center',
      type: 'button',
      text: true,
      items: [
        {
          label: '编辑',
          type: 'modal',
          component: () => import('./form.vue'),
        },
        {
          label: '删除',
          type: 'delete',
          path: 'member/level',
        },
      ],
    }),
  },
]

const actions: UseActionItem[] = [
  {
    label: '添加',
    color: 'primary',
    icon: 'i-tabler:plus',
    type: 'modal',
    component: () => import('./form.vue'),
  },
]

const filter = ref({})

const filterSchema: JsonSchemaNode[] = [
  {
    tag: 'n-input',
    name: 'keyword',
    attrs: {
      'placeholder': '请输入等级名称',
      'v-model:value': [filter.value, 'keyword'],
    },
  },
]
</script>

<template>
  <DuxTablePage
    path="member/level" :filter="filter" :filter-schema="filterSchema" :columns="columns" :actions="actions"
  />
</template>

<style scoped></style>
