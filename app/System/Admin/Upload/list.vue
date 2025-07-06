<script setup lang="ts">
import { DuxCard, DuxFileManage, DuxPage } from '@duxweb/dvha-pro';


</script>

<template>
  <DuxPage :card="false" :scrollbar="false">
    <DuxCard class="h-full flex flex-col" content-class="flex-1">
      <DuxFileManage :page="true" />
    </DuxCard>
  </DuxPage>
</template>

<style scoped></style>
