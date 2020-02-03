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

export const isMobile = mobile => {
  mobile = mobile || '';
  let mobileRegExp = new RegExp(
    /^(?:(?:\+|0{0,2})91(\s*[\-]\s*)?|[0]?)?[789]\d{9}$/
  );
  return mobileRegExp.test(mobile);
};

export const isValidPinCode = pinCode => {
  pinCode = pinCode || '';
  let pinCodeRegExp = new RegExp(/^[1-9][0-9]{5}$/);
  return pinCodeRegExp.test(pinCode);
};

export const isValidName = name => {
  name = name || '';
  let nameRegExp = new RegExp(/^[a-zA-Z ]+$/);
  return nameRegExp.test(name);
};

export const isInteger = (value = '') => {
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

export function validateCompanyAB(value1 = '', value2 = '', isExpOn = false) {
  if (!isExpOn) return false;
  value1 = value1 === null ? '' : value1;
  value2 = value2 === null ? '' : value2;
  return value1.toLowerCase() === value2.toLowerCase()
    ? 'Company name cannot be same as Contact Name'
    : false;
}

export function validatePANCardUnregBiz(value) {
  if (validatePANCard(value)) {
    return validatePANCard(value);
  } else if (value && value[3] !== 'P' && value[3] !== 'p') {
    return "The PAN entered is a business PAN. If you are a registered business, please change your business type in the 'Business Overview' tab.";
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
export const mobile = makeValidator(isMobile, 'Invalid Contact');
export const lenientUrl = makeValidator(isUrlLenient, 'Invalid Url');
export const deepLink = makeValidator(isDeepLink, 'Invalid Link');
export const amount = makeValidator(isAmount, 'Invalid amount');
export const pinCode = makeValidator(isValidPinCode, 'Invalid Pin Code');
export const name = makeValidator(isValidName, 'Invalid name');

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

export function validateSlug(val) {
  if (!val) {
    return;
  }

  const slugRegex = /^[A-Za-z0-9]+(?:-[A-Za-z0-9]+)*$/;

  return slugRegex.test(val);
}

export function validateAlphanumericWithMaxLength(value, maxLength) {
  let regex = new RegExp(`^[a-z0-9]{0,${maxLength}}$`, 'i');

  return regex.test(value);
}

export function validateAlphanumericWithStrictLength(value, length) {
  let regex = new RegExp(`^[a-z0-9]{${length}}$`, 'i');

  return regex.test(value);
}
