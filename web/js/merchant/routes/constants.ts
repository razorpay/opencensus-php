// TODO: This is a temporary change. Refer this slack thread for more: https://razorpay.slack.com/archives/C05RBSKC6KD/p1704894733322289

export const getLocaleFromCountryCode = (countryCode: string): string => {
  if (!countryCode) {
    return 'en'; // default to English if no country code is provided
  }

  try {
    return `en-${countryCode.toUpperCase()}`;
  } catch (error) {
    return 'en';
  }
};
