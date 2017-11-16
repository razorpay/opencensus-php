import moment from 'moment';

moment.updateLocale('en', {
  relativeTime: {
    s: 'few secs',
    ss: '%s secs',
    m: 'a min',
    mm: '%d mins',
  },
});

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

export const normalizeBoolean = bool => {
  if (bool === undefined) {
    return bool;
  }

  return bool ? 1 : 0;
};

export const getFixedINRAmount = amount => (Number(amount) / 100).toFixed(2);

// following regex formats in indian comma separated, i.e. 2,01,20,45,222.66
export const getFormattedAmount = amount =>
  (amount / 100).toFixed(2).replace(/(.{1,2})(?=.(..)+(\...)$)/g, '$1,');

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
};

export const getIntervalCycle = (interval, period) => {
  switch (interval) {
    case 1:
      return `Every ${periods[period]}`;

    case 2:
      return `Bi-${titleCase(period)}`;

    default:
      return `Once in ${interval} ${periods[period]}s`;
  }
};

export const getCustomerDisplayName = ({ name, contact, email }) => {
  let displayParts = [name, contact, email].filter(item => !isBlank(item));

  return `${displayParts.join(' / ').replace('/ ', '(')}${displayParts.length >
  1
    ? ')'
    : ''}`;
};

/**
 * Method to create a query string separated by | instead of &
 * @param {Object} params
 * @return {String}
 */
export const stringifyQueryParamsWithPipe = params => {
  if (!params) return '';
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
      return null;
  }
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
