//@ts-nocheck

import { deepClone } from "./deepClone";

/**
 * Sets a value in a nested object based on a dot-separated path.
 * Supports array notation for keys (e.g., 'sample[0].key').
 * 
 * @param {string} path - The path of the data member (e.g., 'sample.key' or 'sample[0].key').
 * @param {*} value - The value to set in the object.
 * @param {Record<string, any>} [srcObj] - The source object where the value needs to be inserted.
 * @returns {Record<string, any>} - A new object with the updated value.
 * 
 * @example
 * const obj = { sample: [{ key: 'oldValue' }] };
 * const updatedObj = stringToObj('sample[0].key', 'newValue', obj);
 * // returns { sample: [{ key: 'newValue' }] }
 */
export function stringToObj(path: string, value: any, srcObj?: Record<string, any>): Record<string, any> {
  const newObj = srcObj ? deepClone(srcObj) : srcObj;
  // for supporting sample[0][sampleKey]
  const squareBracketPattern = /\[|\]/;
  if (squareBracketPattern.test(path)) {
    parts = path.split(squareBracketPattern).filter((pathEl) => !!pathEl); //splitting with regex gives empty strings
  } else {
    parts = path.split('.');
  }

  let last = parts.pop();

  // converts if numeric for array
  last = isNaN(last) ? last : Number(last);
  let obj = newObj;

  while ((part = parts.shift())) {
    // converts if numeric for array
    part = isNaN(part) ? part : Number(part);

    if (typeof obj[part] !== 'object') {
      // assigning an array if upcoming part is number
      obj[part] = isNaN(parts[0]) ? {} : [];
    }
    obj = obj[part]; // nosemgrep : javascript.lang.security.audit.prototype-pollution.prototype-pollution-loop.prototype-pollution-loop
  }
  obj[last] = value;
  var parts, part;
  return newObj;
}
