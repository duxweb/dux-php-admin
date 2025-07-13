import CryptoJS from 'crypto-js'
import { defineStore } from 'pinia'
import { computed, ref } from 'vue'

export const useRequestStore = defineStore('docs-request', () => {
  // 基础配置 - 持久化存储
  const config = ref({
    baseURL: window.location.origin,
    timeout: 30000,
    secretID: '',
    secretKey: '',
    headers: [
      { key: 'Content-Type', value: 'application/json', enabled: true },
      { key: 'Accept', value: 'application/json', enabled: true },
      { key: 'Authorization', value: '', enabled: true },
    ],
  })

  // 当前 API 信息
  const currentApi = ref(null)

  // 请求参数 - 使用 NDynamicInput 的格式
  const pathParams = ref([])
  const queryParams = ref([])
  const headerParams = ref([])
  const formFields = ref([])
  const bodyParams = ref({
    type: 'json',
    content: '',
    jsonContent: '', // 保存json内容
    rawContent: '', // 保存raw内容
  })

  // 请求状态
  const loading = ref(false)
  const result = ref(null)
  const error = ref(null)

  // 实际请求信息
  const requestInfo = ref({
    url: '',
    method: '',
    headers: {},
    body: null,
  })

  // 计算属性
  const enabledHeaders = computed(() => {
    return config.value.headers.filter(h => h.enabled)
  })

  const requestHeaders = computed(() => {
    const headers = {}

    // 添加配置的headers
    enabledHeaders.value.forEach((h) => {
      headers[h.key] = h.value
    })

    // 添加API特定的headers
    headerParams.value.forEach((param) => {
      if (param.key && param.value) {
        headers[param.key] = param.value
      }
    })

    return headers
  })

  // 检查表单是否需要 multipart
  const needsMultipart = computed(() => {
    return formFields.value.some(field => field.type === 'file')
  })

  // 重置参数
  function resetParams() {
    pathParams.value = []
    queryParams.value = []
    headerParams.value = []
    formFields.value = []
    bodyParams.value = {
      type: 'json',
      content: '',
      jsonContent: '',
      rawContent: '',
    }
    result.value = null
    error.value = null
    requestInfo.value = {
      url: '',
      method: '',
      headers: {},
      body: null,
    }
  }

  // 生成签名
  function generateSignature(url, query, timestamp, secretKey) {
    const signData = [
      url,
      query || '',
      timestamp.toString(),
    ]
    const signStr = signData.join('\n')

    // 使用 CryptoJS 生成 HMAC-SHA256 签名
    const signature = CryptoJS.HmacSHA256(signStr, secretKey)
    return signature.toString(CryptoJS.enc.Hex)
  }

  // 切换 body 类型时保持内容
  function switchBodyType(newType) {
    const oldType = bodyParams.value.type

    // 保存当前内容
    if (oldType === 'json') {
      bodyParams.value.jsonContent = bodyParams.value.content
    }
    else if (oldType === 'raw') {
      bodyParams.value.rawContent = bodyParams.value.content
    }

    // 切换类型并恢复内容
    bodyParams.value.type = newType
    if (newType === 'json') {
      bodyParams.value.content = bodyParams.value.jsonContent
    }
    else if (newType === 'raw') {
      bodyParams.value.content = bodyParams.value.rawContent
    }
    else {
      bodyParams.value.content = ''
    }
  }

  // 生成请求体示例
  function generateBodyExample(schema) {
    if (!schema)
      return ''

    function generateExample(schemaObj) {
      // 优先使用已定义的示例或默认值
      if (schemaObj.example !== undefined)
        return schemaObj.example
      if (schemaObj.default !== undefined)
        return schemaObj.default

      switch (schemaObj.type) {
        case 'string':
          return schemaObj.enum ? schemaObj.enum[0] : 'string'
        case 'number':
        case 'integer':
          return 0
        case 'boolean':
          return true
        case 'array':
          return schemaObj.items ? [generateExample(schemaObj.items)] : []
        case 'object': {
          const obj = {}
          if (schemaObj.properties) {
            Object.keys(schemaObj.properties).forEach((key) => {
              obj[key] = generateExample(schemaObj.properties[key])
            })
          }
          return obj
        }
        default:
          return null
      }
    }

    try {
      const example = generateExample(schema)
      return JSON.stringify(example, null, 2)
    }
    catch {
      return ''
    }
  }

  // 生成表单示例
  function generateFormExample(schema) {
    if (!schema) {
      return []
    }

    const fields = []
    if (schema.properties) {
      Object.keys(schema.properties).forEach((key) => {
        const prop = schema.properties[key]
        // 只保留 text 和 file 两种类型
        const fieldType = (prop.format === 'binary' || (prop.type === 'string' && prop.format === 'binary')) ? 'file' : 'text'

        fields.push({
          key,
          value: fieldType === 'file' ? '' : (prop.example || prop.default || ''),
          type: fieldType,
        })
      })
    }

    return fields
  }

  // 设置当前 API
  function setCurrentApi(api) {
    currentApi.value = api
    resetParams()

    // 自动填充参数
    if (api?.parameters) {
      api.parameters.forEach((param) => {
        const defaultValue = param.example || param.default || ''
        const paramObj = { key: param.name, value: defaultValue }

        switch (param.in) {
          case 'path':
            pathParams.value.push(paramObj)
            break
          case 'query':
            queryParams.value.push(paramObj)
            break
          case 'header':
            headerParams.value.push(paramObj)
            break
        }
      })
    }

    // 自动填充请求体
    if (api?.requestBody?.content) {
      const content = api.requestBody.content

      if (content['application/json']) {
        bodyParams.value.type = 'json'
        const jsonContent = generateBodyExample(content['application/json'].schema)
        bodyParams.value.content = jsonContent
        bodyParams.value.jsonContent = jsonContent
      }
      else if (content['application/x-www-form-urlencoded'] || content['multipart/form-data']) {
        bodyParams.value.type = 'form'
        const schema = content['application/x-www-form-urlencoded']?.schema || content['multipart/form-data']?.schema
        formFields.value = generateFormExample(schema)
      }
      else if (content['text/plain']) {
        bodyParams.value.type = 'raw'
        bodyParams.value.content = ''
      }
      else {
        // 处理其他类型
        const firstType = Object.keys(content)[0]
        if (firstType?.includes('json')) {
          bodyParams.value.type = 'json'
          const jsonContent = generateBodyExample(content[firstType].schema)
          bodyParams.value.content = jsonContent
          bodyParams.value.jsonContent = jsonContent
        }
        else if (firstType?.includes('form')) {
          bodyParams.value.type = 'form'
          formFields.value = generateFormExample(content[firstType].schema)
        }
        else {
          bodyParams.value.type = 'raw'
          bodyParams.value.content = ''
        }
      }
    }
  }

  // 生成请求内容
  function generateRequestContent() {
    if (!currentApi.value) {
      return ''
    }

    const api = currentApi.value

    // 构建请求URL
    let url = api.path
    pathParams.value.forEach((param) => {
      if (param.key && param.value) {
        url = url.replace(`{${param.key}}`, param.value)
      }
    })

    // 构建完整 URL
    const fullUrl = new URL(url, config.value.baseURL || window.location.origin)

    // 添加查询参数
    if (queryParams.value.length > 0) {
      queryParams.value.forEach((param) => {
        if (param.key && param.value) {
          fullUrl.searchParams.append(param.key, param.value)
        }
      })
    }

    // 生成 curl 命令
    const method = api.method?.toUpperCase() || 'GET'
    let curlCommand = `curl -X ${method} '${fullUrl.toString()}'`

    // 添加请求头
    const headers = requestHeaders.value
    Object.entries(headers).forEach(([key, value]) => {
      if (key && value) {
        curlCommand += ` \\\n  -H '${key}: ${value}'`
      }
    })

    // 添加请求体
    if (['POST', 'PUT', 'PATCH'].includes(method)) {
      switch (bodyParams.value.type) {
        case 'json':
          if (bodyParams.value.content) {
            try {
              const jsonData = JSON.parse(bodyParams.value.content)
              curlCommand += ` \\\n  -d '${JSON.stringify(jsonData)}'`
            }
            catch {
              curlCommand += ` \\\n  -d '${bodyParams.value.content}'`
            }
          }
          break
        case 'form':
          if (formFields.value.length > 0) {
            formFields.value.forEach((field) => {
              if (field.key && field.value) {
                if (field.type === 'file') {
                  curlCommand += ` \\\n  -F '${field.key}=@${field.value instanceof File ? field.value.name : field.value}'`
                }
                else {
                  curlCommand += ` \\\n  -F '${field.key}=${field.value}'`
                }
              }
            })
          }
          break
        case 'raw':
          if (bodyParams.value.content) {
            curlCommand += ` \\\n  -d '${bodyParams.value.content}'`
          }
          break
      }
    }

    return curlCommand
  }

  // 构建请求体
  function buildRequestBody(requestOptions) {
    switch (bodyParams.value.type) {
      case 'json':
        if (bodyParams.value.content) {
          try {
            requestOptions.body = JSON.stringify(JSON.parse(bodyParams.value.content))
            requestOptions.headers['Content-Type'] = 'application/json'
          }
          catch {
            requestOptions.body = bodyParams.value.content
            requestOptions.headers['Content-Type'] = 'text/plain'
          }
        }
        break
      case 'form':
        if (formFields.value.length > 0) {
          if (needsMultipart.value) {
            // 使用 FormData 处理文件上传
            const formData = new FormData()
            formFields.value.forEach((field) => {
              if (field.key && field.value) {
                formData.append(field.key, field.value)
              }
            })
            requestOptions.body = formData
            // 不设置 Content-Type，让浏览器自动设置
          }
          else {
            // 使用 URLSearchParams 处理普通表单
            const params = new URLSearchParams()
            formFields.value.forEach((field) => {
              if (field.key && field.value) {
                params.append(field.key, field.value)
              }
            })
            requestOptions.body = params
            requestOptions.headers['Content-Type'] = 'application/x-www-form-urlencoded'
          }
        }
        break
      case 'raw':
        if (bodyParams.value.content) {
          requestOptions.body = bodyParams.value.content
          requestOptions.headers['Content-Type'] = 'text/plain'
        }
        break
    }
  }

  // 执行请求
  async function executeRequest() {
    if (!currentApi.value) {
      return
    }

    loading.value = true
    error.value = null
    result.value = null

    try {
      const startTime = Date.now()

      // 构建请求URL
      let url = currentApi.value.path
      pathParams.value.forEach((param) => {
        if (param.key && param.value) {
          url = url.replace(`{${param.key}}`, param.value)
        }
      })

      // 构建完整 URL
      const fullUrl = new URL(url, config.value.baseURL || window.location.origin)

      // 添加查询参数
      if (queryParams.value.length > 0) {
        queryParams.value.forEach((param) => {
          if (param.key && param.value) {
            fullUrl.searchParams.append(param.key, param.value)
          }
        })
      }

      // 构建请求配置
      const requestOptions = {
        method: currentApi.value.method?.toUpperCase() || 'GET',
        headers: { ...requestHeaders.value },
        signal: AbortSignal.timeout(config.value.timeout),
      }

      // 添加API签名
      if (config.value.secretID && config.value.secretKey) {
        const timestamp = Math.floor(Date.now() / 1000)
        const pathname = fullUrl.pathname
        const query = fullUrl.search ? fullUrl.search.substring(1) : ''

        try {
          const signature = generateSignature(pathname, query, timestamp, config.value.secretKey)
          requestOptions.headers.AccessKey = config.value.secretID
          requestOptions.headers['Content-Date'] = timestamp.toString()
          requestOptions.headers['Content-MD5'] = signature
        }
        catch (signError) {
          error.value = `签名生成失败: ${signError.message}`
          return
        }
      }

      // 添加请求体
      let requestBody = null
      if (['POST', 'PUT', 'PATCH'].includes(requestOptions.method)) {
        buildRequestBody(requestOptions)
        requestBody = requestOptions.body
      }

      // 保存实际请求信息
      requestInfo.value = {
        url: fullUrl.toString(),
        method: requestOptions.method,
        headers: { ...requestOptions.headers },
        body: requestBody,
      }

      const response = await fetch(fullUrl.toString(), requestOptions)
      const endTime = Date.now()

      // 解析响应头
      const responseHeaders = {}
      response.headers.forEach((value, key) => {
        responseHeaders[key] = value
      })

      // 解析响应数据
      const contentType = response.headers.get('content-type')
      let responseData

      if (contentType?.includes('application/json')) {
        responseData = await response.json()
      }
      else if (contentType?.includes('text/')) {
        responseData = await response.text()
      }
      else {
        responseData = await response.blob()
      }

      result.value = {
        status: response.status,
        statusText: response.statusText,
        headers: responseHeaders,
        data: responseData,
        time: endTime - startTime,
      }
    }
    catch (err) {
      switch (err.name) {
        case 'AbortError':
          error.value = '请求超时'
          break
        case 'TypeError':
          error.value = '网络错误'
          break
        default:
          error.value = err.message || '请求失败'
      }
    }
    finally {
      loading.value = false
    }
  }

  // 保存配置
  function saveConfig(newConfig) {
    config.value = { ...config.value, ...newConfig }
  }

  return {
    // 状态
    config,
    currentApi,
    pathParams,
    queryParams,
    headerParams,
    formFields,
    bodyParams,
    loading,
    result,
    error,
    requestInfo,

    // 计算属性
    enabledHeaders,
    requestHeaders,
    needsMultipart,

    // 方法
    setCurrentApi,
    switchBodyType,
    generateRequestContent,
    executeRequest,
    saveConfig,
  }
}, {
  persist: {
    pick: ['config'],
  },
})
