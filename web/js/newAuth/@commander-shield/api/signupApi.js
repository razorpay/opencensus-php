import * as userService from '../user/userService';
import { getReferralPayload } from '../utils/referralParams';
import { DESKTOP_SCREEN_PX } from '../js/analytics';
import request from './request';
import { monthlyRevenueCodeMap, ACCOUNT_ALREADY_EXISTS, twoFaErrors } from './apiHelpers';

export const ENDPOINTS = {
  USER_REGISTER: '/user/register',
  USER_REGISTER_VIA_OTP: '/user/register/otp',
  USER_VERIFY_OTP: 'user/register/otp/verify',
  USER_PRE_SIGNUP: '/user/pre_signup',
  USER_COUPONS: '/user/coupons/validate',
  USER_VERIFY_EMAIL: '/user/verify_email',
  RESEND_EMAIL_OTP: '/user/resend_email_otp',
  VERIFY_INVITATION_CODE: '/user/api/live/invitations/token/', // merchant inviting merchant
  VERIFY_MERCHANT_INVITATION_CODE: '/user/api/live/admin-lead/verify/', // admin inviting merchant
  VERIFY_MERCHANT_INVITATION_CODE_RZP: '/user/api/live/merchant-invitation/verify/', // admin inviting merchant for razorpay org
  USER_WHATSAPP_OPT_IN: '/user/whatsapp/opt_in',
  GET_BUSINESS_TYPES: '/user/business_types',
  DEVICE_DETAILS: '/merchant/api/live/user/device-details',
};

export const registerUser = (user) => {
  const payload = {
    email: user.email,
    password: user.password,
    password_confirmation: user.password,
    captcha: user.captcha,
    partner_intent: user.partnerIntent,
    ref: user.ref || undefined,
    merchant_invitation: user.isMerchantInvite,
    invitation: user.invitation,
    ...getReferralPayload(),
  };

  return new Promise(async (resolve, reject) => {
    try {
      let res = await request.post(ENDPOINTS.USER_REGISTER, payload, {
        headers: {
          'X-Send-Email-OTP': true, // custom header to identify new signup flow
          'X-RECAPTCHA-MODE': user.captchaMode || 'invisible',
        },
      });
      res = userService.transformRegisterUserApi(res.data);
      resolve(res);
    } catch (err) {
      if (twoFaErrors.includes(err.error.message)) {
        err.error.message = ACCOUNT_ALREADY_EXISTS;
      }
      reject(err);
    }
  });
};

// @TODO: refactor the API for mobile registration into common api as schema for login apis mobile are same.
export const sendRegisterOTP = ({ contact, email }) => {
  const payload = {
    contact_mobile: contact,
    email,
  };

  return new Promise(async (resolve, reject) => {
    try {
      let res = await request.post(ENDPOINTS.USER_REGISTER_VIA_OTP, payload);
      res = userService.transformRegisterUserApi(res.data);
      resolve(res);
    } catch (err) {
      reject(err);
    }
  });
};

export const resendSignupOTP = ({ contact, token, email }) => {
  const payload = {
    contact_mobile: contact,
    token,
    email,
  };

  return new Promise(async (resolve, reject) => {
    try {
      let res = await request.post(ENDPOINTS.USER_REGISTER_VIA_OTP, payload);
      res = userService.transformRegisterUserApi(res.data);
      resolve(res);
    } catch (err) {
      reject(err);
    }
  });
};

export const verifyOTP = (user) => {
  const payload = {
    contact_mobile: user.contact,
    token: user.token,
    captcha: user.captcha,
    otp: user.otp,
    partner_intent: user.partnerIntent,
    email: user.email,
    ...getReferralPayload(),
  };

  return new Promise(async (resolve, reject) => {
    try {
      let res = await request.post(ENDPOINTS.USER_VERIFY_OTP, payload, {
        headers: {
          'X-RECAPTCHA-MODE': user.captchaMode || 'invisible',
        },
      });
      res = userService.transformVerifySignUpOtpApi(res.data);
      resolve(res);
    } catch (err) {
      reject(err);
    }
  });
};

