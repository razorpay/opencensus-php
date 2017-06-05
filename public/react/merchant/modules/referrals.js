import Referral from 'merchant/models/Referral';
import { fetchAll } from 'rzp/modules/collection';

const LOGIN_CREATE = 'LOGIN_CREATE';
const MERCHANT_CREATE = 'MERCHANT_CREATE';
const MERCHANT_SWITCH = 'MERCHANT_SWITCH';

export const fetchReferrals = params => fetchAll(params, Referral);

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
