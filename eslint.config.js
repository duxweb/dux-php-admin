import antfu from '@antfu/eslint-config'
import pluginQuery from '@tanstack/eslint-plugin-query'
import unusedImports from 'eslint-plugin-unused-imports'

export default antfu({
  vue: true,
  ignores: ['dist', 'node_modules', 'dist-types', 'public'],
  formatters: {
    markdown: false,
  },
  isInEditor: true,
}, {
  files: ['app/**/*.vue', 'app/**/*.ts', 'app/**/*.js', 'web/**/*.vue', 'web/**/*.ts', 'web/**/*.tsx', 'web/**/*.js'],
  plugins: {
    '@tanstack/query': pluginQuery,
    'unused-imports': unusedImports,
  },
  rules: {
    // 'no-unused-vars': 'off',
    '@tanstack/query/exhaustive-deps': 'error',
    'unused-imports/no-unused-imports': 'error',
  },
})