export const validateCouponCode = (couponCode) => {
  const payload = {
    code: couponCode,
  };

  return new Promise(async (resolve, reject) => {
    try {
      let res = await request.post(ENDPOINTS.USER_COUPONS, payload);
      res = userService.transformValidateCouponApi(res.data);
      resolve(res);
    } catch (err) {
      reject(err);
    }
  });
};

export const whatsAppOptIn = () => {
  const payload = {
    source: 'pg.onboarding.presignup',
  };

  return new Promise(async (resolve, reject) => {
    try {
      const res = await request.post(ENDPOINTS.USER_WHATSAPP_OPT_IN, payload);
      resolve(res);
    } catch (err) {
      reject(err);
    }
  });
};

export const sendPresignupData = (data, isPreSignUpEnabled) => {
  const payload = {
    contact_mobile: data.contact || undefined,
    contact_name: data.name || undefined,
    transaction_volume: data.monthlyRevenue
      ? monthlyRevenueCodeMap[data.monthlyRevenue]
      : undefined,
    referral_code: data.referralCode || undefined,
    coupon_code: data.couponCode || undefined,
    contact_email: data.email || undefined,
  };

  if (isPreSignUpEnabled) {
    payload.business_type = data.type || ''; // mandatory field
  }

  return new Promise(async (resolve, reject) => {
    try {
      const res = await request.post(ENDPOINTS.USER_PRE_SIGNUP, payload);
      if (data.isWhatsAppOptIn) whatsAppOptIn();
      resolve(res);
    } catch (err) {
      reject(err);
    }
  });
};

export const sendDeviceDetails = () => {
  const isMobile = window.innerWidth < DESKTOP_SCREEN_PX;
  return new Promise(async (resolve, reject) => {
    try {
      const res = await request.post(ENDPOINTS.DEVICE_DETAILS, {
        signup_source: isMobile ? 'mweb' : 'dweb',
      });
      resolve(res);
    } catch (err) {
      reject(err);
    }
  });
};

export const getActiveBusinessTypes = () => {
  return new Promise(async (resolve, reject) => {
    try {
      const res = await request.get(ENDPOINTS.GET_BUSINESS_TYPES);
      resolve(res);
    } catch (err) {
      reject(err);
    }
  });
};

export const verifyUserEmail = (data) => {
  const payload = {
    otp: data.otp,
    token: data.token,
  };

  return new Promise(async (resolve, reject) => {
    try {
      let res = await request.post(ENDPOINTS.USER_VERIFY_EMAIL, payload);
      res = userService.transformVerifyUserEmailApi(res.data);
      resolve(res);
    } catch (err) {
      reject(err);
    }
  });
};

export const resendOtp = (data) => {
  const payload = {
    token: data.token,
  };

  return new Promise(async (resolve, reject) => {
    try {
      let res = await request.post(ENDPOINTS.RESEND_EMAIL_OTP, payload);
      res = userService.transformResendOtpApi(res.data);
      resolve(res);
    } catch (err) {
      reject(err);
    }
  });
};

export const verifyInvitationCode = (invitationCode) => {
  return new Promise(async (resolve, reject) => {
    try {
      let res = await request.get(ENDPOINTS.VERIFY_INVITATION_CODE + invitationCode);

      res = userService.transformVerifyInvitationCodeApi(res.data);
      resolve(res);
    } catch (err) {
      reject(err);
    }
  });
};

export const verifyMerchantInvitationCode = (merchantInvitationCode, orgName = '') => {
  let endpoint;
  if (orgName === 'rzp') {
    endpoint = ENDPOINTS.VERIFY_MERCHANT_INVITATION_CODE_RZP;
  } else {
    endpoint = ENDPOINTS.VERIFY_MERCHANT_INVITATION_CODE;
  }
  return new Promise(async (resolve, reject) => {
    try {
      let res = await request.get(endpoint + merchantInvitationCode);
      res = userService.transformVerifyMerchantInvitationCodeApi(res.data);
      resolve(res);
    } catch (err) {
      reject(err);
    }
  });
};
