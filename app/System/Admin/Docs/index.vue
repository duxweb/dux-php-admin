<script setup>
import { useOne } from '@duxweb/dvha-core'
import { DuxCard, DuxDrawEmpty, DuxPage, DuxTreeFilter } from '@duxweb/dvha-pro'
import { useClipboard } from '@vueuse/core'
import { NBadge, NButton, NCode, NScrollbar, NTag, useMessage } from 'naive-ui'
import { computed, h, onMounted, ref, watch } from 'vue'
import Panel from './panel'
import Response from './response'

const docs = ref([])
const message = useMessage()
const { copy } = useClipboard()

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
  switch (method) {
    case 'get':
      return 'primary'
    case 'post':
      return 'success'
    case 'put':
      return 'warning'
    case 'delete':
      return 'error'
    case 'patch':
      return 'info'
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
  return info.value.api ? info.value.api.name : 'API 文档'
})

function handleCopy(path) {
  copy(path)
  message.success('复制成功')
}
</script>

<template>
  <DuxPage :scrollbar="false" :card="false">
    <div class="flex gap-2 h-full">
      <div class="w-64">
        <DuxTreeFilter
          v-model:value="filter.id"
          path="docs/catalogs"
          :data="docs"
          key-field="id"
          label-field="name"
          :render-label="({ option }) => {
            return h('div', {
              class: 'flex items-center gap-2 py-1',
            }, [
              h('div', {
                class: 'flex flex-col flex-1 min-w-0',
              }, [
                h('div', {
                  class: 'text-sm flex-1 min-w-0',
                }, option.name),
                h('div', {
                  class: 'text-muted text-xs',
                }, option.path),
              ]),
              option.method ? h(NTag, {
                type: getMethodType(option.method),
                size: 'small',
              }, () => option.method) : null,
            ])
          }"
        />
      </div>
      <div class="flex-1">
        <NScrollbar>
        <div v-if="info.type === 'tag' || !info.type" class="size-full flex items-center justify-center">
          <div class="flex flex-col items-center gap-2">
            <DuxDrawEmpty class="size-30 mb-4" />
            <div class="text-base">
              {{ info.tag?.name || '打开文档' }}
            </div>
            <div class="text-muted">
              {{ info.tag?.description || '请选择左侧菜单查看文档' }}
            </div>
          </div>
        </div>
        <div v-else class="flex flex-col gap-2">
          <div class="space-y-4">
            <div class="shadow-sm text-white rounded p-4 relative overflow-hidden" :class="`bg-${getMethodType(info.api.method)}`">
              <div class="flex items-center gap-2 mb-4">
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

              <div class="flex items-center gap-2">
                <div class="bg-white/30 rounded px-3 py-1 font-mono text-white/90">
                  {{ info.api.method?.toUpperCase() }}
                </div>
                <div class="font-mono text-base bg-white/30 px-3 py-1 rounded text-white/90">
                  {{ info.api.path }}
                </div>
                <div class="border border-white/80 rounded bg-white/10 gap-2 flex items-center px-3 py-1 hover:bg-white/20 cursor-pointer transition" @click="() => handleCopy(info.api.path)">
                  <div class="size-4 i-tabler:copy" />
                  复制
                </div>
              </div>
            </div>

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
        </div>
      </NScrollbar>
      </div>
    </div>
  </DuxPage>
</template>
