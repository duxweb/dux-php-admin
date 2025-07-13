<script setup>
import { DuxSelect } from '@duxweb/dvha-naiveui'
import { DuxAiEditor, DuxDynamicData, DuxFormItem, DuxFormLayout, DuxImageCrop, DuxImageUpload, DuxPanelCard, DuxSettingForm } from '@duxweb/dvha-pro'
import { NButton, NInput, NInputNumber, NSwitch, NTabPane } from 'naive-ui'
import { ref } from 'vue'

const model = ref({
  default_level: 1,
  default_avatar: '',
  sms_login: true,
  email_login: true,
  register: true,
  comment_interval: 60,
  sms_tpl: undefined,
  sms_global_tpl: undefined,
  email_tpl: undefined,
  service_phone: '',
  service_qrcode: '',
})
</script>

<template>
  <DuxSettingForm v-slot="result" :data="model" default-tab="base" path="member/setting" action="edit" tabs>
    <NTabPane name="base" tab="基础设置" display-directive="show" class="flex flex-col gap-4">
      <DuxPanelCard title="用户设置" description="会员系统的基本设置">
        <template #actions>
          <NButton secondary type="primary" @click="() => result.onSubmit()">
            保存设置
          </NButton>
        </template>
        <DuxFormLayout class="px-4" divider label-placement="setting">
          <DuxFormItem label="默认头像" description="新注册用户的默认头像" path="default_avatar">
            <DuxImageCrop v-model:value="model.default_avatar" />
          </DuxFormItem>
          <DuxFormItem label="默认等级" description="新注册用户的默认等级" path="default_level">
            <DuxSelect v-model:value="model.default_level" path="member/level" label-field="name" value-field="id" :pagination="false" />
          </DuxFormItem>
          <DuxFormItem label="评论间隔" description="用户评论时间间隔限制（秒）" path="comment_interval">
            <NInputNumber v-model:value="model.comment_interval" class="flex-1" :min="0" placeholder="60" />
          </DuxFormItem>
        </DuxFormLayout>
      </DuxPanelCard>

      <DuxPanelCard title="客服设置" description="客服联系方式相关配置">
        <DuxFormLayout class="px-4" divider label-placement="setting">
          <DuxFormItem label="客服电话" description="客服联系电话号码" path="service_phone">
            <NInput v-model:value="model.service_phone" placeholder="请输入客服电话" />
          </DuxFormItem>
          <DuxFormItem label="客服二维码" description="客服微信二维码图片" path="service_qrcode">
            <DuxImageUpload v-model:value="model.service_qrcode" />
          </DuxFormItem>
        </DuxFormLayout>
      </DuxPanelCard>
    </NTabPane>
    <NTabPane name="register" tab="登录设置" display-directive="show" class="flex flex-col gap-4">
      <DuxPanelCard title="注册设置" description="用户注册相关设置">
        <template #actions>
          <NButton secondary type="primary" @click="() => result.onSubmit()">
            保存设置
          </NButton>
        </template>
        <DuxFormLayout class="px-4" divider label-placement="setting">
          <DuxFormItem label="允许注册" description="是否允许用户注册" path="register">
            <NSwitch v-model:value="model.register" />
          </DuxFormItem>
        </DuxFormLayout>
      </DuxPanelCard>
      <DuxPanelCard title="登录设置" description="用户登录相关设置">
        <DuxFormLayout class="px-4" divider label-placement="setting">
          <DuxFormItem label="手机号登录" description="是否开启手机号登录" path="tel_login">
            <NSwitch v-model:value="model.tel_login" />
          </DuxFormItem>
          <DuxFormItem label="邮箱登录" description="是否开启邮箱登录" path="email_login">
            <NSwitch v-model:value="model.email_login" />
          </DuxFormItem>
          <DuxFormItem label="验证码登录" description="是否开启验证码登录/注册" path="code_login">
            <NSwitch v-model:value="model.code_login" />
          </DuxFormItem>
        </DuxFormLayout>
      </DuxPanelCard>
    </NTabPane>
    <NTabPane name="communication" tab="通讯设置" display-directive="show">
      <DuxPanelCard title="短信设置" description="短信服务相关配置">
        <template #actions>
          <NButton secondary type="primary" @click="() => result.onSubmit()">
            保存设置
          </NButton>
        </template>
        <DuxFormLayout class="px-4" divider label-placement="setting">
          <DuxFormItem label="国内短信模板" description="国内短信发送模板ID" path="sms_tpl">
            <DuxSelect v-model:value="model.sms_tpl" path="send/sms" label-field="name" value-field="id" placeholder="请选择短信模板" />
          </DuxFormItem>
          <DuxFormItem label="国际短信模板" description="国际短信发送模板ID" path="sms_global_tpl">
            <DuxSelect v-model:value="model.sms_global_tpl" path="send/sms" label-field="name" value-field="id" placeholder="请选择短信模板" />
          </DuxFormItem>
        </DuxFormLayout>
      </DuxPanelCard>
      <DuxPanelCard title="邮件设置" description="邮件服务相关配置" class="mt-4">
        <DuxFormLayout class="px-4" divider label-placement="setting">
          <DuxFormItem label="邮件模板" description="邮件发送模板选择" path="email_tpl">
            <DuxSelect v-model:value="model.email_tpl" path="send/email" label-field="name" value-field="id" placeholder="请选择邮件模板" />
          </DuxFormItem>
        </DuxFormLayout>
      </DuxPanelCard>
    </NTabPane>
    <NTabPane name="service" tab="服务设置" display-directive="show" class="flex flex-col gap-4">
      <DuxPanelCard title="客服设置" description="客服联系方式相关配置">
        <template #actions>
          <NButton secondary type="primary" @click="() => result.onSubmit()">
            保存设置
          </NButton>
        </template>
        <DuxFormLayout class="p-4" label-placement="top">
          <DuxFormItem label="用户协议" description="用户协议内容" path="agreement">
            <DuxAiEditor v-model:value="model.agreement" placeholder="请输入用户协议内容" />
          </DuxFormItem>
          <DuxFormItem label="隐私协议" description="隐私协议内容" path="privacy">
            <DuxAiEditor v-model:value="model.privacy" placeholder="请输入隐私协议内容" />
          </DuxFormItem>
          <DuxFormItem label="关于我们" description="关于我们内容" path="about">
            <DuxAiEditor v-model:value="model.about" placeholder="请输入关于我们内容" />
          </DuxFormItem>
        </DuxFormLayout>
      </DuxPanelCard>
    </NTabPane>
    <NTabPane name="info" tab="资料设置" display-directive="show" class="flex flex-col gap-4">
      <DuxPanelCard title="扩展资料" description="设置用户扩展资料信息">
        <template #actions>
          <NButton secondary type="primary" @click="() => result.onSubmit()">
            保存设置
          </NButton>
        </template>
        <DuxFormLayout class="p-4" label-placement="top">
          <DuxDynamicData
            v-model:value="model.extend" :columns="[
              {
                title: '名称',
                key: 'name',
                schema: {
                  tag: 'n-input',
                  attrs: {
                    'v-model:value': 'row.name',
                  },
                },
              },
              {
                title: '类型',
                key: 'type',
                schema: {
                  tag: 'n-select',
                  attrs: {
                    'v-model:value': 'row.type',
                    'options': [
                      {
                        label: '文本',
                        value: 'input',
                      },
                      {
                        label: '数字',
                        value: 'number',
                      },
                      {
                        label: '日期',
                        value: 'date',
                      },
                      {
                        label: '时间',
                        value: 'time',
                      },
                      {
                        label: '日期时间',
                        value: 'datetime',
                      },
                      {
                        label: '地区',
                        value: 'area',
                      },
                    ],
                  },
                },
              },
              {
                title: '字段',
                key: 'field',
                schema: {
                  tag: 'n-input',
                  attrs: {
                    'v-model:value': 'row.field',

                  },
                },
              },
            ]"
          />
        </DuxFormLayout>
      </DuxPanelCard>
    </NTabPane>
  </DuxSettingForm>
</template>

<style scoped></style>
