import { set } from 'common/utils/immutable';
import { merchantFetch } from 'merchant/utils/ajax';
import VirtualAccount from 'merchant/models/VirtualAccount';
import {
  makeActionCollectionReducer,
  fetchAll,
} from 'merchant/reducers/collection';
import {
  makeEntityReducer,
  updateEntity,
} from 'merchant_common/reducers/entity';

const VIRTUAL_ACCOUNT_CREATE = 'VIRTUAL_ACCOUNT_CREATE';
const VIRTUAL_ACCOUNT_EDIT = 'VIRTUAL_ACCOUNT_EDIT';
const VIRTUAL_ACCOUNT_DELETE = 'VIRTUAL_ACCOUNT_DELETE';
const VIRTUAL_ACCOUNT_FETCH = 'VIRTUAL_ACCOUNT_FETCH';
const VIRTUAL_ACCOUNT_PAYMENTS_FETCH = 'VIRTUAL_ACCOUNT_PAYMENTS_FETCH';
const VIRTUAL_ACCOUNT_CONFIG = 'VIRTUAL_ACCOUNT_CONFIG';

export const fetchConfigForVirtualAccount = () => {
  return {
    type: VIRTUAL_ACCOUNT_CONFIG,
    payload: merchantFetch({
      url: `virtual_account/configs`,
    }),
  };
};

export const fetchVirtualAccounts = params => {
  if (!params.notes) {
    params.receiver_type = 'bank_account,vpa';
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

// Virtual Accounts Details Reducer
let listInitialState = {
  va_config: null, // null => data is loading

  // Below are same as in defaultInitialState of makeActionCollectionReducer
  loading: true,
  items: [],
  error: null,
};

// List Reducer
export const virtualAccountsReducer = makeActionCollectionReducer(
  'VIRTUAL_ACCOUNTS',
  {
    [`${VIRTUAL_ACCOUNT_CONFIG}::SUCCESS`]: (state, action) => {
      return set(state, 'va_config', action.payload.data);
    },
    [`${VIRTUAL_ACCOUNT_CONFIG}::ERROR`]: (state, action) => {
      return set(state, 'va_config', {}); // Set empty config
    },
  }
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
