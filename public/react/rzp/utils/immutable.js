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

export const set = (state, prop, value) => {
  let props = prop.split('.');
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

// Deep merge is still not available though. Will build on requirement
export const merge = (state, obj) => {
  return {
    ...state,
    ...obj,
  };
};

export const remove = (array, itemToRemove) => {
  if (typeof itemToRemove === 'function') {
    return removeItem(array, array.findIndex(itemToRemove));
  }

  return removeItem(array, array.findIndex(ele => ele === itemToRemove));
};
