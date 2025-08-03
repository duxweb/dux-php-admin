<script setup>
import { useCustom, useCustomMutation, useInfiniteList, useInvalidate } from '@duxweb/dvha-core'
import { DuxBlockEmpty, DuxPage, useModal } from '@duxweb/dvha-pro'
import { NButton, NInfiniteScroll, NSpin, NTab, NTabs, NTime } from 'naive-ui'
import { ref, watch } from 'vue'

const modal = useModal()
const stats = ref({})
const filters = ref({
  tab: '',
})

const { data: statsData, refetch: statsRefetch } = useCustom({
  path: 'system/memo/stats',
})

const { invalidate } = useInvalidate()

watch(statsData, (v) => {
  stats.value = v?.data || {}
})

function handleTabChange(value) {
  filters.value.tab = value
}

const { data, isLoading, fetchNextPage, hasNextPage } = useInfiniteList({
  path: 'system/memo',
  filters: filters.value,
})

function handleLoad() {
  if (hasNextPage.value) {
    fetchNextPage()
  }
}

const { mutateAsync: completeMutate } = useCustomMutation({
  method: 'patch',
})

const { mutateAsync: deleteMutate } = useCustomMutation({
  method: 'delete',
})

function handleCreate() {
  modal.show({
    title: '新建备忘录',
    component: () => import('./form.vue'),
  })
}

function handleEdit(memo) {
  modal.show({
    title: '编辑备忘录',
    component: () => import('./form.vue'),
    componentProps: { id: memo.id },
  })
}

function toggleComplete(memo) {
  completeMutate({
    path: `system/memo/${memo.id}/complete`,
    payload: {
      is_completed: !memo.is_completed,
    },
  }).then(() => {
    refreshData()
  })
}

function deleteMemo(memo) {
  deleteMutate({
    path: `system/memo/${memo.id}`,
  }).then(() => {
    refreshData()
  })
}

function refreshData() {
  statsRefetch()
  invalidate('infinite:system/memo')
}
</script>

