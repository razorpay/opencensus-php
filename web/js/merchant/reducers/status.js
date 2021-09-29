import { merchantFetch } from 'merchant/utils/ajax';

export const fetchOngoingDowntimes = () => {
  return merchantFetch({
    url: 'payments/downtimes/ongoing',
    method: 'get',
  });
};

export const fetchScheduledDowntimes = () => {
  return merchantFetch({
    url: 'payments/downtimes/scheduled',
    method: 'get',
  });
};

export const fetchHistoricalDowntimes = (method, skip, count, startDate, endDate) => {
  return merchantFetch({
    url: 'payments/downtimes/resolved',
    method: 'get',
    data: {
      method,
      skip,
      count,
      startDate,
      endDate,
    },
  });
};
