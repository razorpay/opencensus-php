const SENSITIVE_FIELDS = [
  'customer_contact',
  'customer_email',
  'cust_contact',
  'cust_email',
  'contact',
  'email',
];

/**
 * Encodes sensitive fields in the given object by applying Base64 encoding.
 *
 * @param {Record<string, string>} params - The object containing fields to encode.
 * @returns {Record<string, string>} - The object with encoded sensitive fields.
 */
export const encodeSensitiveFields = (params: Record<string, string>): Record<string, string> => {
  let parameter = { ...params };
  let unescapeFn = window.unescape || window.decodeURI; // using this logic in local scope only

  for (let param in parameter) {
    if (SENSITIVE_FIELDS.includes(param)) {
      parameter[param] = window?.btoa(unescapeFn(encodeURIComponent(parameter[param])));
    }
  }

  return parameter;
};
