// this function is used to remove the empty objects from the payload, since backend dont want empty objects
export function sanitizePayload(obj) {
  for (const key in obj) {
    if (obj.hasOwnProperty(key)) {
      const value = obj[key];

      if (
        value === null ||
        value === undefined ||
        (typeof value === 'object' && Object.keys(value).length === 0)
      ) {
        if (key !== 'discover_rules') {
          delete obj[key];
        }
      } else if (key === 'evaluate_rules' && Array.isArray(value)) {
        // Iterate over the 'evaluate_rules' array and check the 'condition' property of each element
        for (let i = 0; i < value.length; i++) {
          // eslint-disable-next-line max-depth
          if (value[i].condition && Object.keys(value[i].condition).length === 0) {
            delete value[i].condition;
          }
        }
      }
    }
  }
  return obj;
}
