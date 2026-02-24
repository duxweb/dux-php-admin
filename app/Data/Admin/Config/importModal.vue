<script setup lang="ts">
import { useCustomMutation, useInvalidate } from '@duxweb/dvha-core'
import { DuxFormItem, DuxModalPage } from '@duxweb/dvha-pro'
import { NAlert, NButton, NInput, useMessage } from 'naive-ui'
import { ref } from 'vue'

const props = defineProps<{
  onClose: () => void
}>()

const message = useMessage()
const requestClient = useCustomMutation()
const { invalidate } = useInvalidate()

const form = ref({
  code: '',
})
const submitting = ref(false)

const close = () => {
  props.onClose()
}

const submit = async () => {
  if (!form.value.code.trim()) {
    message.error('请粘贴分享数据')
    return
  }
  submitting.value = true
  try {
    await requestClient.mutateAsync({
      path: 'data/config/import',
      method: 'POST',
      payload: {
        code: form.value.code.trim(),
      },
    })
    message.success('导入成功')
    invalidate('data/config')
    close()
  }
  catch (error: any) {
    message.error(error?.message || '导入失败')
  }
  finally {
    submitting.value = false
  }
}
</script>

<template>
  <DuxModalPage @close="close">
    <div class="space-y-4">
      <NAlert type="info">
        请粘贴通过“分享”生成的数据，系统会自动解析并创建数据集配置。
      </NAlert>
      <DuxFormItem label="分享数据" required>
        <NInput
          v-model:value="form.code"
          type="textarea"
          placeholder="请粘贴分享数据"
          :autosize="{ minRows: 10, maxRows: 16 }"
        />
      </DuxFormItem>
    </div>
    <template #footer>
      <NButton @click="close">
        取消
      </NButton>
      <NButton type="primary" :loading="submitting" @click="submit">
        导入
      </NButton>
    </template>
  </DuxModalPage>
</template>

<style scoped></style>
