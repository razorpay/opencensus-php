// Todo: delete this file, it's available in @dashboard/shared-utils
function _isObject(item) {
  return item && typeof item === 'object' && !Array.isArray(item);
}

const getValueFromObject = (obj, prop) => {
  return prop.split('.').reduce((prev, curr) => {
    return prev[curr];
  }, obj);
};

const simpleSet = (state, prop, value) => {
  if (Array.isArray(state)) {
    return updateItem(state, prop, value);
  }

  if (state.toString() === 'model') {
    let Klass = state.constructor;
    return new Klass({
      ...state,
      [prop]: value,
    });
  }

  return {
    ...state,
    [prop]: value,
  };
};

// Public APIs

export const insertItem = (array, index, value) => {
  let newArray = array.slice();
  newArray.splice(index, 0, value);
  return newArray;
};

export const removeItem = (array, index) => {
  let newArray = array.slice();
  newArray.splice(index, 1);
  return newArray;
};

export const updateItem = (array, index, value) => {
  let newArray = array.slice();
  newArray.splice(index, 1);
  newArray.splice(index, 0, value);
  return newArray;
};

export const unshift = (array, value) => {
  return insertItem(array, 0, value);
};

export const push = (array, value) => {
  return insertItem(array, array.length, value);
};

export const set = (state, prop, value) => {
  let props = ('' + prop).split('.');
  if (props.length === 1) {
    return simpleSet(state, prop, value);
  }

  return props.reduce((prev, curr, idx) => {
    if (idx > 0) return prev;
    let slicedProps = props.slice();
    slicedProps.shift();
    return simpleSet(prev, curr, set(prev[curr], slicedProps.join('.'), value));
  }, state);
};

export const merge = (state, obj) => {
  return {
    ...state,
    ...obj,
  };
};

export const mergeAll = (...objs) => {
  const mergedObj = objs.reduce((acc, currObj) => ({
    ...acc,
    ...currObj,
  }));
  return mergedObj;
};

export function deepMerge(target, source) {
  let output = Object.assign({}, target);

  if (_isObject(target) && _isObject(source)) {
    Object.keys(source).forEach((key) => {
      if (_isObject(source[key])) {
        if (!(key in target)) {
          Object.assign(output, { [key]: source[key] });
        } else {
          output[key] = deepMerge(target[key], source[key]);
        }
      } else {
        Object.assign(output, { [key]: source[key] });
      }
    });
  }

  return output;
}

export const remove = (array, itemToRemove) => {
  if (typeof itemToRemove === 'function') {
    return removeItem(array, array.findIndex(itemToRemove));
  }

  return removeItem(
    array,
    array.findIndex((ele) => ele === itemToRemove),
  );
};

/**
 * Deep-copies an object.
 * @param {Object} item
 * @return {Object}
 */
export const deepCopy = (item) => {
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
