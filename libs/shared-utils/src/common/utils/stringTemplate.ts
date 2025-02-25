/**
 * Replaces placeholders in a string with values from a replacer object.
 * 
 * @param {string} str - The template string with placeholders (e.g., '/notes/{category}?noteId={noteId}').
 * @param {Record<string, string>} replacer - An object containing keys and their corresponding values for replacement.
 * @returns {string} - The resulting string with placeholders replaced by values from the replacer.
 * 
 * @example
 * const template = '/notes/{category}?noteId={noteId}';
 * const params = { category: 'development', noteId: '1' };
 * 
 * const result = stringTemplate(template, params);
 * // returns '/notes/development?noteId=1'
 */
export const stringTemplate = (str: string = '', replacer: Record<string, string> = {}): string => {
  let strCopy = str;
  for (let key in replacer) {
    strCopy = strCopy.replace(new RegExp('{' + key + '}', 'g'), replacer[key] ?? '');
  }
  return strCopy;
};
