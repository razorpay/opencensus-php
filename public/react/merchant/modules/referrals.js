import { set, merge, push } from 'rzp/utils/immutable';
import Referral from 'merchant/models/Referral';

const REFERRALS_FETCH = 'REFERRALS_FETCH';
const REFERRALS_NEW = 'REFERRALS_NEW';
const HIGHLIGHT_REFERRAL = 'HIGHLIGHT_REFERRAL';
const REMOVE_HIGHLIGHT_REFERRAL = 'REMOVE_HIGHLIGHT_REFERRAL';
const LOGIN_CREATE = 'LOGIN_CREATE';
const MERCHANT_CREATE = 'MERCHANT_CREATE';
const MERCHANT_SWITCH = 'MERCHANT_SWITCH';

export const fetchReferrals = params => {
  return dispatch => {
    let referral = new Referral();
    return dispatch({
      type: REFERRALS_FETCH,
      payload: referral.fetchAll(),
    });
  };
};

export const switchMerchant = merchantId => {
  var referral = new Referral();
  return dispatch => {
    return dispatch({
      type: MERCHANT_SWITCH,
      payload: referral.switchMerchant(merchantId),
    });
  };
};

export const createLogin = params => {
  var referral = new Referral();
  return dispatch => {
    return dispatch({
      type: LOGIN_CREATE,
      payload: referral.createLogin(params),
    });
  };
};

export const createMerchant = params => {
  var referral = new Referral(params);
  return dispatch => {
    return dispatch({
      type: MERCHANT_CREATE,
      payload: referral.createMerchant(),
    });
  };
};

export const highlightReferral = merchantId => {
  return dispatch => {
    dispatch({
      type: HIGHLIGHT_REFERRAL,
      payload: merchantId,
    });

    setTimeout(() => {
      dispatch({
        type: REMOVE_HIGHLIGHT_REFERRAL,
      });
    }, 6000);
  };
};

let initialState = {
  loading: true,
  referrals: [],
  count: 0,
  highlightReferralId: null,
};

export default function(state = initialState, action) {
  switch (action.type) {
    case `${REFERRALS_FETCH}::PENDING`:
    case `${REFERRALS_NEW}::PENDING`:
      return merge(state, {
        loading: true,
      });

    case `${REFERRALS_FETCH}::SUCCESS`:
      return merge(state, {
        loading: false,
        referrals: action.payload.data,
        count: action.payload.data.length,
      });

    case `${REFERRALS_NEW}::SUCCESS`:
      return merge(state, {
        loading: false,
        referrals: [action.payload],
      });

    case `${MERCHANT_CREATE}::SUCCESS`:
      return merge(state, {
        loading: false,
        referrals: push(state.referrals, action.payload),
      });

    case HIGHLIGHT_REFERRAL:
      return set(state, 'highlightReferralId', action.payload);

    case REMOVE_HIGHLIGHT_REFERRAL:
      return set(state, 'highlightReferralId', null);

    default:
      return state;
  }
}
