import { merchantFetch } from 'merchant/utils/ajax';

export const getPaymentMetricsData = (payload: unknown) => {
  return merchantFetch({
    url: 'merchant/analytics',
    mode: 'live',
    method: 'POST',
    data: payload,
  });
};
