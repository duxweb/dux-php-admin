<script setup>
import { NTag } from 'naive-ui'
import ParamsPanel from './paramsPanel'
import SchemaTree from './components/SchemaTree'
import CodeBlock from './components/CodeBlock'

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
    title="Request Body"
    :description="param.description || ''"
    icon="i-tabler:file-code"
    :required="param.required || false"
    :default-expanded="true"
    @height-change="handleHeightChange"
  >
    <div class="space-y-4">
      <div v-for="(mediaType, contentType) in param.content" :key="contentType">
        <NTag type="primary" size="small" class="mb-3">{{ contentType }}</NTag>

        <SchemaTree
          v-if="mediaType.schema"
          :schema="mediaType.schema"
          title="Schema 结构"
        />

        <CodeBlock
          v-if="mediaType.schema?.example !== undefined"
          :code="mediaType.schema.example"
          title="示例"
        />
      </div>
    </div>
  </ParamsPanel>
</template>


