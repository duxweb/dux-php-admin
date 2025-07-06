import { NBadge, NTag } from 'naive-ui'
import { h } from 'vue'

// 类型配置
export const typeConfig = {
  string: { color: 'success', icon: 'i-tabler:abc' },
  integer: { color: 'info', icon: 'i-tabler:123' },
  number: { color: 'warning', icon: 'i-tabler:decimal' },
  boolean: { color: 'error', icon: 'i-tabler:toggle-left' },
  array: { color: 'primary', icon: 'i-tabler:list' },
  object: { color: 'primary', icon: 'i-tabler:braces' },
  default: { color: 'default', icon: 'i-tabler:question-mark' }
}

// 获取类型颜色
export function getTypeColor(type) {
  return typeConfig[type]?.color || 'default'
}

// 获取类型图标
export function getTypeIcon(type) {
  return typeConfig[type]?.icon || 'i-tabler:question-mark'
}

// 格式化示例值
export function formatExample(example) {
  if (typeof example === 'object') {
    return JSON.stringify(example, null, 2)
  }
  return String(example)
}

// 格式化示例值（简短版本，用于树节点）
export function formatExampleShort(example) {
  if (typeof example === 'object') {
    return JSON.stringify(example)
  }
  return String(example)
}

// 递归转换 Schema 为树形数据
export function schemaToTree(schema, name = 'root', required = false) {
  if (!schema) return null

  const node = {
    key: `${name}_${Date.now()}_${Math.random()}`,
    label: name,
    schema,
    required,
    type: schema.type || 'any'
  }

  // 处理对象类型
  if (schema.type === 'object' && schema.properties) {
    node.children = Object.entries(schema.properties)
      .map(([key, val]) => schemaToTree(val, key, schema.required?.includes(key)))
      .filter(Boolean)
  }

  // 处理数组类型
  if (schema.type === 'array' && schema.items) {
    node.children = [schemaToTree(schema.items, 'items')]
  }

  return node
}

// 生成树形数据
export function getTreeData(schema) {
  const result = []

  if (schema?.type === 'object' && schema.properties) {
    Object.entries(schema.properties).forEach(([key, val]) => {
      const node = schemaToTree(val, key, schema.required?.includes(key))
      if (node) result.push(node)
    })
  }

  return result
}

// 树节点渲染
export function renderLabel({ option }) {
  const config = typeConfig[option.type] || typeConfig.default

  return h('div', { class: 'flex items-center gap-2 py-1' }, [
    h('div', { class: `size-4 ${config.icon}` }),
    h('span', { class: 'font-medium text-sm' }, option.label),
    h(NTag, { type: config.color, size: 'small' }, { default: () => option.type }),
    option.required && h(NBadge, { value: '必需', type: 'error' }),
    option.schema?.description && h('span', { class: 'text-xs text-muted ml-2' }, `- ${option.schema.description}`),
    option.schema?.example !== undefined && h('span', { class: 'text-xs text-muted ml-2' }, `例: ${formatExampleShort(option.schema.example)}`)
  ].filter(Boolean))
}