<script setup>
import { DuxFormItem, DuxModalForm } from '@duxweb/dvha-pro'
import { NInput } from 'naive-ui'
import { ref } from 'vue'

const props = defineProps({
  id: {
    type: [String, Number],
    required: false,
  },
})

const model = ref({
  nickname: '',
})
</script>

<template>
  <DuxModalForm :id="props.id" path="member/blockNickname" :data="model">
    <DuxFormItem label="禁用昵称">
      <NInput v-model:value="model.nickname" placeholder="请输入要禁用的昵称" />
    </DuxFormItem>
  </DuxModalForm>
</template>

<style scoped></style>
