<script setup lang="ts">
import type { JsonSchemaNode } from '@duxweb/dvha-core'
import type { TableColumn } from '@duxweb/dvha-naiveui'
import type { UseActionItem } from '@duxweb/dvha-pro'
import { DuxTablePage, DuxTreeFilter, useAction, useTableColumn } from '@duxweb/dvha-pro'
import { ref, watch } from 'vue'

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
    title: '用户名',
    key: 'username',
    minWidth: 200,
  },
  {
    title: '角色',
    key: 'role_name',
    minWidth: 200,
  },
  {
    title: '部门',
    key: 'dept_name',
    minWidth: 200,
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
        },
        {
          label: '删除',
          type: 'delete',
          path: 'system/user',
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

const filter = ref<Record<string, any>>({})

const filterSchema: JsonSchemaNode[] = [
  {
    tag: 'n-input',
    name: 'keyword',
    attrs: {
      'placeholder': '请输入用户名',
      'v-model:value': [filter.value, 'keyword'],
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

const deptID = ref<string[]>([])

watch(deptID, (v) => {
  filter.value.dept_id = v[0]
}, { immediate: true })
</script>

<template>
  <DuxTablePage
    path="system/user" :tabs="tabs" :filter="filter" :filter-schema="filterSchema" :columns="columns"
    :actions="actions"
  >
    <template #sideLeft>
      <div class="lg:w-50">
        <DuxTreeFilter v-model:value="deptID" :bordered="false" path="system/dept" label-field="name" key-field="id" />
      </div>
    </template>
  </DuxTablePage>
</template>
