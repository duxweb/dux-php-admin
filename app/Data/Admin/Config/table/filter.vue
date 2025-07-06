<script setup>
import { DuxFormItem, DuxFormLayout, DuxModalPage } from '@duxweb/dvha-pro'
import { NButton, NDynamicInput, NInput } from 'naive-ui'
import { ref } from 'vue'

const props = defineProps({
  row: {
    type: Object,
    required: true,
  },
  onClose: {
    type: Function,
    required: true,
  },
  onConfirm: {
    type: Function,
    required: true,
  },
})

const model = ref({
  ...props.row.setting,
})

function submit() {
  props.onConfirm(model.value)
}

function close() {
  props.onClose()
}
</script>

<template>
  <DuxModalPage @close="close">
    <DuxFormLayout label-placement="top">
      <template v-if="row.type === 'select'">
        <DuxFormItem label="选项">
          <NDynamicInput
            v-model:value="model.options" :on-create="(item) => ({
            })"
          >
            <template #default="{ value }">
              <div class="flex gap-2 items-center">
                <NInput v-model:value="value.label" placeholder="选项名" />
                <NInput v-model:value="value.value" placeholder="选项值" />
              </div>
            </template>
          </NDynamicInput>
        </DuxFormItem>
      </template>
      <template v-if="row.type === 'async-select' || row.type === 'cascader'">
        <DuxFormItem label="地址">
          <NInput v-model:value="model.path" placeholder="请输入接口相对地址" />
        </DuxFormItem>
      </template>
    </DuxFormLayout>

    <template #footer>
      <NButton @click="close">
        取消
      </NButton>
      <NButton type="primary" @click="submit">
        确定
      </NButton>
    </template>
  </DuxModalPage>
</template>

<style scoped></style>
