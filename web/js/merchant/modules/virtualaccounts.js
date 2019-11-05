import { set } from 'rzp/utils/immutable';
import VirtualAccount from 'merchant/models/VirtualAccount';
import {
  makeActionCollectionReducer,
  fetchAll,
} from 'merchant/modules/collection';
import { makeEntityReducer, updateEntity } from 'rzp/modules/entity';

const VIRTUAL_ACCOUNT_CREATE = 'VIRTUAL_ACCOUNT_CREATE';
const VIRTUAL_ACCOUNT_EDIT = 'VIRTUAL_ACCOUNT_EDIT';
const VIRTUAL_ACCOUNT_DELETE = 'VIRTUAL_ACCOUNT_DELETE';
const VIRTUAL_ACCOUNT_FETCH = 'VIRTUAL_ACCOUNT_FETCH';
const VIRTUAL_ACCOUNT_PAYMENTS_FETCH = 'VIRTUAL_ACCOUNT_PAYMENTS_FETCH';

// TODO: Check to add UPI ID in receiver_type
export const fetchVirtualAccounts = params => {
  if (!params.notes) {
    params.receiver_type = 'bank_account';
  }
  return fetchAll(params, VirtualAccount, 'VIRTUAL_ACCOUNTS');
};

export const fetchItem = id => {
  let virtualAccount = new VirtualAccount();
  return {
    type: VIRTUAL_ACCOUNT_FETCH,
    payload: virtualAccount.fetch(id),
  };
};

export const fetchVAPayments = id => {
  let virtualAccount = new VirtualAccount({ id });
  return {
    type: VIRTUAL_ACCOUNT_PAYMENTS_FETCH,
    payload: virtualAccount.fetchPayments(),
  };
};

export const saveVirtualAccount = params => {
  const virtualAccount = new VirtualAccount(params);

  return {
    type: virtualAccount.isNew ? VIRTUAL_ACCOUNT_CREATE : VIRTUAL_ACCOUNT_EDIT,
    payload: virtualAccount.save(),
  };
};

export const closeVirtualAccount = params => {
  const virtualAccount = new VirtualAccount(params);
  return {
    type: VIRTUAL_ACCOUNT_EDIT,
    payload: virtualAccount.close(),
  };
};

export const updateVirtualAccountDetails = (id, data) => {
  const virtualAccount = new VirtualAccount();
  return {
    type: VIRTUAL_ACCOUNT_EDIT,
    payload: virtualAccount.updateAccountDetails(id, data),
  };
};

export const createTestPayment = params => {
  const virtualAccount = new VirtualAccount();
  return () => {
    return virtualAccount.createTestPayment(params);
  };
};

// List Reducer
export const virtualAccountsReducer = makeActionCollectionReducer(
  'VIRTUAL_ACCOUNTS'
);

// Virtual Accounts Details Reducer
let detailsInitialState = {
  loading: true,
  entity: {},
  error: null,
  va_payments: [],
};
export const virtualAccountReducer = makeEntityReducer(
  VIRTUAL_ACCOUNT_FETCH,
  {
    [`${VIRTUAL_ACCOUNT_EDIT}::SUCCESS`]: updateEntity,

    [`${VIRTUAL_ACCOUNT_PAYMENTS_FETCH}::SUCCESS`]: (state, action) => {
      return set(state, 'va_payments', action.payload.data.items);
    },
  },
  detailsInitialState
);
