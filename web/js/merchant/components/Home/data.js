import colors, { namedColors } from 'rzp/utils/chart/colors';

const API_ERROR = {
    error: 'An error occured while fetching data from the server',
  },
  API_INVALID_RESP = {
    error: 'Got unexpected response from the server',
  },
  OLDEST_TXN_ERROR = {
    error: 'Unable to get your first transaction date',
  },
  isMobileDevice = window.outerWidth <= 768;

const paymentMethodsOrder = [
    'card',
    'netbanking',
    'upi',
    'wallet',
    'bank transfer',
    'emi',
  ],
  platformColorMap = {
    desktop: namedColors.blue,
    mweb: namedColors.orange,
    android: namedColors.androidGreen,
    ios: namedColors.lightBlue,
    others: namedColors.red,
  },
  paymentMethodsColorMap = paymentMethodsOrder.reduce(
    (result, method, index) => {
      result[method] = colors[index];
      return result;
    },
    {}
  ),
  extraColors = colors.slice(paymentMethodsOrder.length);

let extraColorsUsed = 0;

const getPaymentMethodColor = paymentMethod => {
  paymentMethod = paymentMethod.toLowerCase();

  // see if color exists for the payment method or assign one from
  // extraColors
  let color = paymentMethodsColorMap[paymentMethod];

  if (!color) {
    color = extraColors[extraColorsUsed++ % extraColors.length];

    paymentMethodsOrder.push(paymentMethod);
    paymentMethodsColorMap[paymentMethod] = color;
  }

  return color;
};

const getPlatformColor = platform => {
  return platformColorMap[platform.toLowerCase()];
};

export {
  API_ERROR,
  API_INVALID_RESP,
  OLDEST_TXN_ERROR,
  isMobileDevice,
  getPlatformColor,
  getPaymentMethodColor,
};
