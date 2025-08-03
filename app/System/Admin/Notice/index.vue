<script setup>
import { useCustom, useCustomMutation, useInvalidate, useList } from '@duxweb/dvha-core'
import { DuxBlockEmpty, DuxPage } from '@duxweb/dvha-pro'
import { NBadge, NButton, NPagination, NSpin, NTab, NTabs, NTime } from 'naive-ui'
import { ref, watch } from 'vue'

const loading = ref(false)
const stats = ref({})
const filters = ref({
  tab: '',
})

const { data: statsData, refetch: statsRefetch } = useCustom({
  path: 'system/notice/stats',
})

const { invalidate } = useInvalidate()

watch(statsData, (v) => {
  stats.value = v?.data || {}
})
function handleNoticeClick(notice) {
  if (!notice.url?.startsWith('http')) {
    return
  }
  window.open(notice.url, '_blank')
}

function handleTabChange(value) {
  filters.value.tab = value
}

const { data, pageCount, pagination, refetch } = useList({
  path: 'system/notice',
  filters: filters.value,
})

const { mutateAsync } = useCustomMutation({
  path: 'system/notice',
  method: 'post',
})

function markRead(notice) {
  if (notice.read) {
    return
  }

  mutateAsync({
    payload: {
      type: 'read',
      id: notice.id,
    },
  }).then(() => {
    statsRefetch()
    invalidate('system/notice')
  })
}

function markAllRead() {
  stats.value.read = stats.value.total
  stats.value.unread = 0

  mutateAsync({
    payload: {
      type: 'all_read',
    },
  }).then(() => {
    statsRefetch()
    invalidate('system/notice')
  })
}

function deleteNotice(notice) {
  mutateAsync({
    payload: {
      type: 'delete',
      id: notice.id,
    },
  }).then(() => {
    statsRefetch()
    invalidate('system/notice')
  })
}
</script>

<template>
  <DuxPage :scrollbar="false">
    <div class="h-full flex flex-col gap-4 w-full lg:max-w-4xl mx-auto py-4">
      <!-- 头部操作区 -->
      <div class="flex flex-row gap-4 items-center justify-between">
        <div class="flex items-center gap-2">
          <h1 class="text-xl font-semibold">
            消息通知
          </h1>
          <NBadge v-if="stats.unread > 0" :value="stats.unread" :max="99" />
        </div>
        <div class="flex gap-2">
          <NButton
            v-if="stats.unread > 0"
            secondary
            @click="markAllRead"
          >
            全部已读
          </NButton>
        </div>
      </div>

      <!-- 筛选区 -->
      <div class="flex flex-col sm:flex-row gap-4">
        <NTabs
          :value="filters.tab"
          type="segment"
          @update:value="handleTabChange"
        >
          <NTab name="" tab="全部" />
          <NTab name="1" tab="未读" />
          <NTab name="2" tab="已读" />
        </NTabs>
      </div>

      <!-- 消息列表 -->
      <div class="flex-1 flex flex-col gap-3">
        <div v-if="!loading && !data?.data.length" class="p-6 bg-default dark:bg-elevated rounded-lg border border-muted">
          <DuxBlockEmpty text="暂无消息" desc="暂未发现更多消息" />
        </div>

        <div v-else class="flex flex-col gap-3">
          <NSpin :show="loading">
            <div
              v-for="notice in data?.data || []"
              :key="notice.id"
              class="group bg-default dark:bg-elevated rounded-lg border border-muted p-4 transition-all duration-200 hover:shadow-md cursor-pointer"
              @click="handleNoticeClick(notice)"
            >
              <div class="flex items-start justify-between gap-4">
                <div class="flex-1 min-w-0 flex flex-col">
                  <!-- 标题和状态 -->
                  <div class="flex items-center gap-2 mb-2">
                    <div v-if="notice.url?.startsWith('http')">
                      <div class="i-tabler:external-link size-4.5" />
                    </div>
                    <h3
                      class="font-medium text-base truncate"
                      :class="{
                        'text-muted': notice.read,
                      }"
                    >
                      {{ notice.title }}
                    </h3>
                    <div v-if="!notice.read" class="w-2 h-2 bg-primary rounded-full" />
                  </div>

                  <!-- 内容 -->
                  <p
                    v-if="notice.content"
                    class="text-sm leading-relaxed line-clamp-2"
                    :class="{
                      'text-toned': !notice.read,
                      'text-muted': notice.read,
                    }"
                  >
                    {{ notice.content }}
                  </p>

                  <!-- 底部信息 -->
                  <div class="flex items-center justify-between text-xs text-muted">
                    <div class="flex items-center gap-4">
                      <span>{{ notice.desc }}</span>
                      <span v-if="notice.read_at" class="flex items-center gap-1">
                        <span>已读于</span>
                        <NTime :time="new Date(notice.read_at)" format="MM-dd HH:mm" />
                      </span>
                    </div>

                    <!-- 操作按钮 -->
                    <div class="flex items-center gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                      <NButton
                        v-if="!notice.read"
                        circle
                        type="primary"
                        secondary
                        @click="markRead(notice)"
                      >
                        <template #icon>
                          <i class="i-tabler:check text-base" />
                        </template>
                      </NButton>
                      <NButton
                        circle
                        type="error"
                        secondary
                        @click="deleteNotice(notice)"
                      >
                        <template #icon>
                          <i class="i-tabler:trash text-base" />
                        </template>
                      </NButton>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </NSpin>
        </div>
      </div>

      <div class="flex justify-center mt-4">
        <NPagination v-model:page="pagination.page" :page-count="pageCount" />
      </div>
    </div>
  </DuxPage>
</template>
