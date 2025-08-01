<script setup>
import { NTag } from 'naive-ui'
import { computed } from 'vue'
import CodeBlock from './components/CodeBlock'
import SchemaTree from './components/SchemaTree'
import PanelCard from './panelCard'
import ParamsPanel from './paramsPanel'

const props = defineProps({
  responses: {
    type: Object,
    required: true,
  },
})

const responseCount = computed(() => {
  return Object.keys(props.responses).length
})

function getStatusType(statusCode) {
  if (statusCode.startsWith('2')) {
    return 'success'
  }
  if (statusCode.startsWith('4')) {
    return 'warning'
  }
  return 'error'
}
</script>

<template>
  <PanelCard
    v-if="responses && responseCount > 0"
    title="响应"
    icon="i-tabler:arrow-back-up"
    color="info"
    :count="responseCount"
  >
    <div class="space-y-3 p-3">
      <ParamsPanel
        v-for="(response, statusCode) in responses"
        :key="statusCode"
        :title="statusCode"
        :description="response.description"
        icon="i-tabler:code"
        :default-expanded="statusCode.startsWith('2')"
      >
        <template #title-suffix>
          <NTag :type="getStatusType(statusCode)" size="small">
            {{ response.description || '无描述' }}
          </NTag>
        </template>

        <div v-if="response.content" class="space-y-4">
          <div v-for="(content, mediaType) in response.content" :key="mediaType">
            <div class="flex items-center gap-2 mb-3">
              <span class="text-sm text-muted">Content-Type:</span>
              <NTag size="small">
                {{ mediaType }}
              </NTag>
            </div>

            <div v-if="content.schema" class="space-y-4">
              <SchemaTree
                :schema="content.schema"
                title="Schema 结构"
              />

              <CodeBlock
                v-if="content.schema.example !== undefined"
                :code="content.schema.example"
                title="示例"
              />
            </div>
          </div>
        </div>
      </ParamsPanel>
    </div>
  </PanelCard>
</template>
