<script setup>
import { NTag } from 'naive-ui'
import ParamsPanel from './paramsPanel'
import CodeBlock from './components/CodeBlock'
import { getTypeColor, getTypeIcon } from './hooks/useSchema'

const props = defineProps({
  param: {
    type: Object,
    required: true,
  },
})

const emit = defineEmits(['height-change'])

function handleHeightChange() {
  emit('height-change')
}
</script>

<template>
  <ParamsPanel
    :title="param.name || '未命名参数'"
    :description="param.description || ''"
    :icon="getTypeIcon(param.schema?.type || 'string')"
    :required="param.required || false"
    :default-expanded="false"
    :copyable="true"
    @height-change="handleHeightChange"
  >
    <template #title-suffix>
      <NTag :type="getTypeColor(param.schema?.type || 'string')" size="small">
        {{ param.schema?.type || 'string' }}
      </NTag>
    </template>

    <CodeBlock
      v-if="param.example !== undefined"
      :code="param.example"
      title="示例值"
    />
  </ParamsPanel>
</template>
