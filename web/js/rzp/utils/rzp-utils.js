import moment from 'moment';
import { acronyms, shortenText } from './acronyms';

moment.updateLocale('en', {
  relativeTime: {
    s: 'few secs',
    ss: '%s secs',
    m: 'a min',
    mm: '%d mins',
  },
});

export function isMobileResolution() {
  return window.outerWidth <= 768;
}

export function isFunction(value) {
  return typeof value === 'function';
}

export function isDefined(value) {
  return typeof value !== 'undefined';
}

export function titleCase(sentence) {
  return (sentence || '')
    .split(/\s+|_/)
    .map(word => word.charAt(0).toUpperCase() + word.substr(1).toLowerCase())
    .join(' ');
}

export function humanize(sentence) {
  return titleCase(sentence.split('_').join(' '));
}

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

export const isNone = value => {
  return value === null || value === undefined;
};

export const findBy = (array, prop, value) => {
  return array.find(item => {
    return item[prop] === value;
  });
};

export const filterBy = (array, prop, value) => {
  return array.filter(item => {
    return item[prop] === value;
  });
};

export const mapBy = (array, prop) => {
  return array.map(item => {
    return item[prop];
  });
};

export const pipe = (...funcs) => {
  let first = funcs.shift();
  return (...args) => {
    return funcs.reduce((returnVal, currentFn) => {
      return currentFn(returnVal);
    }, first(...args));
  };
};

export const normalizeDate = date => moment(date).format('D/M/Y');
export const formatFromNow = unixSeconds => moment(unixSeconds * 1e3).fromNow();

/*
  calculates no of days from today for a given date
  negative if date given date (in seconds) was of past
 */
export const daysFromToday = date =>
  Math.ceil((Number(date) - new Date().getTime() / 1000) / 86400);

export const normalizeBoolean = bool => {
  if (bool === undefined) {
    return bool;
  }

  return bool ? 1 : 0;
};

const numberFormatRegex = /(.{1,2})(?=.(..)+(\...)$)/g;

export const getFixedINRAmount = amount => (Number(amount) / 100).toFixed(2);

export const getFixedNumber = value => {
  if (typeof value === 'number') {
    value = value.toFixed(2);
  }

  const valueArr = value.split('.');

  if (valueArr[1] == '00') {
    value = valueArr[0].replace('.', '');
  }

  return value;
};

export const getFormattedNumber = value => {
  if (typeof value === 'number') {
    value = value.toFixed(2);
  }

  value = value.replace(numberFormatRegex, '$1,');

  return getFixedNumber(value);
};

export const currencySymbols = {
  INR: '₹',
  USD: 'US$',
};

export const getFormattedAmountNew = (
  amount,
  showCurrency,
  currency = 'INR'
) => {
  const formattedAmount = getFormattedNumber((amount / 100).toFixed(2));

  return (showCurrency ? currencySymbols[currency] : '') + formattedAmount;
};

// following regex formats in indian comma separated, i.e. 2,01,20,45,222.66
export const getFormattedAmount = amount => {
  return (amount / 100).toFixed(2).replace(numberFormatRegex, '$1,');
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

export const rupeesToPaise = amount => {
  amount = (Number(amount) * 100).toFixed(0);

  return Number(amount);
};

export const paiseToRupees = amount => {
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
export const stringifyQueryParams = params => {
  let queryString;
  let queryElements = [];

  for (let key in params) {
    if (
      params.hasOwnProperty(key) &&
      params[key] != null &&
      params[key] !== ''
    ) {
      queryElements.push(key + '=' + params[key]);
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
  let search = url.split('?')[1];
  let params = {};

  if (search) {
    params = search.split('&').reduce((prev, curr) => {
      let [key, value] = curr.split('=');
      prev[key] = value;
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
  let displayParts = [name, contact, email].filter(item => !isBlank(item));

  return `${displayParts.join(' / ').replace('/ ', '(')}${
    displayParts.length > 1 ? ')' : ''
  }`;
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
export const stringifyQueryParamsWithPipe = params => {
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
export const getEventCategoryFromPath = pathname => {
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
    value = getFixedNumber(divisor / divident * 100);
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

  var multiplier = Math.pow(1 + rate, length);

  return parseInt(principle * rate * multiplier / (multiplier - 1), 10);
};

export const arrayToCsv = array => {
  /*
   * Converts array of arrays to csv
   */

  return array
    .reduce((result, item) => {
      return result.concat(Array.isArray(item) ? item.join(',') : String(item));
    }, [])
    .join('\n');
};

export const arrayToCsvDataUrl = array => {
  /*
   * converts array of arrays to csv data url
   */

  return 'data:text/csv;utf-8,' + encodeURIComponent(arrayToCsv(array));
};

/**
 * @param {*} url
 * Check if valid secure production URL (i.e, HTTPS)
 */
export const checkIfHTTPS = url => {
  const regex = /^https:\/\//i;

  return regex.test(url);
};

/**
 *
 * @param {*} url
 * Add 'http' to the URL if http/https not there
 */
export const autoPrefixUrls = url => {
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
  typeof window.getComputedStyle(document.documentElement)[
    '-webkit-text-security'
  ] === 'string'
    ? true
    : false;

export { acronyms, shortenText };

/**
 *Get human readable file size
 * @param {*} fileSize in bytes in Binary prefixes
 */
export const readableFileSize = bytes => {
  const sizes = ['bytes', 'KB', 'MB', 'GB', 'TB', 'PB'];

  if (!bytes) return `0 bytes`;
  var e = Math.floor(Math.log(bytes) / Math.log(1024));
  return `${(bytes / Math.pow(1024, e)).toFixed(2)} ${sizes[e]}`;
};

/**
 * Method to create a query string separated by | instead of &
 * @param {Object} params
 * @return {String}
 */
export const getKeysSeparatedByPipe = params => {
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
export const trim = str => {
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
 * Returns whether or not a GSTIN is valid.
 * @param {String} gstin
 * @return {Boolean}
 */
export const isValidGSTIN = gstin => {
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
  let regex = /^[0123][0-9][a-z]{5}[0-9]{4}[a-z][0-9][z][a-z0-9]$/gi;
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

// efficient sorting of any collection based on order
export const getArraySorterFromArray = (
  order = [],
  getValue = item => item
) => {
  const orderMap = order.reduce((map, item, index) => {
    map[item] = index;

    return map;
  }, {});

  return (item1, item2) => {
    return orderMap[getValue(item1)] - orderMap[getValue(item2)];
  };
};
