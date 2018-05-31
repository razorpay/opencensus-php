export const getFormattedAmount = amount =>
  (amount / 100).toFixed(2).replace(/(.{1,2})(?=.(..)+(\...)$)/g, '$1,');

export const deepClone = o => {
  try {
    return JSON.parse(JSON.stringify(o));
  } catch (err) {
    console.log('Deepclone error: ', err);
  }
};
export const animObj = { enter: 300, exit: 300 };
export const prevent = e => {
  e.preventDefault();
  e.stopPropagation();
};

export const titleCase = str => {
  if (!str) {
    // to handle empty string or null values
    str = '--';
  }
  const chars = str.split('');

  return chars[0].toUpperCase() + chars.splice(1).join('');
};

export const snakeToTitleCase = (str = '') => {
  return str
    .split('_')
    .map(titleCase)
    .join(' ');
};

/**
 * gets date in format 21st Dec, 2017 05:00
 * @param  {Number} unixTimestamp in seconds
 * @return {String}               date in 21st Dec, 2017 05:00 format
 */
export const formatDate = unixTimestamp =>
  unixTimestamp
    ? moment(unixTimestamp, 'X').format('Do MMM, YYYY hh:mm A')
    : null;

export const removeFromArray = (array, index) => {
  let newArray = array.slice();
  newArray.splice(index, 1);
  return newArray;
};

export const removeLineBreaks = str => str.replace(/[\n|\r]/g, ' ');

/*
 * Check for pending workflow requests
*/
export const isWorkflow = (response, history = null) => {
  if (
    typeof response.id !== 'undefined' &&
    response.id.indexOf('w_action') === 0 &&
    typeof response.workflow_id !== 'undefined'
  ) {
    const url = `/admin/requests/${response.id}`;
    return (location.href = url);
  } else {
    return false;
  }
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
 * Check for empty string/object
 * @param {*} value
 */
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

export function subString(str, length) {
  if (!str) {
    return str;
  }

  if (str.length > length) {
    return `${str.substr(0, length)} ...`;
  } else {
    return str;
  }
}

/*
* Helper fn. to fetch IFSC bank details for IFSC code entered in field
* */
export function getDetailsForIFSC(ifscCode) {
  if (ifscCode.length !== 11) {
    return null;
  }

  return axios('https://ifsc.razorpay.com/' + ifscCode).then(info => {
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

  return arr.filter(item => !map[item] && (map[item] = true));
}
