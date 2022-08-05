import ajax, { merchantFetch } from 'merchant/utils/ajax';
import { set, merge, unshift } from 'common/utils/immutable';
import { decodeSensitiveFields } from 'common/utils/rzp-utils';

const ACCOUNTS_FETCH = 'ACCOUNTS_FETCH';
const ACCOUNT_CREATE = 'ACCOUNT_CREATE';
const ACCOUNT_UPDATE = 'ACCOUNT_UPDATE';
const UPDATE_EMAIL = 'UPDATE_EMAIL';
const ACCOUNT_DASHBOARD_ACCESS = 'ACCOUNT_DASHBOARD_ACCESS';
const ACCOUNT_REFUNDS_ACCESS = 'ACCOUNT_REFUNDS_ACCESS';

export const fetchAccountsApi = (data, params) => {
  return ajax(
    {
      url: '/linked_accounts',
      data: decodeSensitiveFields(data),
      params,
    },
    {},
    '/merchant/api',
  );
};

export const fetchAccountApi = (id) => {
  return ajax(
    {
      url: `/beta/accounts/${id}`,
    },
    {},
    '/merchant/api',
  );
};

export const fetchAccounts = (params) => {
  return {
    type: ACCOUNTS_FETCH,
    payload: fetchAccountsApi(params),
  };
};

export const saveAccount = (data) => {
  return {
    type: ACCOUNT_CREATE,
    payload: ajax({
      url: '/submerchants',
      method: 'post',
      appendModeInQueryParam: true,
      data,
    }).then((response) => response.data),
  };
};

export const updateAccount = (data) => {
  return {
    type: ACCOUNT_UPDATE,
    payload: data,
  };
};

export const toggleDashboardAccess = (data) => {
  return {
    type: ACCOUNT_DASHBOARD_ACCESS,
    payload: merchantFetch({
      url: 'la-merchants/config',
      method: 'post',
      appendModeInURL: true,
      accountId: data.accountId,
      data: { ...data, dashboard_access: data.dashboard_access },
    }).then((response) => response.data),
  };
};

export const toggleAllowRefunds = (data) => {
  return {
    type: ACCOUNT_REFUNDS_ACCESS,
    payload: merchantFetch({
      url: 'la-merchants/config',
      method: 'post',
      appendModeInURL: true,
      accountId: data.accountId,
      data: { ...data, allow_reversals: data.allow_reversals },
    }).then((response) => response.data),
  };
};

export const exportAccountsCSV = () => {
  const data = { year: '2017', month: '1' };

  return () => {
    return ajax({
      url: '/reports/account',
      data,
    });
  };
};

export const updateEmail = (data) => {
  return {
    type: UPDATE_EMAIL,
    payload: merchantFetch({
      url: 'la-merchants/email',
      method: 'put',
      appendModeInURL: true,
      data: { email: data.email },
      accountId: data.accountId,
    }).then((response) => response.data),
  };
};

const initialState = {
  loading: true,
  error: null,
  accounts: [],
  count: 0,
};

export default (state = initialState, action) => {
  switch (action.type) {
    case `${ACCOUNTS_FETCH}::PENDING`:
      return set(state, 'loading', true);

    case `${ACCOUNTS_FETCH}::SUCCESS`:
      return merge(state, {
        loading: false,
        accounts: action.payload.data.items,
        count: action.payload.data.count,
        error: null,
      });

    case `${ACCOUNTS_FETCH}::ERROR`:
      return merge(state, {
        loading: false,
        error: action.error,
        accounts: initialState.accounts,
      });

    case `${ACCOUNT_CREATE}::SUCCESS`:
      return set(state, 'accounts', unshift(state.accounts, action.payload));

    case ACCOUNT_UPDATE:
      return set(
        state,
        'accounts',
        state.accounts.map((acc) => {
          if (acc.id === action.payload.id) return action.payload;

          return acc;
        }),
      );

    default:
      return state;
  }
};
