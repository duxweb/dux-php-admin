<script setup lang="ts">
import type { TableColumn } from '@duxweb/dvha-naiveui'
import { useCheck, useI18n } from '@duxweb/dvha-core'
import { DuxFormItem, DuxFormLayout, DuxImageCrop, DuxPanelCard, DuxSettingForm, DuxTable, useTableColumn } from '@duxweb/dvha-pro'
import { NButton, NInput, NSelect, NTabPane, NTag, NTooltip } from 'naive-ui'
import { h, ref } from 'vue'

const form = ref<Record<string, any>>({
  username: 'admin',
})

const columns = useTableColumn()

const loginColumns: TableColumn[] = [
  {
    title: '客户端',
    key: 'browser',
    width: 220,
    render: columns.renderMedia({
      title: 'browser',
      desc: 'platform',
    }),
  },
  {
    title: 'IP',
    key: 'ip',
    width: 150,
  },
  {
    title: '状态',
    key: 'status',
    width: 100,
    render: columns.renderStatus({
      maps: {
        success: {
          label: '成功',
          value: 1,
        },
        error: {
          label: '失败',
          value: 0,
        },
      },
    }),
  },
  {
    title: '登录时间',
    key: 'time',
    width: 200,
  },
]

const i18n = useI18n()

const langList = i18n.getLocales()?.map((key) => {
  return {
    label: i18n.t(`locale.${key}`),
    value: key,
  }
})

const check = useCheck()

const operateColumns: TableColumn[] = [
  {
    title: '请求地址',
    key: 'request_method',
    width: 220,
    ellipsis: true,
    render: (rowData: Record<string, any>) => {
      let type: 'default' | 'error' | 'primary' | 'info' | 'success' | 'warning' = 'info'
      switch (rowData.request_method) {
        case 'GET':
          type = 'info'
          break
        case 'POST':
          type = 'success'
          break
        case 'PUT':
          type = 'info'
          break
        case 'PATCH':
          type = 'warning'
          break
        case 'DELETE':
          type = 'error'
          break
      }

      return h('div', {
        class: 'flex flex-col gap-1 leading-4',
      }, [
        h('div', {
          class: 'flex gap-2 items-center',
        }, [
          h(NTag, { type, size: 'small' }, () => rowData.request_method),
          h(NTooltip, {}, {
            default: () => rowData.request_url,
            trigger: () => h('div', { class: 'truncate' }, rowData.request_url),
          }),
        ]),
      ])
    },
  },
  {
    title: '路由名 | 请求时间',
    key: 'route_title',
    width: 200,
    ellipsis: true,
    render: columns.renderMedia({
      title: 'route_name',
      desc: 'request_time',
    }),
  },
  {
    title: '请求设备',
    key: 'client_ip',
    width: 100,
    ellipsis: true,
    render: columns.renderMedia({
      title: 'client_ip',
      desc: 'client_device',
    }),
  },
  {
    title: '操作时间',
    key: 'time',
    width: 200,
  },
]
</script>

<template>
  <DuxSettingForm v-slot="result" :data="form" default-tab="base" path="system/profile" action="edit" tabs>
    <NTabPane name="base" tab="个人资料" display-directive="show">
      <DuxPanelCard title="个人资料" description="设置个人资料信息">
        <template #actions>
          <NButton
            secondary type="primary" @click="async () => {
              await result.onSubmit()
              check.mutate()
            }"
          >
            保存信息
          </NButton>
        </template>
        <DuxFormLayout class="px-4" divider label-placement="setting" label-align="right">
          <DuxFormItem label="头像" description="请上传 1M 以内的头像" path="avatar">
            <DuxImageCrop v-model:value="form.avatar" />
          </DuxFormItem>
          <DuxFormItem label="昵称" description="账号显示的名称" path="nickname">
            <NInput v-model:value="form.nickname" />
          </DuxFormItem>
          <DuxFormItem label="手机" description="可以联系到您的手机号" path="tel">
            <NInput v-model:value="form.tel" />
          </DuxFormItem>
          <DuxFormItem label="邮箱" description="可以联系到您的邮箱" path="email">
            <NInput v-model:value="form.email" />
          </DuxFormItem>
          <DuxFormItem label="语言" description="界面语言，不选择可手动切换" path="lang">
            <NSelect v-model:value="form.lang" :options="langList" clearable />
          </DuxFormItem>
          <DuxFormItem label="密码" description="不修改密码请留空" path="password">
            <NInput v-model:value="form.password" type="password" :input-props="{ autocomplete: 'new-password' }" />
          </DuxFormItem>
        </DuxFormLayout>
      </DuxPanelCard>
    </NTabPane>
    <NTabPane name="login" tab="登录日志">
      <DuxPanelCard :bordered="false" title="登录日志" description="个人账户登录日志">
        <DuxTable :columns="loginColumns" path="system/login" />
      </DuxPanelCard>
    </NTabPane>
    <NTabPane name="operate" tab="操作日志">
      <DuxPanelCard :bordered="false" title="操作日志" description="个人账户操作日志">
        <DuxTable :columns="operateColumns" path="system/operate" />
      </DuxPanelCard>
    </NTabPane>
  </DuxSettingForm>
</template>

<style scoped></style>
