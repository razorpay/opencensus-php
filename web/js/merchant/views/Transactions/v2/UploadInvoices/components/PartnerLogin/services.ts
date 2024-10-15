import { merchantFetch } from 'merchant/utils/ajax';
import { Partner } from './types';

export const onboardPartner = async (
  partner: Partner,
  gstin: string,
  username: string,
  password?: string,
): Promise<unknown> => {
  const response = await merchantFetch({
    url: 'payments_cross_border_live/v1/onboard/partner',
    method: 'post',
    data: {
      action: 'onboard',
      partner,
      username,
      gstin,
      password,
    },
  });
  if (response?.data?.data) {
    return response?.data?.data;
  }
  return {}; // Implicitly typed as empty object
};

export const verifyOtp = async (
  partner: Partner,
  otp: string,
  username: string,
): Promise<unknown> => {
  const response = await merchantFetch({
    url: 'payments_cross_border_live/v1/onboard/partner',
    method: 'post',
    data: {
      action: 'verify',
      username,
      partner,
      otp,
    },
  });
  if (response?.data?.data) {
    return response?.data?.data;
  }
  return {}; // Implicitly typed as empty object
};
