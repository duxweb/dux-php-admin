<script setup lang="ts">
import { useCustom, useManage } from '@duxweb/dvha-core'
import { DuxStatsStore } from '@duxweb/dvha-pro'
import { NButton } from 'naive-ui'
import { useRouter } from 'vue-router'
import UserInfoItem from './UserInfoItem.vue'

// 获取用户信息
const { data: userInfo } = useCustom({
  path: 'system/home/profile'
})

const router = useRouter()
const manage = useManage()

// 跳转到个人资料页面
function goToProfile() {
  router.push(manage.getRoutePath('system/profile'))
}

// 跳转到安全设置页面（密码修改）
function goToSecurity() {
  router.push(manage.getRoutePath('system/profile'))
}
</script>

<template>
  <DuxStatsStore
    :title="userInfo?.data?.nickname || 'DuxLite'"
    :avatar="userInfo?.data?.avatar"
    :desc="userInfo?.data?.username || '系统管理员'"
  >
    <!-- 详细信息 -->
    <div class="space-y-3">
      <UserInfoItem label="账户状态">
        <div class="flex items-center gap-2">
          <div class="w-2 h-2 rounded-full  text-primary"></div>
          <span class="text-sm font-medium text-primary">
            {{ userInfo?.data?.status_text || '正常' }}
          </span>
        </div>
      </UserInfoItem>
      
      <UserInfoItem label="权限级别">
        <div class="flex items-center gap-2">
          <div class="w-5 h-5 rounded-full bg-primary/10 flex items-center justify-center">
            <i class="i-tabler-shield text-primary text-xs"></i>
          </div>
          <span class="text-sm font-medium text-primary">
            {{ userInfo?.data?.role_name || '管理员' }}
          </span>
        </div>
      </UserInfoItem>

      <UserInfoItem label="创建时间">
        <span class="text-sm text-muted">
          {{ userInfo?.data?.created_at_formatted || '-' }}
        </span>
      </UserInfoItem>

      <UserInfoItem label="最后登录">
        <span class="text-sm text-muted">
          {{ userInfo?.data?.login_at_formatted || '从未登录' }}
        </span>
      </UserInfoItem>
    </div>
    
    <template #footer>
      <div class="flex gap-2">
        <div class="flex-1">
          <NButton secondary block @click="goToProfile">
            修改资料
          </NButton>
        </div>
        <div class="flex-1">
          <NButton secondary block type="primary" @click="goToSecurity">
            安全设置
          </NButton>
        </div>
      </div>
    </template>
  </DuxStatsStore>
</template>