import { DENOMINATION_TYPE } from './constants';

const validateAlphaNumericSpace = /^[a-z0-9 ]+$/i;
const validateUpperCaseAlphaNumeric = /^[A-Z0-9]*$/;

export function validateName(val: string | undefined): string | boolean {
  if (!val) return 'Please fill out this field';
  if (!validateAlphaNumericSpace.test(val)) {
    return 'Name should be alphanumeric.';
  }
  return false;
}

export function validateDescription(val) {
  if (!val) return 'Please fill out this field';
  if (val.length < 1) {
    return 'Description is a required field';
  }
  return false;
}
export function validateDiscount(val) {
  if (val === '') return 'Discounts needs to be filled';
  const integerVal = Number(val);
  if (Number.isNaN(integerVal)) {
    return 'Discount needs to be a percentage';
  }
  if (integerVal > 100 || integerVal < 0)
    return 'Invalid discount. Enter a value between 1-100% (e.g., 15.75%).';
  return false;
}

export function validateDenominationType(val) {
  if (!val) return 'Please select one of the denomination types';
  return false;
}

export function validateExpiryPeriod(val) {
  if (!val) return 'Please fill this field';
  const integerVal = Number(val);
  if (integerVal === 0) return 'Zero values are not allowed';
  return false;
}

export function validateNotNull(val) {
  if (!val) return 'Please fill the field';
  return false;
}

export function validateDenominationValues(values) {
  const { denomination_type: type, denomination_values: val } = values;
  if (type === DENOMINATION_TYPE.CUSTOMIZABLE.value) {
    if (!val?.from || !val?.to) {
      return 'Please enter the start and end range';
    } else {
      const intFrom = Number(val.from),
        intTo = Number(val.to);
      if (intFrom >= intTo) {
        return 'Invalid range: The ending value must be greater than the starting value.';
      }
    }
  }
  if (type === DENOMINATION_TYPE.FIXED.value) {
    if (!val.length) {
      return 'Please select atleast one value';
    }
  }
  return false;
}

export function validatePin(val) {
  if (typeof val === 'undefined') return 'Please select one of the fields';
  return false;
}

export function validateImage(val) {
  if (!val) return 'Please fill out this field';
  return false;
}

export function validatePrefix(val) {
  if (val && val.length > 4) return 'Prefix cannot be more than 4 characters';
  else if (!validateUpperCaseAlphaNumeric.test(val))
    return 'Only upper case alphabets and numbers are allowed';
  return false;
}

export function validateCardType(val) {
  if (!val) return 'Please select one of the card types';
  return false;
}
