/**
 * Recursively parses an object and trims extra spaces from all string values.
 * 
 * @param {Record<string, any>} params - The object to trim.
 * @returns {Record<string, any>} - A new object with all string values trimmed.
 * 
 * @example
 * const data = {
 *   name: ' Alice ',
 *   details: {
 *     age: 30,
 *     city: ' New York ',
 *   },
 *   tags: [' tag1 ', ' tag2 '],
 * };
 * 
 * const trimmedData = stringDeepTrim(data);
 * // returns { name: 'Alice', details: { age: 30, city: 'New York' }, tags: ['tag1', 'tag2'] }
 */
export const stringDeepTrim = (params: Record<string, any>): Record<string, any> => {
  let temp = Object.assign({}, params);

  for (let key in temp) {
    if (temp.hasOwnProperty(key)) {
      if (typeof temp[key] === 'object' && temp[key]) {
        temp[key] = stringDeepTrim(temp[key]);
      } else if (typeof temp[key] === 'string') {
        temp[key] = temp[key].trim();
      }
    }
  }

  return temp;
};
