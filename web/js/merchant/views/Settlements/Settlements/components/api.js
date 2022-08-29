import { merchantFetch } from 'merchant/utils/ajax';
const ES_SERVICE_PREFIX = 'capital_es/service/early_settlements/ondemand';

export const getLinkedAccountsBalance = () =>
  merchantFetch({
    url: `${ES_SERVICE_PREFIX}/route_settlement_balance`,
    mode: 'live',
    method: 'get',
    headers: {
      'Content-Type': 'application/json',
    },
  });

export const settleLinkedAccountsBalance = (mid, amount) =>
  merchantFetch({
    url: `${ES_SERVICE_PREFIX}/route_settlements`,
    mode: 'live',
    method: 'post',
    data: {
      parent_merchant_id: mid,
      amount,
    },
    headers: {
      'Content-Type': 'application/json',
    },
  });
