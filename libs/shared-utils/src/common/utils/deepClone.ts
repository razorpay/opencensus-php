/**
 * Deep clones an object by serializing and deserializing it.
 *
 * @param {T} o - The object to be deep cloned.
 * @returns {T | undefined} - The deep cloned object, or undefined if an error occurs.
 */
export const deepClone = <T>(o: T): T | undefined => {
  try {
    return JSON.parse(JSON.stringify(o));
  } catch (err) {
    console.error('Deepclone error: ', err);
    return undefined;
  }
};
