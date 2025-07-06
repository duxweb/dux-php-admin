<script setup lang="ts">
import type { JsonSchemaNode } from '@duxweb/dvha-core'
import type { TableColumn } from '@duxweb/dvha-naiveui'
import type { UseActionItem } from '@duxweb/dvha-pro'
import { DuxTablePage, useAction } from '@duxweb/dvha-pro'
import { ref } from 'vue'

const action = useAction()

const columns: TableColumn[] = [
  {
    title: '#',
    key: 'id',
    width: 100,
  },
  {
    title: '编码',
    key: 'code',
    minWidth: 200,
  },
  {
    title: '名称',
    key: 'name',
    minWidth: 200,
  },
  {
    title: '层级',
    key: 'level',
    minWidth: 200,
  },
  {
    title: '操作',
    key: 'action',
    align: 'center',
    width: 120,
    render: action.renderTable({
      align: 'center',
      type: 'button',
      text: true,
      items: [
        {
          label: '删除',
          type: 'delete',
        },
      ],
    }),
  },
]

const actions: UseActionItem[] = [
  {
    label: '导入',
    color: 'primary',
    icon: 'i-tabler:plus',
    type: 'modal',
    component: () => import('./import.vue'),
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
    path="system/area" :filter="filter" :filter-schema="filterSchema" :columns="columns" :actions="actions"
  />
</template>

<style scoped></style>
