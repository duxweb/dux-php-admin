<script setup lang="ts">
import type { JsonSchemaNode } from '@duxweb/dvha-core'
import type { TableColumn } from '@duxweb/dvha-naiveui'
import { DuxTablePage, useTableColumn } from '@duxweb/dvha-pro'
import { ref } from 'vue'

const column = useTableColumn()

const columns: TableColumn[] = [
  {
    title: '#',
    key: 'id',
    width: 100,
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
    title: '客户端',
    key: 'browser',
    width: 220,
    render: column.renderMedia({
      title: 'browser',
      desc: 'platform',
    }),
  },
  {
    title: 'IP',
    key: 'ip',
    width: 150,
  },
  {
    title: '状态',
    key: 'status',
    width: 100,
    render: column.renderStatus({
      key: 'status',
      maps: {
        success: {
          label: '成功',
          value: 1,
        },
        error: {
          label: '失败',
          value: 0,
        },
      },
    }),
  },
  {
    title: '登录时间',
    key: 'time',
    width: 200,
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
    tag: 'n-date-picker',
    name: 'date',
    label: '时间',
    attrs: {
      'clearable': true,
      'type': 'daterange',
      'placeholder': '请选择时间',
      'v-model:value': [filter.value, 'date'],
    },
  },
]

const tabs = [
  {
    label: '全部',
    value: '0',
  },
  {
    label: '成功',
    value: '1',
  },
  {
    label: '失败',
    value: '2',
  },
]
</script>

<template>
  <DuxTablePage
    path="system/login" :tabs="tabs" :filter="filter" :filter-schema="filterSchema" :columns="columns"
  />
</template>

<style scoped></style>
