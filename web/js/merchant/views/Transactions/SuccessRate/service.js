import { merchantFetch } from 'merchant/utils/ajax';

export const getSR = (payload) => {
  return merchantFetch({
    url: 'success-rate/merchant/sr',
    mode: 'live',
    method: 'POST',
    data: payload,
  });
};

export const getResolvedDowntimes = (payload) => {
  return merchantFetch({
    url: 'payments/downtimes/resolved',
    method: 'get',
    data: payload,
  });
};

export const getOngoingDowntimes = () => {
  return merchantFetch({
    url: 'payments/downtimes/ongoing',
    method: 'get',
  });
};

export const getMerchantError = (payload) => {
  return merchantFetch({
    url: 'success-rate/merchant/error',
    mode: 'live',
    method: 'POST',
    data: payload,
  });
};
