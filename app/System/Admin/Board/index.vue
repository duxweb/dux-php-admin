<script setup>
import { useCustom, useCustomMutation, useInvalidate, useList } from '@duxweb/dvha-core'
import { DuxBlockEmpty, DuxPage } from '@duxweb/dvha-pro'
import { NBadge, NButton, NPagination, NSpin, NTab, NTabs, NAvatarGroup, NAvatar, NTooltip, NDropdown } from 'naive-ui'
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

function deleteNotice(item) {
  mutateAsync({
    payload: {
      type: 'delete',
      id: item.id,
    },
  }).then(() => {
    invalidate('system/board')
  })
}
</script>

<template>
  <DuxPage :scrollbar="false">
    <div class="h-full flex flex-col gap-4 w-full lg:max-w-4xl mx-auto py-4">
      <div class="flex flex-row gap-4 items-center justify-between">
        <div class="flex items-center gap-2">
          <h1 class="text-xl font-semibold">
            系统公告
          </h1>
          <NBadge v-if="data?.meta?.stats?.unread > 0" :value="data?.meta?.stats?.unread" :max="99" />
        </div>
      </div>

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

      <div class="flex-1 flex flex-col gap-3">
        <div v-if="!loading && !data?.data.length" class="p-6 bg-default dark:bg-elevated rounded-lg border border-muted">
          <DuxBlockEmpty text="暂无消息" desc="暂未发现更多消息" />
        </div>

        <div v-else class="flex flex-col gap-3">
          <NSpin :show="loading">
            <div
              v-for="item in data?.data || []"
              :key="item.id"
              class="group bg-default dark:bg-elevated rounded-lg border border-muted p-4 transition-all duration-200 hover:shadow-md cursor-pointer"
              @click="handleClick(item)"
            >
              <div class="flex items-start justify-between gap-4">
                <div class="flex-1 min-w-0 flex flex-col">
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

                  <p 
                    class="text-sm leading-relaxed line-clamp-2"
                    :class="{
                      'text-toned': !item.read,
                      'text-muted': item.read,
                    }"
                  >
                    {{ item.content }}
                  </p>

                  <div class="flex items-center justify-between text-xs text-muted">
                    <div class="flex items-center gap-4">
                      <span>{{ item.publish_at }}</span>
                    </div>

                    <div class="flex items-center gap-2 ">
                      <NAvatarGroup  :options="item.read_users?.map(v => {
                        return {
                          src: v.avatar,
                          name: v.nickname || v.username,
                        }
                      })" :size="24" :max="5">
                        <template #avatar="{ option: { name, src } }">
                          <NTooltip>
                            <template #trigger>
                              <n-avatar :src="src" />
                            </template>
                            {{ name }}
                          </NTooltip>
                        </template>
                        <template #rest="{ options: restOptions, rest }">
                          <NDropdown :options="createDropdownOptions(restOptions)" placement="top">
                            <NAvatar>+{{ rest }}</NAvatar>
                          </NDropdown>
                        </template>
                      </NAvatarGroup>
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
