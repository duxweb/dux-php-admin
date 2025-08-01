<script setup>
import { NTag } from 'naive-ui'
import CodeBlock from './components/CodeBlock'
import SchemaTree from './components/SchemaTree'
import ParamsPanel from './paramsPanel'

defineProps({
  param: {
    type: Object,
    required: true,
  },
})
</script>

<template>
  <ParamsPanel
    title="Request Body"
    :description="param.description || ''"
    icon="i-tabler:file-code"
    :required="param.required || false"
    :default-expanded="true"
  >
    <div class="space-y-4">
      <div v-for="(mediaType, contentType) in param.content" :key="contentType" class="flex flex-col gap-4">
        <div>
          <NTag type="primary" size="small">
            {{ contentType }}
          </NTag>
        </div>

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
