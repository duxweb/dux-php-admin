<script setup>
import { DuxCard } from '@duxweb/dvha-pro'
import { NBadge } from 'naive-ui'
import { computed, ref, nextTick, watch } from 'vue'
import Params from './params'
import PanelCard from './panelCard'

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

const emit = defineEmits(['height-change'])

function handleHeightChange() {
  emit('height-change')
}

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
  if (!props.info || !props.info.api) return false

  if (props.type === 'body') {
    return !!props.info.api?.requestBody
  }
  return props.info.api?.parameters?.some(p => p.in === props.type)
})

const paramCount = computed(() => {
  if (!props.info || !props.info.api) return 0

  if (props.type === 'body') {
    return props.info.api?.requestBody ? 1 : 0
  }
  return props.info.api?.parameters?.filter(p => p.in === props.type).length || 0
})

</script>

<template>
  <PanelCard v-if="hasParams" :title="title" :icon="icon" :color="color" :count="paramCount" @height-change="handleHeightChange">
    <Params :info="info" :type="type" @height-change="handleHeightChange" />
 </PanelCard>

</template>
