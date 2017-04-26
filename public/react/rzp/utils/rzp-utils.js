import moment from 'moment';

export function titleCase(sentence) {
  let text = sentence || '';
  return text
    .split(/\s+|_/)
    .map(word => word.charAt(0).toUpperCase() + word.substr(1))
    .join(' ');
}

export function humanize(sentence) {
  return titleCase(sentence.split('_').join(' '));
}

export function makeArray(obj) {
  if (!obj) {
    return [];
  }
  return Array.isArray(obj) ? obj : [obj];
}

export function isBlank(obj) {
  if (!obj) return !obj;

  if (typeof obj === 'object') {
    return !Object.keys(obj).length;
  }

  if (typeof obj === 'string') {
    obj = obj.trim();
  }
  return !obj;
}

export function isPresent(obj) {
  return !isBlank(obj);
}

export const findBy = (array, prop, value) => {
  return array.find(item => {
    return item[prop] === value;
  });
};

export const filterBy = (array, prop, value) => {
  return array.filter(item => {
    return item[prop] === value;
  });
};

export const mapBy = (array, prop) => {
  return array.map(item => {
    return item[prop];
  });
};

export const pipe = (...funcs) => {
  let first = funcs.shift();
  return (...args) => {
    return funcs.reduce((returnVal, currentFn) => {
      return currentFn(returnVal);
    }, first(...args));
  };
};

export const normalizeDate = date => moment(date).format('D/M/Y');

export const normalizeBoolean = bool => {
  if (bool === undefined) {
    return bool;
  }

  return bool ? 1 : 0;
};

export const getFixedINRAmount = amount => (Number(amount) / 100).toFixed(2);

export const without = (source, keys) => {
  keys = makeArray(keys);
  return Object.keys(source).reduce((prev, key) => {
    if (keys.indexOf(key) === -1) {
      prev[key] = source[key];
    }
    return prev;
  }, {});
};
export const objectDiff = (oldObj = {}, newObj = {}) => {
  return Object.keys(newObj).reduce((prev, key) => {
    let value = newObj[key];
    let oldValue = oldObj[key];

    if (JSON.stringify(value) !== JSON.stringify(oldValue)) {
      prev[key] = value;
    }
    return prev;
  }, {});
};

export const noop = () => {};
