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
    title: '昵称',
    key: 'nickname',
    minWidth: 200,
    render: column.renderMedia({
      title: 'nickname',
      image: 'avatar',
      avatar: true,
    }),
  },
  {
    title: '手机号',
    key: 'tel',
    minWidth: 180,
    render: (row: any) => {
      return `${row.tel_code || ''}${row.tel || ''}`
    },
  },
  {
    title: '邮箱',
    key: 'email',
    minWidth: 180,
  },
  {
    title: '等级',
    key: 'level_name',
    minWidth: 120,
  },
  {
    title: '最后登录',
    key: 'login_at',
    minWidth: 150,
  },
  {
    title: '注册时间',
    key: 'created_at',
    minWidth: 150,
  },
  {
    title: '状态',
    key: 'status',
    minWidth: 200,
    render: column.renderSwitch({
      key: 'status',
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
          width: 650,
        },
        {
          label: '删除',
          type: 'delete',
          path: 'member/user',
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
    width: 650,
  },
]

const filter = ref<Record<string, any>>({})

const filterSchema: JsonSchemaNode[] = [
  {
    tag: 'n-input',
    name: 'keyword',
    attrs: {
      'placeholder': '请输入昵称、手机号或邮箱',
      'v-model:value': [filter.value, 'keyword'],
    },
  },
  {
    tag: 'dux-select',
    name: 'level_id',
    attrs: {
      'placeholder': '请选择等级',
      'path': 'member/level',
      'label-field': 'name',
      'value-field': 'id',
      'v-model:value': [filter.value, 'level_id'],
    },
  },
]

const tabs = [
  {
    label: '全部',
    value: '0',
  },
  {
    label: '启用',
    value: '1',
  },
  {
    label: '禁用',
    value: '2',
  },
]
</script>

<template>
  <DuxTablePage
    path="member/user" :tabs="tabs" :filter="filter" :filter-schema="filterSchema" :columns="columns"
    :actions="actions"
  />
</template>

<style scoped></style>
