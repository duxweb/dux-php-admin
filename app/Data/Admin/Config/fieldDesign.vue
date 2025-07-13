<script setup lang="ts">
import type { DuxDynamicDataColumn } from '@duxweb/dvha-pro'
import { useSelect } from '@duxweb/dvha-core'
import { DuxDynamicData, DuxFormItem, DuxPageForm } from '@duxweb/dvha-pro'
import { NCheckbox, NDynamicTags, NInput, NSelect } from 'naive-ui'
import { computed, ref } from 'vue'
import { useRoute } from 'vue-router'

const route = useRoute()

// 获取模型数据
const { options: modelsData } = useSelect({
  path: 'data/config/models',
  pagination: false,
})

const columns: DuxDynamicDataColumn[] = [
  {
    key: 'name',
    title: '名称',
    width: 100,
    schema: {
      tag: NInput,
      attrs: {
        'v-model:value': 'row.name',
      },
    },
  },
  {
    key: 'field',
    title: '字段名',
    width: 100,
    schema: {
      tag: NInput,
      attrs: {
        'v-model:value': 'row.field',
      },
    },
  },
  {
    key: 'type',
    title: '字段类型',
    width: 150,
    schema: {
      tag: NSelect,
      attrs: {
        'v-model:value': 'row.type',
        'options': [
          {
            label: '字符串',
            value: 'string',
          },
          {
            label: '整数',
            value: 'int',
          },
          {
            label: '小数',
            value: 'decimal',
          },
          {
            label: '布尔值',
            value: 'boolean',
          },
          {
            label: '日期时间',
            value: 'datetime',
          },
          {
            label: '日期',
            value: 'date',
          },
          {
            label: 'JSON',
            value: 'json',
          },
        ],
      },
    },
  },
  {
    key: 'default_value',
    title: '默认值',
    width: 120,
    schema: {
      tag: NInput,
      attrs: {
        'v-model:value': 'row.default_value',
      },
    },
  },
  {
    key: 'length',
    title: '长度',
    width: 120,
    schema: {
      tag: NInput,
      attrs: {
        'v-model:value': 'row.length',
        'type': 'number',
      },
    },
  },
  {
    key: 'sort',
    title: '排序',
    width: 80,
    schema: {
      tag: NCheckbox,
      attrs: {
        'v-model:checked': 'row.sort',
      },
    },
  },
  {
    key: 'filter',
    title: '筛选',
    width: 80,
    schema: {
      tag: NCheckbox,
      attrs: {
        'v-model:checked': 'row.filter',
      },
    },
  },
  {
    key: 'where',
    title: '筛选条件',
    width: 100,
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
            label: '大于等于',
            value: '>=',
          },
          {
            label: '小于等于',
            value: '<=',
          },
          {
            label: '包含 (范围)',
            value: 'like',
          },
        ],
      },
    },
  },

]

const hasColumns = computed<DuxDynamicDataColumn[]>(() => [
  {
    key: 'name',
    title: '关联名',
    width: 120,
    schema: {
      tag: NInput,
      attrs: {
        'v-model:value': 'row.name',
      },
    },
  },
  {
    key: 'model',
    title: '关联模型',
    width: 180,
    schema: {
      tag: NSelect,
      attrs: {
        'v-model:value': 'row.model',
        'options': modelsData.value?.map(item => ({
          label: item.label,
          value: item.value,
        })),
        'placeholder': '请选择模型',
        'clearable': true,
      },
    },
  },
  {
    key: 'type',
    title: '关联类型',
    width: 120,
    schema: {
      tag: NSelect,
      attrs: {
        'v-model:value': 'row.type',
        'options': [
          {
            label: '一对一(hasOne)',
            value: 'hasOne',
          },
          {
            label: '一对多(hasMany)',
            value: 'hasMany',
          },
          {
            label: '反向关联(belongsTo)',
            value: 'belongsTo',
          },
        ],
      },
    },
  },
  {
    key: 'foreign_key',
    title: '外键',
    width: 120,
    schema: row => ({
      tag: NInput,
      attrs: {
        'v-model:value': 'row.foreign_key',
        'disabled': !row.model,
        'placeholder': '选择外键字段',
        'clearable': true,
      },
    }),
  },
  {
    key: 'local_key',
    title: '本地键',
    width: 120,
    schema: row => ({
      tag: NInput,
      attrs: {
        'v-model:value': 'row.local_key',
        'disabled': !row.model,
        'placeholder': '选择本地键字段',
        'clearable': true,
      },
    }),
  },
  {
    key: 'fields',
    title: '包含字段',
    width: 120,
    schema: row => ({
      tag: NDynamicTags,
      attrs: {
        'v-model:value': 'row.fields',
        'disabled': !row.model,
        'placeholder': '选择包含字段',
        'clearable': true,
      },
    }),
  },
])

const model = ref({
  data: [] as Record<string, any>[],
  has: [] as Record<string, any>[],
})

const id = route.params.id as string
</script>

<template>
  <DuxPageForm :data="model" :path="`data/config/${id}/field`" action="edit" label-placement="top">
    <div class="container mx-auto flex flex-col gap-4">
      <DuxFormItem label="字段设置">
        <DuxDynamicData
          v-model:value="model.data"
          :columns="columns"
          @create="() => {
            const data = [...model.data]
            data.push({
              name: '',
              field: '',
              type: 'string',
              default_value: '',
              length: null,
              required: true,
              nullable: true,
              index: false,
              sort: false,
              search: true,
              setting: {},
            })

            model.data = data
          }"
        />
      </DuxFormItem>
      <DuxFormItem label="关联设置">
        <DuxDynamicData
          v-model:value="model.has"
          :columns="hasColumns"
          @create="() => {
            const items = [...model.has]
            items.push({
              name: '',
              model: undefined,
              type: 'hasOne',
              foreign_key: 'id',
              local_key: '',
              display_field: '',
              fields: [],
            })
            model.has = items
          }"
        />
      </DuxFormItem>
    </div>
  </DuxPageForm>
</template>
