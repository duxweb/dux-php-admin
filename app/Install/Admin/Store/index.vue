<script setup lang="ts">
import { useCustomMutation } from '@duxweb/dvha-core'
import { DuxCardPage, useModal } from '@duxweb/dvha-pro'
import { NButton, NSelect, NTag, useMessage } from 'naive-ui'
import { onMounted, ref } from 'vue'

const message = useMessage()
const modal = useModal()
const { mutateAsync } = useCustomMutation()

const listKey = ref(0)
const cloudServer = ref('global')
const filter = ref<Record<string, any>>({
  cloud_server: 'global',
  tab: 'all',
})
const tabs = [
  { label: '应用商店', value: 'all' },
  { label: '已安装', value: 'installed' },
]
const cloudServers = ref([
  { label: 'cloud.dux.plus', value: 'global', latency_ms: null as number | null },
  { label: 'cn1.cloud.dux.plus', value: 'cn', latency_ms: null as number | null },
])

// 无 logo 时的顺序背景色
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

function canUpdate(item: Record<string, any>): boolean {
  if (!item?.installed) {
    return false
  }
  const localVersion = String(item?.installed_version || '').trim()
  const latestVersion = String(item?.latest_version || item?.version || '').trim()
  if (!localVersion || !latestVersion || latestVersion === '-' || localVersion === '-') {
    return false
  }
  return compareVersion(latestVersion, localVersion) > 0
}

function refreshList() {
  listKey.value += 1
}

async function loadServerStatus() {
  try {
    const res = await mutateAsync({
      path: 'install/store',
      method: 'get',
      query: {
        cloud_server: cloudServer.value,
      },
    })
    const meta = (res?.meta || {}) as Record<string, any>
    if (meta.server) {
      cloudServer.value = meta.server
      filter.value.cloud_server = meta.server
    }
    const servers = Array.isArray(meta.servers) ? meta.servers : []
    if (servers.length > 0) {
      cloudServers.value = servers.map((item: Record<string, any>) => {
        const latency = Number.isFinite(item.latency_ms) ? item.latency_ms : null
        const title = item.title || item.url || item.key
        const latencyText = latency === null ? 'timeout' : `${latency}ms`
        return {
          label: `${title} (${latencyText})`,
          value: item.key,
          latency_ms: latency,
        }
      })
    }
  }
  catch (e: any) {
    message.error(e?.message || '加载应用服务器状态失败')
  }
}

async function changeServer(value: string) {
  cloudServer.value = value
  filter.value.cloud_server = value
  await loadServerStatus()
  refreshList()
}

function openDetail(item: Record<string, any>) {
  modal.show({
    title: item?.title || '应用详情',
    width: 900,
    component: () => import('./detailModal.vue'),
    componentProps: {
      item,
      cloudServer: cloudServer.value,
      onSuccess: async () => {
        await loadServerStatus()
        refreshList()
      },
    },
  })
}

onMounted(() => {
  loadServerStatus()
})
</script>

<template>
  <DuxCardPage
    :key="listKey"
    path="install/store"
    title="应用商店"
    :pagination="false"
    :filter="filter"
    :tabs="tabs"
    :col-width="280"
  >
    <template #actions>
      <div class="flex items-center gap-2">
        <NSelect
          v-model:value="cloudServer"
          :options="cloudServers"
          style="width: 220px"
          @update:value="changeServer"
        />
        <NButton secondary @click="() => { loadServerStatus(); refreshList() }">
          刷新
        </NButton>
      </div>
    </template>

    <template #default="{ item }">
      <div
        class="cursor-pointer rounded-md border border-muted p-4 transition-colors hover:border-primary/50 mt-2"
        @click="openDetail(item)"
      >
        <div class="flex items-center gap-3">
          <!-- Logo -->
          <div class="size-10 rounded-lg overflow-hidden flex items-center justify-center flex-none">
            <img v-if="item.logo" :src="item.logo" :alt="item.title" class="size-full object-cover">
            <div
              v-else
              class="size-full flex items-center justify-center text-white text-sm font-bold"
              :style="{ backgroundColor: getLogoColor(item.name || item.app || '') }"
            >
              {{ (item.title || item.app || 'A').slice(0, 1).toUpperCase() }}
            </div>
          </div>

          <!-- 名称 -->
          <div class="flex-1 min-w-0">
            <div class="text-base font-medium truncate">
              {{ item.title || item.app }}
            </div>
            <div class="text-sm opacity-40 mt-0.5 truncate">
              {{ item.name }}
            </div>
          </div>
        </div>

        <!-- 描述 -->
        <div class="mt-2.5 text-sm opacity-40 line-clamp-2 leading-relaxed min-h-12">
          {{ item.description || '暂无描述' }}
        </div>

        <!-- 底部 -->
        <div class="mt-3 flex items-center gap-2">
          <NTag size="small" :bordered="false">
            v{{ item.latest_version || item.version || '-' }}
          </NTag>
          <NTag v-if="item.installed" size="small" type="success" :bordered="false">
            已安装
          </NTag>
          <NTag v-if="canUpdate(item)" size="small" type="warning" :bordered="false">
            可更新
          </NTag>
        </div>
      </div>
    </template>
  </DuxCardPage>
</template>

<style scoped></style>
