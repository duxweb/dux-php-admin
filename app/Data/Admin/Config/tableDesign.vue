<script setup lang="ts">
import type { DuxDynamicDataColumn } from '@duxweb/dvha-pro'
import { DuxDynamicData, DuxFormItem, DuxPageForm, useModal } from '@duxweb/dvha-pro'
import { NButton, NInput, NSelect } from 'naive-ui'
import { ref } from 'vue'
import { useRoute } from 'vue-router'

const modal = useModal()
const route = useRoute()

const filters: DuxDynamicDataColumn[] = [
  {
    key: 'name',
    title: '名称',
    schema: {
      tag: NInput,
      attrs: {
        'v-model:value': 'row.name',
      },
    },
  },
  {
    key: 'field',
    title: '字段',
    schema: {
      tag: NInput,
      attrs: {
        'v-model:value': 'row.field',
      },
    },
  },
  {
    key: 'where',
    title: '条件',
    width: 150,
    schema: {
      tag: NSelect,
      attrs: {
        'v-model:value': 'row.where',
        'options': [
          {
            label: '等于',
            value: '=',
          },
          {
            label: '不等于',
            value: '!=',
          },
          {
            label: '大于',
            value: '>',
          },
          {
            label: '小于',
            value: '<',
          },
          {
            label: '包含',
            value: 'like',
          },
        ],
      },
    },
  },
  {
    key: 'type',
    title: '类型',
    width: 150,
    schema: {
      tag: NSelect,
      attrs: {
        'v-model:value': 'row.type',
        'options': [
          {
            label: '文本',
            value: 'text',
          },
          {
            label: '选择器',
            value: 'select',
          },
          {
            label: '异步选择',
            value: 'async-select',
          },
          {
            label: '级联选择',
            value: 'cascader',
          },
          {
            label: '时间范围',
            value: 'daterange',
          },
        ],
      },
    },
  },

  {
    key: 'setting',
    title: '设置',
    width: 100,
    schema: (row) => {
      return [
        {
          tag: NButton,
          attrs: {
            'disabled': row.type === 'text' || row.type === 'daterange',
            '@click': () => {
              modal.show({
                title: '字段设置',
                component: () => import('./table/filter.vue'),
                componentProps: {
                  row,
                },
              }).then((v) => {
                row.setting = v
              }).catch(() => {})
            },
          },
          slots: {
            default: () => ({
              tag: 'div',
              attrs: {
                class: 'i-tabler:settings',
              },
            }),
          },
        },
      ]
    },
  },
]

const columns: DuxDynamicDataColumn[] = [
  {
    key: 'name',
    title: '名称',
    schema: {
      tag: NInput,
      attrs: {
        'v-model:value': 'row.name',
      },
    },
  },
  {
    key: 'field',
    title: '字段',
    schema: {
      tag: NInput,
      attrs: {
        'v-model:value': 'row.field',
      },
    },
  },
  {
    key: 'type',
    title: '类型',
    width: 150,
    schema: {
      tag: NSelect,
      attrs: {
        'v-model:value': 'row.type',
        'options': [
          {
            label: '文本',
            value: 'text',
          },
          {
            label: '图文',
            value: 'media',
          },
          {
            label: '图片',
            value: 'image',
          },
          {
            label: '隐藏',
            value: 'hidden',
          },
          {
            label: '复制',
            value: 'copy',
          },
          {
            label: '状态',
            value: 'status',
          },
          {
            label: '开关',
            value: 'switch',
          },
          {
            label: '颜色',
            value: 'color',
          },
          {
            label: '映射',
            value: 'maps',
          },
        ],
      },
    },
  },
  {
    key: 'setting',
    title: '设置',
    width: 100,
    schema: (row) => {
      return [
        {
          tag: NButton,
          attrs: {
            'disabled': row.type === 'text' || row.type === 'copy' || row.type === 'switch',
            '@click': () => {
              modal.show({
                title: '字段设置',
                component: () => import('./table/column.vue'),
                componentProps: {
                  row,
                },
              }).then((v) => {
                row.setting = v
              }).catch(() => {})
            },
          },
          slots: {
            default: () => ({
              tag: 'div',
              attrs: {
                class: 'i-tabler:settings',
              },
            }),
          },
        },
      ]
    },
  },
]

const model = ref({
  data: [] as Record<string, any>[],
  filter: [] as Record<string, any>[],
})

const id = route.params.id as string
</script>

<template>
  <DuxPageForm :data="model" :path="`data/config/${id}/table`" action="edit" label-placement="top">
    <div class="container mx-auto flex flex-col gap-4">
      <DuxFormItem label="筛选配置">
        <DuxDynamicData
          v-model:value="model.filter"
          :columns="filters"
          @create="() => {
            const data = [...model.filter]
            data.push({
              name: '',
              field: '',
              type: 'text',
              setting: {},
            })

            model.filter = data
          }"
        />
      </DuxFormItem>
      <DuxFormItem label="表格配置">
        <DuxDynamicData
          v-model:value="model.data"
          :columns="columns"
          @create="() => {
            const data = [...model.data]
            data.push({
              name: '',
              field: '',
              type: 'text',
              setting: {},
            })

            model.data = data
          }"
        />
      </DuxFormItem>
    </div>
  </DuxPageForm>
</template>