<template>
  <DuxPage :scrollbar="false">
    <div class="h-full flex flex-col gap-4 w-full lg:max-w-5xl mx-auto py-4">
      <!-- 头部操作区 -->
      <div class="flex flex-row gap-4 items-center justify-between">
        <div class="flex items-center gap-2">
          <h1 class="text-xl font-semibold">
            我的备忘录
          </h1>
          <div class="flex items-center gap-2 text-sm text-muted">
            <span>待办 {{ stats.pending || 0 }}</span>
            <span>/</span>
            <span>总计 {{ stats.total || 0 }}</span>
          </div>
        </div>
        <div class="flex gap-2">
          <NButton
            type="primary"
            secondary
            @click="() => handleCreate()"
          >
            <template #icon>
              <i class="i-tabler:plus text-base" />
            </template>
            新建备忘录
          </NButton>
        </div>
      </div>

      <!-- 筛选区 -->
      <div class="flex justify-center">
        <NTabs
          :value="filters.tab"
          type="segment"
          @update:value="handleTabChange"
        >
          <NTab name="" tab="全部" />
          <NTab name="1" tab="待办" />
          <NTab name="2" tab="已完成" />
        </NTabs>
      </div>

      <!-- 备忘录列表 -->
      <div class="flex-1 flex flex-col gap-3 min-h-0">
        <NInfiniteScroll :distance="10" @load="handleLoad">
          <div v-if="!isLoading && !data?.data.length" class="p-6 bg-default dark:bg-elevated rounded-lg border border-muted">
            <DuxBlockEmpty text="暂无备忘录" desc="点击上方按钮创建第一个备忘录" />
          </div>

          <div class="columns-1 sm:columns-2 lg:columns-3 xl:columns-4 gap-4">
            <div
              v-for="memo in data?.data || []"
              :key="memo.id"
              class="group relative transition-all duration-300 cursor-pointer mb-4 break-inside-avoid relative"
              :class="{
                'opacity-70 grayscale': memo.is_completed,
              }"
            >
              <!-- 便签纸效果 -->
              <div
                class="relative p-5 rounded-lg flex flex-col hover:shadow-xl transition-all duration-300 shadow-[0_0_0_1px_rgba(255,255,255.02)_inset] dark:shadow-none"
                :class="{
                  // 高优先级 - 错误色
                  'bg-error/10 border-error/20 hover:bg-error/20': memo.priority === 3,
                  // 中优先级 - 警告色
                  'bg-warning/10 border-warning/20 hover:bg-warning/20': memo.priority === 2,
                  // 低优先级 - 信息色
                  'bg-info/10 border-info/20 hover:bg-info/20': memo.priority === 1,
                }"
                style="
                  border-width: 1px;
                  clip-path: polygon(0 0, calc(100% - 16px) 0, 100% 16px, 100% 100%, 0 100%);
                "
              >
                <!-- 折角阴影效果 -->
                <div
                  class="absolute size-4 top-0 right-0 opacity-50 border"
                  :class="{
                    'bg-error border-error-700': memo.priority === 3,
                    'bg-warning border-warning-700': memo.priority === 2,
                    'bg-info border-info-700': memo.priority === 1,
                  }"
                />

                <div class="flex flex-col gap-3 h-full relative">
                  <!-- 标题 -->
                  <h3
                    class="font-bold text-lg leading-tight"
                    :class="{
                      'line-through opacity-60': memo.is_completed,
                      'text-error-800': memo.priority === 3,
                      'text-warning-800': memo.priority === 2,
                      'text-info-800': memo.priority === 1,
                    }"
                  >
                    {{ memo.title }}
                  </h3>

                  <!-- 内容 -->
                  <p
                    v-if="memo.content"
                    class="text-sm leading-relaxed flex-1"
                    :class="{
                      'opacity-60': memo.is_completed,
                      'text-error-700': memo.priority === 3,
                      'text-warning-700': memo.priority === 2,
                      'text-info-700': memo.priority === 1,
                    }"
                  >
                    {{ memo.content }}
                  </p>

                  <!-- 提醒时间 - 小标签样式 -->
                  <div
                    v-if="memo.remind_at"
                    class="inline-flex items-center gap-1 text-xs px-2 py-1 rounded-full w-fit"
                    :class="{
                      'bg-red-200 text-red-800 dark:bg-red-800 dark:text-red-200': !memo.is_completed && new Date(memo.remind_at) < new Date(),
                      'bg-orange-200 text-orange-800 dark:bg-orange-800 dark:text-orange-200': !memo.is_completed && new Date(memo.remind_at) > new Date(),
                      'bg-gray-200 text-gray-600 dark:bg-gray-700 dark:text-gray-400': memo.is_completed,
                    }"
                  >
                    <i class="i-tabler:clock text-xs" />
                    <NTime :time="new Date(memo.remind_at)" format="MM-dd HH:mm" />
                  </div>

                  <!-- 底部信息 -->
                  <div class="flex items-center justify-between mt-auto pt-2">
                    <div
                      class="text-xs opacity-60"
                      :class="{
                        'text-error-600': memo.priority === 3,
                        'text-warning-600': memo.priority === 2,
                        'text-info-600': memo.priority === 1,
                      }"
                    >
                      <NTime :time="new Date(memo.created_at)" format="MM-dd" />
                    </div>
                  </div>
                </div>
                <!-- 操作按钮 - 圆形小按钮 -->
                <div class="absolute bottom-3 right-3 flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                  <button
                    class="w-7 h-7 rounded-full bg-white/80 dark:bg-gray-800/80 shadow-sm flex items-center justify-center hover:scale-110 transition-transform"
                    @click="() => handleEdit(memo)"
                  >
                    <i class="i-tabler:edit text-xs text-gray-600 dark:text-gray-400" />
                  </button>
                  <button
                    class="w-7 h-7 rounded-full bg-white/80 dark:bg-gray-800/80 shadow-sm flex items-center justify-center hover:scale-110 transition-transform"
                    :class="{
                      'bg-green-100 dark:bg-green-900': !memo.is_completed,
                      'bg-orange-100 dark:bg-orange-900': memo.is_completed,
                    }"
                    @click="toggleComplete(memo)"
                  >
                    <i
                      class="text-xs" :class="[
                        memo.is_completed ? 'i-tabler:rotate-clockwise' : 'i-tabler:check',
                        {
                          'text-green-600 dark:text-green-400': !memo.is_completed,
                          'text-orange-600 dark:text-orange-400': memo.is_completed,
                        },
                      ]"
                    />
                  </button>
                  <button
                    class="w-7 h-7 rounded-full bg-red-100 dark:bg-red-900 shadow-sm flex items-center justify-center hover:scale-110 transition-transform"
                    @click="deleteMemo(memo)"
                  >
                    <i class="i-tabler:trash text-xs text-red-600 dark:text-red-400" />
                  </button>
                </div>
              </div>
            </div>
          </div>

          <div v-if="isLoading" class="flex justify-center py-2">
            <NSpin />
          </div>
        </NInfiniteScroll>
      </div>
    </div>
  </DuxPage>
</template>
