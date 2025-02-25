import { decodeSensitiveFields } from '@libs/shared-utils';
import { makeActionCollectionReducer, fetchAll } from './collection';
import Dispute from 'apps/self-serve/src/bootstrap/models/Dispute';

const defaultInitialState = {
  loading: true,
  items: [],
  error: null,
};

export const fetchDisputes = (params) => {
  return fetchAll(decodeSensitiveFields(params), Dispute, 'DISPUTES');
};

export const disputeReducer = makeActionCollectionReducer(
  'DISPUTES',
  {},
  // ignore( do not send to API ) "ref" param if seen present the url
  {
    ...defaultInitialState,
    blacklistQueryParams: ['ref'],
  },
);
