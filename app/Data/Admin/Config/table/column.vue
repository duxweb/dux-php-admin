<script setup>
import { DuxFormItem, DuxModalPage, DuxFormLayout, DuxIconPicker } from '@duxweb/dvha-pro'
import { NInput, NInputNumber, NRadio, NRadioGroup, NButton, NSwitch, NSelect, NDynamicInput } from 'naive-ui'
import { ref, toRef } from 'vue'

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
  ...props.row.setting
})

const submit = () => {
  props.onConfirm(model.value)
}

const close = () => {
  props.onClose()
}

</script>

<template>
  <DuxModalPage @close="close">
    <DuxFormLayout label-placement="top">
      <DuxFormItem v-if="row.type === 'hidden'" label="百分比" description="隐藏中间字符串百分比">
        <NInputNumber :default-value="30" v-model:value="model.percent" placeholder="请输入百分比" suffix="%" />
      </DuxFormItem>

      <template  v-if="row.type === 'image'">
        <DuxFormItem label="宽度">
          <NInputNumber :default-value="40" v-model:value="model.width" placeholder="请输入宽度" suffix="px" />
        </DuxFormItem>
        <DuxFormItem label="高度">
          <NInputNumber :default-value="40" v-model:value="model.height" placeholder="请输入高度" suffix="px" />
        </DuxFormItem>
      </template>

      <template  v-if="row.type === 'media'">
        <DuxFormItem label="头像">
          <NSwitch v-model:value="model.avatar" />
        </DuxFormItem>
        <DuxFormItem label="图片字段">
          <NInput v-model:value="model.image" placeholder="图片字段" />
        </DuxFormItem>
        <DuxFormItem label="标题字段">
          <NInput v-model:value="model.title" placeholder="图片字段" />
        </DuxFormItem>
        <DuxFormItem label="描述字段">
          <NInput v-model:value="model.desc" placeholder="描述字段" />
        </DuxFormItem>
      </template>


      <template  v-if="row.type === 'status'">
        <DuxFormItem label="状态类型">
          <NDynamicInput v-model:value="model.config" :on-create="(item) => ({
            type: 'success',
            label: '成功',
            value: 0,
          })">
            <template #default="{ value }">
              <div class="flex gap-2 items-center">
                <NSelect v-model:value="value.type" :options="[
                  {
                    label: '成功',
                    value: 'success',
                  },
                  {
                    label: '失败',
                    value: 'error',
                  },
                  {
                    label: '警告',
                    value: 'warning',
                  },
                  {
                    label: '默认',
                    value: 'info',
                  }
                ]" />
                <NInput v-model:value="model.label" placeholder="状态名" />
                <NInput v-model:value="model.value" placeholder="状态值" />
              </div>
            </template>
          </NDynamicInput>
        </DuxFormItem>
      </template>

       <template  v-if="row.type === 'color'">
        <DuxFormItem label="状态类型">
          <NDynamicInput v-model:value="model.config" :on-create="(item) => ({
            type: 'success',
            label: '成功',
            icon: '',
            value: 0,
          })">
            <template #default="{ value }">
              <div class="flex gap-2 items-center">
                <NSelect v-model:value="value.type" :options="[
                  {
                    label: '成功',
                    value: 'success',
                  },
                  {
                    label: '失败',
                    value: 'error',
                  },
                  {
                    label: '警告',
                    value: 'warning',
                  },
                  {
                    label: '默认',
                    value: 'info',
                  }
                ]" />
                <DuxIconPicker v-model:value="model.icon" />
                <NInput v-model:value="model.label" placeholder="状态名" />
                <NInput v-model:value="model.value" placeholder="状态值" />
              </div>
            </template>
          </NDynamicInput>
        </DuxFormItem>
      </template>

      <template  v-if="row.type === 'maps'">
        <DuxFormItem label="映射类型">
          <NDynamicInput v-model:value="model.config" :on-create="(item) => ({
            label: '',
            key: '',
            icon: '',
          })">
            <template #default="{ value }">
              <div class="flex gap-2 items-center">
                <NInput v-model:value="model.label" placeholder="名称" />
                <NInput v-model:value="model.value" placeholder="字段" />
                <DuxIconPicker v-model:value="model.icon" />
              </div>
            </template>
          </NDynamicInput>
        </DuxFormItem>
      </template>

    </DuxFormLayout>

    <template #footer>
      <NButton  @click="close">取消</NButton>
      <NButton type="primary" @click="submit">确定</NButton>
    </template>
  </DuxModalPage>
</template>

<style scoped></style>
