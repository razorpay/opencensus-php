const _serialize = data => {
  if (typeof data === 'undefined') {
    data = {};
  }
  if (!typeof data === 'object') {
    return data === null ? '' : data.toString();
  }
  let buffer = [];
  let flattenedOb = _flatten(data, 1);

  for (let key in flattenedOb) {
    if (typeof flattenedOb[key] === 'undefined') {
      continue;
    }

    if (typeof flattenedOb[key] === 'boolean') {
      flattenedOb[key] = flattenedOb[key] ? 1 : 0;
    }

    buffer.push(
      encodeURIComponent(key) + '=' + encodeURIComponent(flattenedOb[key])
    );
  }
  let source = buffer.join('&').replace(/%20/g, '+');

  return source;
};

const _flatten = (data, level) => {
  let flattened = {};
  for (let key in data) {
    let val = data[key];
    let keyToSend = key;

    if (level > 1) {
      keyToSend = '[' + keyToSend + ']';
    }

    if (typeof data[key] === 'object') {
      let tmpFlattened = _flatten(data[key], level + 1);

      for (let tmpKey in tmpFlattened) {
        let tmpVal = tmpFlattened[tmpKey];

        flattened[keyToSend + tmpKey] = tmpVal;
      }
    } else {
      flattened[keyToSend] = val;
    }
  }
  return flattened;
};

export default {
  serialize: _serialize,
  flatten: _flatten,
};
