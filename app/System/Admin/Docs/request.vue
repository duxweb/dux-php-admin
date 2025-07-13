<script setup>
import { DuxCard, DuxCodeEditor } from '@duxweb/dvha-pro'
import {
  NAutoComplete,
  NButton,
  NCard,
  NDynamicInput,
  NInput,
  NInputNumber,
  NSelect,
  NSwitch,
  NTabPane,
  NTabs,
  NTag,
  useMessage,
} from 'naive-ui'

import { ref, watch } from 'vue'
import PanelCard from './panelCard.vue'
import { useRequestStore } from './store/request'

const props = defineProps({
  info: {
    type: Object,
    default: () => ({}),
  },
})

const requestStore = useRequestStore()
const message = useMessage()

// 设置对话框状态
const showSettings = ref(false)
const showResult = ref(false)

// 常用请求头选项
const commonHeaders = [
  'Accept',
  'Accept-Encoding',
  'Accept-Language',
  'Authorization',
  'Cache-Control',
  'Content-Type',
  'Cookie',
  'Host',
  'Origin',
  'Referer',
  'User-Agent',
  'X-Requested-With',
  'X-CSRF-Token',
  'X-API-Key',
  'X-Auth-Token',
  'X-Client-Version',
  'X-Device-ID',
  'X-Platform',
  'X-Request-ID',
  'X-Timestamp',
]

// 执行请求
async function executeRequest() {
  try {
    await requestStore.executeRequest()
    showResult.value = true
    if (requestStore.result) {
      message.success('请求执行成功')
    }
  }
  catch {
    message.error('请求执行失败')
  }
}

// 监听 info 变化
watch(() => props.info, (newInfo) => {
  if (newInfo?.api) {
    requestStore.setCurrentApi(newInfo.api)
  }
}, { immediate: true })

// 请求体类型选项
const bodyTypes = [
  { label: 'JSON', value: 'json' },
  { label: 'Form Data', value: 'form' },
  { label: 'Raw Text', value: 'raw' },
]

// 字段类型选项
const fieldTypes = [
  { label: 'Text', value: 'text' },
  { label: 'File', value: 'file' },
]

// 状态颜色
function getStatusColor(status) {
  if (status >= 200 && status < 300)
    return 'success'
  if (status >= 300 && status < 400)
    return 'warning'
  if (status >= 400)
    return 'error'
  return 'default'
}

// 获取格式化的响应数据
function getFormattedResponse() {
  const data = requestStore.result?.data
  if (!data)
    return ''

  if (typeof data === 'string') {
    try {
      // 尝试解析为 JSON
      const parsed = JSON.parse(data)
      return JSON.stringify(parsed, null, 2)
    }
    catch {
      // 如果不是 JSON，返回原始字符串
      return data
    }
  }

  // 如果已经是对象，格式化为 JSON
  return JSON.stringify(data, null, 2)
}

// 获取原始响应数据
function getRawResponse() {
  const data = requestStore.result?.data
  if (!data)
    return ''

  if (typeof data === 'string') {
    return data
  }

  // 如果是对象，转换为字符串
  return JSON.stringify(data)
}

// 获取响应数据的语言类型
function getResponseLanguage() {
  const data = requestStore.result?.data
  if (!data)
    return 'text'

  // 检查 Content-Type
  const contentType = requestStore.result?.headers?.['content-type'] || ''

  if (contentType.includes('application/json')) {
    return 'json'
  }
  else if (contentType.includes('text/html')) {
    return 'html'
  }
  else if (contentType.includes('text/xml') || contentType.includes('application/xml')) {
    return 'xml'
  }
  else if (contentType.includes('text/css')) {
    return 'css'
  }
  else if (contentType.includes('text/javascript') || contentType.includes('application/javascript')) {
    return 'javascript'
  }

  // 如果是对象类型，使用 JSON
  if (typeof data === 'object') {
    return 'json'
  }

  // 尝试解析字符串是否为 JSON
  if (typeof data === 'string') {
    try {
      JSON.parse(data)
      return 'json'
    }
    catch {
      return 'text'
    }
  }

  return 'text'
}

// 获取请求体字符串
function getRequestBodyString() {
  const body = requestStore.requestInfo.body
  if (!body)
    return ''

  if (typeof body === 'string') {
    try {
      return JSON.stringify(JSON.parse(body), null, 2)
    }
    catch {
      return body
    }
  }

  if (body instanceof FormData) {
    const formObj = {}
    for (const [key, value] of body.entries()) {
      if (value instanceof File) {
        formObj[key] = `[File: ${value.name}]`
      }
      else {
        formObj[key] = value
      }
    }
    return JSON.stringify(formObj, null, 2)
  }

  if (body instanceof URLSearchParams) {
    const params = {}
    for (const [key, value] of body) {
      params[key] = value
    }
    return JSON.stringify(params, null, 2)
  }

  return JSON.stringify(body, null, 2)
}

// 处理文件上传事件
function handleFileChange(event, value) {
  const target = event.target
  if (target?.files?.[0]) {
    value.value = target.files[0]
  }
}

