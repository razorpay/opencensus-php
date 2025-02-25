/**
 * Sanitizes a string by escaping HTML characters to prevent XSS attacks.
 * @param {string} str - The string to sanitize.
 * @returns {string} - The sanitized string with HTML characters escaped.
 * 
 * @example
 * // returns "&lt;script&gt;alert('XSS')&lt;/script&gt;"
 * sanitizeHTML("<script>alert('XSS')</script>");
 * 
 * @example
 * // returns "Hello &amp; welcome!"
 * sanitizeHTML("Hello & welcome!");
 */
export const sanitizeHTML = (str: string): string => {
  const temp = document.createElement('div');
  temp.textContent = str;

  return temp.innerHTML;
};
