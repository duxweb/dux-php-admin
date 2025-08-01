<script setup>
import { NTag } from 'naive-ui'
import CodeBlock from './components/CodeBlock'
import { getTypeColor, getTypeIcon } from './hooks/useSchema'
import ParamsPanel from './paramsPanel'

const _props = defineProps({
  param: {
    type: Object,
    required: true,
  },
})
</script>

<template>
  <ParamsPanel
    :title="param.name || '未命名参数'"
    :description="param.description || ''"
    :icon="getTypeIcon(param.schema?.type || 'string')"
    :required="param.required || false"
    :default-expanded="false"
    :copyable="true"
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
