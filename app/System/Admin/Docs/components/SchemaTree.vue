<script setup>
import { NTree } from 'naive-ui'
import { computed } from 'vue'
import { useClipboardWithMessage } from '../hooks/useClipboard'
import { getTreeData, renderLabel } from '../hooks/useSchema'

const props = defineProps({
  schema: {
    type: Object,
    required: true,
  },
  title: {
    type: String,
    default: 'Schema 结构',
  },
})

const treeData = computed(() => {
  if (!props.schema)
    return []
  return getTreeData(props.schema)
})
const expandedKeys = computed(() => treeData.value.map(item => item.key))

const { copyText } = useClipboardWithMessage()
</script>

<template>
  <div v-if="treeData.length">
    <div class="text-sm font-medium mb-2">
      {{ title }}
    </div>
    <div class="border border-muted rounded-lg p-3 bg-elevated overflow-auto">
      <NTree
        class="min-w-100"
        :data="treeData"
        :render-label="renderLabel(copyText)"
        :default-expanded-keys="expandedKeys"
        block-line
      />
    </div>
  </div>
</template>
