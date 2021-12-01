import { merchantFetch } from 'merchant/utils/ajax';

export const OTPMETHOD = {
  EMAIL: 0,
  PHONE: 1,
};

export const sendOtp = (body) => {
  const url = 'users/otp/send';
  const method = 'POST';

  return merchantFetch({
    url,
    method,
    data: body,
  });
};

export const verifyOtp = (body) => {
  return merchantFetch({
    method: 'POST',
    url: 'users/verify/mode/sms',
    data: body,
  });
};

export const addEmail = ({ email, otpAuthToken }) => {
  const url = 'users/email/update';
  const method = 'POST';

  const body = {
    email,
    otp_auth_token: otpAuthToken,
  };

  return merchantFetch({
    url,
    method,
    data: body,
  });
};

export const verifyEmail = ({ otp, email }) => {
  const url = 'users/email/update/verify';
  const method = 'POST';

  const body = {
    otp,
    email,
  };

  return merchantFetch({
    url,
    method,
    data: body,
  });
};
