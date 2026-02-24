<script setup lang="ts">
import { JsonSchemaNode, useList } from '@duxweb/dvha-core'
import { TableColumn } from '@duxweb/dvha-naiveui'
import { useAction, UseActionItem, useModal } from '@duxweb/dvha-pro'
import { useQuery } from '@tanstack/vue-query'
import { useMessage } from 'naive-ui'
import { computed, ref, watch } from 'vue'

const message = useMessage()
const action = useAction()
const modal = useModal()


const { data: locale } = useList({
  path: 'system/locale',
  pagination: false,
})

const columns = computed(() => {
  const cols: TableColumn[] = [
    {
      title: '标识',
      key: 'name',
      width: 100,
    },
  ]

  const localeCols = locale.value?.data?.map?.(item => ({
    title: item.title,
    key: item.name,
    minWidth: 100,
    renderType: 'render',
    render: (row) => {
      return row.data[item.name]
    },
  })) || []

  cols.push(...localeCols)

  cols.push({
    title: '操作',
    key: 'action',
    fixed: 'right',
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
          componentProps: { locale: locale.value?.data || [] },
        },
        {
          label: '删除',
          type: 'delete',
          path: 'system/locale',
        },
      ],
    }),
  })

  return cols
})

const actions: UseActionItem[] = [
  {
    label: '添加',
    color: 'primary',
    type: 'callback',
    icon: 'i-tabler:plus',
    callback: () => {
      modal.show({
        title: '添加',
        component: () => import('./form.vue'),
        componentProps: { locale: locale.value?.data || [] },
      })
    },
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
  <DuxTablePage path="system/localeData" :filter="filter" :filter-schema="filterSchema" :columns="columns" :actions="actions" />
</template>

<style scoped>

</style>
