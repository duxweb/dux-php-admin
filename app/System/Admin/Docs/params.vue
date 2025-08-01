<script setup>
import { computed } from 'vue'
import ParamBody from './paramBody'
import ParamItem from './paramItem'

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

const parameters = computed(() => {
  if (!props.info || !props.info.api)
    return []

  if (props.type === 'body') {
    return props.info.api?.requestBody ? [props.info.api.requestBody] : []
  }
  return props.info.api?.parameters?.filter(item => item.in === props.type) || []
})
</script>

<template>
  <div class="space-y-3 p-4">
    <!-- Body 参数特殊处理 -->
    <template v-if="type === 'body'">
      <ParamBody v-for="param in parameters" :key="param.name" :param="param" />
    </template>

    <!-- 其他参数类型 -->
    <template v-else>
      <ParamItem v-for="param in parameters" :key="param.name" :param="param" />
    </template>
  </div>
</template>
