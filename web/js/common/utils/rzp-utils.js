// Todo: delete this file, it's available in @dashboard/shared-utils
/* eslint-disable babel/no-unused-expressions */
/* eslint-disable guard-for-in */
/* eslint-disable no-useless-escape */
/* eslint-disable func-names */
/* eslint-disable consistent-return */
/* eslint-disable no-unneeded-ternary */
/* eslint-disable no-shadow */
/* eslint-disable vars-on-top */
/* eslint-disable no-var */
/* eslint-disable prefer-template */
/* eslint-disable no-unused-vars */
/* eslint-disable one-var */
/* eslint-disable no-multi-assign */
/* eslint-disable valid-jsdoc */
/* eslint-disable no-use-before-define */
/* eslint-disable prefer-const */
import { formatNumberByParts, convertToMajorUnit } from '@razorpay/i18nify-js/currency';
import axios from 'axios';
import { saveAs } from 'file-saver';
import isEmpty from 'lodash/isEmpty';
import moment from 'moment';
import { utils, write } from 'xlsx';

import { SENSITIVE_FIELDS } from 'common/constant';
import currencies from 'merchant/constants/currency';
import { CURRENCY_FORMATTERS } from 'merchant/helpers/currency/helper';
import abExperimentsMap from 'merchant/utils/abExperimentsMap';

import { acronyms, shortenText } from './acronyms';

moment.updateLocale('en', {
  relativeTime: {
    s: 'few secs',
    ss: '%s secs',
    m: 'a min',
    mm: '%d mins',
  },
});

// https://docs.adyen.com/development-resources/currency-codes
// Ideally this should come from BE
const CURRENCY_DECIMALS = {
  INR: 2,
  MYR: 2,
};

const MONETARY_UNIT_TEXT = {
  IN: 'paise',
  MY: 'cents',
};

export function isMobileResolution() {
  return window && window.innerWidth <= 768;
}

export function isFunction(value) {
  return typeof value === 'function';
}

export function isDefined(value) {
  return typeof value !== 'undefined';
}

export function capitalizeFirstLetter(str) {
  return str.charAt(0).toUpperCase() + str.slice(1);
}

/* Delimiters are space / underscore */
export function titleCase(sentence) {
  return (sentence || '')
    .split(/\s+|_/)
    .map((word) => word.charAt(0).toUpperCase() + word.substr(1).toLowerCase())
    .join(' ');
}

export function humanize(sentence) {
  return titleCase(sentence.split('_').join(' '));
}

export function getCommonAnalyticsProperties(user, config = {}) {
  if (!user) {
    // skip properties if sesion is expired/user details are not availble
    return {};
  }

  const { addUserProperties = false } = config;

  let userProperties = {};

  if (addUserProperties) {
    userProperties = {
      business_type: user.business_type,
      activation_status: user.activated,
      current_activation_status: user.activation_status,
      previous_activation_status:
        user.activationStatusChangeLogs?.[user.activationStatusChangeLogs.length - 1],
      user_business_category: user.business_category,
      user_business_sub_category: user.business_subcategory,
    };
  }

  const mode = localStorage?.getItem(`rzp_mode--${user.id}`);
  return {
    userId: user?.user?.id || 'Unknown',
    mode,
    userRole: user?.role || 'Unknown',
    merchantId: user?.current || 'Unknown',
    ...userProperties,
  };
}

export const isStringAlphabetAndNumberOnly = (input) => !/^[A-Za-z0-9]*$/.test(input);

export const getCommonSegmentProperties = (user = window.rzp_user, config = {}) => {
  if (!user) {
    // skip properties if sesion is expired/user details are not availble
    return {};
  }

  const mode = localStorage.getItem(`rzp_mode--${user.id}`);

  const { addUserProperties = false } = config;

  let userProperties = {};

  if (addUserProperties) {
    userProperties = {
      business_type: user.business_type,
      activation_status: user.activated,
      current_activation_status: user.activation_status,
      previous_activation_status:
        user.activationStatusChangeLogs?.[user.activationStatusChangeLogs.length - 1],
      user_business_category: user.business_category,
      user_business_sub_category: user.business_subcategory,
    };
  }

  const properties = {
    pageUrl: window.location.href.split('?')[0],
    slug: window.location.pathname,
    mode,
    userId: user.user?.id,
    userRole: user.role,
    merchantId: user.current,
    ...userProperties,
  };

  return {
    ...properties,
  };
};

export function makeArray(obj) {
  if (!obj) {
    return [];
  }
  return Array.isArray(obj) ? obj : [obj];
}

export function arrayDiff(arr1, arr2) {
  if (arr1.length < arr2.length) {
    let tempArr = arr1;
    arr1 = arr2;
    arr2 = tempArr;
  }

  return arr1.reduce((prev, curr) => {
    if (arr2.indexOf(curr) === -1) {
      prev.push(curr);
    }
    return prev;
  }, []);
}

export function isBlank(value) {
  if (value !== null && typeof value === 'object') {
    return !Object.keys(value).length;
  }
  if (typeof value === 'string') {
    value = value.trim();
    return !value;
  }
  return isNone(value);
}

export function isPresent(obj) {
  return !isBlank(obj);
}

export const isNone = (value) => {
  return value === null || value === undefined;
};

export const findBy = (array, prop, value) => {
  return array.find((item) => {
    return item[prop] === value;
  });
};

export const filterBy = (array, prop, value) => {
  return array.filter((item) => {
    return item[prop] === value;
  });
};

export const mapBy = (array, prop) => {
  return array.map((item) => {
    return item[prop];
  });
};

/**
 * Converst [{a: 'key', b: 'value'}] => {key: value}
 * @param {Array} array
 * @param {Function} iterator
 */
export const arrayToObject = (array = [], iterator) => {
  return array.reduce((accumulator, currentItem) => {
    const { key, value } = iterator(currentItem);
    return {
      ...accumulator,
      [key]: value,
    };
  }, {});
};

export const groupBy = (records, colName) => {
  const result = {};

  records.forEach((record, index) => {
    if (!record.hasOwnProperty(colName)) {
      return;
    }

    const colValue = record[colName],
      colRecords = (result[colValue] = result[colValue] || []);

    colRecords.push(record);
  });

  return result;
};

