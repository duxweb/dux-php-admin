<script setup lang="ts">
import { useAuthStore, useCustomMutation, useManage } from '@duxweb/dvha-core'
import { DuxModalPage, useDialog } from '@duxweb/dvha-pro'
import { fetchEventSource } from '@microsoft/fetch-event-source'
import { NButton, NCard, NCheckbox, NScrollbar, NSpin, NTabPane, NTabs, NTag, NTime, useMessage } from 'naive-ui'
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'

const props = defineProps<{
  onClose: () => void
  item: Record<string, any>
  cloudServer?: string
  onSuccess?: () => void | Promise<void>
}>()

const message = useMessage()
const manage = useManage()
const dialog = useDialog()
const authStore = useAuthStore()
const { mutateAsync } = useCustomMutation()

const detailLoading = ref(false)
const detailData = ref<Record<string, any>>({
  ...(props.item || {}),
})
const actionRunning = ref(false)
const runningAction = ref<'install' | 'upgrade' | 'uninstall' | ''>('')
const actionLogs = ref<string[]>([])
const actionTasks = ref({
  composer: true,
  sync_menu: true,
  sync_db: true,
})

let streamController: AbortController | null = null

const detailAction = computed(() => {
  return detailData.value?.installed ? 'upgrade' : 'install'
})

const detailActionLabel = computed(() => {
  return detailData.value?.installed ? '升级应用' : '安装应用'
})

const logoColors = [
  '#3b82f6',
  '#10b981',
  '#f59e0b',
  '#ef4444',
  '#8b5cf6',
  '#06b6d4',
  '#ec4899',
  '#14b8a6',
  '#f97316',
  '#6366f1',
]

function getLogoColor(name: string): string {
  let hash = 0
  for (let i = 0; i < name.length; i++) {
    hash = name.charCodeAt(i) + ((hash << 5) - hash)
  }
  return logoColors[Math.abs(hash) % logoColors.length]
}

function compareVersion(a: string, b: string): number {
  const normalize = (input: string) => input.toLowerCase().replace(/^v/, '')
  const left = normalize(a).split(/[.\-_]/)
  const right = normalize(b).split(/[.\-_]/)
  const len = Math.max(left.length, right.length)

  for (let i = 0; i < len; i++) {
    const l = left[i] || '0'
    const r = right[i] || '0'
    const lNum = Number(l)
    const rNum = Number(r)

    if (!Number.isNaN(lNum) && !Number.isNaN(rNum)) {
      if (lNum > rNum) {
        return 1
      }
      if (lNum < rNum) {
        return -1
      }
      continue
    }

    const compared = l.localeCompare(r, undefined, { numeric: true, sensitivity: 'base' })
    if (compared !== 0) {
      return compared > 0 ? 1 : -1
    }
  }
  return 0
}

const canUpgrade = computed(() => {
  if (!detailData.value?.installed) {
    return true
  }
  const localVersion = String(detailData.value?.installed_version || '').trim()
  const latestVersion = String(detailData.value?.latest_version || detailData.value?.version || '').trim()
  if (!localVersion || !latestVersion) {
    return true
  }
  return compareVersion(latestVersion, localVersion) > 0
})

const canUninstall = computed(() => {
  const app = String(detailData.value?.app || '').toLowerCase()
  return !['system', 'data'].includes(app)
})

const detailTags = computed(() => {
  return Array.isArray(detailData.value?.tags) ? detailData.value.tags : []
})

const detailReadme = computed(() => {
  const value = detailData.value?.readme || ''
  return typeof value === 'string' ? value : JSON.stringify(value, null, 2)
})

const detailChangelog = computed(() => {
  const value = detailData.value?.changelog || ''
  return typeof value === 'string' ? value : JSON.stringify(value, null, 2)
})

const detailVerAt = computed(() => {
  const value = detailData.value?.ver_at
  if (!value) {
    return null
  }
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) {
    return null
  }
  return date
})

