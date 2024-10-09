import { Merchant } from '@dashboard/shared-utils/graphql/graph-types';
import { ActivationStatusKeys, APIResponse } from './common';

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
  signup_campaign: 'assisted_onboarding' | 'partner_assisted_onboarding';
  product: string;
}

export interface MerchantOTPVerifyRequestAPIArgs {
  contact_mobile: string;
  captcha: 'Faked';
  otp: string;
  token: string;
  signup_campaign: string;
  product: string;
  skip_sms_request?: boolean;
  captcha_disable: 'DISABLE_THE_CAPTCHA_YOU_SHALL';
}

export interface MerchantOTPVerifySuccessResponse {
  id: string;
  name: string;
  email: string;
  merchants: {
    id: string;
    role: string;
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

export type STATUS_FILTERS =
  | 'all'
  | 'under_review'
  | 'activated'
  | 'rejected'
  | 'needs_clarification'
  | 'pending'
  | 'kyc_qualified_stb';

export type OnboardingStoreState = {
  workflowProduct: string;
  isPosEkycAgent: boolean;
  setWorkflowProduct: (setWorkflowProduct: string) => void;
  setIsPosEkycAgent: (isPosEkycAgent: boolean) => void;
};

export interface AddressField {
  value: string | null;
}

export interface Address {
  city: AddressField;
  country: AddressField;
  district: AddressField;
  line1: AddressField;
  line2: AddressField;
  state: AddressField;
  zipCode: AddressField;
}

export interface Business {
  address: {
    registered: Address;
    operation: Address;
  };
}

export type MerchantDetails = Merchant;

export enum SalesMerchantActivationStatusEnum {
  ACTIVATED = 'ACTIVATED',
  KYC_QUALIFIED_STB = 'KYC_QUALIFIED_STB',
  NEEDS_CLARIFICATION = 'NEEDS_CLARIFICATION',
  PENDING = 'PENDING',
  REJECTED = 'REJECTED',
  UNDER_REVIEW = 'UNDER_REVIEW',
}

export interface SalesOnboardedMerchant {
  createdAt: string;
  merchantId: string;
  merchantMobile: string;
  merchantName?: string;
  progressCompletion: string;
  status?: SalesMerchantActivationStatusEnum;
}

export interface StatusCounts {
  __typename?: 'StatusCounts';
  activated: number | null;
  kycQualifiedStb: number | null;
  needsClarification: number | null;
  pending: number | null;
  rejected: number | null;
  underReview: number | null;
}

export interface SalesOnboardedMerchants {
  hasMore: boolean;
  limit: number;
  merchants: SalesOnboardedMerchant;
  offset: number;
  total: number;
  statusCounts: StatusCounts;
  totalMerchantsOnboarded: number;
  __typename?: string;
}

export interface StatusTile {
  name: string;
  key: ActivationStatusKeys;
}

export type StepProgressTypes = 'pending' | 'completed';
