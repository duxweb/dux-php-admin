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
    title: '用户信息',
    key: 'user',
    minWidth: 200,
    render: column.renderMedia({
      title: 'user.nickname',
      image: 'user.avatar',
      desc: 'user.tel',
      avatar: true,
    }),
  },
  {
    title: '反馈内容',
    key: 'content',
    minWidth: 300,
  },
  {
    title: '反馈图片',
    key: 'images',
    minWidth: 150,
    render: column.renderImage({
      key: 'images',
    }),
  },
  {
    title: '处理状态',
    key: 'status',
    minWidth: 120,
    render: column.renderSwitch({
      key: 'status',
    }),
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
          path: 'member/feedback',
        },
      ],
    }),
  },
]

const actions: UseActionItem[] = []

const filter = ref({})

const filterSchema: JsonSchemaNode[] = [
  {
    tag: 'n-input',
    name: 'keyword',
    attrs: {
      'placeholder': '请输入用户昵称或反馈内容',
      'v-model:value': [filter.value, 'keyword'],
    },
  },
]
</script>

<template>
  <DuxTablePage
    path="member/feedback" :filter="filter" :filter-schema="filterSchema" :columns="columns" :actions="actions"
  />
</template>

<style scoped></style>
