import moment from 'moment';
import { isValidGSTIN } from 'common/utils/rzp-utils';
import { isValidPinCode, isEmail, isPanNumber } from 'common/utils/validators';

export const isMobile = (value) => {
  return !!(value && value.length === 10 && /[1-9][0-9]{5}/.test(value));
};

export const isLegitEmail = (value) => {
  return !!(value && isEmail(value) && !value.includes('+'));
};

export const isPersonalPanNumber = (value) => {
  return !!(value && /[A-Z]{3}[P]{1}[A-Z]{1}[0-9]{4}[A-Z]{1}/.test(value));
};

export function validateGSTIN(gstin) {
  if (!gstin) return '';

  if (!isValidGSTIN(gstin)) {
    return 'Please enter a valid GSTIN Number';
  }

  return '';
}

export function validateAddress(address) {
  if (!address) return 'Residential Address is required';

  return '';
}

export function validateBusinessAddress(address) {
  if (!address) return 'Business address is required';

  return '';
}

export function validatePinCode(pincode) {
  if (!isValidPinCode(pincode)) {
    return 'Please enter a valid Pincode';
  } else {
    return '';
  }
}

export function validateFirstName(name) {
  if (!name) return 'First name is required';
  return '';
}

export function validateLastName(name) {
  if (!name) return 'Last name is required';
  return '';
}

export function validateEmail(email) {
  if (!email || !isLegitEmail(email)) return 'Please enter a valid Email Address';
  return '';
}

export function validateMobile(mobile) {
  if (!mobile || !isMobile(mobile)) return 'Please enter a valid Mobile Number';
  return '';
}

export function validatePanNumber(pan_number) {
  if (!pan_number || !isPersonalPanNumber(pan_number)) return 'Please enter a valid PAN Number';
  return '';
}

export function validateCompanyPan(pan_number) {
  if (!pan_number) return '';
  if (!isPanNumber(pan_number)) return 'Please enter a valid PAN Number';
  return '';
}

export function validateDob(dob) {
  if (!dob) return 'Date of Birth is required';
  const age = moment().diff(dob, 'years');
  const valid = age <= 65 && age >= 23;
  if (!valid) return 'Please enter a valid Date of Birth';
  return '';
}
