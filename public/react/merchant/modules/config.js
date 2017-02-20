import ajax from 'merchant/utils/ajax'

export const fetchConfig = () => {
  return (dispatch) => {
    return ajax({
      url: '/config',
      appendMode: false,
    }).then((response) => {
      return response.data
    })
  }
}
