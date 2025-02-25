import * as userService from '../user/userService';
import { getFormattedNumber } from '../utils/regex';
import request from './request';

export const ENDPOINTS = {
  USER_LOGIN: '/user/signin',
  USER_SEND_OTP: '/user/signin/otp',
  USER_VERIFY_OTP: '/user/signin/otp/verify',
  USER_2FA_PASSWORD: '/user/signin/otp/2fa',
  USER_VERIFICATION_SEND_OTP: '/user/signin/verify-user/otp',
  USER_VERIFICATION_VERIFY_OTP: '/user/signin/verify-user/otp/verify',
  USER_2FA_OTP_VERIFY: '/user/2fa/otp-verify',
  USER_2FA_OTP_RESEND: '/user/2fa/otp-resend',
  USER_2FA_CONTACT: '/user/2fa/contact',
  USER_RESET_PASSWORD: '/user/api/live/users/reset-password',
  USER_RESET_PASSWORD_TOKEN: '/user/api/live/users/reset-password-token',
  USER_EMAIL_UPDATE: '/user/api/live/merchants/email/update/create_user',
};

/**
 * @param {{email: string, password: string, captcha: string, captchaMode: string}} user
 * @returns {Promise<any>}
 */
export const loginUser = (user) => {
  const payload = {
    email: user.email,
    password: user.password,
    captcha: user.captcha,
  };

  return new Promise(async (resolve, reject) => {
    try {
      let res = await request.post(ENDPOINTS.USER_LOGIN, payload, {
        headers: {
          'X-RECAPTCHA-MODE': user.captchaMode || 'invisible',
        },
      });
      /*
      Response will be:
      data: {
          id: "H3XCRDPRc0wg5W"
          merchantIds: ["H3XCRJOMVuEhoj"]
          logged_in_via: "contact_mobile | email"
      },
      success: true
       */
      res = userService.transformLoginUserApi(res.data);
      resolve(res);
    } catch (err) {
      reject(err);
    }
  });
};

/**
 * @param {{otp: string}} data
 * @returns {Promise<any>}
 */
export const verify2faOTP = (data) => {
  const payload = {
    otp: data.otp,
  };
  return new Promise(async (resolve, reject) => {
    try {
      let res = await request.post(ENDPOINTS.USER_2FA_OTP_VERIFY, payload);
      res = userService.transformVerifyOtpApi(res.data);
      /*
      Response will be:
      data: {
          id: "H3XCRDPRc0wg5W"
          merchantIds: ["H3XCRJOMVuEhoj"]
          logged_in_via: "contact_mobile | email"
      },
      success: true
       */
      resolve(res);
    } catch (err) {
      reject(err);
    }
  });
};

/**
 * @returns {Promise<any>}
 */
export const resend2faOTP = () => {
  return new Promise(async (resolve, reject) => {
    try {
      const res = await request.post(ENDPOINTS.USER_2FA_OTP_RESEND);
      /*
     Response will be:
     success: true
     No need to transform
      */
      resolve(res);
    } catch (err) {
      reject(err);
    }
  });
};

/**
 * @returns {Promise<any>}
 * @param data
 */
export const verify2FAPassword = (data) => {
  const payload = {
    password: data.password,
  };
  return new Promise(async (resolve, reject) => {
    try {
      let res = await request.post(ENDPOINTS.USER_2FA_PASSWORD, payload);
      res = userService.transformVerify2FAPasswordApi(res.data);
      /*
      Response will be:
      data: {
          id: "H3XCRDPRc0wg5W"
          merchantIds: ["H3XCRJOMVuEhoj"]
          logged_in_via: "contact_mobile | email"
      },
      success: true
       */
      resolve(res);
    } catch (err) {
      reject(err);
    }
  });
};

/**
 * @param {{email: string}} user
 * @returns {Promise<any>}
 */
export const resetPassword = (user) => {
  const payload = {
    email: user.email,
  };

  return new Promise(async (resolve, reject) => {
    try {
      const res = await request.post(ENDPOINTS.USER_RESET_PASSWORD, payload);
      /*
      Response will be:
      {"status_code":200,"success":true,"data":{"success":true}}
      No need to transform
       */
      resolve(res);
    } catch (err) {
      reject(err);
    }
  });
};

/**
 * @param {{email: string, password: string, passwordConfirmation: string, token: string}} user
 * @returns {Promise<any>}
 */
export const resetPasswordToken = (user) => {
  const payload = {
    email: user.email,
    password: user.password,
    password_confirmation: user.passwordConfirmation,
    token: user.token,
  };

  return new Promise(async (resolve, reject) => {
    try {
      let res = await request.post(ENDPOINTS.USER_RESET_PASSWORD_TOKEN, payload, {});

      // Response will be:
      // { status_code: 200, success: true, data: { success: true, user_id: 'DYHadWGixre6YI' } };
      res = userService.transformResetPasswordTokenApi(res.data);
      resolve(res);
    } catch (err) {
      reject(err);
    }
  });
};

/**
 * @param {{merchant_id: string, password: string, passwordConfirmation: string, token: string}} user
 * @returns {Promise<any>}
 */
