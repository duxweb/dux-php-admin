<script setup>
import { DuxModalForm, DuxFormItem } from '@duxweb/dvha-pro'
import { NInput } from 'naive-ui'
import { ref } from 'vue'

const props = defineProps({
  locale: Array,
  id: [String, Number],
})

const formData = ref({
  name: '',
  data: props.locale?.reduce((acc, item) => {
    acc[item.name] = ''
    return acc
  }, {}),
})

function updateModel(key, value) {
  const data = formData.value.data || {}
  data[key] = value
}
</script>

<template>
  <DuxModalForm :id="props?.id" :data="formData" path="system/localeData">
    <DuxFormItem label="标识">
      <NInput v-model:value="formData.name" />
    </DuxFormItem>

    <DuxFormItem v-for="item in props?.locale" :key="item.name" :label="item.title">
      <NInput :value="formData?.data?.[item.name] || ''" @update:value="(value) => { updateModel(item.name, value) }" />
    </DuxFormItem>
  </DuxModalForm>
</template>

<style scoped></style>
