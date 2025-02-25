/* eslint-disable no-useless-escape */
export const EMAIL_VERIFY_REGEX = /^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,24}$/i;
export const MOBILE_NUMBER_VERIFY_REGEX = /^\+?[0-9]{1}[0-9]{7,13}$/;
export const OTP_VALIDATION_REGEX = /^\d{6}$/i;
export const PASSWORD_REGEX = /^(?=.*\d)(?=.*[a-zA-Z]).{8,}$/;

const MOBILE_NUMBER_REGEX = {
  /**
   * Identifies following patterns
   * 1XXXXXXXX, 01XXXXXXXX, 11XXXXXXXX, 011XXXXXXXX
   * 601XXXXXXXX, 6011XXXXXXXX
   */
  MALAYSIA: /^(0|60)?(1|11)-*[0-9]{8}$/,
};

// format mobile number with prefix dialcode
export const getFormattedNumber = (phoneNumber) => {
  let updatedPhoneNumber = phoneNumber;
  const malaysianRegex = new RegExp(MOBILE_NUMBER_REGEX.MALAYSIA);
  if (malaysianRegex.test(updatedPhoneNumber)) {
    if (phoneNumber.startsWith('60')) {
      updatedPhoneNumber = updatedPhoneNumber.slice(2);
    } else if (phoneNumber.startsWith('01')) {
      updatedPhoneNumber = updatedPhoneNumber.slice(1);
    }
    updatedPhoneNumber = `+60${updatedPhoneNumber}`;
  }
  return updatedPhoneNumber;
};
