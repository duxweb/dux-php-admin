<script setup>
import { DuxFormItem, DuxModalForm } from '@duxweb/dvha-pro'
import { useCustom } from '@duxweb/dvha-core'
import { NCheckbox, NCheckboxGroup, NDatePicker, NInput, NInputNumber, NSelect, NSwitch, NTransfer } from 'naive-ui'
import { ref, watch, computed } from 'vue'

const props = defineProps({
  id: {
    type: [String, Number],
    required: false,
  },
})

const model = ref({
  type: 1,
  target_type: 1,
  target_departments: [],
  target_roles: [],
  sort: 0,
  status: true,
  is_top: false,
})

const { data: optionsData } = useCustom({
  path: 'system/bulletin/options',
})

const departments = computed(() => optionsData.value?.data?.departments || [])
const roles = computed(() => optionsData.value?.data?.roles || [])
const types = computed(() => optionsData.value?.data?.types || [])
const targetTypes = computed(() => optionsData.value?.data?.target_types || [])

</script>

<template>
  <DuxModalForm :id="props.id" path="system/bulletin" :data="model" width="800" label-placement="top">
    <DuxFormItem label="公告标题" required>
      <NInput v-model:value="model.title" placeholder="请输入公告标题" />
    </DuxFormItem>

    <DuxFormItem label="公告内容" required>
      <NInput
        v-model:value="model.content"
        type="textarea"
        :rows="6"
        placeholder="请输入公告内容"
      />
    </DuxFormItem>

    <div class="grid grid-cols-2 gap-4">
      <DuxFormItem label="公告类型">
        <NSelect
          v-model:value="model.type"
          :options="types"
          placeholder="请选择公告类型"
        />
      </DuxFormItem>

      <DuxFormItem label="排序权重">
        <NInputNumber v-model:value="model.sort" :min="0" />
      </DuxFormItem>
    </div>

    <DuxFormItem label="发布目标">
      <NSelect
        v-model:value="model.target_type"
        :options="targetTypes"
        placeholder="请选择发布目标"
      />
    </DuxFormItem>

    <DuxFormItem v-if="model.target_type === 2" label="目标部门">
      <NTransfer
        v-model:value="model.target_departments"
        :options="departments"
      />
    </DuxFormItem>

    <DuxFormItem v-if="model.target_type === 3" label="目标角色">
      <NTransfer
        v-model:value="model.target_roles"
        :options="roles"
      />
    </DuxFormItem>

    <div class="grid grid-cols-2 gap-4">
      <DuxFormItem label="发布时间">
        <NDatePicker
          v-model:formatted-value="model.publish_at" type="date" value-format="yyyy-MM-dd HH:mm:ss"
          placeholder="请选择发布时间"
          clearable
        />
      </DuxFormItem>

      <DuxFormItem label="过期时间">
        <NDatePicker
          v-model:formatted-value="model.expire_at" type="date" value-format="yyyy-MM-dd HH:mm:ss"
          placeholder="请选择过期时间"
          clearable
        />
      </DuxFormItem>
    </div>

    <div class="grid grid-cols-2 gap-4">
      <DuxFormItem label="是否置顶">
        <NSwitch v-model:value="model.is_top" />
      </DuxFormItem>

      <DuxFormItem label="发布状态">
        <NSwitch v-model:value="model.status" />
      </DuxFormItem>
    </div>
  </DuxModalForm>
</template>
