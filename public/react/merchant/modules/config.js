import ajax from 'merchant/utils/ajax'

export const fetchConfig = () => {
  return (dispatch) => {
    return ajax({
      url: '/user/generic',
      appendModeInURL: false,
      data: {
        route_name: 'merchant_fetch_config'
      },
    }).then((response) => {
      return response.data
    })
  }
}
