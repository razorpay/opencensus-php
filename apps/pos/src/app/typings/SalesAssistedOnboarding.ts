import { APIResponse } from './common';

export interface MerchantRegisterArgs {
  contactMobile: string;
  mockSend?: boolean;
}

export interface MerchantRegisterAPIArgs {
  contact_mobile: string;
  skip_sms_request?: boolean;
}

export interface AddMerchantSuccessResponse {
  token: string;
}

export interface AddMerchantErrorResponse {
  code: string;
  description: string;
  internal_error_code: string;
}

export interface MerchantRegistrationError {
  title: string;
  description: string;
}

export interface MerchantRegistrationPhoneNumber {
  country: string;
  dialCode: string;
  value: string;
}

export type MerchantRegisterApiResponse = APIResponse<
  AddMerchantSuccessResponse,
  AddMerchantErrorResponse
>;
export interface MerchantOTPVerifyRequestArgs {
  contactMobile: string;
  otp: string;
  token: string;
  mockSend?: boolean;
}

export interface MerchantOTPVerifyRequestAPIArgs {
  contact_mobile: string;
  captcha: 'Faked';
  otp: string;
  token: string;
  signup_campaign: 'assisted_onboarding';
  skip_sms_request?: boolean;
}

export interface MerchantOTPVerifySuccessResponse {
  id: string;
  name: string;
  email: string;
  merchants: {
    id: string;
    role: string; //TODO: To be replaced with Merchant roles from graph-types
  }[];
}

export interface MerchantOTPVerifyErrorResponse {
  code: string;
  description: string;
  internal_error_code: string;
}

export type MerchantOTPVerifyAPIResponse = APIResponse<
  MerchantOTPVerifySuccessResponse,
  MerchantOTPVerifyErrorResponse
>;

export interface SwitchMerchantArgs {
  merchantId: string;
}

export type SwitchMerchantAPIResponse = APIResponse<null, string>;
