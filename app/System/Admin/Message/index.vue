<script setup lang="ts">
import { useResource } from '@duxweb/dux-vue-admin'

const resource = useResource()
</script>

<template>
  <dux-message-page :tabs="[
    {
      label: '全部',
      value: 'all',
      icon: 'i-tabler:message',
    },
    {
      label: '未读',
      value: 'unread',
      icon: 'i-tabler:mail',
    },
    {
      label: '已读',
      value: 'read',
      icon: 'i-tabler:mail-opened',
    },
  ]" :url="resource.messageUrl" label-field="title" desc-field="desc" content-field="content" time-field="time" />
</template>

<style scoped></style>

<route lang="json">[
  {
    "label": "我的消息",
    "name": "system.message",
    "path": "message",
    "loader": "system/message/index",
    "parent": "system",
    "sort": 0,
    "hidden": true
  }
]</route>
