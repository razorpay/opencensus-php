import ajax from 'rzp/utils/ajax'
import session from 'merchant/modules/session'

export default (url, params = {}) => {
  if (typeof url === 'object') {
    params = normalizeParams(url)
  } else if (typeof url === 'string') {
    params = normalizeParams(params)
    params.url = url
  }

  params.url = normalizeUrl(`/${session.currentMode}/${params.url}`)
  return ajax(params)
}

const normalizeUrl = (url) => {
  return url.replace(/\/{2,}/g, '/')
}

// Converts the immutable to plain objects
const normalizeParams = (params) => {
  if (params.data) {
    params.data = typeof params.data.toJS === 'function' ? params.data.toJS() : params.data
  }
  return params
}
