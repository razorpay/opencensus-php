import ajax from 'merchant/utils/ajax';
import { set } from 'rzp/utils/immutable';

const BANK_ACCOUNT_FETCH = 'BANK_ACCOUNT_FETCH';
const GST_FETCH = 'GST_FETCH';
const GST_SAVE = 'GST_SAVE';

export const fetchBankAccount = () => {
  return {
    type: BANK_ACCOUNT_FETCH,
    payload: ajax({
      url: '/user/generic',
      data: {
        route_name: 'bank_account_fetch',
      },
      appendModeInURL: false,
    }),
  };
};

// To accept invitation
export const acceptInvitation = inviteId => {
  return () => {
    return ajax({
      url: `/settings/invitations/${inviteId}/accept`,
      method: 'post',
      appendModeInURL: false,
    });
  };
};

// To reject invitation
export const rejectInvitation = (inviteId, userId) => {
  return () => {
    return ajax({
      url: '/user/generic',
      method: 'post',
      appendModeInURL: false,
      data: {
        route_name: 'invitation_action',
        url_params: JSON.stringify({
          '{id}': inviteId,
          '{action}': 'reject',
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

export const fetchGST = () => {
  return {
    type: GST_FETCH,
    payload: ajax({
      url: '/user/generic',
      data: {
        route_name: 'merchant_gst_fetch',
      },
      appendModeInURL: false,
    }),
  };
};

export const saveGST = data => {
  let body = {
    route_name: 'merchant_gst_edit',
    body: data,
  };

  return {
    type: GST_SAVE,
    payload: ajax({
      url: '/user/generic',
      method: 'PATCH',
      appendModeInURL: false,
      data: body,
    }),
  };
};

let initialState = {
  invitations: [],
  rzp_gst: {
    gstin: '29AAGCR4375J1ZU',
  },
  merchant_gst: {},
};

export default function(state = initialState, action) {
  switch (action.type) {
    case `${BANK_ACCOUNT_FETCH}::SUCCESS`:
      return set(state, 'bankAccount', action.payload.data);

    case `${GST_FETCH}::SUCCESS`:
    case `${GST_SAVE}::SUCCESS`:
      return set(state, 'merchant_gst', action.payload.data);
    default:
      return state;
  }
}
