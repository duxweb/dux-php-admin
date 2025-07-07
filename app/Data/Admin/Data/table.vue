<script setup lang="ts">
import type { JsonSchemaNode } from '@duxweb/dvha-core'
import type { TableColumn } from '@duxweb/dvha-naiveui'
import type { UseActionItem } from '@duxweb/dvha-pro'
import { useOne } from '@duxweb/dvha-core'
import { DuxTablePage, useAction, useTableColumn } from '@duxweb/dvha-pro'
import { computed, ref } from 'vue'
import { useRoute } from 'vue-router'

const route = useRoute()
const action = useAction()
const column = useTableColumn()

const pathList = route.path.split('/')
const configName = pathList[pathList.length - 1]

const { data: configData } = useOne({
  path: `data/config/${configName}/config`,
})

const tableType = computed(() => {
  return configData.value?.data?.table_type || 'pages'
})

const formType = computed(() => {
  return configData.value?.data?.form_type || 'modal'
})

const columnConfig = computed(() => {
  return configData.value?.data?.table_data?.data || []
})

const filterConfig = computed(() => {
  return configData.value?.data?.table_data?.filter || []
})

function actionRender(edit: boolean = false) {
  let data = {} as UseActionItem
  switch (formType.value) {
    case 'drawer':
      data = {
        type: 'drawer',
        component: () => import('./drawer.vue'),
        componentProps: {
          name: configName,
          config: configData.value?.data,
        },
      }
      break
    case 'page':
      data = {
        type: 'link',
        path: id => edit ? `${route.path}/edit/${id}` : `${route.path}/create`,
      }
      break
    case 'modal':
    default:
      data = {
        type: 'modal',
        component: () => import('./modal.vue'),
        componentProps: {
          name: configName,
          config: configData.value?.data,
        },
      }
      break
  }
  return data
}

const columns = computed<TableColumn[]>(() => {
  if (!columnConfig.value) {
    return []
  }

  const dynamicColumns: TableColumn[] = []

  // 添加 ID 列
  dynamicColumns.push({
    title: '#',
    key: 'id',
    width: 100,
  })

  // 根据配置生成动态列
  columnConfig.value.forEach((columnConfig: any) => {
    if (columnConfig.type === 'hidden')
      return

    const columnDef: TableColumn = {
      title: columnConfig.name,
      key: columnConfig.field,
      minWidth: columnConfig.width || 150,
    }

    // 使用 useTableColumn 的渲染方法
    switch (columnConfig.type) {
      case 'media':
        columnDef.render = column.renderMedia({
          title: columnConfig.setting?.title || columnConfig.field,
          desc: columnConfig.setting?.desc,
          image: columnConfig.setting?.image,
          avatar: columnConfig.setting?.avatar || false,
        })
        break
      case 'image':
        columnDef.render = column.renderImage({
          key: columnConfig.field,
          imageWidth: columnConfig.setting?.width,
          imageHeight: columnConfig.setting?.height,
        })
        break
      case 'copy':
        columnDef.render = column.renderCopy({
          key: columnConfig.field,
        })
        break
      case 'hidden':
        columnDef.render = column.renderHidden({
          key: columnConfig.field,
        })
        break
      case 'switch':
        columnDef.render = column.renderSwitch({
          key: columnConfig.field,
        })
        break
      case 'status':
        columnDef.render = column.renderColor({
          key: columnConfig.field,
          maps: Object.fromEntries(
            (columnConfig.setting?.config || []).map((item: any) => [
              item.type,
              {
                label: item.label,
                value: item.value,
              },
            ]),
          ),
        })
        break
      case 'color':
        columnDef.render = column.renderColor({
          key: columnConfig.field,
          maps: Object.fromEntries(
            (columnConfig.setting?.config || []).map((item: any) => [
              item.type,
              {
                label: item.label,
                value: item.value,
                icon: item.icon,
              },
            ]),
          ),
        })
        break
      case 'maps':
        columnDef.render = column.renderMap({
          maps: columnConfig.setting?.config,
        })
        break
    }

    dynamicColumns.push(columnDef)
  })

  // 添加操作列
  dynamicColumns.push({
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
          ...actionRender(true),
        },
        {
          label: '删除',
          type: 'delete',
          path: `data/data/${configName}`,
        },
      ],
    }),
  })

  return dynamicColumns
})

const actions = computed<UseActionItem[]>(() => {
  return [
    {
      label: '添加',
      color: 'primary',
      icon: 'i-tabler:plus',
      ...actionRender(),
    },
  ]
})

const filter = ref({})

// 根据配置生成动态筛选器
const filterSchema = computed<JsonSchemaNode[]>(() => {
  if (!filterConfig.value) {
    return []
  }

  const filterSchema = filterConfig.value.map((filterConfig: any) => {
    const baseSchema = {
      label: filterConfig.name,
      attrs: {
        'v-model:value': [filter.value, filterConfig.field],
      },
    }

    switch (filterConfig.type) {
      case 'text':
        return {
          ...baseSchema,
          tag: 'n-input',
          attrs: {
            ...baseSchema.attrs,
            placeholder: `请输入${filterConfig.name}`,
          },
        }

      case 'select':
      case 'async-select':
        return {
          ...baseSchema,
          tag: 'n-select',
          attrs: {
            ...baseSchema.attrs,
            placeholder: `请选择${filterConfig.name}`,
            options: filterConfig.setting?.options || [],
            clearable: true,
          },
        }

      case 'cascader':
        return {
          ...baseSchema,
          tag: 'n-cascader',
          attrs: {
            ...baseSchema.attrs,
            placeholder: `请选择${filterConfig.name}`,
            options: filterConfig.setting?.options || [],
            clearable: true,
          },
        }

      case 'daterange':
        return {
          ...baseSchema,
          tag: 'n-date-picker',
          attrs: {
            ...baseSchema.attrs,
            type: 'daterange',
            placeholder: `请选择${filterConfig.name}`,
            clearable: true,
          },
        }

      default:
        return {
          ...baseSchema,
          tag: 'n-input',
          attrs: {
            ...baseSchema.attrs,
            placeholder: `请输入${filterConfig.name}`,
          },
        }
    }
  })

  return filterSchema
})
</script>

<template>
  <DuxTablePage
    :key="tableType"
    :path="`data/data/${configName}`"
    :filter="filter"
    :filter-schema="filterSchema"
    :columns="columns"
    :actions="actions"
    :pagination="tableType === 'pages'"
  />
</template>

<style scoped></style>
