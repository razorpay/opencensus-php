import { isPresent } from './rzp-utils';
import {
  isAmount,
  isPhone,
  isMobile,
  isValidPinCode,
  isValidName,
  isUrlLenient,
  isDeepLink,
  isEmail,
  makeValidator,
} from './validators';
import { isPhoneNumberIndia } from '@libs/shared-utils';

export {
  stringDeepTrim as trimDeep,
  validateCIN,
  validateIFSC,
  validateMultipleEmails,
  isPanNumber,
  validateCompanyAB,
  validatePersonalPAN,
  validateCompanyPAN,
  isEmail,
  isUrlLenient,
  isAppLinkValid,
  isValidWebsite,
  flexibleDevUrl,
  isDeepLink,
  validateEmbeddedVideoUrl,
  isAmount,
  isPhone,
  isMobile,
  isValidPinCode,
  isValidName,
  isInteger,
  isIpAddress,
  validatePincodeLength,
  validatePANCard,
  validateGSTIN,
  validateSlug,
  validateAlphanumericWithMaxLength,
  validateAlphanumericWithMinAndMaxLength,
  validateAlphanumericWithStrictLength,
  validateBeneficiaryName,
  validateAlphanumeric,
  validateAmount,
  makeValidator,
  maxLength,
  minLength,
} from '@libs/shared-utils';

export const length = (length, message = '') => {
  message = message || `Must be ${length} characters`;

  return (value = '') => {
    return value.trim().length !== length ? message : '';
  };
};

export const required = makeValidator(isPresent, 'Required');
export const email = makeValidator(isEmail, 'Invalid Email');
export const phone = makeValidator(isPhone, 'Invalid Contact');
export const phoneIndia = makeValidator(isPhoneNumberIndia, 'Please enter 10 digit number');
export const mobile = makeValidator(isMobile, 'Invalid Contact');
export const lenientUrl = makeValidator(isUrlLenient, 'Invalid Url');
export const deepLink = makeValidator(isDeepLink, 'Invalid Link');
export const amount = makeValidator(isAmount, 'Invalid amount');
export const pinCode = makeValidator(isValidPinCode, 'Invalid Pin Code');
export const name = makeValidator(isValidName, 'Invalid name');
