<script setup>
import { useCustom, useCustomMutation, useInvalidate, useList } from '@duxweb/dvha-core'
import { DuxBlockEmpty, DuxPage } from '@duxweb/dvha-pro'
import { NBadge, NButton, NPagination, NSpin, NTab, NTabs, NAvatarGroup, NAvatar, NTooltip, NScrollbar } from 'naive-ui'
import { ref, watch } from 'vue'

const loading = ref(false)
const filters = ref({
  type: '',
})

const { invalidate } = useInvalidate()

function handleTabChange(value) {
  filters.value.type = value
}

const { data, pageCount, pagination, refetch } = useList({
  path: 'system/board',
  filters: filters.value,
})

const { mutateAsync } = useCustomMutation({
  path: 'system/board',
  method: 'post',
})

function markRead(item) {
  if (item.read) {
    return
  }
  mutateAsync({
    path: `system/board/${item.id}/read`,
  }).then(() => {
    invalidate('system/board')
  })
}

function handleClick(item) {
  markRead(item)
}
</script>

<template>
  <DuxPage :scrollbar="false">
    <div class="h-full flex flex-col gap-4 w-full lg:max-w-4xl mx-auto py-4">
      <!-- 头部操作区 -->
      <div class="flex flex-row gap-4 items-center justify-between">
        <div class="flex items-center gap-2">
          <h1 class="text-xl font-semibold">
            系统公告
          </h1>
          <NBadge v-if="data?.meta?.stats?.unread > 0" :value="data?.meta?.stats?.unread" :max="99" />
        </div>
      </div>

      <!-- 筛选区 -->
      <div class="flex flex-col sm:flex-row gap-4">
        <NTabs
          :value="filters.type"
          type="segment"
          @update:value="handleTabChange"
        >
          <NTab name="" tab="全部" />
          <NTab name="1" tab="通知" />
          <NTab name="2" tab="公告" />
          <NTab name="3" tab="活动" />
        </NTabs>
      </div>

      <!-- 公告列表 -->
      <div class="flex-1 min-h-0 flex flex-col gap-3">
        <div v-if="!loading && !data?.data.length" class="p-6 bg-default dark:bg-elevated rounded-lg border border-muted">
          <DuxBlockEmpty text="暂无公告" desc="暂未发现更多公告" />
        </div>

        <NSpin v-else :show="loading" class="h-full" content-class="h-full">
          <NScrollbar>
            <div class="flex flex-col gap-3">
              <div
                v-for="item in data?.data || []"
                :key="item.id"
                class="group bg-default dark:bg-elevated rounded-lg border border-muted p-4 transition-all duration-200 hover:shadow-md cursor-pointer"
                @click="handleClick(item)"
              >
                <div class="flex items-start justify-between gap-4">
                  <div class="flex-1 min-w-0 flex flex-col">
                    <!-- 标题和状态 -->
                    <div class="flex items-center gap-2 mb-2">
                      <h3 
                        class="font-medium text-base truncate"
                        :class="{
                          'text-muted': item.read,
                        }"
                      >
                        {{ item.title }}
                      </h3>
                      <div v-if="!item.read" class="w-2 h-2 bg-primary rounded-full" />
                    </div>

                    <!-- 内容 -->
                    <p 
                      v-if="item.content"
                      class="text-sm leading-relaxed line-clamp-2"
                      :class="{
                        'text-toned': !item.read,
                        'text-muted': item.read,
                      }"
                    >
                      {{ item.content }}
                    </p>

                    <!-- 底部信息 -->
                    <div class="flex items-center justify-between text-xs text-muted mt-2">
                      <div class="flex items-center gap-4">
                        <span>{{ item.publish_at }}</span>
                        <span v-if="item.read_count > 0">{{ item.read_count }}人已读</span>
                      </div>

                      <!-- 已读用户头像 -->
                      <div v-if="item.read_users && item.read_users.length > 0" class="flex items-center gap-2">
                        <NAvatarGroup :options="item.read_users?.map(v => {
                          return {
                            src: v.avatar,
                            name: v.username,
                          }
                        })" :size="30" :max="3">
                          <template #avatar="{ option: { name, src } }">
                            <NTooltip>
                              <template #trigger>
                                <NAvatar :src="src">
                                  {{ name?.charAt(0).toUpperCase() }}
                                </NAvatar>
                              </template>
                              {{ name }}
                            </NTooltip>
                          </template>
                        </NAvatarGroup>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </NScrollbar>
        </NSpin>
      </div>

      <!-- 分页 -->
      <div class="flex justify-center mt-4">
        <NPagination v-model:page="pagination.page" :page-count="pageCount" />
      </div>
    </div>
  </DuxPage>
</template>
