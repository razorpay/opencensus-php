import { merchantFetch } from 'merchant/utils/ajax';

export const fetchPaymentHandleApi = () => {
  return merchantFetch({ url: `payment_handle`, method: 'GET', mode: 'live' });
};

export const createPaymentHandleApi = () => {
  return merchantFetch({ url: `payment_handle`, method: 'POST', mode: 'live' });
};

export const checkSlugAvailabilityApi = (slug: string) => {
  return merchantFetch({
    url: `payment_handle/@${slug}/exists`,
    method: 'GET',
    mode: 'live',
  });
};

export const getSlugSuggestionsApi = () => {
  return merchantFetch({
    url: `payment_handle/suggestion`,
    method: 'GET',
    mode: 'live',
  });
};

export const editSlugApi = (slug: string) => {
  const requestBody = {
    slug,
  };
  return merchantFetch({
    url: `payment_handle`,
    method: 'PATCH',
    mode: 'live',
    data: requestBody,
  });
};
