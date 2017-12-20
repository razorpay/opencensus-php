import ajax from 'merchant/utils/ajax';
import loadScript from 'rzp/utils/loadScript';

export const fetchHost = () => ajax('/apihost', { appendModeInURL: false });

export const fetchKeys = currentUser => {
  let params = {
    route_name: 'merchant_fetch_keys',
    url_params: {
      '{id}': currentUser,
    },
  };

  return ajax({
    url: '/user/generic',
    data: params,
    appendModeInQueryParam: true,
  }).then(response => {
    if (response.data.count) {
      return response.data.items[0].id;
    }
    throw ['No valid api keys found, check Api Keys page'];
  });
};

export const loadCheckout = apiURL => {
  let checkoutURL = 'https://checkout.razorpay.com/';
  let api = document.createElement('a');
  api.href = apiURL;

  // We call this to ensure that Checkout is calling the correct API
  // Skipped in production
  if (
    typeof window.Razorpay !== 'function' &&
    apiURL !== 'https://api.razorpay.com/v1/'
  ) {
    window.Razorpay = {
      config: {
        api: api.protocol + '//' + api.hostname + '/',
        js: checkoutURL, // path for checkout
      },
    };
  }

  checkoutURL += 'v1/checkout.js';
  return loadScript(checkoutURL);
};

export default (currentUser, onSuccess, onError) => {
  Promise.all([
    fetchHost().then(({ data }) => {
      loadCheckout(data);
    }),
    fetchKeys(currentUser).then(onSuccess),
  ]).catch(onError);
};
