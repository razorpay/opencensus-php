import { set, merge, push } from 'rzp/utils/immutable';
import Referral from 'merchant/models/Referral';

const REFERRALS_FETCH = 'REFERRALS_FETCH';
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

let initialState = {
  loading: true,
  referrals: [],
  count: 0,
};

export default function(state = initialState, action) {
  switch (action.type) {
    case `${REFERRALS_FETCH}::PENDING`:
      return merge(state, {
        loading: true,
      });

    case `${REFERRALS_FETCH}::SUCCESS`:
      return merge(state, {
        loading: false,
        referrals: action.payload.data,
        count: action.payload.data.length,
      });

    case `${MERCHANT_CREATE}::SUCCESS`:
      return merge(state, {
        loading: false,
        referrals: push(state.referrals, action.payload),
      });

    default:
      return state;
  }
}
