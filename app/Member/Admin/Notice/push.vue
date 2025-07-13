<script setup>
import { DuxFormItem, DuxImageUpload, DuxModalForm } from '@duxweb/dvha-pro'
import { NInput } from 'naive-ui'
import { ref } from 'vue'

const props = defineProps({
  id: {
    type: [String, Number],
    required: false,
  },
})

const model = ref({
  title: '',
  desc: '',
  url: '',
  image: '',
})
</script>

<template>
  <DuxModalForm :id="props.id" path="member/notice/push" action="push" :data="model">
    <DuxFormItem label="公告标题" required>
      <NInput v-model:value="model.title" placeholder="请输入公告标题" />
    </DuxFormItem>
    <DuxFormItem label="公告描述" required>
      <NInput v-model:value="model.desc" type="textarea" placeholder="请输入公告描述" />
    </DuxFormItem>
    <DuxFormItem label="跳转链接">
      <NInput v-model:value="model.url" placeholder="请输入跳转链接" />
    </DuxFormItem>
    <DuxFormItem label="封面图片">
      <DuxImageUpload v-model:value="model.image" manager />
    </DuxFormItem>
  </DuxModalForm>
</template>

<style scoped></style>
