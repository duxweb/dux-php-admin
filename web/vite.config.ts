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
    rolldownOptions: {
      input: {
        index: 'main.ts',
      },
      output: {
        codeSplitting: {
          includeDependenciesRecursively: false,
          groups: [
            { name: 'vendor-vue', test: /node_modules[\\/](vue|vue-router)[\\/]/, priority: 100 },
            { name: 'vendor-naive', test: /node_modules[\\/]naive-ui[\\/]/, priority: 95 },
            { name: 'vendor-echarts', test: /node_modules[\\/](echarts|vue-echarts)[\\/]/, priority: 90 },
            { name: 'vendor-vueuse', test: /node_modules[\\/]@vueuse[\\/]/, priority: 85 },
            { name: 'vendor-pinia', test: /node_modules[\\/](pinia|pinia-plugin-persistedstate)[\\/]/, priority: 80 },
            { name: 'vendor-loader', test: /node_modules[\\/]vue3-sfc-loader[\\/]/, priority: 75 },
            { name: 'vendor-lodash', test: /node_modules[\\/](lodash|lodash-es)[\\/]/, priority: 70 },
            { name: 'vendor-icon', test: /node_modules[\\/]@iconify-json[\\/]tabler[\\/]/, priority: 68 },
            { name: 'vendor-editor', test: /node_modules[\\/](vue3-ace-editor|fabric)[\\/]/, priority: 66 },
            { name: 'vendor-ace', test: /node_modules[\\/](ace-builds|aieditor)[\\/]/, priority: 64 },
            { name: 'vendor-vee', test: /node_modules[\\/](vee-validate|@vee-validate)[\\/]/, priority: 62 },
            {
              name: 'vendor-dux-editor',
              test: /node_modules[\\/]@duxweb[\\/]dvha-pro[\\/]dist[\\/]esm[\\/]components[\\/](formEditor|designEditor|flowEditor|schemaEditor|posterEditor|textImageEditor)[\\/]/,
              priority: 61,
            },
            {
              name: 'vendor-dux-media',
              test: /node_modules[\\/]@duxweb[\\/]dvha-pro[\\/]dist[\\/]esm[\\/]components[\\/](upload|editor|draw|widget|mapCoord|chart)[\\/]/,
              priority: 60,
            },
            {
              name: 'vendor-dux',
              test: /node_modules[\\/]@duxweb[\\/]dvha-(core|pro|naiveui)[\\/]/,
              priority: 59,
            },
          ],
        },
      },
    },
  },
})
