<script setup lang="ts">
import { useList } from '@duxweb/dvha-core'
import { DuxTree } from '@duxweb/dvha-naiveui'
import { DuxCollapsePanel, DuxFormItem, DuxPageForm } from '@duxweb/dvha-pro'
import { NCheckbox, NInput, NSelect } from 'naive-ui'
import { computed, ref, watch } from 'vue'
import { useRoute } from 'vue-router'

const model = ref<Record<string, any>>({
  permission: [],
})
const permission = ref<Record<string, any>>({})

const route = useRoute()

const { data } = useList({
  path: 'system/role/permission',
})

watch(data, (v) => {
  permission.value = v?.data || []
}, {
  immediate: true,
})

function changeGroup(v, name) {
  if (v) {
    model.value.permission.push(name)
  }
  else {
    model.value.permission = model.value.permission.filter((v) => {
      return v !== name
    })
  }

  const group = permission.value?.find((item) => {
    return item.name === name
  })

  const names = group?.children?.map((child) => {
    return child.name
  })
  if (v) {
    model.value.permission = model.value.permission.concat(names)
  }
  else {
    names?.forEach((name) => {
      model.value.permission = model.value.permission.filter((v) => {
        return v !== name
      })
    })
  }
}

function changeNode(v, name) {
  if (v) {
    model.value.permission.push(name)
  }
  else {
    model.value.permission = model.value.permission.filter((v) => {
      return v !== name
    })
  }
}

function isSelect(groupName) {
  const group = permission.value?.find((item) => {
    return item.name === groupName
  })

  if (model.value.permission.includes(groupName)) {
    return true
  }

  return group?.children?.some(child =>
    model.value.permission.includes(child.name),
  ) || false
}

const id = computed(() => {
  return route?.params?.id as string
})
</script>

<template>
  <DuxPageForm :id="id" :data="model" path="system/role">
    <DuxFormItem label="信息" description="请输入角色名称">
      <NInput v-model:value="model.name" />
    </DuxFormItem>
    <DuxFormItem label="描述" description="请输入角色描述">
      <NInput v-model:value="model.desc" />
    </DuxFormItem>
    <DuxFormItem label="数据权限" description="定义该角色可操作的数据权限">
      <NSelect
        v-model:value="model.data_type" :options="[
          {
            label: '全部数据',
            value: 0,
          },
          {
            label: '本部门数据',
            value: 1,
          },
          {
            label: '本部门及以下数据',
            value: 2,
          },
          {
            label: '仅本人数据',
            value: 3,
          },
          {
            label: '自定义数据',
            value: 4,
          },
        ]"
      />
    </DuxFormItem>

    <DuxFormItem v-show="model.data_type === 4" label="自定义数据" description="该角色自定义数据权限">
      <DuxTree v-model:value="model.data_permission" path="system/dept" key-field="id" label-field="name" />
    </DuxFormItem>

    <DuxFormItem label="功能权限" description="定义该角色可使用的功能权限">
      <div class="flex flex-col gap-2 w-full">
        <DuxCollapsePanel
          v-for="(group, index) in permission" :key="index" :title="group.label" :desc="group.name"
          :highlight="isSelect(group.name)" :default-show="false"
        >
          <template #suffix>
            <NCheckbox
              :style="{
                '--n-border': '1px solid rgb(var(--ui-border-accented))'
              }"
              :value="group.name" :checked="model.permission.includes(group.name)"
              @update:checked="(v) => changeGroup(v, group.name)"
            />
          </template>
          <div v-if="group?.children?.length > 0" class="grid grid-cols-2 lg:grid-cols-4 gap-y-4 gap-x-6">
            <label v-for="(item, key) in group?.children" :key="key" class="gap-2 flex items-center">
              <NCheckbox
                :value="item.name" :label="item.label || item.name" :checked="model.permission.includes(item.name)"
                @update:checked="(v) => changeNode(v, item.name)"
              />
            </label>
          </div>
          <div v-else class="text-gray-6">
            暂无子权限
          </div>
        </DuxCollapsePanel>
      </div>
    </DuxFormItem>
  </DuxPageForm>
</template>

<style scoped></style>
