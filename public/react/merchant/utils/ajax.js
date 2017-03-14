import ajax from 'rzp/utils/ajax'
import store from '../store'

export default (url, params = {}) => {
  if (typeof url === 'object') {
    params = url
  } else if (typeof url === 'string') {
    params.url = url
  }

  let { appendModeInURL, appendModeInQueryParam, ...ajaxParams } = params
  let mode = store.getState().session.mode

  if (appendModeInQueryParam) {
    ajaxParams.data.mode = mode
  }

  if (appendModeInURL) {
    ajaxParams.url = normalizeUrl(`/${mode}/${params.url}`)
  } else {
    ajaxParams.url = normalizeUrl(params.url)
  }

  return ajax(ajaxParams)
}

const normalizeUrl = (url) => {
  return url.replace(/\/{2,}/g, '/')
}
