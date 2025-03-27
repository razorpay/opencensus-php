import { merchantFetch } from 'merchant/utils/ajax';
import loadScript from 'common/utils/loadScript';

export const fetchKeys = currentUser => {
  return merchantFetch('keys').then(response => {
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
  loadCheckout(window.checkout_api_host);
  fetchKeys(currentUser)
    .then(onSuccess)
    .catch(onError);
};
