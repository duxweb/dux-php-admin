<script setup>
import { DuxFormItem, DuxModalForm } from '@duxweb/dvha-pro'
import { NInput } from 'naive-ui'
import { ref } from 'vue'

const props = defineProps({
  id: {
    type: [String, Number],
    required: false,
  },
})

const model = ref({})
</script>

<template>
  <DuxModalForm :id="props.id" path="system/locale" :data="model">
    <DuxFormItem label="语言名称">
      <NInput v-model:value="model.title" />
    </DuxFormItem>
    <DuxFormItem label="语言标识">
      <NInput v-model:value="model.name" />
    </DuxFormItem>
  </DuxModalForm>
</template>

<style scoped></style>
