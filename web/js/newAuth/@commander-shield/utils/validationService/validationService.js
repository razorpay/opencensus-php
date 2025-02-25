import featureFlags from '../../utils/featureFlags';
import { EMAIL_VERIFY_REGEX, MOBILE_NUMBER_VERIFY_REGEX, OTP_VALIDATION_REGEX } from '../regex';
import { authMethods, authModes } from '../../screens/screenHelpers';

export const EMAIL_OR_NUMBER_VALIDATION_ERROR_MESSAGE =
  'Please enter a valid email id or mobile number.';
const EMAIL_VALIDATION_ERROR_MESSAGE = 'Please enter a valid email.';
const OTP_VALIDATION_ERROR_MESSAGE = 'Please enter a valid OTP';
export const PASSWORD_VALIDATION_ERROR_MESSAGE = 'Please fill in this field';

const isValidEmailOrNumber = (emailOrNumber) => {
  if (!emailOrNumber) {
    return false;
  } else if (
    !EMAIL_VERIFY_REGEX.test(emailOrNumber) &&
    !MOBILE_NUMBER_VERIFY_REGEX.test(emailOrNumber)
  ) {
    return false;
  }

  return true;
};

const isValidEmail = (email) => {
  if (!email) {
    return false;
  } else if (!EMAIL_VERIFY_REGEX.test(email)) {
    return false;
  }

  return true;
};

/**
 * Get the signin/signup method on giving email or number field
 * @param {string} emailOrNumber - Email id or phone number
 * @returns {'phone_number' | 'email' | null} phone_number or email. null when field is neither email or number
 */
export const getSignMethod = (emailOrNumber) => {
  if (MOBILE_NUMBER_VERIFY_REGEX.test(emailOrNumber)) {
    return authMethods.PHONE_NUMBER;
  } else if (EMAIL_VERIFY_REGEX.test(emailOrNumber)) {
    return authMethods.EMAIL;
  }

  // invalid email or password
  return null;
};

// Screen 1: Form with email and next button
export const validateSignInStep1Form = (values) => {
  const errors = {};

  if (featureFlags.ENABLE_MOBILE_OTP_FLOW) {
    if (!isValidEmailOrNumber(values.emailOrNumber)) {
      errors.emailOrNumber = EMAIL_OR_NUMBER_VALIDATION_ERROR_MESSAGE;
    }
  } else {
    // eslint-disable-next-line no-lonely-if
    if (!isValidEmail(values.emailOrNumber)) {
      errors.emailOrNumber = EMAIL_VALIDATION_ERROR_MESSAGE;
    }
  }

  return errors;
};

// Screen 2: Form with email and password
export const validateSignInStep2Form = (values, { authMode }) => {
  const errors = {};
  if (authMode === authModes.OTP) {
    // OTP method so verify otp
    if (!values.otp) {
      errors.otp = OTP_VALIDATION_ERROR_MESSAGE;
    } else if (!OTP_VALIDATION_REGEX.test(values.otp)) {
      errors.otp = OTP_VALIDATION_ERROR_MESSAGE;
    }
  } else if (authMode === authModes.PASSWORD) {
    if (!values.password) {
      errors.password = PASSWORD_VALIDATION_ERROR_MESSAGE;
    }
  }

  return errors;
};
