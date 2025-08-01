<script setup>
import { computed } from 'vue'
import PanelCard from './panelCard'
import Params from './params'

const props = defineProps({
  info: {
    type: Object,
    required: true,
  },
  type: {
    type: String,
    required: true,
  },
})

// 移除不必要的事件处理
// const emit = defineEmits(['heightChange'])
// function handleHeightChange() {
//   emit('heightChange')
// }

const color = computed(() => {
  switch (props.type) {
    case 'body':
      return 'primary'
    default:
      return 'primary'
  }
})

const title = computed(() => {
  switch (props.type) {
    case 'path':
      return 'Path 参数'
    case 'query':
      return 'Query 参数'
    case 'header':
      return 'Header 参数'
    case 'body':
      return 'Body 参数'
    default:
      return '参数'
  }
})

const icon = computed(() => {
  switch (props.type) {
    case 'path':
      return 'i-tabler:route'
    case 'query':
      return 'i-tabler:search'
    case 'header':
      return 'i-tabler:list-details'
    case 'body':
      return 'i-tabler:file-code'
    default:
      return 'i-tabler:settings'
  }
})

const hasParams = computed(() => {
  if (!props.info || !props.info.api)
    return false

  if (props.type === 'body') {
    return !!props.info.api?.requestBody
  }
  return props.info.api?.parameters?.some(p => p.in === props.type)
})

const paramCount = computed(() => {
  if (!props.info || !props.info.api)
    return 0

  if (props.type === 'body') {
    return props.info.api?.requestBody ? 1 : 0
  }
  return props.info.api?.parameters?.filter(p => p.in === props.type).length || 0
})
</script>

<template>
  <PanelCard
    v-if="hasParams" :title="title" :icon="icon" :color="color" :count="paramCount"
  >
    <Params :info="info" :type="type" />
  </PanelCard>
</template>
