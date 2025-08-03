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
  },
  {
    title: '类型',
    key: 'type',
    width: 100,
    render: column.renderStatus({
      key: 'type',
      maps: {
        info: { label: '通知', value: 1 },
        warning: { label: '公告', value: 2 },
        success: { label: '活动', value: 3 },
      },
    }),
  },
  {
    title: '发布目标',
    key: 'target_type',
    width: 120,
    render: column.renderStatus({
      key: 'target_type',
      maps: {
        info: { label: '全部用户', value: 1 },
        success: { label: '指定部门', value: 2 },
        warning: { label: '指定角色', value: 3 },
      },
    }),
  },
  {
    title: '置顶',
    key: 'is_top',
    width: 100,
    render: column.renderSwitch({
      key: 'is_top',
    }),
  },
  {
    title: '状态',
    key: 'status',
    width: 100,
    render: column.renderSwitch({
      key: 'status',
    }),
  },
  {
    title: '发布时间',
    key: 'publish_at',
    width: 200,
  },
  {
    title: '操作',
    key: 'action',
    align: 'center',
    width: 160,
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
          path: 'system/bulletin',
        },
      ],
    }),
  },
]

const actions: UseActionItem[] = [
  {
    label: '发布',
    color: 'primary',
    icon: 'i-tabler:plus',
    type: 'modal',
    component: () => import('./form.vue'),
  },
]

const filter = ref<Record<string, any>>({})

const filterSchema: JsonSchemaNode[] = [
  {
    tag: 'n-input',
    name: 'keyword',
    attrs: {
      'placeholder': '请输入标题或内容关键词',
      'v-model:value': [filter.value, 'keyword'],
    },
  },
  {
    tag: 'n-select',
    name: 'type',
    attrs: {
      'placeholder': '请选择公告类型',
      'v-model:value': [filter.value, 'type'],
      'clearable': true,
      'options': [
        { label: '通知', value: 1 },
        { label: '公告', value: 2 },
        { label: '活动', value: 3 },
      ],
    },
  },
]

const tabs = [
  {
    label: '全部',
    value: '',
  },
  {
    label: '已发布',
    value: '1',
  },
  {
    label: '已下线',
    value: '2',
  },
]
</script>

<template>
  <DuxTablePage
    path="system/bulletin"
    :tabs="tabs"
    :filter="filter"
    :filter-schema="filterSchema"
    :columns="columns"
    :actions="actions"
  />
</template>
