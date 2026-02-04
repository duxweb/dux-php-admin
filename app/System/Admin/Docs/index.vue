<script setup>
import { useCustomMutation, useOne } from '@duxweb/dvha-core'
import { DuxDrawEmpty, DuxPage, DuxTreeFilter } from '@duxweb/dvha-pro'
import { useClipboard } from '@vueuse/core'
import { NButton, NScrollbar, NTag, useMessage } from 'naive-ui'
import { computed, h, onMounted, ref, watch } from 'vue'
import Panel from './panel'
import Request from './request'
import Response from './response'

const docs = ref([])
const message = useMessage()
const { copy } = useClipboard()
const { mutateAsync: buildDocs, isLoading: building } = useCustomMutation()
const refreshKey = ref(0)
const treeParams = computed(() => ({
  refresh_key: refreshKey.value,
}))

onMounted(() => {
  docs.value = []
})

const filter = ref({
  id: [],
  app: '',
  route: '',
  method: '',
})

function getMethodType(method) {
  method = method?.toUpperCase()
  switch (method) {
    case 'GET':
      return 'primary'
    case 'POST':
      return 'info'
    case 'PUT':
      return 'warning'
    case 'DELETE':
      return 'error'
    case 'PATCH':
      return 'pink'
    default:
      return 'default'
  }
}

const info = ref({})

const { data, refetch } = useOne({
  get path() {
    return `docs/info/${filter.value.id[0]}`
  },
  options: {
    enabled: false,
  },
})

watch(() => filter.value.id, async (v) => {
  if (!v[0]) {
    info.value = {}
    return
  }
  await refetch()
  info.value = data.value.data
}, {
  immediate: true,
})

const _name = computed(() => {
  if (info.value.type === 'tag') {
    return info.value.tag.name
  }
  if (info.value.type === 'category') {
    return info.value.category.name
  }
  return info.value.api ? info.value.api.name : 'API 文档'
})

function handleCopy(path) {
  copy(path)
  message.success('复制成功')
}

function handleBuild() {
  buildDocs({
    path: 'docs/build',
    method: 'post',
  }).then(() => {
    refreshKey.value += 1
    message.success('文档已生成')
  }).catch((err) => {
    message.error(err.message)
  })
}

const devOpen = ref(false)
</script>

<template>
  <DuxPage :scrollbar="false" :card="false">
    <div class="flex gap-2 h-full relative">
      <div
        class="flex-1 lg:flex-none lg:w-70" :class="[
          info.type && !['tag', 'category'].includes(info.type) ? 'hidden lg:block' : '',

        ]"
      >
        <DuxTreeFilter
          v-model:value="filter.id"
          path="docs/catalogs"
          :params="treeParams"
          :data="docs"
          key-field="id"
          label-field="name"
          :indent="14"
          :render-prefix="({ option }) => {
            return h(NTag, {
              type: getMethodType(option.method),
              size: 'small',
            }, {
              default: () => option.method,
            })
          }"
          :render-label="({ option }) => {
            return h('div', {
              class: `flex items-center gap-2 ${option.method ? 'py-2' : 'text-muted'}`,
            }, [
              h('div', {
                class: 'flex-1 flex flex-col gap-1 min-w-0',
              }, [
                h('div', {
                  class: 'text-sm',
                }, option.name),
                option.method ? h('div', {
                  class: 'flex items-center gap-1 text-xs',
                }, [
                  h('div', {
                    class: `text-${getMethodType(option.method)}`,
                  }, option.method),
                  h('div', {
                    class: 'text-muted',
                  }, option.path),
                ]) : null,

              ]),

            ])
          }"
        >
          <template #tools>
            <NButton
              secondary
              type="primary"
              class="px-3!"
              :loading="building"
              @click="handleBuild"
            >
              <template #icon>
                <div class="i-tabler:file-text" />
              </template>
            </NButton>
          </template>
        </DuxTreeFilter>
      </div>
      <div
        v-if="info.type === 'tag' || info.type === 'category' || !info.type"
        class="hidden lg:block  flex-1 min-w-0 size-full flex items-center justify-center"
      >
        <div class="flex flex-col items-center justify-center gap-2 h-full">
          <DuxDrawEmpty class="size-30 mb-4" />
          <div class="text-base">
            {{ info.tag?.name || info.category?.name || '打开文档' }}
          </div>
          <div class="text-muted">
            {{ info.tag?.description || '请选择左侧菜单查看文档' }}
          </div>
        </div>
      </div>
      <div
        v-else
        class="h-full flex-1 min-w-0 flex flex-col gap-2"
      >
        <div
          class="shadow-sm text-white rounded p-4 relative overflow-hidden flex-none"
          :class="`bg-${getMethodType(info.api.method)}`"
        >
          <div class="flex justify-between  mb-4">
            <div class="flex items-center gap-2">
              <div class="p-2.5 bg-white/80 rounded-full flex items-center justify-center">
                <div class="size-5 i-tabler:api text-primary" />
              </div>
              <div class="flex flex-col gap-0.5">
                <h3 class="text-lg font-semibold leading-none">
                  {{ info.api.summary || info.api.operationId }}
                </h3>
                <div class="text-sm text-white/50">
                  {{ info.api.operationId }}
                </div>
              </div>
            </div>
            <div class="block lg:hidden">
              <NButton secondary circle @click="filter.id = []">
                <template #icon>
                  <div class="i-tabler:x size-4" />
                </template>
              </NButton>
            </div>
          </div>

          <div class="flex justify-between">
            <div class="flex items-center gap-2 tr">
              <div class="bg-white/30 rounded px-3 py-1 font-mono text-white/90">
                {{ info.api.method?.toUpperCase() }}
              </div>
              <div
                class="font-mono text-base bg-white/30 px-3 py-1 rounded hidden lg:block"
              >
                {{ info.api.path }}
              </div>
              <div
                class="border border-white/80 rounded bg-white/10 gap-2 flex items-center px-3 py-1 hover:bg-white/20 cursor-pointer transition"
                @click="() => handleCopy(info.api.path)"
              >
                <div class="size-4 i-tabler:copy" />
                复制
              </div>
            </div>
            <div>
              <div
                class="border border-white/80 rounded bg-white/10 gap-2 flex items-center px-3 py-1 hover:bg-white/20 cursor-pointer transition"
                @click="() => devOpen = !devOpen"
              >
                <div class="size-4 i-tabler:send" />
                调试
              </div>
            </div>
          </div>
        </div>
        <div
          class="flex-1 min-h-0 gap-2 self-stretch relative flex"
        >
          <div
            class="flex-1" :class="[
              devOpen ? 'hidden lg:flex' : '',
            ]"
          >
            <NScrollbar>
              <div class="space-y-4">
                <div v-if="info.api.description" class="text-sm text-muted bg-white/30 dark:bg-black/10 p-3 rounded">
                  {{ info.api.description }}
                </div>

                <div class="grid gap-2">
                  <Panel :info="info" type="path" />
                  <Panel :info="info" type="query" />
                  <Panel :info="info" type="header" />
                  <Panel :info="info" type="body" />

                  <Response v-if="info.api && info.api.responses" :responses="info.api.responses" />
                </div>
              </div>
            </NScrollbar>
          </div>
          <div v-if="devOpen" class="flex-1 absolute inset-0 lg:inset-auto lg:static">
            <Request :info="info" />
          </div>
        </div>
      </div>
    </div>
  </DuxPage>
</template>
