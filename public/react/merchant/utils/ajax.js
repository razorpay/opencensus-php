import ajax from 'rzp/utils/ajax'
import session from 'merchant/modules/session'

export default (url, params = {}) => {
  if (typeof url === 'object') {
    params = url
  } else if (typeof url === 'string') {
    params.url = url
  }

  params.url = normalizeUrl(`/${session.currentMode}/${params.url}`)
  return ajax(params)
}

const normalizeUrl = (url) => {
  return url.replace(/\/{2,}/g, '/')
}
