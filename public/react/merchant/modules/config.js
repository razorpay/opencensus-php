import ajax from 'merchant/utils/ajax'

export const fetchConfig = () => {
  return (dispatch) => {
    return ajax({
      url: '/generic',
      data: {
        route_name: 'merchant_fetch_config'
      },
      appendMode: false,
    }).then((response) => {
      return response.data
    })
  }
}
