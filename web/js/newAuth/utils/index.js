export const ROUTES = {
  SIGNIN: 'signin',
  SIGNUP: 'signup',
  RESETPASSWORD: 'resetpassword',
  EMAIL_UPDATE: 'emailupdate',
};

export const BANK_NAMES = {
  /** https://icicibank.razorpay.com/signin */
  ICICI: 'icic',
  /** https://idfcbank.razorpay.com/signin */
  IDFC: 'IDFB',
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
  /** https://kotak.razorpay.com/signin */
  KKBK: 'KKBK',
  /** https://axiseasypay.razorpay.com/signin */
  AXIS_EASY_PAY: 'UTIB',
  /** https://hdfcbankcollectnow.razorpay.com/signin */
  HDFC_COLLECT_NOW: 'HDFC',
  /** https://yesbank.razorpay.com/signin */
  YES_BANK: 'YESB',
  /** https://indusindbank.razorpay.com/ */
  INDUSIND_BANK: 'INDB',
  /** https://indusindbankltd.razorpay.com/ */
  INDUSIND_BANK_LTD: 'ibl0',
  /** https://hdfcgig.razorpay.com/signin */
  HDFC_GIG: 'HDFC GIG',
  /** https://sib.razorpay.com/signin */
  SIB: 'SIBL',
};

export const headingDescriptionList = {
  [BANK_NAMES.AXIS]: {
    heading: 'Powered by Razorpay and Axis Bank',
    description:
      'This joint initiative between Axis Bank and Razorpay aims to make accepting payments a seamless experience for fast-growing businesses.',
  },
  [BANK_NAMES.ICICI]: {
    heading: 'ICICI Bank Eazypay Pro powered by Razorpay',
    description:
      'This joint initiative between ICICI Bank Eazypay Pro and Razorpay aims to make accepting payments a seamless experience for fast-growing businesses.',
  },
  [BANK_NAMES.HDFC]: {
    heading: 'Powered by Razorpay and HDFC',
    description:
      'This joint initiative between HDFC and Razorpay aims to make accepting payments a seamless experience for fast-growing businesses.',
  },
  [BANK_NAMES.HDFC_COLLECT_NOW]: {
    heading: 'Welcome to HDFC Bank Collect Now!',
    description:
      'Through this initiative, we aim to provide Single solution, Simpler payments and Seamless Collections for your fast-growing business',
  },
};

export const loginHelpers = {
  [BANK_NAMES.JKB]:
    'J&K Bank POS merchants: Use your registered mobile number to log in and not your email address',
};

export const isBankingOrg = (orgData) =>
  Object.keys(BANK_NAMES).some((bank) => orgData?.orgName === bank);

export const IGNORE_BG_IMAGES_BANKS = [
  BANK_NAMES.HDFC_COLLECT_NOW,
  BANK_NAMES.YES_BANK,
  BANK_NAMES.HDFC_GIG,
];

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

export const redirectToLogIn = () => {
  window.location = '/';
};

export const isPasswordUXImprovementEnabled = () => {
  return window.isPasswordUXExpEnabled || location.search.includes('password_ux=true'); // set from optimize
};

/**
 * window.isTestEnv is set by QA env
 * or else this can be passed via URL param as well
 * Eg: https://dashboard.qa.razorpay.in/signin?isTestEnv=true
 * Eg: https://dashboard.qa.razorpay.in/signup?isTestEnv=true
 * In both case signin captcha will be skipped.
 * @returns {boolean}.
 */
export const isTestEnvironment = () => {
  const { isTestEnv } = getURLQueryParams(window.location.search);
  return !!window.isTestEnv || isTestEnv === 'true';
};
