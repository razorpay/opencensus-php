import ajax from 'merchant/utils/ajax';
import { set, merge, unshift } from 'rzp/utils/immutable';

const ACCOUNTS_FETCH = 'ACCOUNTS_FETCH';
const ACCOUNT_CREATE = 'ACCOUNT_CREATE';

export const fetchAccountsApi = params => {
  return ajax({
    url: '/accounts',
    data: params,
  });
};

export const fetchAccounts = params => {
  return {
    type: ACCOUNTS_FETCH,
    payload: fetchAccountsApi(params),
  };
};

export const saveAccount = data => {
  return {
    type: ACCOUNT_CREATE,
    payload: ajax({
      url: '/submerchants',
      method: 'post',
      appendModeInQueryParam: true,
      data,
    }).then(response => response.data),
  };
};

export const exportAccountsCSV = () => {
  let data = { year: '2017', month: '1' };

  return () => {
    return ajax({
      url: '/reports/account',
      data,
    });
  };
};

let initialState = {
  loading: true,
  error: null,
  accounts: [],
  count: 0,
};

export default function(state = initialState, action) {
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

    default:
      return state;
  }
}
