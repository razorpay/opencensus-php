import { Collection } from 'merchant/views/Transactions/v2/common/types';
import { Item as PaymentItem } from 'merchant/views/Transactions/v2/Payments/types';

import { ONBOARDING_PARTNERS, ONBOARDING_STATUS, MODAL_TYPES } from './constants';

export type Payments = Collection<Item>;

export type Partner = typeof ONBOARDING_PARTNERS[keyof typeof ONBOARDING_PARTNERS];
export type Status = typeof ONBOARDING_STATUS[keyof typeof ONBOARDING_STATUS];
export type ModalType = typeof MODAL_TYPES[keyof typeof MODAL_TYPES];

export interface Item extends PaymentItem {
  enitity_id: null | string;
  sender_details?: {
    name: null | string;
    country: null | string;
  };
}

export type FetchStatsResponse =
  | {
      total_unmapped_invoices: number;
      total_mapped_invoices: number;
      invoices_with_pending_actions: number;
    }
  | object;

export type FetchOnboardingStatusResponse =
  | Array<{
      name: 'einvoice' | 'gstportal';
      status: 'onboarded' | 'expired';
    }>
  | undefined;

export type InvoiceStatsContextType = {
  invoiceStats: FetchStatsResponse | undefined;
  isInvoiceStatsLoading: boolean;
  refetchinvoiceStats: () => void;
};

export type OnboardingDetailsContextType = {
  onboardingData: FetchOnboardingStatusResponse | undefined;
  isOnboardingDataLoading: boolean;
  refetchOnboardingData: () => void;
};

export type PopupContextType = {
  openPopup: (type: ModalType, props: unknown) => void;
  closePopup: () => void;
  popupDetails: {
    type: null | ModalType;
    props: null | unknown;
  };
};
