import { ValidationState } from './types';

export const STEPS = {
  PURPOSE_CODE: 1,
  IEC_CODE: 2,
  INTERNATIONAL_DETAILS: 3,
  VIDEO_KYC: 4,
} as const;

export const DEFAULT_VALIDATION: ValidationState = {
  state: 'none',
  errorText: '',
};

export const PURPOSE_CODE_DESC =
  'Helps us determine the purpose of your business, for reporting and compliance mandated by the Reserve Bank of India.';
export const PURPOSE_CODE_LINK = 'https://razorpay.com/blog/streamlining-purpose-codes/';