function appendLog(messageText: string) {
  const text = (messageText || '').trim()
  if (!text) {
    return
  }
  actionLogs.value.push(`[${new Date().toLocaleTimeString()}] ${text}`)
}

function appendErrorLog(messageText: string) {
  const text = (messageText || '').trim()
  if (!text) {
    return
  }
  const formatted = `[error] ${text}`
  const lastLog = actionLogs.value.at(-1) || ''
  if (lastLog.includes(formatted)) {
    return
  }
  appendLog(formatted)
}

function closeEventSource() {
  if (streamController) {
    streamController.abort()
    streamController = null
  }
}

function parseEventData(raw: string): Record<string, any> {
  try {
    return JSON.parse(raw || '{}')
  }
  catch {
    return { message: raw || '' }
  }
}

async function handleCompleteEvent(data: Record<string, any>, action: 'install' | 'upgrade' | 'uninstall') {
  appendLog(data.message || '执行完成')
  message.success(action === 'uninstall' ? '卸载成功' : '操作成功')
  const syncMenu = Boolean(data?.result?.tasks_executed?.sync_menu ?? actionTasks.value.sync_menu)
  if (syncMenu) {
    dialog.confirm({
      title: '菜单已同步',
      content: '请刷新浏览器页面查看新菜单',
    }).then(() => {
      window.location.reload()
    }).catch(() => {})
  }
  actionRunning.value = false
  runningAction.value = ''
  closeEventSource()
  await props.onSuccess?.()
  await loadDetail()
}

async function loadDetail() {
  const detailId = props.item?.id
  if (!detailId) {
    return
  }
  detailLoading.value = true
  try {
    const res = await mutateAsync({
      path: `install/store/detail/${detailId}`,
      method: 'get',
      query: {
        cloud_server: props.cloudServer || '',
      },
    })
    detailData.value = {
      ...detailData.value,
      ...(res?.data || {}),
    }
  }
  catch (e: any) {
    message.error(e?.message || '加载应用详情失败')
  }
  finally {
    detailLoading.value = false
  }
}

function runAction(targetAction?: 'install' | 'upgrade' | 'uninstall') {
  const action = targetAction || detailAction.value
  if (action === 'upgrade' && !canUpgrade.value) {
    message.info('当前已是最新版本')
    return
  }
  if (action === 'uninstall' && !canUninstall.value) {
    message.warning('System/Data 模块不允许卸载')
    return
  }
  if (action === 'uninstall') {
    dialog.confirm({
      title: '确认卸载',
      content: '确认卸载该应用吗？',
    }).then(() => {
      void executeAction(action)
    }).catch(() => {})
    return
  }
  void executeAction(action)
}

