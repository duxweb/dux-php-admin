<script setup>
import { DuxSelect } from '@duxweb/dvha-naiveui'
import { DuxFormItem, DuxFormLayout, DuxPanelCard, DuxSettingForm } from '@duxweb/dvha-pro'
import { NButton, NInput, NInputNumber, NTabPane } from 'naive-ui'
import { ref } from 'vue'

const model = ref({
  title: '',
  copyright: '',
  storage: null,
  files: [],
})
</script>

<template>
  <DuxSettingForm v-slot="result" :data="model" default-tab="base" path="system/config" action="edit" tabs>
    <NTabPane name="base" tab="系统信息" display-directive="show">
      <DuxPanelCard title="系统信息" description="后台系统的基本信息">
        <template #actions>
          <NButton secondary type="primary" @click="() => result.onSubmit()">
            保存信息
          </NButton>
        </template>
        <DuxFormLayout class="px-4" divider label-placement="setting" label-align="right">
          <DuxFormItem label="系统标题" description="浏览器头部标题" path="title">
            <NInput v-model:value="model.title" />
          </DuxFormItem>
          <DuxFormItem label="版权信息" description="浏览器底部版权信息" path="copyright">
            <NInput v-model:value="model.copyright" />
          </DuxFormItem>
        </DuxFormLayout>
      </DuxPanelCard>
    </NTabPane>
    <NTabPane name="upload" tab="上传配置" display-directive="show">
      <DuxPanelCard title="上传配置" description="上传配置">
        <template #actions>
          <NButton secondary type="primary" @click="() => result.onSubmit()">
            保存配置
          </NButton>
        </template>
        <DuxFormLayout class="px-4" divider label-placement="setting" label-align="right">
          <DuxFormItem label="默认上传" description="系统默认上传驱动" path="storage" rule="required">
            <DuxSelect v-model:value="model.storage" path="system/storage" label-field="title" value-field="id" :pagination="false" />
          </DuxFormItem>
          <DuxFormItem label="上传扩展" description="系统默认上传扩展" path="upload_ext">
            <NInput v-model:value="model.upload_ext" type="textarea" />
          </DuxFormItem>
          <DuxFormItem label="上传大小" description="系统默认上传大小 (MB)" path="upload_size">
            <NInputNumber v-model:value="model.upload_size" />
          </DuxFormItem>
        </DuxFormLayout>
      </DuxPanelCard>
    </NTabPane>
  </DuxSettingForm>
</template>

<style scoped></style>
