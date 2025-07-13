<script setup>
import { NBadge } from 'naive-ui'
import { ref } from 'vue'
import { useClipboardWithMessage } from './hooks/useClipboard'

const props = defineProps({
  title: {
    type: String,
    required: true,
  },
  description: {
    type: String,
    default: '',
  },
  icon: {
    type: String,
    default: 'i-tabler:settings',
  },
  required: {
    type: Boolean,
    default: false,
  },
  defaultExpanded: {
    type: Boolean,
    default: false,
  },
  copyable: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['heightChange'])

const { copyText } = useClipboardWithMessage()

const isExpanded = ref(props.defaultExpanded)

function toggleExpanded() {
  isExpanded.value = !isExpanded.value
  emit('heightChange')
}

function handleCopy() {
  copyText(props.title)
}
</script>

<template>
  <div class="border border-muted rounded overflow-hidden">
    <div
      :class="`p-4 bg-muted ${isExpanded ? 'border-b' : ''} border-muted cursor-pointer hover:bg-muted/80 transition-colors`"
      @click="toggleExpanded"
    >
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
          <div class="size-8 bg-primary/10 rounded-full flex items-center justify-center">
            <div class="size-4" :class="icon" />
          </div>
          <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2">
              <span class="font-medium">{{ title }}</span>
              <slot name="title-suffix" />
              <div v-if="copyable" class="p-1 hover:bg-primary/20 rounded cursor-pointer" @click.stop="handleCopy">
                <div class="size-3 i-tabler:copy text-primary" />
              </div>
            </div>
            <div v-if="description" class="text-sm text-muted mt-1">
              {{ description }}
            </div>
          </div>
        </div>
        <div class="flex items-center gap-2">
          <NBadge :value="required ? '必需' : '可选'" :type="required ? 'error' : 'info'" />
          <div class="p-1 bg-primary/10 rounded-full transition-all duration-300" :class="{ 'rotate-180': isExpanded }">
            <div class="size-4 i-tabler:chevron-down text-primary" />
          </div>
        </div>
      </div>
    </div>

    <div v-if="isExpanded" class="p-4">
      <slot />
    </div>
  </div>
</template>
