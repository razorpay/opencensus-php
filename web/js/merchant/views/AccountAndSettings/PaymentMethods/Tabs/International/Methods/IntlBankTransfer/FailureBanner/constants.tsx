import React from 'react';
import { Text, Link, AlertProps } from '@razorpay/blade/components';

import { RAZORPAY_SUPPORT_LINK } from 'merchant/views/AccountAndSettings/PaymentMethods/Tabs/International/Methods/IntlBankTransfer/Details/constants';
import {
  V_KYC_REJECTION,
  V_KYC_STATUS,
  VERIFICATION_STATUS,
} from 'merchant/views/AccountAndSettings/PaymentMethods/Tabs/International/Methods/IntlBankTransfer/verificationStates/constants';
import { VerificationStatus } from 'merchant/views/AccountAndSettings/PaymentMethods/Tabs/International/Methods/IntlBankTransfer/verificationStates/types';

export const V_KYC_REJECTION_REASON_MAPPING = {
  [V_KYC_REJECTION.INCONSISTENT_NAME]:
    'Your video KYC was unsuccessful due to (name is inconsistent in submitted documents). Please try again.',
  [V_KYC_REJECTION.FACE_NOT_VISIBLE]:
    'Your video KYC was unsuccessful due to (face is not visible on document(s)). Please try again.',
  [V_KYC_REJECTION.NO_DETAILS_RESUBMITED]:
    'Your video KYC was unsuccessful due to (details were not resubmitted). Please try again.',
  [V_KYC_REJECTION.VIDEO_NOT_VISIBLE]:
    'Your video KYC was unsuccessful due to (video was not visible during the video KYC call). Please try again.',
  [V_KYC_REJECTION.FRAUD_CUSTOMER]:
    "Unfortunately we couldn't approve your request since <insert reason>. You can try to request for more international payment methods again after 90 days. Meanwhile you can collect international payments via international cards.",
};

const REQUEST_REJECTED =
  'Unfortunately, your request to activate international bank transfers is rejected';

const REQUEST_CANNOT_BE_APPROVED = "We couldn't approve your request.";

export const SHOW_V_KYC_BANNER_WHEN = new Set([
  V_KYC_STATUS.APPROVED,
  V_KYC_STATUS.REJECTED,
  V_KYC_STATUS.UNDER_REVIEW,
  V_KYC_STATUS.INITIATED,
  V_KYC_STATUS.FRAUD_REJECTED,
  V_KYC_STATUS.FAILED,
]);

export const FAILURE_BANNER: {
  [key in VerificationStatus[keyof Omit<VerificationStatus, 'NO_PURPOSE_CODE'>]]: Pick<
    AlertProps,
    'title' | 'color' | 'description'
  >;
} = {
  [VERIFICATION_STATUS.BLACKLISTED]: {
    title: REQUEST_REJECTED,
    color: 'notice',
    description: 'We do not support your business category at the moment.',
  },
  [VERIFICATION_STATUS.NO_PROMOTER_PAN_NAME]: {
    title: 'Authorised Signatory PAN details are mandatory for activating virtual accounts',
    color: 'notice',
    description: (
      <Text>
        To get help,{' '}
        <Link href={RAZORPAY_SUPPORT_LINK} target="_blank" rel="noopener">
          contact our support team
        </Link>
      </Text>
    ),
  },
  [VERIFICATION_STATUS.NO_IEC_CODE]: {
    title: 'Update your IEC code to activate international bank transfers',
    color: 'negative',
    description: '',
  },
  [VERIFICATION_STATUS.EDD_NOT_VERIFIED]: {
    title:
      'International bank transfers are not yet enabled. To enable, please complete your video KYC.',
    color: 'negative',
    description: '',
  },
  [VERIFICATION_STATUS.V_KYC_APPROVED]: {
    title:
      'Your Video Based Customer Identification process (V-CIP) is successfully completed and verified.',
    color: 'positive',
    description: '',
  },
  [VERIFICATION_STATUS.V_KYC_REJECTED]: {
    title: REQUEST_CANNOT_BE_APPROVED,
    color: 'notice',
    description: '',
  },
  [VERIFICATION_STATUS.V_KYC_UNDER_REVIEW]: {
    title: 'Your video KYC details are under review. This can take up to 24 hours.',
    color: 'notice',
    description: '',
  },
  [VERIFICATION_STATUS.V_KYC_INITIATED]: {
    title:
      'Video KYC is currently in progress and must be completed exclusively by the authorized signatory.',
    color: 'notice',
    description: '',
  },
  [VERIFICATION_STATUS.V_KYC_FRAUD_REJECTED]: {
    title: REQUEST_REJECTED,
    color: 'notice',
    description:
      "Unfortunately we couldn't approve your request since <insert reason>. You can try to request for more international payment methods again after 90 days. Meanwhile you can collect international payments via international cards.",
  },
  [VERIFICATION_STATUS.V_KYC_FAILED]: {
    title: REQUEST_CANNOT_BE_APPROVED,
    color: 'notice',
    description: '',
  },
};
