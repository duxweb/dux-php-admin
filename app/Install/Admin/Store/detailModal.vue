<script setup lang="ts">
import { useCustomMutation, useManage } from '@duxweb/dvha-core'
import { DuxModalPage } from '@duxweb/dvha-pro'
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
const { mutateAsync } = useCustomMutation()

const detailLoading = ref(false)
const detailData = ref<Record<string, any>>({
  ...(props.item || {}),
})
const actionRunning = ref(false)
const actionLogs = ref<string[]>([])
const actionTasks = ref({
  composer: true,
  sync_menu: true,
  sync_db: true,
})

let eventSource: EventSource | null = null

const detailAction = computed(() => {
  return detailData.value?.installed ? 'upgrade' : 'install'
})

const detailActionLabel = computed(() => {
  return detailData.value?.installed ? '升级应用' : '安装应用'
})

const logoColors = [
  '#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6',
  '#06b6d4', '#ec4899', '#14b8a6', '#f97316', '#6366f1',
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

function closeEventSource() {
  if (eventSource) {
    eventSource.close()
    eventSource = null
  }
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

async function runAction() {
  if (detailAction.value === 'upgrade' && !canUpgrade.value) {
    message.info('当前已是最新版本')
    return
  }
  if (!detailData.value?.app || actionRunning.value) {
    return
  }
  actionRunning.value = true
  actionLogs.value = []
  appendLog('正在准备任务...')

  try {
    const prepare = await mutateAsync({
      path: 'install/store/action/prepare',
      method: 'post',
      payload: {
        app: detailData.value.app,
        action: detailAction.value,
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
    closeEventSource()
    eventSource = new EventSource(streamUrl)

    eventSource.addEventListener('log', (event: MessageEvent) => {
      const data = JSON.parse(event.data || '{}')
      appendLog(data.message || '')
    })

    eventSource.addEventListener('error', (event: MessageEvent) => {
      try {
        const data = JSON.parse(event.data || '{}')
        appendLog(`[error] ${data.message || '执行失败'}`)
        message.error(data.message || '执行失败')
      }
      catch {
        appendLog('[error] 执行失败')
        message.error('执行失败')
      }
      actionRunning.value = false
      closeEventSource()
    })

    eventSource.addEventListener('complete', async (event: MessageEvent) => {
      const data = JSON.parse(event.data || '{}')
      appendLog(data.message || '执行完成')
      message.success('操作成功')
      actionRunning.value = false
      closeEventSource()
      await props.onSuccess?.()
      await loadDetail()
    })
  }
  catch (e: any) {
    appendLog(`[error] ${e?.message || '执行失败'}`)
    message.error(e?.message || '执行失败')
    actionRunning.value = false
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
  <DuxModalPage @close="close">
    <NSpin :show="detailLoading">
      <div class="space-y-4">
        <!-- 应用头部信息 -->
        <div class="flex gap-4">
          <!-- Logo -->
          <div
            class="size-16 rounded-2xl overflow-hidden bg-gray-1 flex items-center justify-center flex-none ring-1 ring-black/5"
            :style="!detailData.logo ? { backgroundColor: getLogoColor(detailData.name || detailData.app || '') } : undefined"
          >
            <img v-if="detailData.logo" :src="detailData.logo" :alt="detailData.title" class="size-full object-cover">
            <span v-else class="text-2xl font-bold text-white">{{ (detailData.title || detailData.app || 'A').slice(0, 1).toUpperCase() }}</span>
          </div>

          <!-- 信息 -->
          <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2">
              <h2 class="text-lg font-semibold truncate">
                {{ detailData.title || detailData.app }}
              </h2>
              <NTag v-if="detailData.installed" size="small" type="success" :bordered="false" round>
                已安装
              </NTag>
            </div>
            <div class="text-xs opacity-40 mt-1">
              {{ detailData.name }}
            </div>
            <div class="mt-2 text-sm opacity-60 line-clamp-2">
              {{ detailData.description || '暂无描述' }}
            </div>
          </div>
        </div>

        <!-- 版本 + 元信息 -->
        <div class="flex flex-wrap items-center gap-2">
          <NTag size="small" :bordered="false" round>
            最新 {{ detailData.latest_version || detailData.version || '-' }}
          </NTag>
          <NTag v-if="detailData.ver_type" size="small" :bordered="false" round>
            {{ detailData.ver_type }}
          </NTag>
          <NTag v-if="detailData.installed" size="small" type="info" :bordered="false" round>
            已装 {{ detailData.installed_version || '-' }}
          </NTag>
          <span class="text-xs opacity-30 ml-1">{{ detailData.nickname || '-' }}</span>
          <span class="text-xs opacity-30">{{ detailData.download_num || 0 }} 次下载</span>
          <span class="text-xs opacity-30">
            更新于
            <NTime v-if="detailVerAt" :time="detailVerAt" format="yyyy-MM-dd HH:mm" />
            <template v-else>
              -
            </template>
          </span>
        </div>

        <!-- 标签 -->
        <div v-if="detailTags.length" class="flex flex-wrap gap-1.5">
          <NTag
            v-for="tag in detailTags"
            :key="tag.id || tag.name"
            size="small"
            round
            :bordered="false"
            :color="tag.color ? { color: tag.color + '18', textColor: tag.color, borderColor: 'transparent' } : undefined"
          >
            {{ tag.name }}
          </NTag>
        </div>

        <!-- Tab 内容区 -->
        <NTabs type="line" size="small" default-value="readme">
          <NTabPane name="readme" tab="应用介绍">
            <NScrollbar v-if="detailReadme" style="max-height: 240px">
              <pre class="text-xs leading-5 opacity-60 whitespace-pre-wrap break-all p-1">{{ detailReadme }}</pre>
            </NScrollbar>
            <div v-else class="text-xs opacity-30 py-4 text-center">
              暂无介绍
            </div>
          </NTabPane>
          <NTabPane name="changelog" tab="更新记录">
            <NScrollbar v-if="detailChangelog" style="max-height: 240px">
              <pre class="text-xs leading-5 opacity-60 whitespace-pre-wrap break-all p-1">{{ detailChangelog }}</pre>
            </NScrollbar>
            <div v-else class="text-xs opacity-30 py-4 text-center">
              暂无更新记录
            </div>
          </NTabPane>
        </NTabs>

        <!-- 安装设置 -->
        <NCard size="small" title="安装任务" :bordered="true">
          <div class="flex flex-wrap gap-x-6 gap-y-2">
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

        <!-- 日志输出 -->
        <div v-if="actionLogs.length" class="rounded-lg bg-[#1a1a2e] p-3 ring-1 ring-white/5">
          <NScrollbar style="max-height: 280px">
            <pre class="text-xs leading-5 text-emerald-400 whitespace-pre-wrap break-all font-mono">{{ actionLogs.join('\n') }}</pre>
          </NScrollbar>
        </div>
      </div>
    </NSpin>
    <template #footer>
      <NButton :disabled="actionRunning" @click="close">
        关闭
      </NButton>
      <NButton type="primary" :loading="actionRunning" :disabled="!canUpgrade && !actionRunning" @click="runAction">
        <template #icon>
          <div :class="detailData?.installed ? 'i-tabler:refresh' : 'i-tabler:download'" class="size-3.5" />
        </template>
        {{ detailData?.installed && !canUpgrade ? '已是最新版本' : detailActionLabel }}
      </NButton>
    </template>
  </DuxModalPage>
</template>

<style scoped></style>
