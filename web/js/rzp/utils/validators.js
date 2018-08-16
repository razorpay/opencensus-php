import { isPresent, isValidGSTIN } from './rzp-utils';

export const isEmail = email => {
  email = email || '';
  let emailRegExp = new RegExp(
    /^$|[a-zA-Z0-9.!#$%&’*+/=?^_`{|}~-]+@[a-zA-Z0-9-]+(\.[a-zA-Z0-9-]+)+$/
  );
  return emailRegExp.test(email);
};

//- validates url without http/https/www
export const isUrlLenient = url => {
  url = url || '';

  let urlRegExp = /^(https?:\/\/)?[\w.-]+(?:\.[\w\.-]+)+[\w\-\._~:/?#[\]@!\$&'\(\)\*\+,;=.]+$/;
  return urlRegExp.test(url);
};

/*
* Regex to allow development urls like localhost:8000, localhost, anything that user can put in url.
* It doesn't allow strange urls like ...., etc. which are not allowed in url in general
* */
export const flexibleDevUrl = url => {
  url = url || '';

  let urlRegExp = /^(http(s?)?:\/\/)?[\w.-]+(\.[\w.-]+)*(:[0-9]+)?\/?(\/[.\w\-]*)*$/;

  return urlRegExp.test(url);
};

export const isDeepLink = url => {
  url = url || '';

  let urlRegExp = /[A-Za-z]+:\/\/.*/;
  return urlRegExp.test(url);
};

export const isAmount = amount => {
  amount = amount || '';
  let amountRexExp = /^\d+(\.\d{1,2})?$/;
  return amountRexExp.test(amount);
};

export const isPhone = phone => {
  phone = phone || '';
  let phoneRegExp = new RegExp(/^$|\+?[0-9]{8,15}$/);
  return phoneRegExp.test(phone);
};

export const isInteger = value => {
  value = value || '';
  let integerRegExp = new RegExp(/^[0-9]+$/);

  return integerRegExp.test(value);
};

export const isIpAddress = ipAddress => {
  const ipRegExp = new RegExp(
    /\b(?:(?:2(?:[0-4][0-9]|5[0-5])|[0-1]?[0-9]?[0-9])\.){3}(?:(?:2([0-4][0-9]|5[0-5])|[0-1]?[0-9]?[0-9]))\b/
  );
  return ipRegExp.test(ipAddress);
};

// Use required validator if the field is mandatory. This fn. only check whether value if present is valid or not
export function validatePincodeLength(value) {
  return !value || /^[0-9]{6}$/.test(value)
    ? undefined
    : 'Pin Code must be 6 digits';
}

// TODO: Convert to return true/false and make it consumable
// Use required validator if the field is mandatory. This fn. only check whether value if present is valid or not
export function validatePANCard(value) {
  if (value) {
    if (value.length !== 10) {
      return 'PAN card must be 10 characters';
    } else if (!/^[a-zA-z]{5}\d{4}[a-zA-Z]{1}$/.test(value)) {
      return 'Invalid PAN card';
    }
  }
}

// TODO: Convert to return true/false and make it consumable
// Use required validator if the field is mandatory. This fn. only check whether value if present is valid or not
export function validateCIN(value) {
  return value && value.length != 21
    ? 'CIN length must be 21 characters'
    : undefined;
}

// TODO: Convert to return true/false and make it consumable
// Use required validator if the field is mandatory. This fn. only check whether value if present is valid or not
export function validateIFSC(value) {
  return value && value.length != 11 && 'IFSC code must be 11 characters';
}

export function validateMultipleEmails(emails) {
  if (!emails || !emails.length) {
    return false;
  }

  return emails.every(email => !!email && isEmail(email));
}

// Parse Object recursively and trims off extra spaces in strings
export const trimDeep = params => {
  let temp = Object.assign({}, params);

  for (let key in temp) {
    if (temp.hasOwnProperty(key)) {
      if (typeof temp[key] === 'object' && temp[key]) {
        temp[key] = trimDeep(temp[key]);
      } else if (typeof temp[key] === 'string') {
        temp[key] = temp[key].trim();
      }
    }
  }

  return temp;
};

export const length = (length, message = '') => {
  message = message || `Must be ${length} characters`;

  return (value = '') => {
    return value.trim().length !== length ? message : '';
  };
};

export const maxLength = (length, message = '') => {
  message = message || `Enter upto ${length} characters`;

  return (value = '') => {
    return value.trim().length > length ? message : '';
  };
};

const makeValidator = (truthyFn, defaultMessage) => (
  message = defaultMessage
) => value => (truthyFn(value) ? undefined : message);

export const required = makeValidator(isPresent, 'Required');
export const email = makeValidator(isEmail, 'Invalid Email');
export const phone = makeValidator(isPhone, 'Invalid Contact');
export const lenientUrl = makeValidator(isUrlLenient, 'Invalid Url');
export const deepLink = makeValidator(isDeepLink, 'Invalid Link');
export const amount = makeValidator(isAmount, 'Invalid amount');

/**
 * GSTIN Validator for Redux-Form.
 * @param {String} gstin
 * @return {String}
 */
export function validateGSTIN(gstin) {
  // No error if field is empty.
  if (!gstin) return undefined;

  // Return error message if invalid.
  if (!isValidGSTIN(gstin)) {
    return 'Invalid GSTIN';
  }

  // Implicit is better than explicit.
  return undefined;
}
