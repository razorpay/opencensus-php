import { PAYMENT_HANDLE_PREFIX } from './constants';

// returns separate domain and slug
export const getHandleEntities = (url) => {
  const prefixIndex = url.indexOf(PAYMENT_HANDLE_PREFIX);
  return {
    domain: prefixIndex !== -1 ? url?.slice(0, prefixIndex) : '',
    slug: prefixIndex !== -1 ? url?.slice(prefixIndex + 1) : '',
    prefix: PAYMENT_HANDLE_PREFIX,
  };
};
