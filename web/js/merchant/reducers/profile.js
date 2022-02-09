import ajax, { merchantFetch } from 'merchant/utils/ajax';
import { set, merge } from 'common/utils/immutable';
import store, { getUser } from 'merchant/store';

const BANK_ACCOUNT_FETCH = 'BANK_ACCOUNT_FETCH';
const GST_FETCH = 'GST_FETCH';
const GST_SAVE = 'GST_SAVE';
const BANK_ACCOUNT_CHANGE_STATUS_FETCH = 'BANK_ACCOUNT_CHANGE_STATUS_FETCH';
const BANK_ACCOUNT_CHANGES_SAVE = 'BANK_ACCOUNT_CHANGES_SAVE';
const ADD_WEBSITE_WORKFLOW_STATUS = 'ADD_WEBSITE_WORKFLOW_STATUS';
const FETCH_RESERVE_BALANCE = 'FETCH_RESERVE_BALANCE';
const STORE_TICKET_DETAILS = 'STORE_TICKET_DETAILS';
const GET_TICKET_STATUS = 'GET_TICKET_STATUS';
const CHECK_PASSWORD = 'CHECK_PASSWORD';
const INVALID_MERCHANT_CALL = 'INVALID_MERCHANT_CALL';
const GET_FIRC_DETAILS = 'GET_FIRC_DETAILS';
const SAVE_FIRC_DETAILS = 'SAVE_FIRC_DETAILS';

export const fetchBankAccount = () => {
  if (!window.rzp_user) {
    return {
      type: INVALID_MERCHANT_CALL,
    };
  }

  return {
    type: BANK_ACCOUNT_FETCH,
    payload: merchantFetch({
      url: 'account/bank_account',
      mode: 'live',
    }),
  };
};

