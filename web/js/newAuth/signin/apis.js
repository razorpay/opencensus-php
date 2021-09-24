import ajax from 'common/utils/ajax';

const ENDPOINTS = {
  ORG: '/org',
};

const OTP_AUTH_MODE = {
  sms: 'phone number',
  email: 'email',
  sms_and_email: 'phone number and email',
};

export const fetchOrg = () => {
  return ajax({
    url: ENDPOINTS.ORG,
  });
};

export const transformFetchOrgData = (data) => {
  return {
    logo: data.login_logo_url || 'img/logo_full.png',
    isOrgRZP: data.custom_code === 'rzp',
    orgName: data.custom_code,
    secondFactorAuthMode: OTP_AUTH_MODE[data.second_factor_auth_mode] || 'phone number/email',
    businessName: data.business_name,
    isSignupAllowed: data.allow_sign_up,
    backgroundImgUrl: data.background_image_url,
  };
};