async function executeAction(action: 'install' | 'upgrade' | 'uninstall') {
  if (!detailData.value?.app || actionRunning.value) {
    return
  }
  actionRunning.value = true
  runningAction.value = action
  actionLogs.value = []
  appendLog('正在准备任务...')
  let handledError = false

  try {
    const prepare = await mutateAsync({
      path: 'install/store/action/prepare',
      method: 'post',
      payload: {
        app: detailData.value.app,
        action,
        cloud_server: props.cloudServer || '',
        tasks: {
          composer: actionTasks.value.composer,
          sync_menu: actionTasks.value.sync_menu,
          sync_db: actionTasks.value.sync_db,
        },
      },
    })
    const token = prepare?.data?.token
    if (!token) {
      throw new Error('任务令牌获取失败')
    }

    const streamUrl = manage.getApiUrl(`install/store/action/stream/${token}`)
    const auth = authStore.getUser()
    if (!auth?.token) {
      throw new Error('登录已失效，请重新登录')
    }

    closeEventSource()
    streamController = new AbortController()

    await fetchEventSource(streamUrl, {
      method: 'GET',
      credentials: 'include',
      headers: {
        Authorization: auth.token,
        Accept: 'text/event-stream',
      },
      signal: streamController.signal,
      openWhenHidden: true,
      onmessage: (event) => {
        const data = parseEventData(event.data)
        if (event.event === 'log') {
          appendLog(data.message || '')
          return
        }
        if (event.event === 'error') {
          handledError = true
          appendErrorLog(data.message || '执行失败')
          message.error(data.message || '执行失败')
          actionRunning.value = false
          runningAction.value = ''
          closeEventSource()
          return
        }
        if (event.event === 'complete') {
          void handleCompleteEvent(data, action)
        }
      },
      onclose: () => {
        if (actionRunning.value) {
          throw new Error('连接已断开')
        }
      },
      onerror: (error) => {
        if (actionRunning.value) {
          handledError = true
          const errorMessage = (error as any)?.message || '执行失败'
          appendErrorLog(errorMessage)
          message.error(errorMessage)
          actionRunning.value = false
          runningAction.value = ''
          closeEventSource()
        }
        throw error
      },
    })
  }
  catch (e: any) {
    if ((e as any)?.name === 'AbortError') {
      return
    }
    if (handledError) {
      return
    }
    appendErrorLog(e?.message || '执行失败')
    message.error(e?.message || '执行失败')
    actionRunning.value = false
    runningAction.value = ''
  }
}

function close() {
  if (actionRunning.value) {
    return
  }
  props.onClose()
}

onMounted(() => {
  loadDetail()
})

onBeforeUnmount(() => {
  closeEventSource()
})
</script>

