import isEmpty from '@razorpay/universe-utils/isEmpty';
import * as userService from '../user/userService';
import { SIGNUP } from '../screens/screenHelpers';
import { getReferralPayload } from '../utils/referralParams';
import request from './request';
import { ACCOUNT_ALREADY_EXISTS, COMMON_ENDPOINTS, twoFaErrors } from './apiHelpers';

/**
 * Some info regarding "/user" call:
 * experiments=0&splitz_experiments=0 => skip experiments/razorx/splitz internal call
 * this will help in reducing issues(eg: ID provided does not exists) which
 * is caused by internal slave DB call. Also it will help in improving /user response time.
 */

export const getOrgDetails = () => {
  return new Promise(async (resolve, reject) => {
    try {
      let res = await request.get(COMMON_ENDPOINTS.ORG);
      res = userService.transformOrgApi(res.data);
      resolve(res);
    } catch (err) {
      reject(err);
    }
  });
};

export const getUserDetails = () => {
  return new Promise(async (resolve, reject) => {
    try {
      let res = await request.get(COMMON_ENDPOINTS.USER);
      res = userService.transformGetUserDetailsApi(res.data);
      resolve(res);
    } catch (err) {
      reject(err);
    }
  });
};

export const registerUserWithGoogle = (data) => {
  let signupCampaign = {};
  // if there is no referral payload and partnerIntent is `false` then set signupCampaign
  if (isEmpty(getReferralPayload()) && !data.partnerIntent) {
    signupCampaign = data.signupCampaign ?? {};
  }
  const payload = {
    email: data.email,
    id_token: data.googleToken,
    oauth_provider: 'google',
    partner_intent: data.partnerIntent,
    ...signupCampaign,
    ...getReferralPayload(),
  };

  return new Promise(async (resolve, reject) => {
    try {
      let res = await request.post(COMMON_ENDPOINTS.USER_REGISTER_GOOGLE, payload);
      res = userService.transformRegisterUserWithGoogleApi(res.data);
      resolve(res);
    } catch (err) {
      if (twoFaErrors.includes(err.error.message)) {
        err.error.message = ACCOUNT_ALREADY_EXISTS;
      }
      reject(err);
    }
  });
};

export const loginWithGoogle = (data) => {
  const payload = {
    email: data.email,
    id_token: data.googleToken,
    oauth_provider: 'google',
  };

  return new Promise(async (resolve, reject) => {
    try {
      let res = await request.post(COMMON_ENDPOINTS.USER_LOGIN_GOOGLE, payload);
      res = userService.transformLoginUserApi(res.data);
      resolve(res);
    } catch (err) {
      // show ACCOUNT_ALREADY_EXISTS only in case of signup
      if (twoFaErrors.includes(err.error.message) && data.route === SIGNUP) {
        err.error.message = ACCOUNT_ALREADY_EXISTS;
      }
      reject(err);
    }
  });
};

export const updateTermsAndConditions = (status) => {
  const payload = {
    activation_form_milestone: 'L2',
    consent: status,
    documents_detail: [
      {
        type: 'Privacy Policy',
        url: 'https://razorpay.com/privacy/',
      },
      {
        type: 'Service Agreement',
        url: 'https://razorpay.com/agreement/',
      },
      {
        type: 'Terms and Conditions',
        url: 'https://razorpay.com/terms/',
      },
    ],
  };

  return new Promise(async (resolve, reject) => {
    try {
      const res = await request.post(COMMON_ENDPOINTS.UPDATE_TERMS_AND_CONDITIONS, payload);
      resolve(res);
    } catch (err) {
      reject(err);
    }
  });
};
