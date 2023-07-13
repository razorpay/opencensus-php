import { merchantFetchWithContentType } from 'merchant/utils/ajax';

export const getPaymentMetricsData = (payload: unknown) => {
  return merchantFetchWithContentType({
    url: 'merchant/analytics',
    mode: 'live',
    method: 'POST',
    data: payload,
  });
};