export const pipe = (...funcs) => {
  let first = funcs.shift();
  return (...args) => {
    return funcs.reduce((returnVal, currentFn) => {
      return currentFn(returnVal);
    }, first(...args));
  };
};

export const normalizeDate = (date) => moment(date).format('D/M/Y');
export const formatFromNow = (unixSeconds) => moment(unixSeconds * 1e3).fromNow();

/*
  calculates no of days from today for a given date
  negative if date given date (in seconds) was of past
 */
export const daysFromToday = (date) =>
  Math.floor((Number(date) - new Date().getTime() / 1000) / 86400);

export const getCurrentFinancialYear = () => {
  const today = new Date();
  const currentMonth = today.getMonth() + 1;
  if (currentMonth <= 3) {
    return today.getFullYear() - 1;
  } else {
    return today.getFullYear();
  }
};

export const normalizeBoolean = (bool) => {
  if (bool === undefined) {
    return bool;
  }

  return bool ? 1 : 0;
};

export const numberFormatRegex = /(.{1,2})(?=.(..)+(\...)$)/g;

export const getFixedINRAmount = (amount) => (Number(amount) / 100).toFixed(2);

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

export const getFormattedNumber = (value) => {
  if (typeof value === 'number') {
    value = value.toFixed(2);
  }

  value = value.replace(numberFormatRegex, '$1,');

  return getFixedNumber(value);
};

export const currencySymbols = {
  INR: '₹',
  USD: 'US$',
  MYR: 'RM',
};

export const getSplitzExperimentVariant = (experimentName) => {
  const splitzExperiments = window.rzp_user?.splitz_experiments;
  let splitzExperimentVariant = null;

  if (splitzExperiments) {
    Object.keys(splitzExperiments).forEach((experimentId) => {
      const splitzExperiment = splitzExperiments[experimentId];
      if (abExperimentsMap[experimentName]?.includes(experimentId) && !isEmpty(splitzExperiment)) {
        splitzExperimentVariant = splitzExperiment;
      }
    });
  }
  return splitzExperimentVariant || {};
};

//This will be removed once experiment is ramped to 100%
export const isNExponentSupported = () =>
  getSplitzExperimentVariant('n_exponent_support').variables?.result === 'on';

/**
 * This function returns decimals and formatter for the currency passed
 * 1. We get the currencylist from the window object or local file depending upon availability
 * 2. We get the denomination value from currency object (default to 100 if denomination key does not exist)
 * 3. We get the formatter from currency object (default to 3 comma formatter if key does not exist)
 * 4. return the data object with decimals and formatter.
 * @param {*} currency
 * @returns {Object}
 */
export const getCurrencyConfig = (currency = 'INR') => {
  if (isNExponentSupported()) {
    const currencyList = window.currencyList || currencies;
    const denomination = currencyList[currency]?.denomination || currencies.default.denomination;
    const formatter = currencyList[currency]?.format || currencies.default.format;
    return { decimals: denomination.toString().length - 1, formatter };
  } else {
    return { decimals: 2, formatter: CURRENCY_FORMATTERS.inr };
  }
};

/**
 * Parses the amount string and returns the parsed object with integer, decimal, fraction, currency, etc
 * @param {string | number} amount Amount to format
 * @param {string} currencyCode
 * @returns {ReturnType<formatNumberByParts>}
 */
export const getFormattedAmountByParts = (amount, currency = 'INR') => {
  let updatedAmount = convertToMajorUnit(amount, { currency }).toString();

  const integer = updatedAmount.split('.')[0] || '';
  const fraction = updatedAmount.split('.')[1] || '';

  let byParts;

  try {
    byParts = formatNumberByParts(updatedAmount, {
      currency,
      intlOptions: {
        style: 'currency',
      },
    });
  } catch (e) {
    byParts = {
      integer,
      decimal: '.',
      fraction,
      isPrefixSymbol: true,
    };
  }

  return byParts;
};

// following regex formats in indian comma separated, i.e. 2,01,20,45,222.66
export const getFormattedAmount = (amount, currency = 'INR') => {
  if (isNExponentSupported()) {
    const { decimals, formatter } = getCurrencyConfig(currency);
    return formatter((amount / 10 ** decimals).toFixed(decimals), decimals);
  }
  return (amount / 100).toFixed(2).replace(numberFormatRegex, '$1,');
};

/**
 * Formats a numeric amount into a localized currency string.
 * This function takes a numeric amount and returns it as a formatted string in a given currency style.
 * It utilizes `formatNumberByParts`, a custom implementation of `Intl.NumberFormat.prototype.formatToParts()` from i18nify,
 * to handle the localization and formatting based on the provided options.
 */
export const formatAmount = (amt, showCurrency, currency) => {
  try {
    let options = {
      intlOptions: {
        minimumFractionDigits: 2,
      },
    };

    if (showCurrency) {
      options.currency = currency;
    }
    const byParts = formatNumberByParts(amt, options);
    return byParts.rawParts.reduce((acc, curr) => `${acc}${curr.value}`, '');
  } catch (e) {
    console.error(e);
    return showCurrency ? `${currency} ${amt}` : amt;
  }
};

export const getFormattedAmountNew = (amount, showCurrency, currency = 'INR') => {
  const adjustedAmount = convertToMajorUnit(amount, { currency }).toString();

  return formatAmount(adjustedAmount, showCurrency, currency);
};

//used to merge formatting of local currency list with api response
export const mergeCurrencyFormatting = (data) => {
  if (typeof data === 'object') {
    const mergedData = { ...data };
    Object.keys(data).forEach((currency) => {
      const formatting = currencies[currency]?.format;
      if (formatting) {
        mergedData[currency].format = formatting;
      }
    });
    return mergedData;
  }
  return currencies;
};

export const without = (source, keys) => {
  keys = makeArray(keys);
  return Object.keys(source).reduce((prev, key) => {
    if (keys.indexOf(key) === -1) {
      prev[key] = source[key];
    }
    return prev;
  }, {});
};

export const truncatedString = (string, length = 24) => {
  if (string?.length > length) {
    return `${string?.substring(0, length)}...`;
  }
  return string;
};