<template>
  <DuxModalPage :title="detailData.title || detailData.app || '应用详情'" width="900" @close="close">
    <div class="space-y-4">
      <NSpin :show="detailLoading">
        <div class="grid gap-4 lg:grid-cols-[1fr_280px]">
          <div class="space-y-4">
            <NCard size="small" embedded>
              <div class="flex items-start gap-4">
                <div
                  class="size-16 rounded-2xl overflow-hidden flex items-center justify-center flex-none ring-1 ring-black/5"
                  :style="!detailData.logo ? { backgroundColor: getLogoColor(detailData.name || detailData.app || '') } : undefined"
                >
                  <img v-if="detailData.logo" :src="detailData.logo" :alt="detailData.title" class="size-full object-cover">
                  <span v-else class="text-2xl font-bold text-white">{{ (detailData.title || detailData.app || 'A').slice(0, 1).toUpperCase() }}</span>
                </div>
                <div class="min-w-0 flex-1">
                  <div class="text-lg font-semibold">
                    {{ detailData.title || detailData.app }}
                  </div>
                  <div class="mt-1 text-sm text-muted break-all">
                    {{ detailData.name }}
                  </div>
                  <div class="mt-2 text-sm leading-6 text-muted">
                    {{ detailData.description || '暂无应用说明' }}
                  </div>
                  <div class="mt-3 flex flex-wrap gap-2">
                    <NTag size="small" :bordered="false">
                      最新 {{ detailData.latest_version || detailData.version || '-' }}
                    </NTag>
                    <NTag v-if="detailData.ver_type" size="small" :bordered="false">
                      {{ detailData.ver_type }}
                    </NTag>
                    <NTag v-if="detailData.installed" size="small" type="success" :bordered="false">
                      已安装
                    </NTag>
                    <NTag v-if="detailData.installed" size="small" type="info" :bordered="false">
                      已装 {{ detailData.installed_version || '-' }}
                    </NTag>
                    <NTag
                      v-for="tag in detailTags"
                      :key="tag.id || tag.name"
                      size="small"
                      :bordered="false"
                      :color="tag.color ? { color: `${tag.color}18`, textColor: tag.color, borderColor: 'transparent' } : undefined"
                    >
                      {{ tag.name }}
                    </NTag>
                  </div>
                </div>
              </div>
            </NCard>

            <NCard size="small" embedded>
              <NTabs type="line" animated>
                <NTabPane name="readme" tab="应用介绍">
                  <NScrollbar x-scrollable style="max-height: 360px">
                    <pre class="whitespace-pre-wrap text-sm leading-6">{{ detailReadme || '暂无介绍' }}</pre>
                  </NScrollbar>
                </NTabPane>
                <NTabPane name="changelog" tab="更新记录">
                  <NScrollbar x-scrollable style="max-height: 360px">
                    <pre class="whitespace-pre-wrap text-sm leading-6">{{ detailChangelog || '暂无更新记录' }}</pre>
                  </NScrollbar>
                </NTabPane>
                <NTabPane name="logs" tab="执行日志">
                  <NScrollbar x-scrollable style="max-height: 360px">
                    <pre class="whitespace-pre-wrap text-sm leading-6">{{ actionLogs.join('\n') || '暂无日志' }}</pre>
                  </NScrollbar>
                </NTabPane>
              </NTabs>
            </NCard>
          </div>

          <div class="space-y-4">
            <NCard size="small" embedded>
              <div class="flex flex-col gap-2">
                <NButton
                  type="primary"
                  block
                  :loading="actionRunning && runningAction !== 'uninstall'"
                  :disabled="(detailData?.installed && !canUpgrade && !actionRunning) || (actionRunning && runningAction === 'uninstall')"
                  @click="runAction()"
                >
                  {{ detailData?.installed && !canUpgrade ? '已是最新版本' : detailActionLabel }}
                </NButton>
                <NButton
                  v-if="detailData?.installed"
                  block
                  secondary
                  :disabled="actionRunning || !canUninstall"
                  :loading="actionRunning && runningAction === 'uninstall'"
                  @click="runAction('uninstall')"
                >
                  {{ canUninstall ? '卸载应用' : '不可卸载' }}
                </NButton>
                <NButton :disabled="actionRunning" block @click="close">
                  关闭
                </NButton>
              </div>
            </NCard>

            <NCard size="small" embedded title="安装任务">
              <div class="flex flex-col gap-2">
                <NCheckbox v-model:checked="actionTasks.composer" size="small">
                  安装依赖
                </NCheckbox>
                <NCheckbox v-model:checked="actionTasks.sync_menu" size="small">
                  同步菜单
                </NCheckbox>
                <NCheckbox v-model:checked="actionTasks.sync_db" size="small">
                  同步数据库
                </NCheckbox>
              </div>
            </NCard>

            <NCard size="small" embedded title="应用信息">
              <div class="space-y-2 text-sm">
                <div class="flex justify-between gap-3">
                  <span class="text-muted">标识</span>
                  <span class="text-right">{{ detailData.app || '-' }}</span>
                </div>
                <div class="flex justify-between gap-3">
                  <span class="text-muted">作者</span>
                  <span class="text-right">{{ detailData.nickname || '-' }}</span>
                </div>
                <div class="flex justify-between gap-3">
                  <span class="text-muted">云端版本</span>
                  <span class="text-right">{{ detailData.latest_version || detailData.version || '-' }}</span>
                </div>
                <div class="flex justify-between gap-3">
                  <span class="text-muted">本地版本</span>
                  <span class="text-right">{{ detailData.installed_version || '-' }}</span>
                </div>
                <div class="flex justify-between gap-3">
                  <span class="text-muted">下载量</span>
                  <span class="text-right">{{ detailData.download_num || 0 }}</span>
                </div>
                <div v-if="detailVerAt" class="flex justify-between gap-3">
                  <span class="text-muted">更新时间</span>
                  <span class="text-right">
                    <NTime :time="detailVerAt" format="yyyy-MM-dd HH:mm" />
                  </span>
                </div>
              </div>
            </NCard>
          </div>
        </div>
      </NSpin>
    </div>
  </DuxModalPage>
</template>

<style scoped></style>
