<script setup>
import { DuxModalForm, DuxSelectUpload } from '@duxweb/dvha-pro'
import { NAlert, NButton } from 'naive-ui'
import { ref } from 'vue'

const model = ref({
  file: null,
})
</script>

<template>
  <DuxModalForm path="system/area/import" :data="model">
    <NAlert type="info">
      请上传 <NButton text tag="a" href="https://lbsyun.baidu.com/faq/api?title=webapi/download" target="_blank">
        [百度地图行政区划adcode映射表]
      </NButton> 文件
    </NAlert>
    <DuxSelectUpload v-model:value="model.file" accept=".xlsx" />
  </DuxModalForm>
</template>

<style scoped></style>
