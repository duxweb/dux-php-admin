<script setup lang="ts">
import { useOne, useUpdate } from '@duxweb/dvha-core'
import { DuxFormEditor } from '@duxweb/dvha-pro'
import { useMessage } from 'naive-ui'
import { ref, watch } from 'vue'
import { useRoute } from 'vue-router'

const route = useRoute()
const form = ref<any>({})
const message = useMessage()

const { data: formData } = useOne({
  path: `data/config/${route.params.id}/form`,
})

const { mutate } = useUpdate({
  path: `data/config/${route.params.id}/form`,
  onSuccess() {
    message.success('保存成功')
  },
  onError(error) {
    message.error(error.message || '保存失败')
  },
})

watch(formData, (val) => {
    form.value = val?.data || {}
}, {
  immediate: true
})


function handleSave() {
  mutate({
    data: {
      data: form.value,
    },
  })
}
</script>

<template>
  <DuxFormEditor
    :data="form"
    @save="handleSave"
  />
</template>
