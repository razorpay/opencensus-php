import { isPresent, isValidGSTIN, getCurrencyConfig } from './rzp-utils';

const rzp_gst = '29AAGCR4375J1ZU';

export const isEmail = (email) => {
  email = email || '';
  const emailRegExp = new RegExp(
    /^$|[a-zA-Z0-9.!#$%&’*+/=?^_`{|}~-]+@[a-zA-Z0-9-]+(\.[a-zA-Z0-9-]+)+$/,
  );
  return emailRegExp.test(email);
};

//- validates url without http/https/www
export const isUrlLenient = (url) => {
  url = url || '';
  /* eslint-disable */
  const urlRegExp = /^(https?:\/\/)?[\w.-]+(?:\.[\w\.-]+)+[\w\-\._~:/?#[\]@!\$&'\(\)\*\+,;=.]+$/;
  return urlRegExp.test(url);
};

/*
 * Regex to allow development urls like localhost:8000, localhost, anything that user can put in url.
 * It doesn't allow strange urls like ...., etc. which are not allowed in url in general
 * */
export const flexibleDevUrl = (url) => {
  url = url || '';
  const urlRegExp = new RegExp(
    /^(http(s?)?:\/\/)?[\w.-]+(\.[\w.-]+)*(:[0-9]+)?\/?(\/[.\w\-]*)*(\?.*)?$/,
  );
  return urlRegExp.test(url);
};

export const isDeepLink = (url) => {
  url = url || '';

  const urlRegExp = /[A-Za-z]+:\/\/.*/;
  return urlRegExp.test(url);
};

// Note: Fallacy in this method is, the 3rd party urls can keep modifying / may add new url shortner. So this would have to be updated.
export const validateEmbeddedVideoUrl = (url) => {
  url = url || '';
  const urlRegExp = new RegExp(
    /^(http(s)?:\/\/)((w){3}.)?(vimeo\.com|youtu\.be|youtube\.com)\/([\w-_\/]+)([\?].*)?$/i,
  );

  return urlRegExp.test(url);
};

export const isAmount = (amount) => {
  amount = amount || '';
  const amountRexExp = new RegExp(/^\d+(\.\d{1,2})?$/);
  return amountRexExp.test(amount);
};

export const isPhone = (phone) => {
  phone = phone || '';
  const phoneRegExp = new RegExp(/^$|\+?[0-9]{8,15}$/);
  return phoneRegExp.test(phone);
};

const PHONE_NUMBER_REGEX_MAP = {
  /**
   * Regex to verify Indian mobile numbers
   * starting with 6,7,8,9 followed by 9 digits
   */
  IN: /^(?:(?:\+|0{0,2})91(\s*[\-]\s*)?|[0]?)?[6789]\d{9}$/,
  /**
   * Regex to verify malaysian mobile numbers
   * (60|0) => states a number can either start with 0 or 60
   * -* => stands for proceeding with
   * (11|1) => states a number can be 1 or 11
   * -* => stands for proceeding with
   * [0-9]{8} => followed by 8 digits between range 0 to 9
   */
  MY: /^(0|60)-*(1|11)-*[0-9]{8}$/,
};

export const isMobile = (mobile, countryCode = 'IN') => {
  mobile = mobile || '';
  const mobileRegExp = new RegExp(PHONE_NUMBER_REGEX_MAP[countryCode]);
  return mobileRegExp.test(mobile);
};

export const isValidPinCode = (pinCode) => {
  pinCode = pinCode || '';
  const pinCodeRegExp = new RegExp(/^[1-9][0-9]{5}$/);
  return pinCodeRegExp.test(pinCode);
};

export const isValidName = (name) => {
  name = name || '';
  const nameRegExp = new RegExp(/^[a-zA-Z ]+$/);
  return nameRegExp.test(name);
};

export const isInteger = (value = '') => {
  const integerRegExp = new RegExp(/^[0-9]+$/);

  return integerRegExp.test(value);
};

export const isIpAddress = (ipAddress) => {
  const ipRegExp = new RegExp(
    /\b(?:(?:2(?:[0-4][0-9]|5[0-5])|[0-1]?[0-9]?[0-9])\.){3}(?:(?:2([0-4][0-9]|5[0-5])|[0-1]?[0-9]?[0-9]))\b/,
  );
  return ipRegExp.test(ipAddress);
};

// Use required validator if the field is mandatory. This fn. only check whether value if present is valid or not
export function validatePincodeLength(value) {
  return !value || /^[0-9]{6}$/.test(value) ? undefined : 'Pin Code must be 6 digits';
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

export function isPanNumber(value) {
  return !!(value && value.length === 10 && /^[a-zA-z]{5}\d{4}[a-zA-Z]{1}$/.test(value));
}

export function validateCompanyAB(value1 = '', value2 = '', isExpOn = false) {
  if (!isExpOn) return false;
  value1 = value1 === null ? '' : value1;
  value2 = value2 === null ? '' : value2;
  return value1.toLowerCase() === value2.toLowerCase()
    ? 'Company name cannot be same as Contact Name'
    : false;
}

export function validatePersonalPAN(value, isUnregisteredBusiness) {
  if (validatePANCard(value)) {
    return validatePANCard(value);
  } else if (value && value[3] !== 'P' && value[3] !== 'p') {
    if (isUnregisteredBusiness) {
      return "The PAN entered is a business PAN. If you are a registered business, please change your business type in the 'Business Overview' tab.";
    } else {
      return 'Invalid PAN format';
    }
  }
}

export function validateCompanyPAN(value) {
  if (!value) {
    return;
  }
  const panValidationError = validatePANCard(value);
  if (panValidationError) {
    return panValidationError;
  } else if (['C', 'H', 'F', 'A', 'T', 'B', 'J', 'G', 'L'].indexOf(value[3].toUpperCase()) === -1) {
    return 'Invalid PAN format';
  }
}

// TODO: Convert to return true/false and make it consumable
// Use required validator if the field is mandatory. This fn. only check whether value if present is valid or not
export function validateCIN(value, type = 'CIN') {
  if (value) {
    if (value.length != 21 && type === 'CIN') {
      return 'CIN length must be 21 characters';
    } else if (
      !/^([a-z]{3}-\d{4}|([F|f]\w{3}-\d{4})|[ul]\d{5}[a-z]{2}\d{4}[a-z]{3}\d{6})$/i.test(value)
    ) {
      return `Please Provide Valid ${type}`;
    }
  }
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

  return emails.every((email) => !!email && isEmail(email));
}

// Parse Object recursively and trims off extra spaces in strings
export const trimDeep = (params) => {
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

export const minLength = (minLength) => (value) => {
  if (typeof value !== 'string' || value.length < minLength) {
    return `Must be ${minLength} characters or more`;
  }
  return undefined;
};

const makeValidator =
  (truthyFn, defaultMessage) =>
  (message = defaultMessage) =>
  (value) =>
    truthyFn(value) ? undefined : message;

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

  if (gstin === rzp_gst) return `This is Razorpay's GSTIN number. Please enter your GSTIN number`;

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
  const regex = new RegExp(`^[a-z0-9]{0,${maxLength}}$`, 'i');

  return regex.test(value);
}

export function validateAlphanumericWithStrictLength(value, length) {
  const regex = new RegExp(`^[a-z0-9]{${length}}$`, 'i');

  return regex.test(value);
}

export function validateBeneficiaryName(value) {
  const regex = new RegExp(/^[a-zA-Z0-9 ]+$/);

  return value.length >= 4 && value.length <= 120 && regex.test(value);
}

export function validateAlphanumeric(value) {
  const regex = new RegExp(/^[a-z0-9]+$/i);

  return regex.test(value);
}

export function validateAlphanumericWithMinAndMaxLength(value, minLength, maxLength) {
  if (value.length < minLength) return false;

  return validateAlphanumericWithMaxLength(value, maxLength);
}

export function validateAmount(val, minAmountAllowed, currency = 'INR') {
  if (val) {
    const { decimals } = getCurrencyConfig(currency);
    const decimalPart = val?.split('.')?.[1] ?? '';
    const amountPattern = `^[0-9]+(.([0-9]){1,${decimals}})?$`;
    const regex = new RegExp(amountPattern);
    const validPattern = 123.450;

    if (!regex.test(Number(val))) {
      return `Amount must be a number in the format ${validPattern.toFixed(decimals)}`;
    }

    if (typeof minAmountAllowed !== 'undefined' && Number(val) < Number(minAmountAllowed)) {
      return `Amount must be at least ${minAmountAllowed}`;
    }

    if (decimals === 3 && decimalPart.length === 3 && decimalPart[2] != 0) {
      return 'Last digit should be 0 for three decimal currencies';
    }
  }
}
