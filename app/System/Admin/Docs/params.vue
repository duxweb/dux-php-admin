<script setup>
import { useClipboard } from '@vueuse/core'
import { NBadge, NButton, NCode, NInput, NTag, useMessage } from 'naive-ui'
import { computed, ref } from 'vue'
import ParamItem from './paramItem'
import ParamBody from './paramBody'

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

const { copy } = useClipboard()
const message = useMessage()

const parameterValues = ref({})

const parameters = computed(() => {
  if (!props.info || !props.info.api) return []

  if (props.type === 'body') {
    return props.info.api?.requestBody ? [props.info.api.requestBody] : []
  }
  return props.info.api?.parameters?.filter(item => item.in === props.type) || []
})

function handleHeightChange() {
  emit('height-change')
}

function getTypeColor(type) {
  const colors = {
    string: 'success',
    integer: 'info',
    number: 'warning',
    boolean: 'error',
    array: 'primary',
    object: 'default',
  }
  return colors[type] || 'default'
}

function getTypeIcon(type) {
  const icons = {
    string: 'i-tabler:abc',
    integer: 'i-tabler:123',
    number: 'i-tabler:decimal',
    boolean: 'i-tabler:toggle-left',
    array: 'i-tabler:list',
    object: 'i-tabler:braces',
  }
  return icons[type] || 'i-tabler:question-mark'
}

function handleCopyExample(example) {
  copy(JSON.stringify(example, null, 2))
  message.success('示例已复制')
}

function formatExample(example) {
  if (typeof example === 'object') {
    return JSON.stringify(example, null, 2)
  }
  return String(example)
}

</script>

<template>
  <div class="space-y-3 p-4">
    <!-- Body 参数特殊处理 -->
    <template v-if="type === 'body'">
      <ParamBody v-for="param in parameters" :key="param.name" :param="param" @height-change="handleHeightChange" />
    </template>

    <!-- 其他参数类型 -->
    <template v-else>
      <ParamItem v-for="param in parameters" :key="param.name" :param="param" @height-change="handleHeightChange" />
    </template>
  </div>
</template>
