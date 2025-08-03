<script setup>
import { DuxFormItem, DuxModalForm } from '@duxweb/dvha-pro'
import { NDatePicker, NInput, NSelect } from 'naive-ui'
import { ref } from 'vue'

const props = defineProps({
  id: {
    type: [String, Number],
    required: false,
  },
})

const model = ref({
  title: '',
  content: '',
  priority: 1,
  remind_at: null,
})

const priorityOptions = [
  { label: '低优先级', value: 1 },
  { label: '中优先级', value: 2 },
  { label: '高优先级', value: 3 },
]
</script>

<template>
  <DuxModalForm :id="props.id" path="system/memo" :data="model">
    <DuxFormItem label="标题" required>
      <NInput v-model:value="model.title" placeholder="请输入备忘录标题" />
    </DuxFormItem>
    
    <DuxFormItem label="内容">
      <NInput 
        v-model:value="model.content" 
        type="textarea" 
        :rows="4"
        placeholder="请输入备忘录内容"
      />
    </DuxFormItem>

    <div class="grid grid-cols-2 gap-4">
      <DuxFormItem label="优先级">
        <NSelect 
          v-model:value="model.priority" 
          :options="priorityOptions"
          placeholder="请选择优先级"
        />
      </DuxFormItem>
      
      <DuxFormItem label="提醒时间">
        <NDatePicker 
          v-model:formatted-value="model.remind_at" 
          type="datetime"
          value-format="yyyy-MM-dd HH:mm:ss"
          placeholder="选择提醒时间"
          clearable
        />
      </DuxFormItem>
    </div>
  </DuxModalForm>
</template>