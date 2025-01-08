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
  FRAUD_REJECTED: 'fraud_rejected', // UI only state, updated here: /IntlBankTransfer/helpers.ts
  FAILED: 'failed',
  NOT_VERIFIED: 'not_verified',
  DEFAULT: null,
} as const;

export const SHOW_V_KYC_BANNER_WHEN = new Set([
  V_KYC_STATUS.INITIATED,
  V_KYC_STATUS.UNDER_REVIEW,
  V_KYC_STATUS.APPROVED,
  V_KYC_STATUS.FRAUD_REJECTED,
  V_KYC_STATUS.REJECTED,
  V_KYC_STATUS.FAILED,
]);

export type VKycStatus = typeof V_KYC_STATUS;

export const V_KYC_REJECTION = {
  INCONSISTENT_NAME: 'name is inconsistent',
  FACE_NOT_VISIBLE: 'face is not visible on document(s)',
  NO_DETAILS_RESUBMITED: 'customer did not resubmit the details',
  VIDEO_NOT_VISIBLE: 'video is not visible',
  FRAUD_CUSTOMER: 'customer seems to be a fraud',
  DEFAULT: 'default',
} as const;

export const ACCOUNTS_STATUS = {
  ACTIVATED: 'activated',
  DEACTIVATED: 'deactivated',
} as const;

export const V_KYC_STATUS_FOR_ACTIVATION: string[] = [
  V_KYC_STATUS.INITIATED,
  V_KYC_STATUS.UNDER_REVIEW,
  V_KYC_STATUS.REJECTED,
  V_KYC_STATUS.FRAUD_REJECTED,
  V_KYC_STATUS.APPROVED,
];

export const BLACKLISTED_MCC_CODES_SET = new Set([
  '5813',
  '7995',
  '5966',
  '5933',
  '8651',
  '5094',
  '5193',
  '5993',
]);

export const VERIFICATION_STATUS = {
  BLACKLISTED: 'blacklisted',
  NO_PROMOTER_PAN_NAME: 'no_promoter_pan_name',
  NO_IEC_CODE: 'no_iec_code',
  NO_PURPOSE_CODE: 'no_purpose_code',
  EDD_NOT_VERIFIED: 'edd_not_verified',
  V_KYC_APPROVED: V_KYC_STATUS.APPROVED,
  V_KYC_REJECTED: V_KYC_STATUS.REJECTED,
  V_KYC_UNDER_REVIEW: V_KYC_STATUS.UNDER_REVIEW,
  V_KYC_INITIATED: V_KYC_STATUS.INITIATED,
  V_KYC_FRAUD_REJECTED: V_KYC_STATUS.FRAUD_REJECTED,
  V_KYC_FAILED: V_KYC_STATUS.FAILED,
} as const;

export const VERIFICATION_STATUS_ACTIVATION_MODAL_STEP = {
  [VERIFICATION_STATUS.NO_IEC_CODE]: 2,
  [VERIFICATION_STATUS.EDD_NOT_VERIFIED]: 4,
  [VERIFICATION_STATUS.V_KYC_APPROVED]: 4,
  [VERIFICATION_STATUS.V_KYC_REJECTED]: 4,
  [VERIFICATION_STATUS.V_KYC_UNDER_REVIEW]: 4,
  [VERIFICATION_STATUS.V_KYC_INITIATED]: 4,
  [VERIFICATION_STATUS.V_KYC_FRAUD_REJECTED]: 4,
};
