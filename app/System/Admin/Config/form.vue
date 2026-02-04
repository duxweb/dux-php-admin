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
  map: {
    tianditu_tk_browser: '',
    tianditu_tk_server: '',
  },
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
          <DuxFormItem label="系统标题" description="浏览器标题与登录页面名称" path="title">
            <NInput v-model:value="model.title" />
          </DuxFormItem>
          <DuxFormItem label="版权信息" description="登录页面系统版权信息" path="copyright">
            <NInput v-model:value="model.copyright" />
          </DuxFormItem>
        </DuxFormLayout>
      </DuxPanelCard>

      <DuxPanelCard title="标志配置" description="后台系统的基本信息" class="mt-4">
        <DuxFormLayout class="px-4" divider label-placement="setting" label-align="right">
          <DuxFormItem label="完整标志 （亮色）" description="针对登录页面等长方形 LOGO" path="logo_light">
            <NInput v-model:value="model.logo_light" />
          </DuxFormItem>
          <DuxFormItem label="完整标志 （暗色）" description="针对登录页面等长方形 LOGO" path="logo_dark">
            <NInput v-model:value="model.logo_dark" />
          </DuxFormItem>

          <DuxFormItem label="图标标志 （亮色）" description="针对菜单等方形 LOGO" path="app_logo_light">
            <NInput v-model:value="model.app_logo_light" />
          </DuxFormItem>
          <DuxFormItem label="图标标志 （暗色）" description="针对菜单等方形 LOGO" path="app_logo_dark">
            <NInput v-model:value="model.app_logo_dark" />
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
    <NTabPane name="other" tab="其他配置" display-directive="show">
      <DuxPanelCard title="地图配置" description="用于地图展示与定位服务（目前仅天地图）">
        <template #actions>
          <NButton secondary type="primary" @click="() => result.onSubmit()">
            保存配置
          </NButton>
        </template>
        <DuxFormLayout class="px-4" divider label-placement="setting" label-align="right">
          <DuxFormItem label="天地图 TK（浏览器端）" description="用于 Web 前端调用天地图 JS API" path="map.tianditu_tk_browser">
            <NInput v-model:value="model.map.tianditu_tk_browser" />
          </DuxFormItem>
          <DuxFormItem label="天地图 TK（服务器端）" description="用于服务端调用天地图接口（不要暴露到前端）" path="map.tianditu_tk_server">
            <NInput v-model:value="model.map.tianditu_tk_server" />
          </DuxFormItem>
        </DuxFormLayout>
      </DuxPanelCard>
    </NTabPane>
  </DuxSettingForm>
</template>

<style scoped></style>
