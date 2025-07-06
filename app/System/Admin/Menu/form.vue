<script setup lang="ts">
import { useCustomMutation, useExtendForm } from '@duxweb/dvha-core'
import { DuxTreeSelect } from '@duxweb/dvha-naiveui'
import { DuxCard, DuxFormItem, DuxFormLayout } from '@duxweb/dvha-pro'
import { NButton, NInput, NRadio, NRadioGroup, NScrollbar, NSwitch, NTooltip, useMessage } from 'naive-ui'
import { h, ref, toRef } from 'vue'

const props = defineProps({
  id: {
    type: [Number, String],
    default: undefined,
  },
})

const id = toRef(props, 'id')
const message = useMessage()

const data = ref({
  type: 'directory',
  parent_id: null,
  name: '',
  label: '',
  label_lang: '',
  icon: '',
  path: '',
  loader: '',
  url: '',
  buttons: [],
  hidden: false,
})

const form = useExtendForm({
  path: 'system/menu',
  id,
  form: data.value,
  onSuccess: () => {
    message.success('保存成功')
  },
  onError: (error) => {
    message.error(error?.message || '保存失败')
  },
})

const typeName: Record<string, string> = {
  directory: '目录',
  menu: '菜单',
  iframe: '嵌入',
  link: '外链',
}

const client = useCustomMutation()

function onSyncButton() {
  client.mutateAsync({
    path: `system/menu/button`,
    method: 'GET',
    query: {
      name: data.value?.name,
    },
  }).then((res) => {
    if (!data.value) {
      return
    }
    data.value.label_lang = data.value.name
    data.value.buttons = res.data?.map(v => ({
      ...v,
      label_lang: v.name,
    })) || []
    const nameParts = data.value.name.split('.')
    const hasMultipleParts = nameParts.length >= 3
    const lastPart = hasMultipleParts ? nameParts[nameParts.length - 1] : ''
    const pathParts = hasMultipleParts ? nameParts.slice(0, -1) : nameParts

    data.value.path = pathParts.join('/')

    const loaderPath = pathParts.map(part =>
      part.charAt(0).toUpperCase() + part.slice(1),
    ).join('/')
    let pageType = 'page'
    switch (lastPart) {
      case 'index':
        pageType = 'index'
        break
      case 'create':
      case 'edit':
        pageType = 'form'
        break
      case 'show':
        pageType = 'view'
        break
      case 'list':
        pageType = 'table'
        break
    }
    data.value.loader = `${loaderPath}/${pageType}`
  })
}
</script>

<template>
  <DuxCard
    content-size="none"
    class="h-full"
    :title="id ? '编辑菜单' : '新增菜单'"
    header-bordered
    footer-bordered
    footer-size="small"
    content-class="flex-1 h-1"
  >
    <template #headerExtra>
      <slot name="headerExtra" />
    </template>
    <NScrollbar>
      <DuxFormLayout layout="top" class="p-4" :label-width="100">
        <DuxFormItem label="类型" field="label">
          <NRadioGroup v-model:value="data.type" class="!flex gap-4">
            <NRadio v-for="type in Object.keys(typeName)" :key="type" :value="type">
              {{ typeName[type] }}
            </NRadio>
          </NRadioGroup>
        </DuxFormItem>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
          <DuxFormItem label="上级菜单" field="parent_id" tooltip="请选择父级目录" required>
            <DuxTreeSelect
              v-model:value="data.parent_id" path="system/menu" label-field="label" key-field="id"
              clearable
            />
          </DuxFormItem>

          <DuxFormItem label="菜单名称" tooltip="菜单展示文字信息" field="label" required>
            <NInput v-model:value="data.label" />
          </DuxFormItem>

          <DuxFormItem label="菜单标识" tooltip="菜单标识，如果对应权限，请填写权限名" field="name" required>
            <NInput v-model:value="data.name">
              <template #suffix>
                <NTooltip>
                  <template #trigger>
                    <NButton
                      text @click="() => {
                        onSyncButton()
                      }"
                    >
                      识别
                    </NButton>
                  </template>
                  <div>
                    自动识别其他信息
                  </div>
                </NTooltip>
              </template>
            </NInput>
          </DuxFormItem>

          <DuxFormItem label="国际化" tooltip="多语言菜单标识" field="labelLang">
            <NInput v-model:value="data.label_lang" />
          </DuxFormItem>

          <DuxFormItem
            v-if="data.type === 'menu' || data.type === 'iframe' || data.type === 'link'" label="页面路由"
            tooltip="页面路由地址" field="path" required
          >
            <NInput v-model:value="data.path" />
          </DuxFormItem>
          <DuxFormItem v-if="data.type === 'menu'" label="页面路径" tooltip="页面对应vue路径" field="loader">
            <NInput v-model:value="data.loader" placeholder="不填写则根据路由获取">
              <template #suffix>
                .vue | .json
              </template>
            </NInput>
          </DuxFormItem>

          <DuxFormItem label="图标" field="icon">
            <DuxIconPicker v-model:value="data.icon" />
          </DuxFormItem>

          <DuxFormItem v-if="data.type === 'menu'" label="隐藏" tooltip="隐藏后菜单不显示" field="hidden">
            <NSwitch v-model:value="data.hidden" />
          </DuxFormItem>
          <DuxFormItem
            v-if="data.type === 'iframe' || data.type === 'link'" label="网页地址" tooltip="网页地址" field="url"
            required
          >
            <NInput v-model:value="data.url" />
          </DuxFormItem>
        </div>

        <DuxFormItem v-if="data.type === 'menu'" label="按钮权限" field="buttons">
          <DuxDynamicData
            v-model:value="data.buttons" :columns="[
              { key: 'label', title: '按钮名称', render: (v) => h(NInput, { value: v.label, onUpdateValue: (newValue) => { v.label = newValue } }) },
              { key: 'name', title: '按钮标识', render: (v) => h(NInput, { value: v.name, onUpdateValue: (newValue) => { v.name = newValue } }) },
              { key: 'label_lang', title: '国际化', render: (v) => h(NInput, { value: v.label_lang, onUpdateValue: (newValue) => { v.label_lang = newValue } }) },
            ]"
          />
        </DuxFormItem>
      </DuxFormLayout>
    </NScrollbar>
    <template #footer>
      <div class="flex gap-2 justify-end">
        <NButton
          type="primary" tertiary @click="() => {
            form.onReset()
          }"
        >
          重置
        </NButton>
        <NButton
          type="primary" :loading="form.isLoading.value" @click="() => {
            form.onSubmit()
          }"
        >
          保存
        </NButton>
      </div>
    </template>
  </DuxCard>
</template>

<style scoped></style>