export const pickProps = (source, keys) => {
  keys = makeArray(keys);
  return Object.keys(source).reduce(
    (collector, key) => ({
      ...collector,
      ...(keys.indexOf(key) > -1 && { [key]: source[key] }),
    }),
    {},
  );
};

export const rupeesToPaise = (amount) => {
  amount = (Number(amount) * 100).toFixed(0);
  return Number(amount);
};

export const paiseToRupees = (amount) => {
  amount = (Number(amount) / 100).toFixed(2);
  return Number(amount);
};

export const objectDiff = (oldObj = {}, newObj = {}) => {
  return Object.keys(newObj).reduce((prev, key) => {
    let value = newObj[key];
    let oldValue = oldObj[key];

    if (JSON.stringify(value) !== JSON.stringify(oldValue)) {
      prev[key] = value;
    }
    return prev;
  }, {});
};

/*
 * Convert the object to url query string
 * Don't allow undefined, null and empty string as values
 * Note: It doesn't handle nested object
 */
export const stringifyQueryParams = (params) => {
  let queryString;
  let queryElements = [];

  for (let key in params) {
    if (params.hasOwnProperty(key) && params[key] != null && params[key] !== '') {
      queryElements.push(key + '=' + encodeURIComponent(params[key]));
    }
  }

  queryString = '?' + queryElements.join('&');
  return queryString;
};

/*
 * Convert the location into query params object
 * Usually, passing url = this.props.location.search
 * Use Case: utilize to populate filter form
 */
export const getURLQueryParams = (url = document.location.hash) => {
  const search = url.split('?')[1];
  let params = {};

  if (search) {
    /* split using '&' as separator
    and get the key value pairs for query params. */
    params = search.split('&').reduce((prev, curr) => {
      const [key, value] = curr.split('=');
      prev[key] = decodeURIComponent(value);
      return prev;
    }, {});
  }

  return params;
};

export const noop = () => {};

export const colors = ['primary', 'success', 'info', 'warn', 'danger'];

export const paymentStatusColor = {
  captured: colors[1],
  authorized: colors[2],
  refunded: colors[3],
  failed: colors[4],
};

export const intervals = [
  {
    value: 'day',
    label: 'Daily',
  },
  {
    value: 'week',
    label: 'Weekly',
  },
  {
    value: 'month',
    label: 'Monthly',
  },
  {
    value: 'year',
    label: 'Yearly',
  },
];

const periods = {
  weekly: 'Week',
  monthly: 'Month',
  yearly: 'Year',
  daily: 'Day',
};

export const getIntervalCycle = (interval, period) => {
  if (interval === 1) {
    return `Every ${periods[period]}`;
  } else {
    return `Once in ${interval} ${periods[period]}s`;
  }
};

export const getCustomerDisplayName = ({ name, contact, email }) => {
  let displayParts = [name, contact, email].filter((item) => !isBlank(item));

  return `${displayParts.join(' / ').replace('/ ', '(')}${displayParts.length > 1 ? ')' : ''}`;
};

/**
 * Flattens an object.
 * @param {Object} object
 * @param {String} delimeter
 */
export const flattenObject = (object, delimeter = '.') => {
  let keys = Object.keys(object);
  let flat = {};
  for (let i = 0; i < keys.length; i++) {
    let key = keys[i];
    let val = object[key];

    // if the value is an object and not falsy (null)
    if (typeof val === 'object' && !!val) {
      var _obj = flattenObject(val, delimeter);
      var _keys = Object.keys(_obj);
      for (var j = 0; j < _keys.length; j++) {
        flat[key + delimeter + _keys[j]] = _obj[_keys[j]];
      }
    } else {
      flat[key] = val;
    }
  }
  return flat;
};

/**
 * Method to create a query string separated by | instead of &
 * @param {Object} params
 * @return {String}
 */
