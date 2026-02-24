<script setup lang="ts">
import { useCustomMutation } from '@duxweb/dvha-core'
import { DuxFormItem, DuxModalPage } from '@duxweb/dvha-pro'
import { NButton, NInput, NSpin, useMessage } from 'naive-ui'
import { onMounted, ref } from 'vue'

const props = defineProps<{
  id: number | string
  onClose: () => void
}>()

const message = useMessage()
const requestClient = useCustomMutation()
const loading = ref(false)
const shareCode = ref('')
const datasetName = ref('')
const datasetLabel = ref('')

const close = () => {
  props.onClose()
}

const loadShareCode = async () => {
  loading.value = true
  try {
    const result = await requestClient.mutateAsync({
      path: `data/config/${props.id}/share`,
      method: 'GET',
    })
    const data = result?.data || {}
    shareCode.value = data.code || ''
    datasetName.value = data.name || ''
    datasetLabel.value = data.label || ''
  }
  catch (error: any) {
    message.error(error?.message || '获取分享数据失败')
  }
  finally {
    loading.value = false
  }
}

const handleCopy = async () => {
  if (!shareCode.value) {
    message.error('暂无可复制内容')
    return
  }
  try {
    await navigator.clipboard.writeText(shareCode.value)
    message.success('复制成功')
  }
  catch {
    message.error('复制失败，请手动复制')
  }
}

onMounted(() => {
  loadShareCode()
})
</script>

<template>
  <DuxModalPage @close="close">
    <div class="space-y-4">
      <div class="text-sm text-gray-500">
        <span>数据名称：{{ datasetName || '-' }}</span>
        <span class="mx-3">|</span>
        <span>数据标识：{{ datasetLabel || '-' }}</span>
      </div>
      <DuxFormItem label="分享数据">
        <NSpin :show="loading">
          <NInput
            v-model:value="shareCode"
            type="textarea"
            readonly
            placeholder="正在生成可分享数据..."
            :autosize="{ minRows: 10, maxRows: 16 }"
          />
        </NSpin>
      </DuxFormItem>
    </div>
    <template #footer>
      <NButton @click="close">
        关闭
      </NButton>
      <NButton @click="loadShareCode">
        重新生成
      </NButton>
      <NButton type="primary" :disabled="!shareCode" @click="handleCopy">
        复制
      </NButton>
    </template>
  </DuxModalPage>
</template>

<style scoped></style>
