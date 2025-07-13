<script setup>
import { useOne } from '@duxweb/dvha-core'
import { DuxSelect } from '@duxweb/dvha-naiveui'
import { DuxFormItem, DuxImageCrop, DuxImageUpload, DuxLevel, DuxModalForm } from '@duxweb/dvha-pro'
import { NDatePicker, NInput, NInputNumber, NSelect, NSwitch, NTimePicker } from 'naive-ui'
import { computed, ref } from 'vue'

const props = defineProps({
  id: {
    type: [String, Number],
    required: false,
  },
})

const model = ref({
  level_id: null,
  nickname: '',
  email: '',
  tel: '',
  tel_code: '',
  password: '',
  avatar: '',
  sex: 0,
  birthday: null,
  status: true,
  area: [],
  info: {},
})

const { data } = useOne({
  path: 'member/setting',
})

const config = computed(() => data.value?.data?.extend || [])
</script>

<template>
  <DuxModalForm :id="props.id" path="member/user" :data="model" label-placement="top">
    <div class="grid grid-cols-2 gap-4">
      <DuxFormItem label="头像">
        <DuxImageCrop v-model:value="model.avatar" />
      </DuxFormItem>
      <DuxFormItem label="封面图">
        <DuxImageUpload v-model:value="model.cover" />
      </DuxFormItem>
      <DuxFormItem label="昵称" required path="nickname">
        <NInput v-model:value="model.nickname" placeholder="请输入昵称" />
      </DuxFormItem>
      <DuxFormItem label="用户等级" required path="level_id">
        <DuxSelect v-model:value="model.level_id" path="member/level" label-field="name" value-field="id" />
      </DuxFormItem>

      <DuxFormItem label="手机号" required path="tel" description="国内手机号无需加区号">
        <div class="flex gap-2 flex-1">
          <div class="flex-1">
            <NInput v-model:value="model.tel" placeholder="请输入手机号" />
          </div>
          <div class="flex-none w-15">
            <NInput v-model:value="model.tel_code" placeholder="+86" />
          </div>
        </div>
      </DuxFormItem>
      <DuxFormItem label="邮箱" path="email">
        <NInput v-model:value="model.email" placeholder="请输入邮箱" />
      </DuxFormItem>

      <DuxFormItem label="性别">
        <NSelect v-model:value="model.sex" :options="[{ label: '保密', value: 0 }, { label: '男', value: 1 }, { label: '女', value: 2 }]" />
      </DuxFormItem>
      <DuxFormItem label="生日">
        <NDatePicker v-model:formatted-value="model.birthday" type="date" value-format="yyyy-MM-dd" />
      </DuxFormItem>
    </div>
    <DuxFormItem label="介绍">
      <NInput v-model:value="model.introduction" type="textarea" path="introduction" />
    </DuxFormItem>
    <DuxFormItem label="地区">
      <DuxLevel v-model:value="model.area" path="area" :max-level="3" />
    </DuxFormItem>

    <DuxFormItem v-for="(item, key) in config" :key="key" :label="item.name">
      <NInput v-if="item?.type === 'input'" v-model:value="model.info[item.field]" />
      <NInputNumber v-if="item?.type === 'number'" v-model:value="model.info[item.field]" />
      <NDatePicker v-if="item?.type === 'date'" v-model:formatted-value="model.info[item.field]" value-format="yyyy-MM-dd" />
      <NTimePicker v-if="item?.type === 'time'" v-model:formatted-value="model.info[item.field]" value-format="HH:mm:ss" />
      <NDatePicker v-if="item?.type === 'datetime'" v-model:formatted-value="model.info[item.field]" type="datetime" value-format="yyyy-MM-dd HH:mm:ss" />
      <DuxLevel v-if="item?.type === 'area'" v-model:value="model.info[item.field]" path="area" />
    </DuxFormItem>

    <DuxFormItem label="密码" description="不修改请留空">
      <NInput v-model:value="model.password" type="password" placeholder="请输入密码" />
    </DuxFormItem>
    <DuxFormItem label="状态">
      <NSwitch v-model:value="model.status" />
    </DuxFormItem>
  </DuxModalForm>
</template>

<style scoped></style>
