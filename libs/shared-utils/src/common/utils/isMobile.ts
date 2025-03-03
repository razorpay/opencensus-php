/**
 * Validates a mobile number based on the provided country code.
 * Defaults to 'IN' (India) if no country code is provided.
 *
 * @param {string} mobile - The mobile number to validate.
 * @param {string} [countryCode='IN'] - The country code for validation.
 * @returns {boolean} - Returns true if the mobile number is valid for the specified country code, false otherwise.
 */

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

export const isMobile = (mobile: string = '', countryCode: keyof typeof PHONE_NUMBER_REGEX_MAP = 'IN'): boolean => {
  const mobileRegExp = new RegExp(PHONE_NUMBER_REGEX_MAP[countryCode]);
  return mobileRegExp.test(mobile);
};
