import vue from '@vitejs/plugin-vue'
import VueJsx from '@vitejs/plugin-vue-jsx'
import { defineConfig } from 'vite'

export default defineConfig({
  plugins: [
    vue(),
    VueJsx(),
  ],
  base: '/static/web/',
  resolve: {
  },
  server: {
    cors: {
      origin: '*',
    },
  },
  build: {
    emptyOutDir: true,
    outDir: '../public/static/web',
    manifest: true,
    cssCodeSplit: false,
    chunkSizeWarningLimit: 500,
    rollupOptions: {
      input: {
        index: 'main.ts',
      },
      output: {
        manualChunks: {
          'vendor-vue': ['vue', 'vue-router'],
          'vendor-naive': ['naive-ui'],
          'vendor-echarts': ['echarts', 'vue-echarts'],
          'vendor-vueuse': ['@vueuse/core'],
          'vendor-pinia': ['pinia', 'pinia-plugin-persistedstate'],
          'vendor-loader': ['vue3-sfc-loader'],
          'vendor-lodash': ['lodash-es', 'lodash'],
          'vendor-tools': ['colorizr', 'jsep', 'clsx', 'mathjs', 'mime'],
          'vendor-icon': ['@iconify-json/tabler'],
          'vendor-dux': ['@duxweb/dvha-core', '@duxweb/dvha-pro', '@duxweb/dvha-naiveui'],
          'vendor-editor': ['vue3-ace-editor'],
          'vendor-ace': ['ace-builds', 'aieditor'],
          'vendor-vee': ['vee-validate', '@vee-validate/i18n', '@vee-validate/rules'],
        },
      },
    },
  },
})
