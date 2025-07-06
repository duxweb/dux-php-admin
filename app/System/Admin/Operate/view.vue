<script setup>
import { useOne } from '@duxweb/dvha-core'
import { DuxCodeEditor, DuxModalPage } from '@duxweb/dvha-pro'

const props = defineProps({
  id: Number,
})

const { data: info } = useOne({
  path: 'system/operate',
  id: props.id,
})
</script>

<template>
  <DuxModalPage>
    <div class="flex mb-2">
      <div class="p-2 bg-primary text-white">
        {{ info?.data?.request_method }}
      </div>
      <div class="p-2 bg-primary/70 text-white">
        {{ info?.data?.request_time }}
      </div>
      <div class="p-2 bg-primary/10 text-primary flex-1">
        {{ info?.data?.request_url }}
      </div>
    </div>

    <div>
      <div class="py-2 px-4 bg-muted border border-muted border-b-none">
        请求参数
      </div>
      <DuxCodeEditor readonly :value="JSON.stringify(info?.data?.request_params, null, '\t')" />
    </div>
  </DuxModalPage>
</template>

<style scoped>

</style>
