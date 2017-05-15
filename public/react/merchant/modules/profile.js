import ajax from 'merchant/utils/ajax';
import { set } from 'rzp/utils/immutable';

const INVITATIONS_FETCH = 'INVITATIONS_FETCH';
const BANK_ACCOUNT_FETCH = 'BANK_ACCOUNT_FETCH';

export const fetchAjax = url => {
  return ajax({
    url,
    appendModeInURL: false,
  });
};

export const fetchPendingInvitations = () => {
  return dispatch => {
    return dispatch({
      type: INVITATIONS_FETCH,
      payload: fetchAjax('/settings/invitations'),
    });
  };
};

export const fetchBankAccount = () => {
  return dispatch => {
    return dispatch({
      type: BANK_ACCOUNT_FETCH,
      payload: fetchAjax('/bank_account'),
    });
  };
};

// To accept-reject invitation
export const updateInvitation = (type, inviteId) => {
  return dispatch => {
    return ajax({
      url: `/settings/invitations/${inviteId}/${type}`,
      method: type === 'reject' ? 'delete' : 'post',
      appendModeInURL: false,
    });
  };
};

export const upgradeAccount = data => {
  return dispatch => {
    return ajax({
      url: '/merchants/register',
      method: 'post',
      data: data,
      appendModeInURL: false,
    });
  };
};

export const updatePassword = data => {
  return dispatch => {
    return ajax({
      url: '/password',
      method: 'post',
      data: data,
      appendModeInQueryParam: true,
    });
  };
};

let initialState = {
  invitations: [],
};

export default function(state = initialState, action) {
  switch (action.type) {
    case `${BANK_ACCOUNT_FETCH}::SUCCESS`:
      return set(state, 'bankAccount', action.payload.data);

    case `${INVITATIONS_FETCH}::SUCCESS`:
      return set(state, 'invitations', action.payload.data);

    default:
      return state;
  }
}
