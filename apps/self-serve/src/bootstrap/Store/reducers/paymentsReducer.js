import { decodeSensitiveFields } from '@dashboard/shared-utils/rzp-utils';
import Payment from '../../models/Payment';
import { makeActionCollectionReducer, fetchAll } from './collection';

const defaultInitialState = {
  loading: true,
  items: [],
  error: null,
};

export const fetchPayments = (params) => {
  return fetchAll(decodeSensitiveFields(params), Payment, 'PAYMENTS');
};

export const paymentsReducer = makeActionCollectionReducer(
  'PAYMENTS',
  {},
  // ignore( do not send to API ) "ref" param if seen present the url
  {
    ...defaultInitialState,
    blacklistQueryParams: ['ref'],
  },
);
