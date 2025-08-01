<script setup>
import { DuxCodeEditor, DuxFileUpload, DuxFormItem, DuxImageUpload, DuxModalForm } from '@duxweb/dvha-pro'
import { NInput, NInputNumber, NRadio, NRadioGroup, NSwitch } from 'naive-ui'
import { ref } from 'vue'

const props = defineProps({
  id: {
    type: [String, Number],
    required: false,
  },
})

const model = ref({
  type: 'string',
  value: undefined,
  public: false,
})
</script>

<template>
  <DuxModalForm :id="props.id" path="system/setting" :data="model">
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
        <NRadio value="json">
          JSON
        </NRadio>
        <NRadio value="image">
          图片
        </NRadio>
        <NRadio value="file">
          文件
        </NRadio>
      </NRadioGroup>
    </DuxFormItem>
    <DuxFormItem label="参数">
      <div class="w-full">
        <NInput v-if="model.type === 'string'" v-model:value="model.value" />
        <NInputNumber v-if="model.type === 'number'" v-model:value="model.value" />
        <NRadioGroup v-if="model.type === 'boolean'" v-model:value="model.value">
          <NRadio :value="true">
            是
          </NRadio>
          <NRadio :value="false">
            否
          </NRadio>
        </NRadioGroup>
        <DuxCodeEditor v-if="model.type === 'json'" v-model:value="model.value" language="json" />
        <DuxImageUpload v-if="model.type === 'image'" v-model:value="model.value" />
        <DuxFileUpload v-if="model.type === 'file'" v-model:value="model.value" />
      </div>
    </DuxFormItem>
    <DuxFormItem label="备注">
      <NInput v-model:value="model.remark" type="textarea" />
    </DuxFormItem>
    <DuxFormItem label="公开">
      <NSwitch v-model:value="model.public" />
    </DuxFormItem>
  </DuxModalForm>
</template>

<style scoped>

</style>
