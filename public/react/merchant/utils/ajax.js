import ajax from 'rzp/utils/ajax'
import store from '../store'

export default (url, params = {}) => {
  if (typeof url === 'object') {
    params = url
  } else if (typeof url === 'string') {
    params.url = url
  }

  let appendMode = params.appendMode !== undefined ? params.appendMode : true
  let currentMode = appendMode ? store.getState().session.mode : ''
  params.url = normalizeUrl(`/${currentMode}/${params.url}`)
  return ajax(params)
}

const normalizeUrl = (url) => {
  return url.replace(/\/{2,}/g, '/')
}
