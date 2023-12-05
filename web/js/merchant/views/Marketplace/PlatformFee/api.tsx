import { merchantFetch } from 'merchant/utils/ajax';

export const fetchTransfersById = (id: string) => {
  return merchantFetch({
    url: `transfers/${id}?transfer_type=platform`,
    method: 'get',
  });
};

export const fetchReversals = (id: string) => {
  return merchantFetch({
    url: `transfers/${id}/reversals`,
    method: 'get',
  });
};

export const fetchTransfers = (params: string) => {
  return merchantFetch({
    url: `transfers${params}`,
    method: 'get',
  });
};
