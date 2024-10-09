import { salesFetch } from '.';
import {
  MerchantRegisterArgs,
  MerchantRegisterAPIArgs,
  MerchantRegisterApiResponse,
  MerchantOTPVerifyRequestArgs,
  MerchantOTPVerifyRequestAPIArgs,
  MerchantOTPVerifyAPIResponse,
  SwitchMerchantAPIResponse,
  SwitchMerchantArgs,
} from 'apps/pos/src/app/types/SalesAssistedOnboarding';

export const registerMerchant = ({
  contactMobile,
  mockSend = false,
}: MerchantRegisterArgs): Promise<MerchantRegisterApiResponse> =>
  salesFetch<MerchantRegisterAPIArgs, MerchantRegisterApiResponse>({
    url: '/user/register/otp',
    method: 'POST',
    mode: 'live',
    data: {
      contact_mobile: String(contactMobile),
      ...(mockSend ? { skip_sms_request: !!mockSend } : {}),
    },
    isAbsUrl: true,
  });

export const verifyMerchantOTP = ({
  contactMobile,
  otp,
  token,
  mockSend = false,
  signup_campaign,
  product,
}: MerchantOTPVerifyRequestArgs): Promise<MerchantOTPVerifyAPIResponse> =>
  salesFetch<MerchantOTPVerifyRequestAPIArgs, MerchantOTPVerifyAPIResponse>({
    url: 'register/merchant/otp/verify',
    method: 'POST',
    mode: 'live',
    data: {
      otp,
      token,
      contact_mobile: String(contactMobile),
      signup_campaign,
      product,
      captcha: 'Faked',
      captcha_disable: 'DISABLE_THE_CAPTCHA_YOU_SHALL',
      ...(mockSend ? { skip_sms_request: !!mockSend } : {}),
    },
  });

export const switchMerchant = ({
  merchantId,
}: SwitchMerchantArgs): Promise<SwitchMerchantAPIResponse> =>
  salesFetch<SwitchMerchantArgs, SwitchMerchantAPIResponse>({
    url: `/settings/merchants/switch/${merchantId}`,
    method: 'GET',
    mode: 'live',
    isAbsUrl: true,
  });
