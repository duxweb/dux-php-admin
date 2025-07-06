<script setup>
import { NButton, NCode } from 'naive-ui'
import { computed } from 'vue'
import { useClipboardWithMessage } from '../hooks/useClipboard'

const props = defineProps({
  code: {
    type: [String, Object],
    default: '',
  },
  language: {
    type: String,
    default: 'json',
  },
  title: {
    type: String,
    default: '示例',
  },
  copyable: {
    type: Boolean,
    default: true,
  },
})

const { copyExample } = useClipboardWithMessage()

const formattedCode = computed(() => {
  if (props.code === null) {
    return 'null'
  }
  if (props.code === undefined) {
    return 'undefined'
  }
  if (typeof props.code === 'object') {
    return JSON.stringify(props.code, null, 2)
  }
  return String(props.code)
})

function handleCopy() {
  copyExample(props.code)
}
</script>

<template>
  <div class="bg-elevated rounded-lg p-4">
    <div class="flex items-center justify-between mb-2">
      <span class="text-sm font-medium">{{ title }}</span>
      <NButton v-if="copyable" size="tiny" quaternary @click="handleCopy">
        <template #icon>
          <div class="size-3 i-tabler:copy" />
        </template>
        复制
      </NButton>
    </div>
    <NCode :code="formattedCode" :language="language" />
  </div>
</template>