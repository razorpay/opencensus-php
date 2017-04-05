import ajax from 'merchant/utils/ajax'
import loadScript from 'rzp/utils/loadScript'

export const fetchHost = () => {
  return (dispatch) => {
    return ajax('/apihost', { appendModeInURL: false })
  }
}

export const fetchKeys = () => {
  return (dispatch) => {
    return ajax('/keys').then((response) => {
      if (response.data.count) {
        return response.data.items[0].id
      }
      throw new Error('No valid api keys found, check Api Keys page')
    })
  }
}

export const loadCheckout = (apiURL) => {
  return (dispatch) => {
    let checkoutURL = 'https://checkout.razorpay.com/'
    let api = document.createElement('a')
    api.href = apiURL

    // We call this to ensure that Checkout is calling the correct API
    // Skipped in production
    if (typeof window.Razorpay !== 'function' && apiURL !== 'https://api.razorpay.com/v1/') {
      window.Razorpay = {
        config: {
          api: api.protocol + '//' + api.hostname + '/',
          js: checkoutURL   // path for checkout
        }
      };
    }

    checkoutURL += 'v1/checkout.js'
    return loadScript(checkoutURL)
  }
}

export const addFunds = (data = {}) => {
  return (dispatch) => {
    return ajax({
      url: '/addfunds',
      method: 'post',
      data
    })
  }
}
