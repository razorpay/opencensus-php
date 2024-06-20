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
} from 'apps/pos/src/app/typings/SalesAssistedOnboarding';

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
}: MerchantOTPVerifyRequestArgs): Promise<MerchantOTPVerifyAPIResponse> =>
  salesFetch<MerchantOTPVerifyRequestAPIArgs, MerchantOTPVerifyAPIResponse>({
    url: 'register/merchant/otp/verify',
    method: 'POST',
    mode: 'live',
    data: {
      otp,
      token,
      contact_mobile: String(contactMobile),
      captcha: 'Faked',
      signup_campaign: 'assisted_onboarding',
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
