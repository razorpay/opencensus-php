/**
 * Flattens a nested object into a single level object with delimited keys.
 *
 * @param {Record<string, any>} object - The object to flatten.
 * @param {string} [delimiter='.'] - The string used to separate nested keys.
 * @returns {Record<string, any>} - The flattened object with delimited keys.
 *
 * @example
 * const nestedObject = { a: 1, b: { c: 2, d: { e: 3 } } };
 * const result = flattenObject(nestedObject, '_');
 * console.log(result); 
 * // Output: { 'a': 1, 'b_c': 2, 'b_d_e': 3 }
 */
export const flattenObject = (object: Record<string, any>, delimiter: string = '.'): Record<string, any> => {
  let keys = Object.keys(object);
  let flat: Record<string, any> = {};
  
  for (let i = 0; i < keys.length; i++) {
    let key = keys[i];
    let val = object[key];

    // if the value is an object and not falsy (null)
    if (typeof val === 'object' && !!val) {
      const _obj = flattenObject(val, delimiter);
      const _keys = Object.keys(_obj);
      for (let j = 0; j < _keys.length; j++) {
        flat[key + delimiter + _keys[j]] = _obj[_keys[j]];
      }
    } else {
      flat[key] = val;
    }
  }
  return flat;
};
