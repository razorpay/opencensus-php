import { decodeSensitiveFields, getURLQueryParams } from 'common/utils/rzp-utils';

export const getDecodedParams = (search: string = location.search): Record<string, string> => {
  let params = {};
  if (search) {
    params = getURLQueryParams(search);
  }

  for (const key in params) {
    if (params.hasOwnProperty(key)) {
      params[key] = decodeURI(params[key]);
      if (key === 'count' || key === 'skip') params[key] = Number(params[key]);
    }
  }

  return decodeSensitiveFields(params);
};
