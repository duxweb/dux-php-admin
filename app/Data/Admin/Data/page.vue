<script setup>
import { useOne } from '@duxweb/dvha-core'
import { DuxFormRenderer, DuxPageForm } from '@duxweb/dvha-pro'
import { computed, ref } from 'vue'
import { useRoute } from 'vue-router'

const route = useRoute()
const id = route.params.id
const pathList = route.path.split('/')
const configName = id ? pathList[pathList.length - 3] : pathList[pathList.length - 2]

const { data: configData } = useOne({
  path: `data/config/${configName}/config`,
})

const data = computed(() => {
  return configData.value?.data?.form_data?.data || []
})

const config = computed(() => {
  return configData.value?.data?.form_data?.config || []
})

const model = ref({
})
</script>

<template>
  <DuxPageForm :id="id" :path="`data/data/${configName}`" :data="model">
    <DuxFormRenderer v-model:value="model" :data="data" :config="config" />
  </DuxPageForm>
</template>

<style scoped></style>
