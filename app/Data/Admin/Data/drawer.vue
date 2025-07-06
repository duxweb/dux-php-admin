<script setup>
import { DuxFormItem, DuxDrawerForm, DuxFormRenderer } from '@duxweb/dvha-pro'
import { useOne } from '@duxweb/dvha-core'
import { NInput, NInputNumber, NRadio, NRadioGroup } from 'naive-ui'
import { ref, computed } from 'vue'

const props = defineProps({
  id: {
    type: [String, Number],
    required: false,
  },
  name: {
    type: String,
    required: false,
  },
  config: {
    type: Object,
    required: false,
  }
})

const { data: configData } = useOne({
  path: `data/config/${props.name}/config`,
  options: {
    enabled: !props.config
  }
})

const data = computed(() => {
  return props.config?.form_data?.data || configData.value?.data?.form_data?.data || []
})

const config = computed(() => {
  return props.config?.form_data?.config || configData.value?.data?.form_data?.config || []
})

const model = ref({
})
</script>

<template>
  <DuxDrawerForm :id="props.id" :path="`data/data/${props.name}`" :data="model">
    <DuxFormRenderer v-model:value="model" :data="data" :config="config" />
  </DuxDrawerForm>
</template>

<style scoped></style>
