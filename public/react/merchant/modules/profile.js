import ajax from 'merchant/utils/ajax';

const BANK_ACCOUNT_AND_INVITATIONS_FETCH = 'BANK_ACCOUNT_AND_INVITATIONS_FETCH';
const INVITATIONS_FETCH = 'INVITATIONS_FETCH';

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

/* Fetch bank account info and pending invitations */
export const fetchBankInfoAndInvitations = currentUserId => {
  return dispatch => {
    return dispatch({
      type: BANK_ACCOUNT_AND_INVITATIONS_FETCH,
      payload: Promise.all([
        fetchAjax('/settings/invitations'),
        fetchAjax('bank_account'),
      ]),
    });
  };
};

// To accept-reject invitation
export const updateInvitation = (type, inviteId) => {
  return dispatch => {
    return ajax({
      url: `settings/invitations/${inviteId}/${type}`,
      method: 'post',
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
      appendModeInQueryParam: true,
    });
  };
};

export const updatePassword = data => {
  return dispatch => {
    return ajax({
      url: 'password',
      method: 'post',
      data: data,
      appendModeInQueryParam: true,
    });
  };
};

let initialState = {
  loading: true,
};

export default function(state = initialState, action) {
  switch (action.type) {
    case `${BANK_ACCOUNT_AND_INVITATIONS_FETCH}::PENDING`:
      return state.set('loading', true);

    case `${BANK_ACCOUNT_AND_INVITATIONS_FETCH}::SUCCESS`:
      return state.merge({
        loading: false,
        invitations: action.payload[0].data.data,
        bankAccount: action.payload[1].data.data,
        error: null,
      });

    case `${BANK_ACCOUNT_AND_INVITATIONS_FETCH}::ERROR`:
      return state.merge({
        loading: false,
        bankAccount: false,
        error: action.error,
      });

    case `${INVITATIONS_FETCH}::SUCCESS`:
      return state.merge({
        loading: false,
        invitations: action.payload.data.data,
        error: null,
      });

    case `${INVITATIONS_FETCH}::ERROR`:
      return state.merge({
        loading: false,
        error: action.error,
      });

    default:
      return state;
  }
}
