<script setup lang="ts">
import type { TableColumn } from '@duxweb/dvha-naiveui'
import { DuxDrawerPage, DuxTable, useAction, useModal } from '@duxweb/dvha-pro'
import { NButton } from 'naive-ui'
import { defineProps } from 'vue'

const props = defineProps({
  id: Number,
  type: String,
})

const modal = useModal()
const action = useAction()

const columns: TableColumn[] = [
  {
    title: '#',
    key: 'id',
    width: 100,
  },
  {
    title: '名称',
    key: 'title',
    width: 100,
  },
  {
    title: '键值',
    key: 'value_show',
    width: 100,
  },
  {
    title: '备注',
    key: 'remark',
    width: 100,
  },
  {
    title: '操作',
    key: 'action',
    align: 'center',
    width: 150,
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
          componentProps: { dictionaryID: props.id, type: props.type },
        },
        {
          label: '删除',
          type: 'delete',
          path: 'system/dictionaryData',
        },
      ],
    }),
  },
]
</script>

<template>
  <DuxDrawerPage :scrollbar="false">
    <template #header>
      <NButton
        quaternary
        size="small"
        type="primary"
        @click="modal.show({
          title: '添加',
          component: () => import('./form.vue'),
          componentProps: { dictionaryID: props.id, type: props.type },
        })"
      >
        添加
      </NButton>
    </template>

    <div class="h-full p-4">
      <DuxTable
        class="h-full" flex-height :path="`system/dictionaryData/${props.id}`" :pagination="false" :columns="columns"
      />
    </div>
  </DuxDrawerPage>
</template>

<style scoped></style>
