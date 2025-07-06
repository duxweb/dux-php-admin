<script setup lang="ts">
import { useOne, useUpdate } from '@duxweb/dvha-core'
import { DuxFormEditor } from '@duxweb/dvha-pro'
import { cloneDeep } from 'lodash-es'
import { useMessage } from 'naive-ui'
import { ref, watch } from 'vue'
import { useRoute } from 'vue-router'

const route = useRoute()
const form = ref({})
const message = useMessage()

const { data } = useOne({
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

watch(data, (val) => {
  form.value = cloneDeep(val?.data || {})
}, {
  immediate: true,
})

function handleSave() {
  mutate({
    data: {
      data: form.value,
    },
  })
}

function handleUpdateData(v) {
  form.value = v
}
</script>

<template>
  <DuxFormEditor
    :data="data?.data || {}"
    @save="handleSave"
    @update-data="handleUpdateData"
  />
</template>
