<script setup>
import { DuxFormItem, DuxModalForm } from '@duxweb/dvha-pro'
import { NInput, NInputNumber, NRadio, NRadioGroup } from 'naive-ui'
import { ref } from 'vue'

const props = defineProps({
  id: {
    type: [String, Number],
    required: false,
  },
})

const model = ref({
  name: '',
  growth: 0,
  type: 0,
  default: 0,
})
</script>

<template>
  <DuxModalForm :id="props.id" path="member/level" :data="model">
    <DuxFormItem label="等级名称">
      <NInput v-model:value="model.name" placeholder="请输入等级名称" />
    </DuxFormItem>
    <DuxFormItem label="成长值">
      <NInputNumber v-model:value="model.growth" placeholder="请输入成长值" />
    </DuxFormItem>
    <DuxFormItem label="类型">
      <NRadioGroup v-model:value="model.type">
        <NRadio :value="0">
          普通
        </NRadio>
        <NRadio :value="1">
          招募
        </NRadio>
      </NRadioGroup>
    </DuxFormItem>
  </DuxModalForm>
</template>

<style scoped></style>
