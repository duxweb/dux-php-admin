<script setup lang="ts">
import { DuxPage, useDrawer } from '@duxweb/dvha-pro'
import { useWindowSize } from '@vueuse/core'
import { NButton } from 'naive-ui'
import { ref } from 'vue'
import Form from './form.vue'
import Sider from './sider.vue'

const id = ref<number>()

function onUpdateValue(value: number) {
  id.value = value
}

const { width } = useWindowSize()

const drawer = useDrawer()
</script>

<template>
  <DuxPage :card="false" :scrollbar="false">
    <div class="flex gap-2 h-full">
      <div v-if="width >= 1024" class="w-64">
        <Sider :value="id" @update-value="onUpdateValue" />
      </div>
      <div class="flex-1 min-w-0">
        <Form :id="id">
          <template #headerExtra>
            <div class="lg:hidden">
              <NButton
                @click="() => {
                  drawer.show({
                    title: '选择菜单',
                    component: () => import('./sider.vue'),
                    componentProps: {
                      value: id,
                      onUpdateValue: (value) => {
                        id = value
                      },
                    },
                  })
                }"
              >
                <template #icon>
                  <div class="i-tabler:menu-2" />
                </template>
              </NButton>
            </div>
          </template>
        </Form>
      </div>
    </div>
  </DuxPage>
</template>

<style scoped>

</style>
