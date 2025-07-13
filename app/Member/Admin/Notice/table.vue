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
    title: '标题',
    key: 'title',
    minWidth: 200,
    render: column.renderMedia({
      title: 'title',
      image: 'image',
      desc: 'desc',
    }),
  },
  {
    title: '链接',
    key: 'url',
    minWidth: 200,
  },
  {
    title: '创建时间',
    key: 'created_at',
    minWidth: 180,
  },
  {
    title: '操作',
    key: 'action',
    align: 'center',
    width: 100,
    fixed: 'right',
    render: action.renderTable({
      align: 'center',
      type: 'button',
      text: true,
      items: [
        {
          label: '删除',
          type: 'delete',
          path: 'member/notice',
        },
      ],
    }),
  },
]

const actions: UseActionItem[] = [
  {
    label: '推送公告',
    color: 'primary',
    icon: 'i-tabler:send',
    type: 'modal',
    component: () => import('./push.vue'),
  },
]

const filter = ref({})

const filterSchema: JsonSchemaNode[] = [
  {
    tag: 'n-input',
    name: 'keyword',
    attrs: {
      'placeholder': '请输入标题',
      'v-model:value': [filter.value, 'keyword'],
    },
  },
]
</script>

<template>
  <DuxTablePage
    path="member/notice" :filter="filter" :filter-schema="filterSchema" :columns="columns" :actions="actions"
  />
</template>

<style scoped></style>
