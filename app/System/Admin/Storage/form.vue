<script setup>
import { DuxFormItem, DuxModalForm } from '@duxweb/dvha-pro'
import { NInput, NRadio, NRadioGroup, NAlert } from 'naive-ui'
import { ref } from 'vue'

const model = ref({
  type: 'local',
  config: {},
})
</script>

<template>
  <DuxModalForm :data="model" path="system/storage">
    <DuxFormItem label="名称">
      <NInput v-model:value="model.title" />
    </DuxFormItem>
    <DuxFormItem label="标识" help="标识用于区分不同的存储，请输入字母、数字、下划线，且不能重复">
      <NInput v-model:value="model.name" />
    </DuxFormItem>
    <DuxFormItem label="类型">
      <NRadioGroup :value="model.type" @update:value="(v) => { model.type = v; model.value = undefined }">
        <NRadio value="local">
          本地存储
        </NRadio>
        <NRadio value="s3">
          S3 协议
        </NRadio>
      </NRadioGroup>
    </DuxFormItem>
    <DuxFormItem v-if="model.type === 'local'" label="存储目录">
      <div class="w-full">
        <NInput v-model:value="model.config.path" />
        <NAlert type="info" class="mt-2">
          路径为 public 目录下的路径
        </NAlert>
      </div>
    </DuxFormItem>
    <DuxFormItem v-if="model.type === 'local'" label="访问域名">
      <NInput v-model:value="model.config.domain" />
    </DuxFormItem>

    <DuxFormItem v-if="model.type === 's3'" label="区域">
      <NInput v-model:value="model.config.region" />
    </DuxFormItem>
    <DuxFormItem v-if="model.type === 's3'" label="访问端点">
      <NInput v-model:value="model.config.endpoint" />
    </DuxFormItem>
    <DuxFormItem v-if="model.type === 's3'" label="存储桶">
      <NInput v-model:value="model.config.bucket" />
    </DuxFormItem>
    <DuxFormItem v-if="model.type === 's3'" label="AccessKey">
      <NInput v-model:value="model.config.access_key" />
    </DuxFormItem>
    <DuxFormItem v-if="model.type === 's3'" label="SecretKey">
      <NInput v-model:value="model.config.secret_key" />
    </DuxFormItem>
    <DuxFormItem v-if="model.type === 's3'" label="访问域名">
      <NInput v-model:value="model.config.domain" />
    </DuxFormItem>
  </DuxModalForm>
</template>

<style scoped>

</style>
