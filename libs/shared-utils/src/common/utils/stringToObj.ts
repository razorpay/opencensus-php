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
  // Ensure newObj is always a Record<string, any>
  const newObj: Record<string, any> = deepClone(srcObj) || {};
  const squareBracketPattern = /\[|\]/;
  const parts: string[] = path.split(squareBracketPattern).filter((pathEl) => pathEl);

  // Ensure there are parts to process
  if (parts.length === 0) return newObj;

  let last: string | number = parts.pop() as string | number; // Last part as string or number
  last = isNaN(Number(last)) ? last : Number(last); // Convert last to number if numeric

  let obj: Record<string, any> = newObj;

  for (const part of parts) {
    const key: string | number = isNaN(Number(part)) ? part : Number(part); // Key as string or number

    // Initialize the next level in the object if it doesn't exist
    if (obj[key] === undefined) {
      obj[key] = isNaN(Number(parts[0])) ? {} : [];
    } else if (typeof obj[key] !== 'object' || obj[key] === null) {
      // Convert to an array or object if it's not the right type
      obj[key] = isNaN(Number(parts[0])) ? {} : [];
    }

    obj = obj[key]; // Move down to the next level
  }

  obj[last] = value; // Set the value
  return newObj; // Return the modified object
}
