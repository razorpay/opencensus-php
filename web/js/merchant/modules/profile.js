import ajax from 'merchant/utils/ajax';
import { merchantFetch } from 'rzp/utils/ajax';
import { set } from 'rzp/utils/immutable';

const BANK_ACCOUNT_FETCH = 'BANK_ACCOUNT_FETCH';
const GST_FETCH = 'GST_FETCH';
const GST_SAVE = 'GST_SAVE';

export const fetchBankAccount = () => {
  return {
    type: BANK_ACCOUNT_FETCH,
    payload: merchantFetch('account/bank_account'),
  };
};

// To accept invitation
export const acceptInvitation = inviteId => {
  return () => {
    return ajax({
      url: `/settings/invitations/${inviteId}/accept`,
      method: 'post',
    });
  };
};

// To reject invitation
export const rejectInvitation = (inviteId, userId) => {
  return () => {
    return merchantFetch({
      url: `invitations/${inviteId}/reject`,
      mode: 'live',
      method: 'post',
      data: {
        user_id: userId,
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
      mode: 'live',
    });
  };
};

export const updatePassword = data => {
  return () => {
    return ajax({
      url: '/password',
      mode: 'live',
      method: 'post',
      data: data,
      appendModeInQueryParam: true,
    });
  };
};

export const fetchGST = () => {
  return {
    type: GST_FETCH,
    payload: merchantFetch({
      url: 'merchant/gst',
      mode: 'live',
    }),
  };
};

export const saveGST = data => {
  return {
    type: GST_SAVE,
    payload: merchantFetch({
      url: 'merchant/gst',
      method: 'patch',
      data,
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
