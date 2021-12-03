export const ROUTES = {
  SIGNIN: 'signin',
  SIGNUP: 'signup',
};

export const BANK_NAMES = {
  ICICI: 'icic',
  HDFC: 'hdfc',
  BOB: 'bob',
  AXIS: 'axis',
};

export const getCookie = (name) => {
  const value = `; ${document.cookie}`;
  const parts = value.split(`; ${name}=`);
  if (parts.length === 2) return parts[1].split(';')[0];
  return null;
};

//No using rzp utils as it impacts the signup bundle perfromce

export const currencySymbols = {
  INR: '₹',
  USD: 'US$',
};

export const getFixedNumber = (value) => {
  if (typeof value === 'number') {
    value = value.toFixed(2);
  }
  const valueArr = value.split('.');
  if (valueArr[1] == '00') {
    value = valueArr[0].replace('.', '');
  }
  return value;
};

const numberFormatRegex = /(.{1,2})(?=.(..)+(\...)$)/g;
export const getFormattedNumber = (value) => {
  if (typeof value === 'number') {
    value = value.toFixed(2);
  }
  value = value.replace(numberFormatRegex, '$1,');
  return getFixedNumber(value);
};

export const getFormattedAmount = (amount, showCurrency, currency = 'INR') => {
  const formattedAmount = getFormattedNumber((amount / 100).toFixed(2));

  return (showCurrency ? currencySymbols[currency] : '') + formattedAmount;
};

export const getURLQueryParams = (url = document.location.hash) => {
  const search = url.split('?')[1];
  let params = {};

  if (search) {
    /* split using '&' as separator
      and get the key value pairs for query params. */
    params = search.split('&').reduce((prev, curr) => {
      const [key, value] = curr.split('=');
      prev[key] = value;
      return prev;
    }, {});
  }

  return params;
};

export const getHostName = () => {
  return window.location.hostname;
};