export const emailUpdate = (user) => {
  const payload = {
    merchant_id: user.merchant_id,
    password: user.password,
    password_confirmation: user.passwordConfirmation,
    token: user.token,
  };

  return new Promise(async (resolve, reject) => {
    try {
      let res = await request.post(ENDPOINTS.USER_EMAIL_UPDATE, payload, {});

      // TODO: check if below transform is needed
      res = userService.transformResetPasswordTokenApi(res.data);
      resolve(res);
    } catch (err) {
      reject(err);
    }
  });
};

/**
 * @param {{mobileNumber: string}} data
 * @returns {Promise<any>}
 */
export const setup2faMobileNumber = (data) => {
  const payload = {
    contact_mobile: data.mobileNumber,
  };

  return new Promise(async (resolve, reject) => {
    try {
      const res = await request.patch(ENDPOINTS.USER_2FA_CONTACT, payload);
      /*
      Response will be:
      {"success":true}
      No need to transform
       */
      resolve(res);
    } catch (err) {
      reject(err);
    }
  });
};

/**
 * @param {{mobileNumber: string} | {email: string}} data
 * @returns {Promise<any>}
 */
export const sendOTP = (data) => {
  let payload = {};
  if (data.mobileNumber) {
    payload = {
      contact_mobile: getFormattedNumber(data.mobileNumber),
    };
  } else {
    payload = {
      email: data.email,
    };
  }

  return new Promise(async (resolve, reject) => {
    try {
      const res = await request.post(ENDPOINTS.USER_SEND_OTP, payload);
      /*
      Response will be:
      {"success":true}
      No need to transform
       */
      resolve(res);
    } catch (err) {
      reject(err);
    }
  });
};

/**
 * @param {{contact: string, password: string}} data
 * @returns {Promise<any>}
 */
export const userVerificationSendOTP = (data) => {
  const payload = {
    contact_mobile: data.contact,
    password: data.password,
  };

  return new Promise(async (resolve, reject) => {
    try {
      const res = await request.post(ENDPOINTS.USER_VERIFICATION_SEND_OTP, payload);
      /*
      Response will be:
      {"success":true}
      No need to transform
       */
      resolve(res);
    } catch (err) {
      reject(err);
    }
  });
};

/**
 * @param {{mobileNumber?: string, email?: string, otpToken: string}} data
 * @returns {Promise<any>}
 */
export const resendOTP = (data) => {
  let payload = {};
  if (data.mobileNumber) {
    payload = {
      contact_mobile: getFormattedNumber(data.mobileNumber),
      token: data.otpToken,
    };
  } else {
    payload = {
      email: data.email,
      token: data.otpToken,
    };
  }

  return new Promise(async (resolve, reject) => {
    try {
      const res = await request.post(ENDPOINTS.USER_SEND_OTP, payload);
      /*
      Response will be:
      {"success":true}
      No need to transform
       */
      resolve(res);
    } catch (err) {
      reject(err);
    }
  });
};

/**
 * @param {{mobileNumber: string, password: string, otpToken: string}} data
 * @returns {Promise<any>}
 */
export const userVerificationResendOTP = (data) => {
  const payload = {
    contact_mobile: getFormattedNumber(data.mobileNumber),
    password: data.password,
    token: data.otpToken,
  };

  return new Promise(async (resolve, reject) => {
    try {
      const res = await request.post(ENDPOINTS.USER_VERIFICATION_SEND_OTP, payload);
      /*
      Response will be:
      {"success":true}
      No need to transform
       */
      resolve(res);
    } catch (err) {
      reject(err);
    }
  });
};

/**
 * @param {{mobileNumber?: string; email?: string; otpToken: string, otp: number, captcha: string}} data
 * @returns {Promise<any>}
 */
export const verifyOTP = (data) => {
  const payload = {
    contact_mobile: data.mobileNumber ? getFormattedNumber(data.mobileNumber) : undefined,
    email: data.email ? data.email : undefined,
    token: data.otpToken,
    otp: data.otp,
    captcha: data.captcha,
  };

  return new Promise(async (resolve, reject) => {
    try {
      let res = await request.post(ENDPOINTS.USER_VERIFY_OTP, payload, {
        headers: {
          'X-RECAPTCHA-MODE': data.captchaMode || 'invisible',
        },
      });
      res = userService.transformVerifyOtpApi(res.data);
      resolve(res);
    } catch (err) {
      reject(err);
    }
  });
};

/**
 * @param {{mobileNumber: string, otpToken: string, otp: number, captcha: string}} data
 * @returns {Promise<any>}
 */
export const userVerificationVerifyOTP = (data) => {
  const payload = {
    contact_mobile: getFormattedNumber(data.mobileNumber),
    token: data.otpToken,
    otp: data.otp,
    captcha: data.captcha,
  };

  return new Promise(async (resolve, reject) => {
    try {
      let res = await request.post(ENDPOINTS.USER_VERIFICATION_VERIFY_OTP, payload, {
        headers: {
          'X-RECAPTCHA-MODE': data.captchaMode || 'invisible',
        },
      });
      res = userService.transformVerifyOtpApi(res.data);
      resolve(res);
    } catch (err) {
      reject(err);
    }
  });
};

export const logoutUser = () => {
  return new Promise(async (resolve, reject) => {
    try {
      const res = await request.post('/user/logout');
      resolve(res);
    } catch (err) {
      reject(err);
    }
  });
};
