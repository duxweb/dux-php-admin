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
    title: '名称',
    key: 'name',
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
          label: '编辑',
          type: 'modal',
          component: () => import('./form.vue'),
        },
        {
          label: '删除',
          type: 'delete',
          path: 'system/dept',
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
      'placeholder': '请输入部门名称',
      'v-model:value': [filter.value, 'keyword'],
    },
  },
]
</script>

<template>
  <DuxTablePage
    path="system/dept" :filter="filter" :filter-schema="filterSchema" :columns="columns" :actions="actions"
  />
</template>

<style scoped></style>
