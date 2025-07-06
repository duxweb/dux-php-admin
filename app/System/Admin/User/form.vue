<script setup>
import { DuxCascader, DuxSelect } from '@duxweb/dvha-naiveui'
import { DuxFormItem, DuxModalForm } from '@duxweb/dvha-pro'
import { NInput, NSwitch } from 'naive-ui'
import { ref } from 'vue'

const props = defineProps({
  id: {
    type: [String, Number],
    required: false,
  },
})

const model = ref({
  status: true,
})
</script>

<template>
  <DuxModalForm :id="props.id" path="system/user" :data="model">
      <DuxFormItem label="用户名">
        <NInput v-model:value="model.username" />
      </DuxFormItem>
      <DuxFormItem label="昵称">
        <NInput v-model:value="model.nickname" />
      </DuxFormItem>
      <DuxFormItem label="所属角色">
        <DuxSelect v-model:value="model.role_id" path="system/role" label-field="name" value-field="id" />
      </DuxFormItem>
      <DuxFormItem label="所属部门">
        <DuxCascader v-model:value="model.dept_id" :cascade="false" path="system/dept" label-field="name" value-field="id" />
      </DuxFormItem>

      <DuxFormItem label="密码" description="不修改请留空">
        <NInput v-model:value="model.password" type="password" />
      </DuxFormItem>

      <DuxFormItem label="状态">
        <NSwitch v-model:value="model.status" />
      </DuxFormItem>
  </DuxModalForm>
</template>

<style scoped>

</style>
