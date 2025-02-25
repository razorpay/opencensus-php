import axios from 'axios';
import { newAuthFetch } from '@dashboards/newAuth/utils/newAuthFetch';

export const registerMobileOTP = (contact_mobile) => {
  return axios({
    method: 'post',
    url: '/user/register/otp',
    data: { contact_mobile },
  }).then(({ data }) => {
    if (data.success) return data;
    throw data;
  });
};

export const verifyMobileOTP = (data, captchaMode) => {
  return axios({
    method: 'post',
    url: '/user/register/otp/verify',
    data,
    headers: {
      'X-RECAPTCHA-MODE': captchaMode || 'invisible',
    },
  }).then(({ data }) => {
    if (data.success) return data;
    throw data;
  });
};
export const sendEmailOTP = (payload) => {
  return newAuthFetch({
    url: 'merchant/activation/otp/send',
    method: 'POST',
    data: payload,
    mode: 'live',
  });
};
export const verifyEmailOTP = (payload) => {
  return newAuthFetch({ url: 'users/verify_email', method: 'POST', data: payload });
};

export const userPreSignup = (data) => {
  return axios({
    method: 'post',
    url: 'user/pre_signup',
    data,
  }).then(({ data }) => {
    if (data.success) return data;
    throw data;
  });
};

export const updatePartnerTypeAndConsent = (partnerType) => {
  return newAuthFetch({
    url: 'merchant/partner_type',
    method: 'PATCH',
    data: {
      partner_type: partnerType,
      consent: 1,
    },
  });
};

export const userVerifyEmail = (data) => {
  return axios({
    method: 'post',
    url: 'user/verify_email',
    data,
  }).then(({ data }) => {
    if (data.success) return data;
    throw data;
  });
};

export const userResendEmailOtp = (data) => {
  return axios({
    method: 'post',
    url: 'user/resend_email_otp',
    data,
  }).then(({ data }) => {
    if (data.success) return data;
    throw data;
  });
};

export const userWhatsappOptIn = () => {
  return axios({
    method: 'post',
    url: 'user/whatsapp/opt_in',
    data: { source: 'pg.onboarding.presignup' },
  }).then(({ data }) => {
    if (data.success) return data;
    throw data;
  });
};
