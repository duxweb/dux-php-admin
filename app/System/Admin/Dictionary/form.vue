<script setup>
import { DuxFormItem, DuxModalForm } from '@duxweb/dvha-pro'
import { NInput, NRadio, NRadioGroup } from 'naive-ui'
import { ref } from 'vue'

const model = ref({
  type: 'string',
  value: undefined,
})
</script>

<template>
  <DuxModalForm :data="model" path="system/dictionary">
    <DuxFormItem label="名称">
      <NInput v-model:value="model.title" />
    </DuxFormItem>
    <DuxFormItem label="键名">
      <NInput v-model:value="model.key" />
    </DuxFormItem>
    <DuxFormItem label="类型">
      <NRadioGroup :value="model.type" @update:value="(v) => { model.type = v; model.value = undefined }">
        <NRadio value="string">
          字符串
        </NRadio>
        <NRadio value="number">
          数字
        </NRadio>
        <NRadio value="boolean">
          布尔
        </NRadio>
      </NRadioGroup>
    </DuxFormItem>
    <DuxFormItem label="备注">
      <NInput v-model:value="model.remark" type="textarea" />
    </DuxFormItem>
  </DuxModalForm>
</template>

<style scoped></style>
