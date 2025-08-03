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
    tree: true,
  },
  {
    title: '名称',
    key: 'title',
    minWidth: 200,
  },
  {
    title: '键名',
    key: 'key',
    minWidth: 200,
  },
  {
    title: '类型',
    key: 'type_name',
    minWidth: 200,
  },
  {
    title: '备注',
    key: 'remark',
    minWidth: 200,
  },
  {
    title: '公开',
    key: 'public',
    minWidth: 200,
    render: column.renderStatus({
      key: 'public',
      maps: {
        success: {
          label: '公开',
          value: true,
        },
        info: {
          label: '私有',
          value: false,
        },
      },
    }),
  },
  {
    title: '时间',
    key: 'time',
    minWidth: 200,
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
          path: 'system/setting',
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
      'placeholder': '请输入名称',
      'v-model:value': [filter.value, 'keyword'],
    },
  },
]
</script>

<template>
  <DuxTablePage
    path="system/setting" :filter="filter" :filter-schema="filterSchema" :columns="columns" :actions="actions"
  />
</template>

<style scoped></style>
