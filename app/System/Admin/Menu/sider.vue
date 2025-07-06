<script setup lang="ts">
import type { TreeMenu } from '@duxweb/dvha-pro'
import { useCustomMutation, useInvalidate } from '@duxweb/dvha-core'
import { DuxTreeFilter } from '@duxweb/dvha-pro'
import { NButton, NTag, useMessage } from 'naive-ui'
import { h, ref, watch } from 'vue'

const props = defineProps({
  value: {
    type: Number,
    default: undefined,
  },
  onUpdateValue: {
    type: Function,
  },
})

const sideValue = ref<number[]>(props.value ? [props.value] : [])

watch(sideValue, (value) => {
  props?.onUpdateValue?.(value[0])
}, {
  deep: true,
})

const message = useMessage()

const typeName: Record<string, string> = {
  directory: '目录',
  menu: '菜单',
  button: '按钮',
  iframe: '网页',
  link: '外链',
}

const typeColor: Record<string, string> = {
  directory: 'info',
  menu: 'primary',
  button: 'warning',
  iframe: 'error',
  link: 'error',
}

function renderSuffix({ option }) {
  return h('div', {
    class: 'flex items-center gap-2',
  }, [
    option.hidden
      ? h(NTag, {
          type: 'warning',
          size: 'small',
        }, { default: () => '隐藏' })
      : null,
    h(NTag, {
      type: typeColor[option.type] as any,
      size: 'small',
    }, { default: () => typeName[option.type] }),
  ])
}

const { invalidate } = useInvalidate()
const { mutateAsync } = useCustomMutation()

const menus: TreeMenu[] = [
  {
    label: '删除',
    value: 'del',
    icon: 'i-tabler:trash',
    onSelect: (item) => {
      mutateAsync({
        path: `system/menu/${item?.id}`,
        method: 'delete',
      }).then(() => {
        invalidate('system/menu')
      }).catch((err) => {
        message.error(err.message)
      })
    },
  },
]
</script>

<template>
  <DuxTreeFilter
    v-model:value="sideValue"
    :menus="menus"
    :show-line="true"
    :render-suffix="renderSuffix"
    path="system/menu"
    sort-path="system/menu/sort"
    label-field="label"
    key-field="id"
    icon-field="icon"
    :draggable="true"
  >
    <template #tools>
      <NButton
        secondary
        type="primary"
        @click="() => {
          sideValue = []
        }"
      >
        <template #icon>
          <div class="i-tabler:plus" />
        </template>
      </NButton>
    </template>
  </DuxTreeFilter>
</template>
