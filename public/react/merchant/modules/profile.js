import ajax from 'merchant/utils/ajax';
import { set } from 'rzp/utils/immutable';

const BANK_ACCOUNT_FETCH = 'BANK_ACCOUNT_FETCH';

export const fetchAjax = url => {
  return ajax({
    url,
    appendModeInURL: false,
  });
};

export const fetchBankAccount = () => {
  return {
    type: BANK_ACCOUNT_FETCH,
    payload: fetchAjax('/bank_account'),
  };
};

// To accept invitation
export const acceptInvitation = (type, inviteId) => {
  return () => {
    return ajax({
      url: `/settings/invitations/${inviteId}/${type}`,
      method: 'post',
      appendModeInURL: false,
    });
  };
};

// To reject invitation
export const rejectInvitation = (type, inviteId, userId) => {
  return () => {
    return ajax({
      url: '/user/generic',
      method: 'post',
      appendModeInURL: false,
      data: {
        route_name: 'invitation_action',
        url_params: JSON.stringify({
          '{id}': inviteId,
          '{action}': type,
        }),
        body: {
          user_id: userId,
        },
      },
    });
  };
};

export const upgradeAccount = data => {
  return () => {
    return ajax({
      url: '/merchants/register',
      method: 'post',
      data: data,
      appendModeInURL: false,
    });
  };
};

export const updatePassword = data => {
  return () => {
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

    default:
      return state;
  }
}
