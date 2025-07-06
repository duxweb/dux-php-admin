<script setup lang="ts">
import type { JsonSchemaNode } from '@duxweb/dvha-core'
import type { TableColumn } from '@duxweb/dvha-naiveui'
import { DuxTablePage, useAction, useTableColumn } from '@duxweb/dvha-pro'
import { NTag, NTooltip } from 'naive-ui'
import { h, ref } from 'vue'

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
    title: '用户',
    key: 'nickname',
    width: 150,
    render: column.renderMedia({
      title: 'nickname',
      image: 'avatar',
      avatar: true,
      desc: 'username',
    }),
  },
  {
    title: '请求地址',
    key: 'request_method',
    width: 220,
    render: (rowData: Record<string, any>) => {
      let type: 'default' | 'error' | 'primary' | 'info' | 'success' | 'warning' = 'info'
      switch (rowData.request_method) {
        case 'GET':
          type = 'info'
          break
        case 'POST':
          type = 'success'
          break
        case 'PUT':
          type = 'info'
          break
        case 'PATCH':
          type = 'warning'
          break
        case 'DELETE':
          type = 'error'
          break
      }

      return h('div', {
        class: 'flex flex-col gap-1 leading-4',
      }, [
        h('div', {
          class: 'flex gap-2 items-center',
        }, [
          h(NTag, { type, size: 'small' }, () => rowData.request_method),
          h(NTooltip, { }, {
            default: () => rowData.request_url,
            trigger: () => h('div', { class: 'truncate' }, rowData.request_url),
          }),
        ]),
      ])
    },
  },
  {
    title: '路由名 | 响应时间',
    key: 'route_title',
    width: 250,
    render: column.renderMedia({
      title: 'route_name',
      desc: (rowData: Record<string, any>) => `${rowData.request_time}s`,
    }),
  },
  {
    title: '请求设备',
    key: 'client_ip',
    width: 150,
    render: column.renderMedia({
      title: 'client_ip',
      desc: 'client_device',
    }),
  },
  {
    title: '操作时间',
    key: 'time',
    width: 200,
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
          label: '查看',
          type: 'modal',
          component: () => import('./view.vue'),
        },
      ],
    }),
  },
]

const filter = ref({})

const filterSchema: JsonSchemaNode[] = [
  {
    tag: 'dux-select',
    name: 'user_id',
    attrs: {
      'clearable': true,
      'path': 'system/user',
      'label-field': 'nickname',
      'desc-field': 'username',
      'avatar-field': 'avatar',
      'value-field': 'id',
      'placeholder': '请选择用户',
      'v-model:value': [filter.value, 'user_id'],
    },
  },
  {
    tag: 'n-select',
    name: 'method',
    label: '请求方法',
    attrs: {
      'clearable': true,
      'placeholder': '请选择方法',
      'block': true,
      'options': [
        {
          label: 'POST',
          value: 'POST',
        },
        {
          label: 'PUT',
          value: 'PUT',
        },
        {
          label: 'PATCH',
          value: 'PATCH',
        },
        {
          label: 'DELETE',
          value: 'DELETE',
        },
      ],
      'v-model:value': [filter.value, 'method'],
    },
  },
  {
    tag: 'n-date-picker',
    name: 'date',
    label: '操作时间',
    attrs: {
      'clearable': true,
      'type': 'daterange',
      'placeholder': '请选择时间',
      'v-model:value': [filter.value, 'date'],
    },
  },
]
</script>

<template>
  <DuxTablePage
    path="system/operate" :filter="filter" :filter-schema="filterSchema" :columns="columns"
  />
</template>

<style scoped></style>
