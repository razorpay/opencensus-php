import ajax from 'merchant/utils/ajax';
import { set, merge, unshift } from 'rzp/utils/immutable';

const ACCOUNTS_FETCH = 'ACCOUNTS_FETCH';
const ACCOUNT_CREATE = 'ACCOUNT_CREATE';
const HIGHLIGHT_ITEM = 'HIGHLIGHT_ITEM';
const REMOVE_ITEM_HIGHLIGHT = 'REMOVE_ITEM_HIGHLIGHT';

export const fetchAccounts = params => {
  return dispatch => {
    return dispatch({
      type: ACCOUNTS_FETCH,
      payload: ajax({
        url: '/accounts',
        data: params,
      }),
    });
  };
};

export const saveAccount = data => {
  return dispatch => {
    return dispatch({
      type: ACCOUNT_CREATE,
      payload: ajax({
        url: '/submerchants',
        method: 'post',
        appendModeInURL: false,
        data,
      }).then(response => response.data),
    });
  };
};

export const exportAccountsCSV = () => {
  return dispatch => {
    let data = { year: '2017', month: '1' };

    return ajax({
      url: '/reports/account',
      data,
    });
  };
};

export const highlightItemRow = params => {
  return dispatch => {
    dispatch({
      type: HIGHLIGHT_ITEM,
      payload: params,
    });

    setTimeout(() => {
      dispatch({
        type: REMOVE_ITEM_HIGHLIGHT,
      });
    }, 6000);
  };
};

let initialState = {
  loading: true,
  error: null,
  accounts: [],
  count: 0,
  highlightRowId: null,
};

export default function(state = initialState, action) {
  switch (action.type) {
    case `${ACCOUNTS_FETCH}::PENDING`:
      return merge(state, {
        loading: true,
        highlightRowId: null,
      });

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

    case HIGHLIGHT_ITEM:
      return set(state, 'highlightRowId', action.payload.id);

    case REMOVE_ITEM_HIGHLIGHT:
      return set(state, 'highlightRowId', null);

    default:
      return state;
  }
}
