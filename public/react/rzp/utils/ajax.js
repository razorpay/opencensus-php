import { getCookie } from './cookies'

/*
 * jQuery deferred promises doesn't align with the Promises/A+ spec.
 * You can check the difference at http://codepen.io/selvagsz/pen/oYNjJb?editors=0010
 * So transforming the $.ajax into true promises.
 */

export default (params = {}) => {
  return new Promise((resolve, reject) => {
    let { headers = {} } = params
    headers['X-XSRF-TOKEN'] = getCookie('XSRF-TOKEN')
    headers['Accept'] = 'application/json, text/plain, */*'
    params.headers = headers

    $.ajax(params).then((response) => {
      if (response.success) {
        resolve(response)
      } else {
        reject(Object.assign({
          code: 'UNKNOWN_ERROR_CODE',
        }, response))
      }
    }, (err) => {
      reject(Object.assign({
        code: err.status
      }, err.responseJSON))
    })
  })
}
