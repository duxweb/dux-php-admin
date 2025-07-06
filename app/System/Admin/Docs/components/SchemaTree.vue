<script setup>
import { NTree } from 'naive-ui'
import { computed } from 'vue'
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
  if (!props.schema) return []
  return getTreeData(props.schema)
})
const expandedKeys = computed(() => treeData.value.map(item => item.key))
</script>

<template>
  <div v-if="treeData.length" class="mb-4">
    <div class="text-sm font-medium mb-2">{{ title }}</div>
    <div class="border border-muted rounded-lg p-3 bg-elevated">
      <NTree
        :data="treeData"
        :render-label="renderLabel"
        :default-expanded-keys="expandedKeys"
        block-line
      />
    </div>
  </div>
</template>