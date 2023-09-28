import { match } from 'path-to-regexp';

const removeParenthesesContent = (url) => {
  // Define the regex pattern to match content within parentheses
  const pattern = /\([^)]+\)/g;

  // Use replace() to remove the matched content within parentheses
  const resultString = url.replace(pattern, '');

  return resultString;
};

/**
 * @deprecated Regex path is not supported anymore in react router 6.
 * @description Adds support for regex in url path
 * @param refRoutePath Path string including the regex and other validations.
 * @param pathname Present pathname from react-router.
 * @returns {string}
 */
export const validateRoute = (refRoutePath: string, pathname: string) => {
  const isMatched = match(refRoutePath, {
    decode: decodeURIComponent,
  })(pathname) as { path: string };

  const cleanedRoutePath = removeParenthesesContent(refRoutePath);

  return Boolean(isMatched) ? cleanedRoutePath : refRoutePath;
};
