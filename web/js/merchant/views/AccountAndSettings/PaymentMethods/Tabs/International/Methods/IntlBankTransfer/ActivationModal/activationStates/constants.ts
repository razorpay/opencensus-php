import { ActivationModalState, ValidationState } from './types';

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

export const STEPS_INITIAL_STATE: ActivationModalState['steps'] = {
  [STEPS.PURPOSE_CODE]: {
    isCompleted: false,
    isReadOnly: false,
    fields: {
      purposeCode: '',
      purposeCodeDesc: '',
    },
    validationState: {
      purposeCode: DEFAULT_VALIDATION,
      purposeCodeDesc: DEFAULT_VALIDATION,
    },
  },
  [STEPS.IEC_CODE]: {
    isCompleted: false,
    isReadOnly: false,
    fields: {
      iecCodeOption: '',
      iecCode: '',
      acceptNotApplicableTnc: '',
      acceptTnc: '',
    },
    validationState: {
      iecCodeOption: DEFAULT_VALIDATION,
      iecCode: DEFAULT_VALIDATION,
      acceptNotApplicableTnc: DEFAULT_VALIDATION,
      acceptTnc: DEFAULT_VALIDATION,
    },
  },
  [STEPS.INTERNATIONAL_DETAILS]: {
    isCompleted: false,
    isReadOnly: false,
    submitBtnText: 'Continue',
    fields: {
      purposeCode: '',
      purposeCodeDesc: '',
      iecCode: '',
      acceptTnc: '',
    },
    validationState: {
      purposeCode: DEFAULT_VALIDATION,
      purposeCodeDesc: DEFAULT_VALIDATION,
      iecCode: DEFAULT_VALIDATION,
      acceptTnc: DEFAULT_VALIDATION,
    },
  },
  [STEPS.VIDEO_KYC]: {
    isCompleted: false,
    isReadOnly: false,
    submitBtnText: 'Continue',
    fields: {
      owner: '',
      promoterPanName: '',
      webLink: '',
    },
    validationState: {
      owner: DEFAULT_VALIDATION,
      promoterPanName: DEFAULT_VALIDATION,
      webLink: DEFAULT_VALIDATION,
    },
  },
};

export const IEC_CODE_REGEX = /^[a-zA-Z0-9]+$/;

export const NOT_APPLICABLE = 'NOT_APPLICABLE';

export const VERIFIED = 'VERIFIED';
