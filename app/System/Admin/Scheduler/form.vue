<script setup lang="ts">
import { useCustom } from '@duxweb/dvha-core'
import { DuxFormItem, DuxModalForm } from '@duxweb/dvha-pro'
import { NInput, NInputNumber, NSelect, NSwitch } from 'naive-ui'
import { computed, ref } from 'vue'

const props = defineProps({
  id: {
    type: [String, Number],
    required: false,
  },
})

const model = ref({
  selected_task: null as string | null,
  name: '',
  cron: '',
  desc: '',
  sort: 0,
  status: 1,
})
const { data: optionsData } = useCustom({
  path: 'system/scheduler/options',
})
const taskOptions = computed(() => optionsData.value?.data?.tasks || [])
</script>

<template>
  <DuxModalForm :id="props.id" path="system/scheduler" :data="model" label-placement="top" :width="720">
    <div class="grid grid-cols-2 gap-4">
      <DuxFormItem label="可选任务" path="selected_task" required>
        <NSelect
          v-model:value="model.selected_task"
          :options="taskOptions"
          placeholder="选择注解任务"
          clearable
        />
      </DuxFormItem>
      <DuxFormItem label="任务名称" path="name" required>
        <NInput v-model:value="model.name" placeholder="请输入任务名称" />
      </DuxFormItem>
      <DuxFormItem label="Cron 表达式" path="cron" required>
        <NInput v-model:value="model.cron" placeholder="* * * * *" />
      </DuxFormItem>
      <DuxFormItem label="排序" path="sort">
        <NInputNumber v-model:value="model.sort" class="w-full" />
      </DuxFormItem>
      <DuxFormItem label="状态" path="status">
        <NSwitch v-model:value="model.status" />
      </DuxFormItem>
      <DuxFormItem label="描述" path="desc" class="col-span-2">
        <NInput v-model:value="model.desc" type="textarea" placeholder="请输入描述" />
      </DuxFormItem>
    </div>
  </DuxModalForm>
</template>

<style scoped></style>
