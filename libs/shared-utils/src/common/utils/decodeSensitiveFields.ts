import { isBase64 } from './isBase64';

const SENSITIVE_FIELDS = [
  'customer_contact',
  'customer_email',
  'cust_contact',
  'cust_email',
  'contact',
  'email',
];
/**
 * Decodes sensitive fields from an object using URL parameters.
 * If a sensitive field is found in the URL and is Base64 encoded, it is decoded.
 *
 * @param {Record<string, string>} params - The object containing parameters to be decoded.
 * @returns {Record<string, string>} - The object with decoded sensitive fields.
 */
export const decodeSensitiveFields = (params: Record<string, string>): Record<string, string> => {
  const getURLFields = new URLSearchParams(window.location.search);
  let parameter = { ...params };

  for (let param in parameter) {
    if (SENSITIVE_FIELDS.includes(param) && getURLFields?.get?.(param) !== undefined) {
      if (isBase64(getURLFields.get(param) ?? '')) {
        parameter[param] = window?.atob(getURLFields.get(param) ?? '') ?? '';
      } else {
        parameter[param] = decodeURI(parameter[param]);
      }
    }
  }

  return parameter;
};
