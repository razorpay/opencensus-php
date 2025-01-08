export const STEPS = {
  PURPOSE_CODE: 1,
  IEC_CODE: 2,
  INTERNATIONAL_DETAILS: 3,
  VIDEO_KYC: 4,
} as const;

export const ACTION_LIST = [
  {
    label: 'Purpose Code',
    value: STEPS.PURPOSE_CODE,
  },
  {
    label: 'Import Export Code',
    value: STEPS.IEC_CODE,
  },
  {
    label: 'International Details',
    value: STEPS.INTERNATIONAL_DETAILS,
  },
  {
    label: 'Video KYC',
    value: STEPS.VIDEO_KYC,
  },
];

export const NOT_APPLICABLE = 'NOT_APPLICABLE';

export const VERIFIED = 'VERIFIED';
