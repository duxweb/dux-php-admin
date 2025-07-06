<script setup>
import { DuxFormItem, DuxModalForm } from '@duxweb/dvha-pro'
import { NInput } from 'naive-ui'
import { ref } from 'vue'

const props = defineProps({
  dictionaryID: Number,
  type: String,
})

const model = ref({
  value: undefined,
  dictionary_id: props.dictionaryID,
})
</script>

<template>
  <DuxModalForm :data="model" :path="`system/dictionaryData/${props.dictionaryID}`">
    <DuxFormItem label="名称">
      <NInput v-model:value="model.title" />
    </DuxFormItem>

    <DuxFormItem label="参数">
      <div class="w-full">
        <NInput v-if="props.type === 'string'" v-model:value="model.value" />
        <NInputNumber v-if="props.type === 'number'" v-model:value="model.value" />
        <NRadioGroup v-if="props.type === 'boolean'" v-model:value="model.value">
          <NRadio :value="true">
            True
          </NRadio>
          <NRadio :value="false">
            False
          </NRadio>
        </NRadioGroup>
      </div>
    </DuxFormItem>

    <DuxFormItem label="备注">
      <NInput v-model:value="model.remark" type="textarea" />
    </DuxFormItem>
  </DuxModalForm>
</template>

<style scoped></style>
