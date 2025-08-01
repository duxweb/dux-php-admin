import { useClipboard } from '@vueuse/core'
import { useMessage } from 'naive-ui'

export function useClipboardWithMessage() {
  const { copy } = useClipboard()
  const message = useMessage()

  const copyText = (text) => {
    copy(text)
    message.success('已复制')
  }

  const copyExample = (example) => {
    let text
    if (example === null) {
      text = 'null'
    }
    else if (example === undefined) {
      text = 'undefined'
    }
    else if (typeof example === 'object') {
      text = JSON.stringify(example, null, 2)
    }
    else {
      text = String(example)
    }
    copyText(text)
  }

  return {
    copyText,
    copyExample,
  }
}
