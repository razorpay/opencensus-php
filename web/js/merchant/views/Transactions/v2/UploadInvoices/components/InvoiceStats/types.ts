import { FetchOnboardingStatusResponse } from 'merchant/views/Transactions/v2/UploadInvoices/types';
import { INVOICE_STATUS, ONBOARDING_PARTNERS, ONBOARDING_STATUS } from './constant';

export type InvoiceStatus = typeof INVOICE_STATUS[keyof typeof INVOICE_STATUS];
export type Partner = typeof ONBOARDING_PARTNERS[keyof typeof ONBOARDING_PARTNERS];
export type Status = typeof ONBOARDING_STATUS[keyof typeof ONBOARDING_STATUS];

export type Tab = {
  type: InvoiceStatus;
  name: string;
  tooltipText: string;
};

export type StatsCardProps = Tab & {
  count?: string;
  isLoading: boolean;
};

export type OnboardingCardProps = {
  onboardingDetails: FetchOnboardingStatusResponse;
  isLoading: boolean;
};

export type OnboardingCardDetailsType = {
  bannerText: string;
  buttonText: string;
  partner: Partner;
  status: undefined | Status;
};