// 过滤请求头选项
function filterHeaders(query) {
  return commonHeaders.filter(h => h.toLowerCase().includes(query.toLowerCase()))
}
</script>

<template>
  <div class="h-full flex flex-col gap-2">
    <DuxCard class="flex-none">
      <div class="flex items-center gap-2 rounded justify-between py-2 px-4 bg-default">
        <div class="text-primary">
          模拟请求
        </div>
        <div class="flex gap-2">
          <NButton
            tertiary
            @click="showSettings = !showSettings"
          >
            <template #icon>
              <div class="i-tabler:settings" />
            </template>
          </NButton>
          <NButton
            :loading="requestStore.loading"
            :disabled="!requestStore.currentApi"
            @click="executeRequest"
          >
            <template #icon>
              <div class="i-tabler:send" />
            </template>
            发送
          </NButton>
        </div>
      </div>
    </DuxCard>

    <div class="flex-1 min-h-0 overflow-auto">
      <div class="flex flex-col gap-2">
        <!-- 设置面板 -->
        <NCard v-if="showSettings" title="请求设置">
          <div class="space-y-4">
            <!-- 基础设置 -->
            <div class="grid grid-cols-2 gap-4">
              <div class="space-y-2">
                <div class="text-sm font-medium">
                  Base URL
                </div>
                <NInput
                  v-model:value="requestStore.config.baseURL"
                  placeholder="https://api.example.com"
                />
              </div>
              <div class="space-y-2">
                <div class="text-sm font-medium">
                  超时时间 (ms)
                </div>
                <NInputNumber
                  v-model:value="requestStore.config.timeout"
                  placeholder="30000"
                />
              </div>
            </div>

            <!-- API 签名设置 -->
            <div class="grid grid-cols-2 gap-4">
              <div class="space-y-2">
                <div class="text-sm font-medium">
                  Secret ID
                </div>
                <NInput
                  v-model:value="requestStore.config.secretID"
                  placeholder="请输入 Secret ID"
                  type="password"
                  show-password-on="mousedown"
                />
              </div>
              <div class="space-y-2">
                <div class="text-sm font-medium">
                  Secret Key
                </div>
                <NInput
                  v-model:value="requestStore.config.secretKey"
                  placeholder="请输入 Secret Key"
                  type="password"
                  show-password-on="mousedown"
                />
              </div>
            </div>

            <!-- 默认请求头 -->
            <div class="space-y-2">
              <div class="text-sm font-medium">
                默认请求头
              </div>
              <NDynamicInput
                v-slot="{ value }"
                v-model:value="requestStore.config.headers"
                :min="0"
                :on-create="() => ({ key: '', value: '', enabled: true })"
              >
                <div class="flex gap-2 flex-1 items-center">
                  <NSwitch v-model:value="value.enabled" size="small" />
                  <NAutoComplete
                    v-model:value="value.key"
                    :options="commonHeaders"
                    class="flex-1"
                    placeholder="请求头名称"
                    :filter-option="false"
                    @search="filterHeaders"
                  />
                  <NInput
                    v-model:value="value.value"
                    class="flex-1"
                    placeholder="请求头值"
                  />
                </div>
              </NDynamicInput>
            </div>
          </div>
        </NCard>

        <DuxCard v-if="requestStore.currentApi" size="small">
          <NTabs
            type="line" size="small" :default-value="requestStore?.pathParams?.length > 0 ? 'path' : 'query'"
            display-directive="show"
          >
            <NTabPane v-if="requestStore?.pathParams?.length > 0" name="path" tab="Path 参数">
              <NDynamicInput
                v-slot="{ value }"
                v-model:value="requestStore.pathParams"
                :min="0"
                :on-create="() => ({ key: '', value: '' })"
              >
                <div class="grid grid-cols-2 gap-2 flex-1">
                  <NInput
                    v-model:value="value.key"
                    placeholder="参数名"
                    readonly
                  />
                  <NInput
                    v-model:value="value.value"
                    placeholder="参数值"
                  />
                </div>
              </NDynamicInput>
            </NTabPane>
            <NTabPane name="query" tab="Query 参数">
              <NDynamicInput
                v-slot="{ value }"
                v-model:value="requestStore.queryParams"
                :min="0"
                :on-create="() => ({ key: '', value: '' })"
              >
                <div class="grid grid-cols-2 gap-2 flex-1">
                  <NInput
                    v-model:value="value.key"
                    placeholder="参数名"
                  />
                  <NInput
                    v-model:value="value.value"
                    placeholder="参数值"
                  />
                </div>
              </NDynamicInput>
            </NTabPane>
            <NTabPane name="header" tab="Header 参数">
              <NDynamicInput
                v-slot="{ value }"
                v-model:value="requestStore.headerParams"
                :min="0"
                :on-create="() => ({ key: '', value: '' })"
              >
                <div class="flex-1 flex gap-2">
                  <NAutoComplete
                    v-model:value="value.key"
                    :options="commonHeaders"
                    placeholder="请求头名称"
                    size="small"
                    class="flex-1"
                    :filter-option="false"
                    @search="filterHeaders"
                  />
                  <NInput
                    v-model:value="value.value"
                    placeholder="请求头值"
                    size="small"
                    class="flex-1"
                  />
                </div>
              </NDynamicInput>
            </NTabPane>
          </NTabs>
        </DuxCard>

        <PanelCard
          v-if="requestStore.currentApi?.method && ['POST', 'PUT', 'PATCH'].includes(requestStore.currentApi.method?.toUpperCase())"
          color="primary" icon="i-tabler:home" :count="0" title="Body 参数"
        >
          <div class="flex flex-col gap-4 p-4">
            <NSelect
              v-model:value="requestStore.bodyParams.type"
              :options="bodyTypes"
              @update:value="requestStore.switchBodyType"
            />
            <div v-if="requestStore.bodyParams.type === 'json'">
              <DuxCodeEditor
                v-model:value="requestStore.bodyParams.content"
                language="json"
                placeholder="请输入 JSON 内容"
                class="min-h-40"
              />
            </div>

            <div v-else-if="requestStore.bodyParams.type === 'form'">
              <NDynamicInput
                v-slot="{ value }"
                v-model:value="requestStore.formFields"
                :min="0"
                :on-create="() => ({ key: '', value: '', type: 'text' })"
              >
                <div class="flex-1 flex gap-2 items-center">
                  <div class="flex-1">
                    <NInput
                      v-model:value="value.key"
                      placeholder="字段名"
                    />
                  </div>
                  <div class="w-30">
                    <NSelect
                      v-model:value="value.type"
                      :options="fieldTypes"
                      class="w-full"
                    />
                  </div>
                  <div
                    v-if="value.type !== 'file'"
                    class="flex-1"
                  >
                    <NInput
                      v-model:value="value.value"
                      placeholder="字段值"
                    />
                  </div>
                  <div v-else class="flex-1 text-sm text-gray-500">
                    <input
                      type="file"
                      class="w-full px-4 py-1.5 border border-accented rounded focus:border-primary hover:border-primary transition-all"
                      @change="(e) => handleFileChange(e, value)"
                    >
                  </div>
                </div>
              </NDynamicInput>
            </div>

            <div v-else-if="requestStore.bodyParams.type === 'raw'">
              <DuxCodeEditor
                v-model:value="requestStore.bodyParams.content"
                language="text"
                placeholder="请输入原始文本内容"
                class="min-h-40"
              />
            </div>
          </div>
        </PanelCard>

        <!-- 请求结果 -->
        <NCard v-if="showResult && (requestStore.result || requestStore.error)" size="small" title="请求结果">
          <div v-if="requestStore.result" class="space-y-4">
            <!-- 状态信息 -->
            <div class="flex items-center gap-4">
              <div class="flex items-center gap-2">
                <span class="text-sm">状态：</span>
                <NTag :type="getStatusColor(requestStore.result.status)">
                  {{ requestStore.result.status }} {{ requestStore.result.statusText }}
                </NTag>
              </div>
              <div class="text-sm text-gray-500">
                耗时：{{ requestStore.result.time }}ms
              </div>
            </div>

            <!-- 请求URL -->
            <div class="text-sm">
              <span class="font-medium">请求URL：</span>
              <span class="text-gray-600 break-all">{{ requestStore.requestInfo.url }}</span>
            </div>

            <!-- 响应数据 -->
            <NTabs type="line" size="small" default-value="response-formatted">
              <NTabPane name="response-formatted" tab="格式化">
                <DuxCodeEditor
                  :value="getFormattedResponse()"
                  :language="getResponseLanguage()"
                  readonly
                  class="min-h-40"
                />
              </NTabPane>
              <NTabPane name="response-raw" tab="原始数据">
                <DuxCodeEditor
                  :value="getRawResponse()"
                  language="text"
                  readonly
                  class="min-h-40"
                />
              </NTabPane>
              <NTabPane name="request-body" tab="请求数据">
                <DuxCodeEditor
                  :value="getRequestBodyString()"
                  language="json"
                  readonly
                  class="min-h-20"
                />
              </NTabPane>
            </NTabs>

            <!-- 响应头 -->
            <NTabs type="line" size="small" default-value="response-headers">
              <NTabPane name="response-headers" tab="响应头">
                <DuxCodeEditor
                  :value="JSON.stringify(requestStore.result.headers, null, 2)"
                  language="json"
                  readonly
                  class="min-h-20"
                />
              </NTabPane>
              <NTabPane name="request-headers" tab="请求头">
                <DuxCodeEditor
                  :value="JSON.stringify(requestStore.requestInfo.headers, null, 2)"
                  language="json"
                  readonly
                  class="min-h-20"
                />
              </NTabPane>
            </NTabs>
          </div>

          <div v-else-if="requestStore.error" class="text-red-500">
            {{ requestStore.error }}
          </div>
        </NCard>

        <!-- 空状态 -->
        <div v-if="!requestStore.currentApi" class="flex-1 flex items-center justify-center">
          <div class="text-center text-gray-500">
            <div class="text-lg mb-2">
              请选择一个 API
            </div>
            <div class="text-sm">
              选择左侧的 API 接口开始测试
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