export const stringifyQueryParamsWithPipe = (params) => {
  if (!params) return '';

  params = flattenObject(params, '_');

  return JSON.stringify(params)
    .replace(/:/g, '=') // Replace : with =
    .replace(/{/g, '') // Remove {
    .replace(/}/g, '') // Remove }
    .replace(/"/g, '') // Remove "
    .replace(/,/g, '|'); // Replace , with |
};

/**
 * Method to get eventCategory for Analytics based on the given pathname
 * @param {String} pathname
 * @return {String}
 */
export const getEventCategoryFromPath = (pathname) => {
  // Remove slashes from path. Eg: /plans/ => plan
  pathname = pathname && pathname.split('/').join('');
  switch (pathname) {
    case 'payments':
      return 'Dashboard - Payments';
    case 'refunds':
      return 'Dashboard - Refunds';
    case 'paymentlinks':
      return 'Dashboard - Payment Links';
    case 'orders':
      return 'Dashboard - Orders';
    case 'settlements':
      return 'Dashboard - Settlements';
    case 'invoices':
    case 'items':
      return 'Dashboard - Invoices';
    case 'plans':
    case 'subscriptions':
      return 'Dashboard - Subscriptions';
    case 'virtualaccounts':
      return 'Dashboard - Smart Collect';
    default:
      return 'Dashboard - Home';
  }
};

export const getPercentage = (divident, divisor) => {
  let value = 0;

  if (divident) {
    value = getFixedNumber((divisor / divident) * 100);
  }

  return Number(value);
};

export const getPercentages = (...args) => {
  /*
   * Given Number arguments, returns a dictionary
   * with keys as given numbers and values as the
   * percentage of value compared to sum
   *
   * eg:
   * getPercentages(1,2,3); // => {1: "16.67", 2: "33.33", 3: "50"}
   */

  const sum = args.reduce((sum, item) => item + sum, 0);

  return args.reduce((result, item) => {
    result[item] = getPercentage(sum, item);

    return result;
  }, {});
};

export const getEMI = (principle, length, rate) => {
  /*
   * Calculates EMI given amount, interestRate and Number of months
   *
   * @param {Number} amount
   * @param {Number} interestRate
   * @param {Number} numMonths
   *
   * `amount` must be in paise , `interestRate` is a number
   * representing the percentage and `numMonths` is positive integer >=1
   */

  if (!rate) {
    return Math.ceil(principle / length);
  }

  rate /= 1200;

  var multiplier = (1 + rate) ** length;

  return parseInt((principle * rate * multiplier) / (multiplier - 1), 10);
};

export const arrayToCsv = (array) => {
  /*
   * Converts array of arrays to csv
   */

  return array
    .reduce((result, item) => {
      return result.concat(Array.isArray(item) ? item.join(',') : String(item));
    }, [])
    .join('\n');
};

export const arrayToCsvDataUrl = (array) => {
  /*
   * converts array of arrays to csv data url
   */

  return 'data:text/csv;utf-8,' + encodeURIComponent(arrayToCsv(array));
};

export const arrayObjToCsv = (arr) => {
  /*
   * converts array of objects to csv. note that this only works for
   *  one-level nested JSON objects.
   *
   * input -> [
      {
        "id": "FBXzLRJYkqA237",
        "amount": 1200,
        "status": "PENDING",
        "created_at": "2020-07-07 14:25:37.11105 +0530 IST m=+37.486976552",
        "interest_repaid": 0,
        "principal_repaid": 500
      },
      {
        "id": "ABCzLRJYkqA237",
        "amount": 1100,
        "status": "PENDING",
        "created_at": "2020-07-07 14:25:37.11105 +0530 IST m=+37.486976552",
        "interest_repaid": 0,
        "principal_repaid": 1000
      }
    ]
    * output ->
    id,amount,status,created_at,interest_repaid,principal_repaid
    FBXzLRJYkqA237,1200,PENDING,2020-07-07 14:25:37.11105 +0530 IST m=+37.486976552,0,500
    ABCzLRJYkqA237,1100,PENDING,2020-07-07 14:25:37.11105 +0530 IST m=+37.486976552,0,1000
   */
  const array = [Object.keys(arr[0])].concat(arr);

  return array
    .map((it) => {
      return Object.values(it).toString();
    })
    .join('\n');
};

/**
 * @param {*} url
 * Check if valid secure production URL (i.e, HTTPS)
 */
export const checkIfHTTPS = (url) => {
  const regex = /^https:\/\//i;

  return regex.test(url);
};

/**
 *
 * @param {*} url
 * Add 'http' to the URL if http/https not there
 */
export const autoPrefixUrls = (url) => {
  const regex = /^https?:\/\//i;
  let tempUrl;
  if (!url || url.length === 0) {
    return url;
  }

  tempUrl = url.toLowerCase();

  if (!regex.test(tempUrl)) {
    url = 'http://' + url;
  }
  return url;
};

// Check if webkit browsers
export const isWebkit =
  typeof window !== 'undefined' &&
  typeof window.getComputedStyle(document.documentElement)['-webkit-text-security'] === 'string'
    ? true
    : false;

export { acronyms, shortenText };

/**
 * Returns the applicable GST groups, a corresponding mapping, and the rate per GST group.
 * @param {Integer} slab Slab. eg: 50000 (5%, value is multiple of 10000)
 * @param {Integer} serviceStateCode State Code of Service (Merchant)
 * @param {Integer} supplyStateCode State Code of Supply (Customer)
 * @param {Object} mapping TaxGroup_Slab => Razorpay_Tax_ID mapping
 * @param {Boolean} isUT Whether or not any of the states is a Union Territory
 * @return {Object}
 *  @prop {Array} groups List of applicable groups
 *  @prop {Object} mapping Mapping that was passed, but only the ones applicable
 *  @prop {Integer} perGroup % rate per group
 */
export const getApplicableGSTForSlab = (slab, serviceStateCode, supplyStateCode, mapping, isUT) => {
  let mapKeys = Object.keys(mapping);

  /**
   * For different states, it is IGST.
   * For same state, if it is a Union Territory, it is CGST+UTGST.
   * For same state, if it not is a Union Territory, it is CGST+SGST.
   */
  let groups = ['IGST'];
  if (serviceStateCode === supplyStateCode) {
    groups = isUT ? ['CGST', 'UTGST'] : ['CGST', 'SGST'];
  }

  let applicable = {};
  let perKey = slab / groups.length;
  for (let i = 0; i < mapKeys.length; i++) {
    let key = mapKeys[i];
    for (let j = 0; j < groups.length; j++) {
      let group = groups[j];
      if (`${group}_${perKey}` === key) {
        applicable[key] = mapping[key];
      }
    }
  }

  return {
    groups,
    mapping: applicable,
    perGroup: perKey,
  };
};

/**
 * Returns the applicable GST groups, a corresponding mapping, and the rate per GST group for given slabs.
 * @param {Array} slabs Slabs. eg: [0, 500, 1200, ...]
 * @param {Integer} serviceStateCode State Code of Service (Merchant)
 * @param {Integer} supplyStateCode State Code of Supply (Customer)
 * @param {Boolean} isUT Whether or not any of the states is a Union Territory
 * @return {Object}
 *  @prop {Number} slab Slab eg. 500, 1200, ...
 *    @prop {Array} groups List of applicable groups
 *    @prop {Object} mapping Mapping that was passed, but only the ones applicable
 *    @prop {Integer} perGroup % rate per group
 */
export const getGSTSlabs = (slabs, serviceStateCode, supplyStateCode, mapping, isUT) => {
  let toReturn = {};
  slabs.forEach((slab) => {
    toReturn[slab] = getApplicableGSTForSlab(
      slab,
      serviceStateCode,
      supplyStateCode,
      mapping,
      isUT,
    );
  });
  return toReturn;
};

/**
 * Stringifies an address.
 * @param {Object} addr
 * @return {String}
 */
export const stringifyAddress = (addr) => {
  let str = '';

  // Add Line 1 and Line 2
  if (addr.line1) {
    str += `${addr.line1},\n`;
  }
  if (addr.line2) {
    str += `${addr.line2},\n`;
  }

  // Generate and add last line (city, state, country)
  let lastLine = [];
  if (addr.city) {
    lastLine.push(addr.city);
  }
  if (addr.state) {
    lastLine.push(addr.state);
  }
  if (addr.country) {
    let country = addr.country;
    if (country.toLowerCase() === 'in') {
      country = 'India';
    } else {
      country = country.toUpperCase();
    }
    lastLine.push(country);
  }
  str += lastLine.join(', ');

  // Add zipcode
  if (addr.zipcode) {
    str += ` (${addr.zipcode})`;
  }

  return str;
};

/**
 * Get human readable file size
 * @param {*} fileSize in bytes in Binary prefixes
 */
export const readableFileSize = (bytes) => {
  const sizes = ['bytes', 'KB', 'MB', 'GB', 'TB', 'PB'];

  if (!bytes) return `0 bytes`;
  var e = Math.floor(Math.log(bytes) / Math.log(1024));
  return `${(bytes / 1024 ** e).toFixed(2)} ${sizes[e]}`;
};

/**
 * Method to create a query string separated by | instead of &
 * @param {Object} params
 * @return {String}
 */
export const getKeysSeparatedByPipe = (params) => {
  if (!params) return '';

  params = flattenObject(params, '_');

  let keys = Object.keys(params);
  for (let i = 0; i < keys.length; i++) {
    let key = keys[i],
      val = params[key];

    // Remove keys that don't contain a value.
    if (val === null || val === undefined || val === '' || val == 0) {
      delete params[key];
    }
  }

  // Stringify all the other keys and return the string.
  return Object.keys(params).join('|');
};

/**
 * Remove all white spaces from a given string
 **/
export const trim = (str) => {
  return str.replace(/\s+/g, '');
};

/**
 * convert array of items to a sentence
 * ex. item1, item2 and item3
 */
export const arrayToSentence = (arr = []) => {
  if (arr.length === 1) {
    return arr[0];
  } else {
    return arr.slice(0, arr.length - 1).join(', ') + ' and ' + arr.slice(-1);
  }
};

export const pluralize = (str, length) => {
  return length > 1 ? `${str}s` : str;
};

/**
 * Capital-cases a string.
 * @param {String} input
 * @return {String}
 */
export const capitalize = (input) =>
  input ? input.charAt(0).toUpperCase() + input.substr(1).toLowerCase() : '';

const countries = {
  UK: 'united kingdom',
  IND: 'india',
};

export const getCountryPINcodeType = (country = '') => {
  const countryLowerCase = country.toLowerCase();

  switch (countryLowerCase) {
    case countries.UK: {
      return 'text';
    }
    default: {
      return 'number';
    }
  }
};

/**
 * Checks the validity of an address.
 * Line1, City, State, Country are required fields in an address.
 * @param {Object} address
 * @return {Bool}
 */
export const isAddressValid = (address) => {
  const allKeys = Boolean(
    address && address.line1 && address.city && address.state && address.country && address.zipcode,
  );

  if (!allKeys) {
    return false;
  }

  const { line1, line2, city, state, country } = address;

  const requiredFieldsLengthCheck = Boolean(
    line1.length >= 10 &&
      line1.length <= 255 &&
      city.length >= 2 &&
      city.length <= 32 &&
      state.length >= 2 &&
      state.length <= 32 &&
      country.length >= 2 &&
      country.length <= 64,
  );

  let optionalFieldsLengthCheck = true;
  if (line2 && !(line2.length >= 5 && line2.length <= 255)) {
    optionalFieldsLengthCheck = false;
  }

  const lengthCheck = requiredFieldsLengthCheck && optionalFieldsLengthCheck;

  if (!lengthCheck) {
    return false;
  }

  return true;
};

/**
 * Returns whether or not a GSTIN is valid.
 * @param {String} gstin
 * @return {Boolean}
 */
export const isValidGSTIN = (gstin) => {
  // If GSTIN is not provided or it isn't 15-char long, it is invalid.
  if (!gstin || gstin.length !== 15) {
    return false;
  }

  /**
   * 1st character ∈ {0,1,2,3} (3 for future)
   * 2nd character ∈ {0...9}
   * 3rd - 7th characters are alphabets
   * 8th - 11th characters are numbers
   * 12th character is an alphabet
   * 13th character is a number
   * 14th character is “Z”
   * 15th character could be anything (alphabet or number)
   */
  let regex = /^[0123][0-9][a-z]{5}[0-9]{4}[a-z][0-9][a-z0-9][a-z0-9]$/gi;
  return regex.test(gstin);
};

export const subString = (str, length) => {
  if (!str) {
    return str;
  }

  if (str.length > length) {
    return `${str.substr(0, length)} ...`;
  } else {
    return str;
  }
};

/**
 * Figure out if the given Tax is of type cess.
 * @param {Tax} tax
 * @return {Boolean}
 */
export const isTaxOfTypeCess = (tax) =>
  Boolean(
    tax && tax.name && tax.name.toLowerCase().startsWith('cess') && tax.rate_type === 'percentage',
  );

/**
 * Calculates tax.
 * @param {Number} base Amount.
 * @param {Number} rate Rate
 * @param {Boolean} inclusive Whether or not tax is inclusive
 *
 * eg:  base: 500
 *      rate: 18 (18%)
 *
 * @return {Number} tax.
 */
export const calculateTax = (base, rate, inclusive = false) => {
  if (inclusive) {
    return base - base / (1 + rate / 100);
  } else {
    return base * (rate / 100);
  }
};

// efficient sorting of any collection based on order
export const getArraySorterFromArray = (order = [], getValue = (item) => item) => {
  const orderMap = order.reduce((map, item, index) => {
    map[item] = index;

    return map;
  }, {});

  return (item1, item2) => {
    return orderMap[getValue(item1)] - orderMap[getValue(item2)];
  };
};

export const loadImage = (src, onLoad, onError) => {
  if (!src || !Image) {
    return;
  }

  const image = new Image();

  if (onLoad) {
    image.onload = onLoad;
  }

  if (onError) {
    image.onerror = onError;
  }

  image.src = src;
};

/*
 * Reference: https://github.com/facebook/react/issues/10135#issuecomment-314441175
 *
 * This is helper fn. as a work around for dispatching manual events on native elements.
 *
 * */
export function setNativeValue(element, value) {
  const valueSetter = Object.getOwnPropertyDescriptor(element, 'value').set;
  const prototype = Object.getPrototypeOf(element);
  const prototypeValueSetter = Object.getOwnPropertyDescriptor(prototype, 'value').set;

  if (valueSetter && valueSetter !== prototypeValueSetter) {
    prototypeValueSetter.call(element, value);
  } else {
    valueSetter.call(element, value);
  }
}

export const sanitizeHTML = (str) => {
  const temp = document.createElement('div');
  temp.textContent = str;

  return temp.innerHTML;
};

export const deepClone = (o) => {
  try {
    return JSON.parse(JSON.stringify(o));
  } catch (err) {
    console.log('Deepclone error: ', err);
  }
};

/*
 * Helper fn. to fetch IFSC bank details for IFSC code entered in field
 * */
export function getDetailsForIFSC(ifscCode) {
  const IFSCCodeValidatorRegex = new RegExp(/^[A-Z]{4}0[A-Z0-9]{6}$/i);
  if (ifscCode.length !== 11) {
    return null;
  }
  if (!IFSCCodeValidatorRegex.test(ifscCode)) {
    return null;
  }

  return axios(`https://ifsc.razorpay.com/${ifscCode}`).then((info) => {
    info = info.data;

    if (info) {
      info = {
        Bank: info.BANK,
        Branch: info.BRANCH,
        City: info.CITY,
        State: info.STATE,
      };

      return info;
    }

    return null; // Invalid IFSC code
  });
}

/* Returns array with non-duplicate entries */
export function uniqueArray(arr) {
  if (!arr || !arr.length) {
    return;
  }

  const map = {};

  return arr.filter((item) => !map[item] && (map[item] = true));
}

export function isMobileAndTablet() {
  let check = false;

  (function (a) {
    if (
      /(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino|android|ipad|playbook|silk/i.test(
        a,
      ) ||
      /1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i.test(
        a.substr(0, 4),
      )
    )
      check = true;
  })(navigator.userAgent || navigator.vendor || window.opera);

  return check;
}

/**
 * Determine the mobile operating system.
 * This function returns one of 'iOS', 'Android', 'Windows Phone', or 'unknown'.
 *
 * @returns {String}
 */
export function getMobileOperatingSystem() {
  var userAgent = navigator.userAgent || navigator.vendor || window.opera;

  // Windows Phone must come first because its UA also contains "Android"
  if (/windows phone/i.test(userAgent)) {
    return 'Windows Phone';
  }

  if (/android/i.test(userAgent)) {
    return 'Android';
  }

  if (/iPhone/.test(userAgent) && !window.MSStream) {
    return 'iOS';
  }

  return 'unknown';
}

/**
 *
 * @param {String} path - path of data member with dots as string type
 * @param {String} value - value of variable
 * @param {Object} srcObj - object where value needs to be inserted
 */
export function stringToObj(path, value, srcObj) {
  const newObj = srcObj ? deepClone(srcObj) : srcObj;
  // for supporting sample[0][sampleKey]
  const squareBracketPattern = /\[|\]/;
  if (squareBracketPattern.test(path)) {
    parts = path.split(squareBracketPattern).filter((pathEl) => !!pathEl); //splitting with regex gives empty strings
  } else {
    parts = path.split('.');
  }

  let last = parts.pop();

  // converts if numeric for array
  last = isNaN(last) ? last : Number(last);
  let obj = newObj;

  while ((part = parts.shift())) {
    // converts if numeric for array
    part = isNaN(part) ? part : Number(part);

    if (typeof obj[part] !== 'object') {
      // assigning an array if upcoming part is number
      obj[part] = isNaN(parts[0]) ? {} : [];
    }
    obj = obj[part]; // nosemgrep : javascript.lang.security.audit.prototype-pollution.prototype-pollution-loop.prototype-pollution-loop
  }
  obj[last] = value;
  var parts, part;
  return newObj;
}

export const addPrefixToObjectKeys = (prefix, data) => {
  const newData = {};

  for (const key in data) {
    newData[`${prefix}${key}`] = data[key];
  }

  return newData;
};

const _arrayMoveMutate = (array, from, to) => {
  array.splice(to < 0 ? array.length + to : to, 0, array.splice(from, 1)[0]);
};

/* For swapping positions of 2 indices in an array */
export const arrayMove = (array, from, to) => {
  array = array.slice();
  _arrayMoveMutate(array, from, to);
  return array;
};

export function classList(...args) {
  const classes = [];

  for (var i = 0; i < args.length; i++) {
    if (args[i]) {
      if (args[i] instanceof Array) {
        args[i] = args[i].join(' ');
      }

      classes.push(args[i]);
    }
  }

  return classes.join(' ');
}

/**
 *
 * @param {Object}, keys: Objet keys to be converted into sentence.
 * Returns comma separated sentence ending in is/are.
 */
export function keysToSentence(keys) {
  if (typeof keys !== 'object' || !Object.keys(keys).length) {
    return;
  }

  let joiner;

  keys = Object.keys(keys).map((key) => {
    if (key[key.length - 1] === 's') {
      // plural term
      joiner = 'are';
    }

    return titleCase(key);
  });

  joiner = joiner || (keys.length > 1 ? 'are' : 'is');

  let sentence = keys[0];

  for (let i = 1; i < keys.length; i++) {
    if (i === keys.length - 1) {
      sentence = sentence + ' and ' + keys[i];
    } else {
      sentence = sentence + ', ' + keys[i];
    }
  }

  return sentence + ' ' + joiner;
}

export const prevent = (e) => {
  e.preventDefault();
  e.stopPropagation();
};

export function handleNegativeBalanceLimit(balanceConfig, balance) {
  if (balance >= 0 || balance === undefined) return false;

  if (
    balanceConfig.loading === true ||
    balanceConfig.error ||
    balanceConfig.data.items.length === 0
  ) {
    return false;
  }

  let { items } = balanceConfig.data;

  const { negative_limit_auto, negative_limit_manual } = items[0];

  let maxLimit = -Math.max(negative_limit_auto, negative_limit_manual);

  if (balance > maxLimit) return false;
  else return true;
}

/**
 * function will return true if
 * object is empty
 * and object has empty string in the value
 */

export function checkIsObjectEmpty(obj) {
  for (var key in obj) {
    if (obj.hasOwnProperty(key) && obj[key] !== '') return false;
  }
  return true;
}

/**
 *
 * @param {String} fileName - name of the file
 * @return {String}
 * Returns the file type of the filename passed.
 */
export function getFileTypeIcon(fileName) {
  const avlblFileTypeIcons = ['pdf', 'jpg', 'png', 'csv', 'xlsx', 'mp4', 'mp3'];
  let fileType = fileName.split('.');
  fileType = fileType[fileType.length - 1];

  return avlblFileTypeIcons.indexOf(fileType) > -1 ? `${fileType}-new` : 'misc';
}

/**
 *
 * @param {String} awsURL - aws signed url
 * @param {String} defaultUnit - default minimum time in unit if there is any error in URL parsing
 * @param {String} UNIT_TYPE - unit type
 * @return {Number} time difference between now and the expiry
 */
export function getAttachmentExpiryTime(awsURL, defaultUnit, UNIT_TYPE) {
  try {
    const params = getURLQueryParams(awsURL);
    const dateTimeStr = params['X-Amz-Date'];
    const offsetTime = parseInt(params['X-Amz-Expires'], 10);

    if (!dateTimeStr || !offsetTime) {
      throw new Error('Invalid AWS URL');
    }

    return moment.utc(dateTimeStr).add(offsetTime, 's');
  } catch (ex) {
    // return default 2 hours
    return moment().add(defaultUnit, UNIT_TYPE);
  }
}

/* This Function is used to validate the bank details and respond back accordingly */
export function validateBankDetails(value, type) {
  const typeMapRegx = {
    accNo: /^\d{9,18}$/,
    ifsc: /^[A-Z]{4}0[A-Z0-9]{6}$/i,
    name: /^([a-zA-Z0-9\s-_()/.']){4,120}$/i,
  };
  if (value && type && typeMapRegx[type]) {
    return new RegExp(typeMapRegx[type]).test(value);
  }
  return null;
}

/**
 *
 * @param {HTML element} element - HTML element to check
 * @param {Number} percentVisible - Percent of the HTML element that should be visible
 * @param {Number} offsetTop - Starting point off top of the window to check if in view
 * @param {Number} offsetBottom - Starting point off bottom of the window to check if in view
 * @returns {Boolean} - true if the specified percent of the element is in the viewport discounting the offsets
 */
export const isElementXPercentInViewport = (
  element,
  percentVisible,
  offsetTop = 0,
  offsetBottom = 0,
) => {
  let elementTop, elementHeight, elementBottom, percentCutFromTop, percentCutFromBottom;
  let rect, windowHeight;

  rect = element.getBoundingClientRect();
  windowHeight = window.innerHeight || document.documentElement.clientHeight;

  elementTop = rect.top - offsetTop >= 0 ? 0 : offsetTop - rect.top;
  elementHeight = rect.height;
  percentCutFromTop = (elementTop / elementHeight) * 100;

  elementBottom = rect.bottom + offsetBottom - windowHeight;
  percentCutFromBottom = (elementBottom / elementHeight) * 100;

  return !(
    Math.floor(100 - percentCutFromTop) < percentVisible ||
    Math.floor(100 - percentCutFromBottom) < percentVisible
  );
};

/**
 *
 * @param {Array} errors - error array from api response
 * @return {(String|Array|null)} error message
 */
export function getErrorMessageFromResponse(errors) {
  let err = errors;

  if (Array.isArray(err)) {
    err = [];

    errors.length &&
      errors.forEach((e) => {
        if (e && e.toLowerCase().indexOf('status code') === -1) {
          err.push(e);
        }
      });

    err = err.length ? err : null;
  }

  if (!err) {
    err = `Some network error has occured`;
  }

  return err;
}

export const linkFromSource = (link = '', source = '') => {
  let isFromSource = link?.indexOf(source) >= 0;

  if (source === 'youtube') isFromSource = isFromSource || link?.indexOf('youtu') >= 0;

  return isFromSource;
};

export const isLoggedInViaMobile = () => localStorage?.getItem('loggedInVia') === 'contact_mobile';

// Add all list of html5 apis here to check
export const htmlApiList = ['URLSearchParams'];

export const checkHTML5APIvalidity = () => htmlApiList.find((apiName) => !window[apiName]);

/**
 * truncate a String.
 * @param {String} str
 * @param {Number} num
 * @return {String}
 */
export const truncateString = (str, num) => (str?.length > num ? `${str.slice(0, num)}...` : str);

// This function converts any string to camelcase E.g Some RandomString -> someRandomString
export const camelize = (str) => {
  if (typeof str !== 'string') return str;
  return str
    .replace(/(?:^\w|[A-Z]|\b\w)/g, (word, index) =>
      index === 0 ? word.toLowerCase() : word.toUpperCase(),
    )
    .replace(/\s+/g, '');
};

/**
 * get a nested property from an object
 * @param {Object} obj
 * @param {String} path
 * @param {*} defaultValue
 * @return {*}
 */
export const resolvePath = (obj, path, defaultValue) => {
  const arr = path?.split('.');
  let returnValue;
  try {
    returnValue = arr.reduce((acc, curr) => {
      return acc[curr];
    }, obj);
  } catch (e) {
    returnValue = defaultValue;
  } finally {
    returnValue = returnValue || defaultValue;
  }
  return returnValue;
};

const isBase64 = (str) => {
  const base64regex = /^([0-9a-zA-Z+/]{4})*(([0-9a-zA-Z+/]{2}==)|([0-9a-zA-Z+/]{3}=))?$/;
  return base64regex.test(str);
};

/**
 * get a object with encoded sensitive fields
 * @param {Object} object
 */
export const encodeSensitiveFields = (params) => {
  let parameter = { ...params };
  let unescape = window.unescape || window.decodeURI; // using this logic in local scope only
  for (let param in parameter) {
    if (SENSITIVE_FIELDS.includes(param)) {
      parameter[param] = window?.btoa(unescape(encodeURIComponent(parameter[param])));
    }
  }
  return parameter;
};

/**
 * get a object with decoded sensitive fields
 * @param {Object} object
 */
export const decodeSensitiveFields = (params) => {
  const getURLFields = new URLSearchParams(window.location.search);
  let parameter = { ...params };
  for (let param in parameter) {
    if (SENSITIVE_FIELDS.includes(param) && getURLFields?.get?.(param) !== undefined) {
      if (isBase64(getURLFields.get(param))) {
        parameter[param] = window?.atob(getURLFields.get(param));
      } else {
        parameter[param] = decodeURI(parameter[param]);
      }
    }
  }
  return parameter;
};

/*
 * @param {Number} diff
 * @param {Moment} endDate
 *
 * given , diff (seconds) and endDate , gives startDate
 */
export const getStartDateFromDiff = (diff, endDate) =>
  moment(endDate.toDate() - diff * 1000).startOf('day');

export const scrollToTop = (ref) => {
  ref?.current?.scroll({
    top: 0,
    behavior: 'smooth',
  });
};

/**
 * Fetches the Youtube video id from a Youtube url for a video
 * @param {string} url - The Youtube url for the video
 * @returns {string | false} The Youtube video id if the parsing is successful. Otherwise `false` is returned
 */
export const getYoutubeVideoID = (url = '') => {
  /** Regex used from {@link https://stackoverflow.com/a/8260383 Stack Overflow} */
  const regExp = /^.*((youtu.be\/)|(v\/)|(\/u\/\w\/)|(embed\/)|(watch\?))\??v?=?([^#&?]*).*/;
  const match = url?.match(regExp);
  return match && match[7].length === 11 ? match[7] : false;
};

/* Update url extension , with the extension passed */
export const updateExtension = (fileUrl, extension) => {
  let url = fileUrl;
  return url?.substr(0, url?.lastIndexOf('.')) + extension;
};

/* convert unix timestamp to human readable date format */
export const convertUnixToDate = (unixTimeStamp) => {
  const date = new Date(unixTimeStamp * 1000).toLocaleString('en-US', {
    month: 'long',
    day: 'numeric',
    year: 'numeric',
  });
  return date;
};

/**
 * converts minor unit of amount to common unit of amount, ex: paise to rupees
 * 1. This function calls the getCurrencyConfig to get the decimals of the passed currency
 * 2. We divide the passed amount with (10^decimals) to get the amount in common unit or rupees in case if INR
 * @param {*} amount minor unit
 * @param {*} currency
 * @returns {Number} common unit
 */
export const i18CurrencyConversionFromMinorUnitToCommonUnit = (amount, currency = 'INR') => {
  const { decimals } = getCurrencyConfig(currency);
  amount = (Number(amount) / 10 ** decimals).toFixed(decimals);
  return Number(amount);
};

/**
 * converts common unit of amount to minor unit of amount, ex: rupees to paise
 * 1. This function calls the getCurrencyConfig to get the decimals of the passed currency
 * 2. We multiply the passed amount with (10^decimals) to get the amount in minor unit or paise in case if INR
 * @param {*} amount common unit
 * @param {*} currency
 * @returns {Number} minor unit
 */
export const i18CurrencyConversionFromCommonUnitToMinorUnit = (amount, currency = 'INR') => {
  const { decimals } = getCurrencyConfig(currency);
  amount = (Number(amount) * 10 ** decimals).toFixed(0);
  return Number(amount);
};

/**
 * @param {String} str -'/notes/{category}?noteId={noteId}'
 * @param {Object} replacer - { category: 'development', noteId: '1' }
 * @returns {String} - '/notes/development?noteId=1'
 */

export const stringTemplate = (str = '', replacer = {}) => {
  let strCopy = str;
  for (let key in replacer) {
    strCopy = strCopy.replace(new RegExp('{' + key + '}', 'g'), replacer[key] ?? '');
  }
  return strCopy;
};

export const isProductionEnv = () => window.APP_ENV === 'production';

export const randomInt = (min, max) => {
  return Math.floor(Math.random() * (max - min + 1) + min);
};

export const exportFileAsExcel = ({ finalDataSend, fileName, fileFormat }) => {
  const fileType = fileFormat === 'xlsx' ? 'xlsx' : 'csv';
  const obj = finalDataSend.reduce(
    (obj, item) => {
      const json = utils.json_to_sheet(item.data);
      obj.Sheets[item.category] = json;
      obj.SheetNames.push(item.category);
      return obj;
    },
    { Sheets: {}, SheetNames: [] },
  );
  let excelBuffer;
  if (fileType === 'xlsx') {
    excelBuffer = write(obj, { bookType: 'xlsx', type: 'array' });
  } else if (fileType === 'csv') {
    excelBuffer = utils.sheet_to_csv(obj.Sheets[obj.SheetNames[0]]);
  }
  const data = new Blob([excelBuffer], { type: fileType });
  saveAs(data, `${fileName}.${fileFormat}`);
};
/*
 * @param {String} countryCode -country code of merchant, ex-IN, MY
 * @returns {String} - monetary unit text
 */
export const monetaryUnitText = (countryCode) => MONETARY_UNIT_TEXT[countryCode];

// modal won't open again on same id
export const openTicketModal = (data = {}) => {
  const id = `ticket-${Date.now()}`;
  window.rzpTicketSystem?.openModal(id, data);
};

export function isConfigTagAPISupported(merchantCountryCode) {
  const SUPPORTED_COUNTRIES = ['MY'];

  return SUPPORTED_COUNTRIES.find((countryCode) => countryCode === merchantCountryCode);
}
export const isExperimentActive = (experimentHashKey) =>
  experimentHashKey?.variables?.result === 'on';

export function convertToLocale(amount, countryCode) {
  const SUPPORTED_LOCALE = {
    IN: 'en-IN',
    MY: 'en-MY',
  };

  const locale = SUPPORTED_LOCALE[countryCode] || SUPPORTED_LOCALE.IN;
  const hasIntlFormatSupport = typeof window.Intl?.NumberFormat === 'function';
  if (hasIntlFormatSupport) {
    return new Intl.NumberFormat(locale).format(amount);
  }

  return Number.toLocaleString ? Number(amount).toLocaleString(locale) : amount;
}
