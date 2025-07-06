<script setup lang="ts">
import type { JsonSchemaNode } from '@duxweb/dvha-core'
import type { TableColumn } from '@duxweb/dvha-naiveui'
import type { UseActionItem } from '@duxweb/dvha-pro'
import { DuxTablePage, useAction, useTableColumn } from '@duxweb/dvha-pro'
import { NAlert, NTag } from 'naive-ui'

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
    title: '数据集',
    key: 'name',
    minWidth: 200,
  },
  {
    title: '标识',
    key: 'label',
    minWidth: 150,
  },
  {
    title: 'API 鉴权',
    key: 'api_sign',
    minWidth: 100,
    render: column.renderStatus({
      key: 'api_sign',
      maps: {
        success: {
          label: '鉴权',
          value: true,
        },
        info: {
          label: '非鉴权',
          value: false,
        },
      },
    }),
  },
  {
    title: '列表类型',
    key: 'table_type',
    minWidth: 100,
    render: column.renderStatus({
      key: 'table_type',
      maps: {
        warning: {
          label: '树形',
          value: 'tree',
        },
        success: {
          label: '分页',
          value: 'pages',
        },
        info: {
          label: '列表',
          value: 'list',
        },
      },
    }),
  },
  {
    title: '表单类型',
    key: 'form_type',
    minWidth: 100,
    render: column.renderStatus({
      key: 'form_type',
      maps: {
        warning: {
          label: '页面',
          value: 'page',
        },
        success: {
          label: '抽屉',
          value: 'drawer',
        },
        info: {
          label: '弹窗',
          value: 'modal',
        },
      },
    }),
  },
  {
    title: '操作',
    key: 'action',
    align: 'center',
    width: 220,
    fixed: 'right',
    render: action.renderTable({
      align: 'center',
      type: 'button',
      text: true,
      items: [
        {
          label: '表格',
          type: 'link',
          path: id => `data/config/${id}/table`,
        },
        {
          label: '表单',
          type: 'link',
          path: id => `data/config/${id}/form`,
        },
        {
          label: '菜单',
          type: 'request',
          content: '确定重新生产数据菜单？',
          path: id => `data/config/${id}/menu`,
        },
        {
          label: '编辑',
          type: 'modal',
          component: () => import('./form.vue'),
        },
        {
          label: '删除',
          type: 'delete',
          path: 'data/config',
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
    path="data/config" :filter="filter" :filter-schema="filterSchema" :columns="columns" :actions="actions"
  >
  <template #header>
    <NAlert type="info">开启 API 鉴权后需要从 <NTag>/api/data/:label</NTag> 获取数据，关闭后从 <NTag>/data/:label</NTag> 获取数据</NAlert>
  </template>
  </DuxTablePage>
</template>

<style scoped></style>
