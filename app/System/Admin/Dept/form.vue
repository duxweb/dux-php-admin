<script setup>
import { DuxCascader } from '@duxweb/dvha-naiveui'
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
  <DuxModalForm :id="props.id" path="system/dept" :data="model">
    <DuxFormItem label="上级">
      <DuxCascader v-model:value="model.parent_id" path="system/dept" label-field="name" value-field="id" />
    </DuxFormItem>
    <DuxFormItem label="名称">
      <NInput v-model:value="model.name" />
    </DuxFormItem>
  </DuxModalForm>
</template>

<style scoped></style>
