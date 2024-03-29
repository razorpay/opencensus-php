import { V_KYC_STATUS } from 'merchant/reducers/unlockIntlPaymentMethods/initialState';
import { ICProductStates } from 'merchant/views/AccountAndSettings/PaymentMethods/typings';

export const VIDEO_KYC = 'videoKyc';
export const KYC_DOCUMENTS = 'kycDocuments';

export const UNLOCK_METHODS_STEPS = [
  {
    id: 'kycDocuments',
    title: 'KYC documents',
    description: "We'll verify the KYC documents in a few days and update the status",
    ctaText: { default: 'Upload KYC Documents' },
  },
  {
    id: 'videoKyc',
    title: 'Video KYC',
    description: 'To be completed by the authorised signatory only',
    ctaText: {
      default: 'Complete video KYC',
      [V_KYC_STATUS.REJECTED]: 'Retry video KYC',
      [V_KYC_STATUS.INITIATED]: 'Resume video KYC',
    },
  },
];

//KYC status and error mapping
const KYC_REJECTION_REASON = {
  DOCUMENTS_INCORRECT: 'documents_incorrect',
  DOCUMENTS_FORMAT_INCONSISTENT: 'documents_format_inconsistent',
  INCOMPLETE_DOCUMENTS_SUBMITTED: 'incomplete_documents_submitted',
  VCIP_NOT_COMPLETED: 'vcip_not_completed',
};

const KYC_DOC_ERROR_MAPPING = {
  [KYC_REJECTION_REASON.DOCUMENTS_INCORRECT]:
    'Please upload the correct KYC document(s). One or more KYC documents that you submitted during the request to activate more international payment methods was/were found to be incorrect.',
  [KYC_REJECTION_REASON.DOCUMENTS_FORMAT_INCONSISTENT]:
    'Please upload the correct KYC document(s). One or more KYC documents that you submitted during the request to activate more international payment methods was/were found to be incorrect.',
  [KYC_REJECTION_REASON.INCOMPLETE_DOCUMENTS_SUBMITTED]:
    'Please upload the KYC document(s) after self-attestation or digital signature of the issuing authority. One or more KYC documents that you submitted during the request to activate more international payment methods was/were found to be without self-attestation or digital signature of the issuing authority.',
  [KYC_REJECTION_REASON.VCIP_NOT_COMPLETED]: 'Your video kyc was not completed in stipulated time',
};

export const KYC_DOCUMENT_STATUS_BADGE_MAPPING = {
  [ICProductStates.NOT_ACTIVATED]: {
    status: 'neutral',
    label: 'Not Activated',
    description: "We'll verify the KYC documents in a few days and update the status",
  },
  [ICProductStates.REJECTED]: {
    status: 'negative',
    label: 'Rejected',
    description: 'Please upload the correct documents',
  },
  [ICProductStates.UNDER_REVIEW]: {
    status: 'notice',
    label: 'Under Review',
    description: "We'll verify the KYC documents in a few days and update the status",
  },
  [ICProductStates.ACTIVE]: {
    status: 'positive',
    label: 'Successful',
    description: 'Your documents have been verified successfully',
  },
  [ICProductStates.ACTION_REQUIRED]: {
    status: 'notice',
    label: 'Action Required',
    description: 'We need a few more details for your activating other payment methods',
  },
  default: null,
} as const;

//VKYC status and error mapping
export const V_KYC_REJECTION = {
  INCONSISTENT_NAME: 'name is inconsistent',
  FACE_NOT_VISIBLE: 'face is not visible on document(s)',
  NO_DETAILS_RESUBMITED: 'customer did not resubmit the details',
  VIDEO_NOT_VISIBLE: 'video is not visible',
  FRAUD_CUSTOMER: 'customer seems to be a fraud',
  DEFAULT: 'default',
};

export const V_KYC_REJECTION_REASON_MAPPING = {
  [V_KYC_REJECTION.INCONSISTENT_NAME]: 'name is inconsistent in submitted documents',
  [V_KYC_REJECTION.FACE_NOT_VISIBLE]: 'face is not visible on document(s)',
  [V_KYC_REJECTION.NO_DETAILS_RESUBMITED]: 'details were not resubmitted',
  [V_KYC_REJECTION.VIDEO_NOT_VISIBLE]: 'video was not visible during the video KYC call',
  [V_KYC_REJECTION.FRAUD_CUSTOMER]: 'the team flagged your account due to risk reasons',
  [V_KYC_REJECTION.DEFAULT]: 'Your video KYC was unsuccessful. Please try again.',
};

export const V_KYC_STATUS_BADGE_MAPPING = {
  [V_KYC_STATUS.APPROVED]: {
    status: 'positive',
    label: 'Successful',
    description:
      'Your Video Based Customer Identification process (V-CIP) is successfully completed and verified',
  },
  [V_KYC_STATUS.REJECTED]: {
    status: 'negative',
    label: 'Rejected',
    description: 'Your video KYC was unsuccessful due to <insert reason>. Please try again.',
  },
  [V_KYC_STATUS.UNDER_REVIEW]: {
    status: 'notice',
    label: 'Under Review',
    description: 'We are reviewing your Video Based Customer Identification process (V-CIP)',
  },
  [V_KYC_STATUS.INITIATED]: {
    status: 'notice',
    label: 'In Progress',
    description: 'To be completed by the authorised signatory only',
  },
  default: null,
} as const;

export const ALERT_STATUS_MAPPING = {
  [ICProductStates.REJECTED]: {
    title: 'Your request to activate more international payment methods is rejected',
    description: {
      ...KYC_DOC_ERROR_MAPPING,
      default: KYC_DOCUMENT_STATUS_BADGE_MAPPING[ICProductStates.REJECTED].description,
    },
    color: 'negative',
    ctaText: 'Upload KYC documents',
  },
  [ICProductStates.ACTION_REQUIRED]: {
    title: 'We need a few more details for your activating other payment methods',
    description: {
      default: KYC_DOCUMENT_STATUS_BADGE_MAPPING[ICProductStates.ACTION_REQUIRED].description,
    },
    color: 'notice',
    ctaText: 'Submit details now',
  },
};
