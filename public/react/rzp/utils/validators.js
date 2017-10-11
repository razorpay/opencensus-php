import { isPresent } from './rzp-utils';

export const isEmail = email => {
  email = email || '';
  let emailRegExp = new RegExp(
    /^$|[a-zA-Z0-9.!#$%&’*+/=?^_`{|}~-]+@[a-zA-Z0-9-]+(\.[a-zA-Z0-9-]+)+$/
  );
  return emailRegExp.test(email);
};

export const isUrl = url => {
  url = url || '';

  let urlRegExp = /^(https?:\/\/)(\w|\-)+(\.{1}(\w|\-)+)*\.[a-z]{2,}(:[0-9]{1,5})?(\/.*)?/;
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

// Use required validator if the field is mandatory. This fn. only check whether value if present is valid or not
export function validatePincodeLength(value) {
  return !value || /^[0-9]{6}$/.test(value)
    ? undefined
    : 'Pin Code must be 6 digits';
}

// Use required validator if the field is mandatory. This fn. only check whether value if present is valid or not
export function validatePANCard(value) {
  return !value ||
    (value.length === 10 && /^[a-zA-z]{5}\d{4}[a-zA-Z]{1}$/.test(value))
    ? undefined
    : 'Invalid PAN card';
}

// Use required validator if the field is mandatory. This fn. only check whether value if present is valid or not
export function validateCIN(value) {
  return value && value.length != 21
    ? 'CIN length must be 21 characters'
    : undefined;
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

const makeValidator = (truthyFn, defaultMessage) => (
  message = defaultMessage
) => value => (truthyFn(value) ? undefined : message);

export const required = makeValidator(isPresent, 'Required');
export const email = makeValidator(isEmail, 'Invalid Email');
export const phone = makeValidator(isPhone, 'Invalid Contact');
export const url = makeValidator(isUrl, 'Invalid Url');
export const deepLink = makeValidator(isDeepLink, 'Invalid Link');
export const amount = makeValidator(isAmount, 'Invalid amount');