// To accept invitation
export const acceptInvitation = (inviteId) => {
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

export const upgradeAccount = (data) => {
  return () => {
    return ajax({
      url: '/merchants/register',
      method: 'post',
      data,
      appendModeInURL: false,
    });
  };
};

export const updatePassword = (data) => {
  return () => {
    return ajax({
      url: '/password',
      method: 'post',
      data,
      appendModeInQueryParam: true,
    });
  };
};

export const updateMerchantConfig = (data) => {
  return () => {
    return merchantFetch({
      url: 'account/config',
      method: 'put',
      data,
    });
  };
};

export const updateBillingLabel = (data) => {
  return () => {
    return merchantFetch({
      url: 'merchants/billing_label/update',
      method: 'patch',
      data,
    });
  };
};

export const fetchGST = () => {
  if (!window.rzp_user) {
    return {
      type: INVALID_MERCHANT_CALL,
    };
  }

  return {
    type: GST_FETCH,
    payload: merchantFetch({
      url: 'merchant/gst',
      mode: 'live',
    }),
  };
};

export const saveGST = (data) => {
  return {
    type: GST_SAVE,
    payload: merchantFetch({
      url: 'merchant/gst',
      method: 'patch',
      mode: 'live',
      data,
    }),
  };
};

export const fetchBillingLabelSuggestions = () => {
  return merchantFetch({
    url: 'merchants/billing_label/suggestions',
    method: 'get',
  });
};

export const fetchBankAccountChangeStatus = (merchantId) => {
  if (!window.rzp_user) {
    return {
      type: INVALID_MERCHANT_CALL,
    };
  }

  return {
    type: BANK_ACCOUNT_CHANGE_STATUS_FETCH,
    payload: merchantFetch({
      url: `merchants/${merchantId}/bank_account_change/status`,
      mode: 'live',
    }),
  };
};

export const fetchAddWebsiteWorkflowStatus = () => {
  return {
    type: ADD_WEBSITE_WORKFLOW_STATUS,
    payload: merchantFetch({
      url: `merchant/activation/websites/status`,
      mode: 'live',
    }),
  };
};

export const saveBankAccountChanges = (merchantId, formdata) => {
  return {
    type: BANK_ACCOUNT_CHANGES_SAVE,
    payload: merchantFetch({
      url: `merchants/bank_account`,
      method: 'post',
      mode: 'live',
      data: formdata,
    }),
  };
};

export const saveBankAccountChangesAutomate = (merchantId, formdata) => {
  return {
    type: BANK_ACCOUNT_CHANGES_SAVE,
    payload: merchantFetch({
      url: `merchants/bank_account/update`,
      method: 'post',
      mode: 'live',
      data: formdata,
    }),
  };
};

export const fetchReserveBalance = () => {
  return {
    type: FETCH_RESERVE_BALANCE,
    payload: merchantFetch({
      url: `balances`,
      method: 'get',
      data: {
        type: 'reserve_primary',
      },
    }),
  };
};

export const storeTicketDetails = ({ ticketNo, description }) => {
  return {
    type: STORE_TICKET_DETAILS,
    payload: merchantFetch({
      url: `fd/reserve_balance/tickets`,
      method: 'post',
      data: {
        ticket_id: ticketNo,
        ticket_details: description,
        type: 'reserve_balance_activate',
      },
    }),
  };
};

export const getTicketStatus = () => {
  return {
    type: GET_TICKET_STATUS,
    payload: merchantFetch('fd/reserve_balance/tickets/status'),
  };
};

export const checkPassword = () => {
  if (!window.rzp_user) {
    return {
      type: INVALID_MERCHANT_CALL,
    };
  }

  return {
    type: CHECK_PASSWORD,
    payload: merchantFetch({
      url: 'users/set/password',
      method: 'get',
      mode: 'live',
    }),
  };
};

export const setPassword = (data) => {
  return () => {
    return merchantFetch({
      url: 'users/set/password',
      method: 'post',
      mode: 'live',
      data,
    });
  };
};

// {"text_80g_12a":"some text","image_url_80g":"some_url"}
export function set80gMerchantDetails(params) {
  return merchantFetch({
    url: 'payment_pages/merchant_details', // 80G details are stored in settings table corresponding to MID. So, `payment_pages/` might change later
    method: 'post',
    data: params,
    headers: {
      'content-type': 'application/json',
    },
  });
}

export function get80gMerchantDetails() {
  const currentUser = store.getState().session.user.user;

  // 80G details are stored in settings table corresponding to MID. So, `payment_pages/` might change later
  return merchantFetch(`payment_pages/merchant_details/${currentUser.id}`);
}

export function upload80gSignatoryImage(file) {
  const fd = new FormData();
  fd.append('images[0]', file);

  return merchantFetch({
    url: 'payment_pages/images', // Currently, same route is used as payment_pages, however would be changed later.
    method: 'post',
    data: fd,
  });
}

export const getPurposeCodes = () => {
  return merchantFetch({
    url: 'purposecode',
    method: 'get',
  });
};

export const fetchPurposeCode = () => {
  const user = getUser();
  return {
    type: GET_FIRC_DETAILS,
    payload: merchantFetch({
      url: `users/purpose/code`,
      method: 'get',
      data: {
        email: user.email,
      },
    }),
  };
};

export const updatePurposeCode = (data) => async (dispatch) => {
  const res = await merchantFetch({
    url: 'merchants/purpose/code',
    method: 'patch',
    data,
  });
  dispatch({ type: SAVE_FIRC_DETAILS, payload: data });
  return res;
};

const initialState = {
  invitations: [],
  rzp_gst: {
    gstin: '29AAGCR4375J1ZU',
  },
  merchant_gst: {},
  reserve_balance: {
    loading: true,
    data: {},
    error: null,
  },
  reserve_balance_activate: {
    loading: true,
    data: {},
    error: null,
  },
  ticket_status: {
    loading: true,
    data: {},
    error: null,
  },
  check_password: {
    loading: true,
    data: {},
    error: null,
  },
  fircDetails: {
    loading: true,
    data: {},
    error: null,
  },
};

export default (state = initialState, action) => {
  switch (action.type) {
    case `${BANK_ACCOUNT_FETCH}::SUCCESS`:
      return set(state, 'bankAccount', action.payload.data);

    case `${BANK_ACCOUNT_CHANGE_STATUS_FETCH}::SUCCESS`:
      return set(state, 'bankAccountChangeStatus', action.payload?.data);

    case `${GST_FETCH}::SUCCESS`:
    case `${GST_SAVE}::SUCCESS`:
      return set(state, 'merchant_gst', action.payload.data);

    case `${FETCH_RESERVE_BALANCE}::SUCCESS`:
      return merge(state, {
        reserve_balance: {
          data: action.payload.data,
          loading: false,
          error: null,
        },
      });

    case `${FETCH_RESERVE_BALANCE}::ERROR`:
      return set(state, 'reserve_balance', {
        loading: false,
        error: action.payload.errors,
        data: initialState.reserve_balance.data,
      });

    case `${STORE_TICKET_DETAILS}::SUCCESS`:
      return merge(state, {
        reserve_balance_activate: {
          data: action.payload.data,
          loading: false,
          error: null,
        },
      });

    case `${STORE_TICKET_DETAILS}::ERROR`:
      return set(state, 'reserve_balance_activate', {
        loading: false,
        error: action.payload.errors,
        data: initialState.reserve_balance_activate.data,
      });

    case `${GET_TICKET_STATUS}::SUCCESS`:
      return merge(state, {
        ticket_status: {
          data: action.payload.data,
          loading: false,
          error: null,
        },
      });

    case `${GET_TICKET_STATUS}::ERROR`:
      return set(state, 'ticket_status', {
        loading: false,
        error: action.payload.errors,
        data: initialState.ticket_status.data,
      });

    case `${CHECK_PASSWORD}::SUCCESS`:
      return set(state, 'check_password', {
        data: action.payload.data,
        loading: false,
        error: null,
      });
    case `${CHECK_PASSWORD}::ERROR`:
      return set(state, 'check_password', {
        loading: false,
        error: action.payload.errors,
        data: initialState.check_password.data,
      });

    case `${GET_FIRC_DETAILS}::PENDING`:
      return merge(state, {
        fircDetails: {
          loading: true,
          ...state.fircDetails,
        },
      });

    case `${GET_FIRC_DETAILS}::SUCCESS`:
      return set(state, 'fircDetails', {
        data: action.payload.data?.merchants[0],
        loading: false,
        error: null,
      });

    case `${GET_FIRC_DETAILS}::ERROR`:
      return merge(state, {
        fircDetails: {
          loading: false,
          error: action.payload.errors,
        },
      });

    case SAVE_FIRC_DETAILS:
      return merge(state, {
        fircDetails: {
          data: action.payload,
        },
      });

    case INVALID_MERCHANT_CALL:
      return state;

    default:
      return state;
  }
};
