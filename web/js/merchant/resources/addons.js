import { rupeesToPaise } from 'rzp/utils/rzp-utils';

/*
  Description: Filter out extra values on basis of `resourceFields`
  Input:
    1) fields = string / array
    2) data = value / object
*/
export function formatFields(fields, data) {
  if (!Array.isArray(fields)) {
    return _serializeProperty(fields, data);
  }

  const formattedData = {};

  // Parsing over fields(array) is better than parsing over data(object)
  // Parsing over data(object) will lead to iterating over not to be formatted keys
  fields.forEach(key => {
    let value = data[key];
    if (value) {
      formattedData[key] = _serializeProperty(key, value);
    }
  });

  return { ...data, ...formattedData }; // This will send formatted data back along with other values in data which are not to be processed
}

/* ================= Util functions ===================== */

// Process any data before sending in this fn.
function _serializeProperty(key, value) {
  switch (key) {
    case 'description':
    case 'name':
      return value && value.trim();
    case 'amount':
      return rupeesToPaise(value); // Ideally should be multiplied by unit price. Currently assuming INR
    case 'quantity':
      return Number(value);
  }
}

// Process any data on receiving in this fn.
function _deserializeProperty(key, value) {
  switch (key) {
    case 'amount':
      return Number(value) / 100; // Ideally should be divided by unit price. Currently assuming INR
  }
}
