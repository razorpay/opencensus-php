import colors, { namedColors } from './chart/colors';

const API_ERROR = {
  error: 'An error occured while fetching data from the server',
};
const API_INVALID_RESP = {
  error: 'Got unexpected response from the server',
};
const OLDEST_TXN_ERROR = {
  error: 'Unable to get your first transaction date',
};
const isMobileDevice = (customWidth) =>
  document.documentElement?.clientWidth
    ? document.documentElement.clientWidth <= (customWidth ? customWidth : 767)
    : window.innerWidth <= (customWidth ? customWidth : 767);

const paymentMethodsOrder = [
  'card',
  'netbanking',
  'upi',
  'wallet',
  'bank transfer',
  'emi',
  'emandate',
];
const platformsOrder = ['desktop', 'mweb', 'android', 'ios', 'others'];
const platformColors = [
  namedColors.blue,
  namedColors.orange,
  namedColors.androidGreen,
  namedColors.lightBlue,
  namedColors.red,
];
const platformColorMap = platformsOrder.reduce((result, platform, index) => {
  result[platform] = platformColors[index];
  return result;
}, {});
const paymentMethodsColorMap = paymentMethodsOrder.reduce((result, method, index) => {
  result[method] = colors[index];
  return result;
}, {});
const extraColors = colors.slice(paymentMethodsOrder.length);

let extraColorsUsed = 0;

const getPaymentMethodColor = (paymentMethod) => {
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

const getPlatformColor = (platform) => {
  return platformColorMap[platform.toLowerCase()];
};

export {
  API_ERROR,
  API_INVALID_RESP,
  OLDEST_TXN_ERROR,
  isMobileDevice,
  platformsOrder,
  getPlatformColor,
  paymentMethodsOrder,
  getPaymentMethodColor,
};
