// this function is used to remove the empty objects from the payload, since backend dont want empty objects
export function sanitizePayload(obj) {
  for (const key in obj) {
    if (obj.hasOwnProperty(key)) {
      const value = obj[key];

      // sanitizing the payload for mandatory fields
      switch (key) {
        case 'evaluate_rules':
          if (Array.isArray(value)) {
            // Iterate over the 'evaluate_rules' array and check the 'condition' property of each element
            // eslint-disable-next-line max-depth
            for (let i = 0; i < value.length; i++) {
              // eslint-disable-next-line max-depth
              if (value[i].condition && Object.keys(value[i].condition).length === 0) {
                delete value[i].condition;
              }
            }
          }
          break;
        case 'flags':
          if (!value.force_display) {
            value.force_display = false;
          }
          break;
        case 'combined_coupons':
          obj[key] =
            value?.filter((combineCoupon) => {
              return combineCoupon.type;
            }) || [];
          break;
        case 'discover_rules':
          if (Array.isArray(value)) {
            // eslint-disable-next-line max-depth
            if (typeof value[0] === 'object' && Object.keys(value[0]).length === 0) obj[key] = null;
          }
          break;
        default:
          if (
            value === null ||
            value === undefined ||
            (typeof value === 'object' && Object.keys(value).length === 0)
          ) {
            delete obj[key];
          }
      }
    }
  }
  return obj;
}
