<script setup>
import { DuxFormItem, DuxModalForm } from '@duxweb/dvha-pro'
import { NCheckbox, NInput, NRadio, NRadioGroup } from 'naive-ui'
import { ref } from 'vue'

const props = defineProps({
  id: {
    type: [String, Number],
    required: false,
  },
})

const model = ref({
  name: '',
  label: '',
  table_type: 'pages',
  form_type: 'modal',
  api_sign: false,
  api_user: false,
  api_list: false,
  api_info: false,
  api_create: false,
  api_update: false,
  api_delete: false,
})
</script>

<template>
  <DuxModalForm :id="props.id" path="data/config" :data="model" label-placement="top">
    <DuxFormItem label="数据名称">
      <NInput v-model:value="model.name" placeholder="请输入名称" />
    </DuxFormItem>
    <DuxFormItem label="数据标识">
      <NInput v-model:value="model.label" placeholder="请输入标识" />
    </DuxFormItem>

    <DuxFormItem label="列表类型">
      <NRadioGroup v-model:value="model.table_type">
        <NRadio value="list">
          列表
        </NRadio>
        <NRadio value="pages">
          分页
        </NRadio>
        <NRadio value="tree">
          树形
        </NRadio>
      </NRadioGroup>
    </DuxFormItem>

    <DuxFormItem label="表单类型">
      <NRadioGroup v-model:value="model.form_type">
        <NRadio value="modal">
          弹窗
        </NRadio>
        <NRadio value="drawer">
          抽屉
        </NRadio>
        <NRadio value="page">
          页面
        </NRadio>
      </NRadioGroup>
    </DuxFormItem>

    <DuxFormItem label="API 鉴权" tooltip="开启鉴权后需要对 API 请求进行签名">
      <NRadioGroup v-model:value="model.api_sign">
        <NRadio :value="true">
          鉴权
        </NRadio>
        <NRadio :value="false">
          非鉴权
        </NRadio>
      </NRadioGroup>
    </DuxFormItem>

    <DuxFormItem label="用户授权" tooltip="开启授权后含有 Authorization 才能访问数据">
      <NRadioGroup v-model:value="model.api_user">
        <NRadio :value="true">
          授权
        </NRadio>
        <NRadio :value="false">
          非授权
        </NRadio>
      </NRadioGroup>
    </DuxFormItem>

    <DuxFormItem label="用户数据" tooltip="私有只能访问自身数据，公开后所有用户都可以访问">
      <NRadioGroup v-model:value="model.api_user_self">
        <NRadio :value="true">
          私有
        </NRadio>
        <NRadio :value="false">
          公开
        </NRadio>
      </NRadioGroup>
    </DuxFormItem>

    <DuxFormItem label="API 权限" tooltip="对外公开的 API 接口权限，增删改强制用户授权">
      <NCheckbox v-model:checked="model.api_list">
        列表
      </NCheckbox>
      <NCheckbox v-model:checked="model.api_info">
        详情
      </NCheckbox>
      <NCheckbox v-model:checked="model.api_create">
        创建
      </NCheckbox>
      <NCheckbox v-model:checked="model.api_update">
        更新
      </NCheckbox>
      <NCheckbox v-model:checked="model.api_delete">
        删除
      </NCheckbox>
    </DuxFormItem>
  </DuxModalForm>
</template>

<style scoped></style>
