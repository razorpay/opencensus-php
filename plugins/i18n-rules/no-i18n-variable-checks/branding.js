// List of regular expressions to match the names of internationalization-related organizations or branding related texts.
const i18nVariableNamePatterns = [/.*rzp.*/, /.*curlec.*/, /.*razorpay.*/];

// List of names of variables that should be ignored during the check
const ignoreVariableNamesChecks = ['rzpAnalytics', 'rzp_user', 'rzpQ', 'rzpTicketSystem'];

/**
 * Checks if the given variable name should trigger a warning message
 * @param {string} value - The name of the variable to check
 * @returns {string|false} A warning message if the variable name should trigger a warning, otherwise false
 */
const getIsI18nNameCheckFound = (value) => {
  // Check if the variable name is in the ignore list
  const isIgnored = ignoreVariableNamesChecks.includes(value);

  // Check if the variable name matches any of the defined patterns
  const isOrgNameCheckFound = i18nVariableNamePatterns.some((pattern) =>
    pattern.test(value.toLowerCase()),
  );

  if (isOrgNameCheckFound && !isIgnored) {
    // Return the warning message if the variable name matches a pattern and is not in the ignore list
    return `Avoid using variables like ${value}. As they may cause the code to be region-centric.`;
  }

  // Return false if the variable name does not match any pattern or is in the ignore list
  return false;
};

module.exports = {
  getIsI18nNameCheckFound,
};
