export const ROUTES = {
  SIGNIN: 'signin',
  SIGNUP: 'signup',
};

export const BANK_NAMES = {
  /** https://icicibank.razorpay.com/signin */
  ICICI: 'icic',
  /** https://hdfc.razorpay.com/signin */
  HDFC: 'hdfc',
  /** https://bankofbaroda.razorpay.com/signin */
  BOB: 'bob',
  /** https://axis.razorpay.com/signin */
  AXIS: 'axis',
  /** https://ndmlpaygov.razorpay.com/signin */
  NSDL: 'nsdl',
  /** https://hsbc.razorpay.com/signin */
  HSBC: 'HSBC',
  /** https://sib.razorpay.com/signin */
  SIBL: 'SIBL',
  /** https://citibank.razorpay.com/signin */
  CITI: 'citi',
  /** https://jkbank.razorpay.com/signin */
  JKB: 'jkb',
  /** https://bajaj.razorpay.com/signin */
  BAJAJ: 'bajaj',
};

export const getCookie = (name) => {
  const value = `; ${document.cookie}`;
  const parts = value.split(`; ${name}=`);
  let cookieValue = null;
  if (parts.length === 2) {
    cookieValue = parts[1].split(';')[0];
  }

  if (!cookieValue) {
    return cookieValue;
  }

  try {
    return decodeURIComponent(cookieValue);
  } catch {
    return cookieValue;
  }
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

/**
 * mobile signup is enabled when in below cases
 * #1. ?mobile-signup = true
 * #2. window.isMobileSignup = true
 * isMobileSignup is set by google optimize for A/B
 * @returns {boolean}.
 */
export const isMobileSignupEnabled = () => {
  const { 'mobile-signup': isMobileSignupParam } = getURLQueryParams(window.location.search);
  return !!window.isMobileSignup || isMobileSignupParam === 'true';
};

export const isPasswordUXImprovementEnabled = () => {
  return window.isPasswordUXExpEnabled || location.search.includes('password_ux=true'); // set from optimize
};

/**
 * window.isTestEnv is set by QA env
 * or else this can be passed via URL param as well
 * Eg: https://dashboard.qa.razorpay.in/signin?isTestEnv=true
 * In both case signin captcha will be skipped.
 * @returns {boolean}.
 */
export const isTestEnvironment = () => {
  const { isTestEnv } = getURLQueryParams(window.location.search);
  return !!window.isTestEnv || isTestEnv === 'true';
};
