import { ICProductStates } from 'merchant/views/AccountAndSettings/PaymentMethods/typings';

export const MORE_PAYMENT_METHOD_STATUS = {
  DEFAULT: null,
  INITIAL: 'initial',
  IN_PROGRESS: 'in_progress',
  VERIFIED: 'verified',
  REJECTED: 'rejected',
  KYC_DOCUMENT_REJECTED: 'kyc_document_rejected',
  EDD_REJECTED: 'edd_rejected',
  NOT_VERIFIED: 'not_verified',
  RETRY_IMPOSSIBLE: 'retry_impossible',
  NOT_ABLE_TO_FETCH: 'not_able_to_fetch',
  ACTION_REQUIRED: 'action_required',
} as const;

export const EDD_STATUS = {
  INITIATED: 'initiated',
  VERIFIED: 'verified',
  REJECTED: 'rejected',
  NOT_VERIFIED: 'not_verified',
  DEFAULT: null,
} as const;

export const V_KYC_STATUS = {
  INITIATED: 'initiated',
  UNDER_REVIEW: 'under_review',
  APPROVED: 'approved',
  REJECTED: 'rejected',
  FAILED: 'failed',
  DEFAULT: null,
} as const;

export type REDUCER_INITIAL_STATE = {
  status: (typeof MORE_PAYMENT_METHOD_STATUS)[keyof typeof MORE_PAYMENT_METHOD_STATUS];
  kycDocumentStatus: (typeof ICProductStates)[keyof typeof ICProductStates] | null;
  kycRejectedReason: string | null;
  vKycRejectedReason: string;
  eddStatus: (typeof EDD_STATUS)[keyof typeof EDD_STATUS];
  vKycStatus: (typeof V_KYC_STATUS)[keyof typeof V_KYC_STATUS];
  isStatusLoading: boolean;
  showMorePaymentMethodsSection: boolean;
  isMethodEnablementFormOpen: boolean;
  defaultTab: number;
  isCreatingLink: boolean;
};

export const INITIAL_STATE: REDUCER_INITIAL_STATE = {
  status: MORE_PAYMENT_METHOD_STATUS.DEFAULT,

  // Additional KYC documents
  kycDocumentStatus: ICProductStates.NOT_ACTIVATED,
  kycRejectedReason: null,

  // V-CIP
  vKycRejectedReason: '',
  eddStatus: EDD_STATUS.DEFAULT,
  vKycStatus: V_KYC_STATUS.DEFAULT,
  isStatusLoading: false,

  // more payment methods section
  showMorePaymentMethodsSection: true,

  // method enablement form
  isMethodEnablementFormOpen: false,
  defaultTab: 0,

  // vkyc link creation
  isCreatingLink: false,
};
