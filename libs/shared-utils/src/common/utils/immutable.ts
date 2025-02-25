/**
 * Checks if the provided item is an object and not an array.
 * 
 * @param {unknown} item - The item to check.
 * @returns {boolean} - Returns true if the item is an object and not an array.
 * 
 * @example
 * _isObject({ key: 'value' }); // true
 * _isObject([1, 2, 3]); // false
 */
function _isObject(item: unknown): item is Record<string, any> {
  return Boolean(item) && typeof item === 'object' && !Array.isArray(item);
}



/**
 * Updates an item in an array at the specified index with a new value.
 * 
 * @param {T[]} array - The array to update.
 * @param {number} index - The index to update.
 * @param {T} value - The new value to insert.
 * @returns {T[]} - A new array with the updated value.
 * 
 * @example
 * updateItem([1, 2, 3], 1, 42); // [1, 42, 3]
 */
export const updateItem = <T>(array: T[], index: number, value: T): T[] => {
  const newArray = array.slice();
  newArray.splice(index, 1, value);
  return newArray;
};

/**
 * Sets a property in an array or object state.
 * 
 * @param {T} state - The current state (array or object).
 * @param {string | number} prop - The property/index to update.
 * @param {T} value - The new value to set.
 * @returns {T} - A new state with the updated value.
 */
const simpleSet = <T>(state: T, prop: string | number, value: T): T => {
  if (Array.isArray(state)) {
    return updateItem(state, prop as number, value) as unknown as T;
  }

  if (_isObject(state)) {
    const Klass = (state as any).constructor;
    return new Klass({
      ...state,
      [prop]: value,
    }) as T;
  }

  return {
    ...state as {},
    [prop]: value,
  } as T;
};


/**
 * Inserts an item into an array at a specified index.
 * 
 * @param {T[]} array - The array to insert into.
 * @param {number} index - The index to insert at.
 * @param {T} value - The value to insert.
 * @returns {T[]} - A new array with the item inserted.
 */
export const insertItem = <T>(array: T[], index: number, value: T): T[] => {
  const newArray = array.slice();
  newArray.splice(index, 0, value);
  return newArray;
};

/**
 * Removes an item from an array at a specified index.
 * 
 * @param {T[]} array - The array to remove from.
 * @param {number} index - The index to remove.
 * @returns {T[]} - A new array with the item removed.
 */
export const removeItem = <T>(array: T[], index: number): T[] => {
  const newArray = array.slice();
  newArray.splice(index, 1);
  return newArray;
};

/**
 * Adds an item to the beginning of an array.
 * 
 * @param {T[]} array - The array to modify.
 * @param {T} value - The value to add.
 * @returns {T[]} - A new array with the value added at the beginning.
 */
export const unshift = <T>(array: T[], value: T): T[] => {
  return insertItem(array, 0, value);
};

/**
 * Adds an item to the end of an array.
 * 
 * @param {T[]} array - The array to modify.
 * @param {T} value - The value to add.
 * @returns {T[]} - A new array with the value added at the end.
 */
export const push = <T>(array: T[], value: T): T[] => {
  return insertItem(array, array.length, value);
};

/**
 * Sets a property in a deeply nested object or array.
 * Supports dot notation for deeply nested properties.
 * 
 * @param {T} state - The current state.
 * @param {string} prop - The property to set (supports dot notation).
 * @param {T} value - The value to set.
 * @returns {T} - A new state with the updated value.
 */
export const set = <T>(state: T, prop: string, value: T): T => {
  const props = (prop || "").split('.');
  
  if (props.length === 1) {
    return simpleSet(state, prop, value) as T;
  }

  return props.reduce((prev: any, curr, idx) => {
    if (idx > 0) return prev;
    const slicedProps = props.slice();
    slicedProps.shift();
    return simpleSet(prev, curr, set(prev[curr], slicedProps.join('.'), value));
  }, state);
};


/**
 * Merges two objects.
 * 
 * @param {Record<string, T>} state - The target state.
 * @param {Record<string, T>} obj - The object to merge into the state.
 * @returns {Record<string, T>} - A new state with the merged object.
 * 
 * @example
 * const merged = merge({ a: 1, b: 2 }, { b: 3, c: 4 });
 * // returns { a: 1, b: 3, c: 4 }
 */
export const merge = <T>(state: Record<string, T>, obj: Record<string, T>): Record<string, T> => {
  return {
    ...state,
    ...obj,
  };
};

/**
 * Merges multiple objects into one.
 * 
 * @param {...Record<string, T>[]} objs - The objects to merge.
 * @returns {Record<string, T>} - A single object with all merged properties.
 * 
 * @example
 * const merged = mergeAll({ a: 1 }, { b: 2 }, { c: 3 });
 * // returns { a: 1, b: 2, c: 3 }
 */
export const mergeAll = <T>(...objs: Record<string, T>[]): Record<string, T> => {
  return objs.reduce((acc, currObj) => ({
    ...acc,
    ...currObj,
  }), {} as Record<string, T>);
};


/**
 * Performs a deep merge of two objects.
 * 
 * @param {Record<string, any>} target - The target object.
 * @param {Record<string, any>} source - The source object.
 * @returns {Record<string, any>} - The deeply merged object.
 * 
 * @example
 * const merged = deepMerge({ a: 1, b: { c: 2 } }, { b: { d: 3 }, e: 4 });
 * // returns { a: 1, b: { c: 2, d: 3 }, e: 4 }
 */
export function deepMerge(target: Record<string, any>, source: Record<string, any>): Record<string, any> {
  const output = { ...target };

  if (_isObject(target) && _isObject(source)) {
    Object.keys(source).forEach((key) => {
      if (_isObject(source[key])) {
        if (!(key in target)) {
          output[key] = source[key]; // Direct assignment
        } else {
          output[key] = deepMerge(target[key], source[key]);
        }
      } else {
        output[key] = source[key]; // Direct assignment
      }
    });
  }

  return output;
}

/**
 * Removes an item from an array. Can take a function or a value to remove.
 * 
 * @param {T[]} array - The array to remove from.
 * @param {T | ((item: T) => boolean)} itemToRemove - The item or function to determine what to remove.
 * @returns {T[]} - A new array with the item removed.
 * 
 * @example
 * const updatedArray = remove([1, 2, 3], 2);
 * // returns [1, 3]
 * 
 * const updatedArrayFunc = remove([1, 2, 3], (item) => item > 1);
 * // returns [1]
 */
export const remove = <T>(array: T[], itemToRemove: T | ((item: T) => boolean)): T[] => {
  if (typeof itemToRemove === 'function') {
    return removeItem(array, array.findIndex(itemToRemove as (item: T) => boolean));
  }

  return removeItem(
    array,
    array.findIndex((ele) => ele === itemToRemove),
  );
};



/**
 * Deep-copies an object.
 * @param {T} item - The object to copy.
 * @return {T} - A deep copy of the object.
 */
export const deepCopy = <T>(item: T): T => {
  if (typeof item !== 'object') {
    return item;
  }

  try {
    const str = JSON.stringify(item);
    const _item = JSON.parse(str);
    return _item;
  } catch (e) {
    return item;
  }
};
